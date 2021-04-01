<?php
namespace TNW\Salesforce\Synchronize\Queue\Website;

/**
 * Create By Customer
 */
class CreateByCustomer implements \TNW\Salesforce\Synchronize\Queue\CreateInterface
{
    const CREATE_BY = 'customer';

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    private $resourceCustomer;

    /**
     * CreateByCustomer constructor.
     * @param \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
    ) {
        $this->resourceCustomer = $resourceCustomer;
    }

    /**
     * Create By
     *
     * @return string
     */
    public function createBy()
    {
        return self::CREATE_BY;
    }

    /**
     * Process
     *
     * @param int[] $entityIds
     * @param array $additional
     * @param callable $create
     * @param int $websiteId
     * @return mixed
     */
    public function process(array $entityIds, array $additional, callable $create, $websiteId)
    {
        $queues = [];
        foreach ($this->entities($entityIds) as $entity) {
            $queues[] = $create(
                'website',
                $entity['website_id'],
                $entity['base_entity_id'],
                ['website' => $entity['website']]
            );
        }

        return $queues;
    }

    /**
     * Entities
     *
     * @param int[] $entityIds
     * @return array
     */
    public function entities(array $entityIds)
    {
        $connection = $this->resourceCustomer->getConnection();
        $select = $connection->select()
            ->from(
                ['main_table' => $this->resourceCustomer->getEntityTable()],
                [
                    'main_table.website_id',
                    'website' => 'website.name',
                    'base_entity_id' => 'main_table.' . $this->resourceCustomer->getEntityIdField()
                ]
            )
            ->joinLeft(
                ['website' => $this->resourceCustomer->getTable('store_website')],
                'main_table.website_id = website.website_id',
                []
            )
            ->where($connection->prepareSqlCondition(
                $this->resourceCustomer->getEntityIdField(),
                ['in' => $entityIds]
            ));

        return $connection->fetchAll($select);
    }
}

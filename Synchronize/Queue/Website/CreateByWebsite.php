<?php
namespace TNW\Salesforce\Synchronize\Queue\Website;

/**
 * Create By Website
 */
class CreateByWebsite implements \TNW\Salesforce\Synchronize\Queue\CreateInterface
{
    const CREATE_BY = 'website';

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order
     */
    private $resourceWebsite;

    /**
     * CreateByWebsite constructor.
     * @param \Magento\Store\Model\ResourceModel\Website $resourceWebsite
     */
    public function __construct(
        \Magento\Store\Model\ResourceModel\Website $resourceWebsite
    ) {
        $this->resourceWebsite = $resourceWebsite;
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(array $entityIds, array $additional, callable $create, $websiteId)
    {
        $queues = [];
        foreach ($this->entities($entityIds) as $entity) {
            $queues[] = $create(
                'website',
                $entity['website_id'],
                $entity['base_entity_id'],
                ['website' => $entity['name']]
            );
        }

        return $queues;
    }

    /**
     * Entities
     *
     * @param int[] $entityIds
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function entities(array $entityIds)
    {
        $connection = $this->resourceWebsite->getConnection();
        $select = $connection->select()
            ->from($this->resourceWebsite->getMainTable(), [
                'website_id',
                'name',
                'base_entity_id' => $this->resourceWebsite->getIdFieldName()
            ])
            ->where($connection->prepareSqlCondition($this->resourceWebsite->getIdFieldName(), ['in' => $entityIds]));

        return $connection->fetchAll($select);
    }
}

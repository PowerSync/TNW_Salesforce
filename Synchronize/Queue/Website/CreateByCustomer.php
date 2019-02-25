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
     * @param int $entityId
     * @param array $additional
     * @param callable $create
     * @param int $websiteId
     * @return mixed
     */
    public function process($entityId, array $additional, callable $create, $websiteId)
    {
        $queues = [];
        foreach ($this->entities($entityId) as $entity) {
            $queues[] = $create('website', $entity['website_id'], ['website' => $entity['email']]);
        }

        return $queues;
    }

    /**
     * Entities
     *
     * @param int $entityId
     * @return array
     */
    public function entities($entityId)
    {
        $connection = $this->resourceCustomer->getConnection();
        $select = $connection->select()
            ->from($this->resourceCustomer->getEntityTable(), ['website_id', 'email'])
            ->where("{$this->resourceCustomer->getEntityIdField()} = :customer_id");

        return $connection->fetchAll($select, ['customer_id' => $entityId]);
    }
}

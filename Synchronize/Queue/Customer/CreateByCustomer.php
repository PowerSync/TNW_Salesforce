<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Queue\Customer;

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
     * @return \TNW\Salesforce\Model\Queue[]
     */
    public function process(array $entityIds, array $additional, callable $create, $websiteId)
    {
        $queues = [];
        foreach ($this->entities($entityIds) as $entity) {
            $queues[] = $create(
                'customer',
                $entity['entity_id'],
                $entity['base_entity_id'],
                ['customer' => $entity['email']]
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
            ->from($this->resourceCustomer->getEntityTable(), [
                'entity_id' => $this->resourceCustomer->getEntityIdField(),
                'base_entity_id' => $this->resourceCustomer->getEntityIdField(),
                'email'
            ])
            ->where($connection->prepareSqlCondition(
                $this->resourceCustomer->getEntityIdField(),
                ['in' => $entityIds]
            ));

        return $connection->fetchAll($select);
    }
}

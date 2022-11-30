<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Queue\Customer;

use Magento\Customer\Model\ResourceModel\Customer;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Service\Synchronize\Queue\Create\GetEntities;
use TNW\Salesforce\Synchronize\Queue\CreateInterface;

/**
 * Create By Customer
 */
class CreateByCustomer implements CreateInterface
{
    const CREATE_BY = 'customer';

    /**
     * @var Customer
     */
    private $resourceCustomer;

    /** @var GetEntities */
    private $getEntities;

    /**
     * CreateByCustomer constructor.
     *
     * @param Customer    $resourceCustomer
     * @param GetEntities $getEntities
     */
    public function __construct(
        Customer    $resourceCustomer,
        GetEntities $getEntities
    ) {
        $this->resourceCustomer = $resourceCustomer;
        $this->getEntities = $getEntities;
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
     * @return Queue[]
     */
    public function process(array $entityIds, array $additional, callable $create, $websiteId)
    {
        $queues = [];
        $entities = $this->getEntities->execute($entityIds, $this, 'entity_id');
        foreach ($entities as $entity) {
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

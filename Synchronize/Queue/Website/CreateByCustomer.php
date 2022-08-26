<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Queue\Website;

use Magento\Customer\Model\ResourceModel\Customer;

/**
 * Create By Customer
 */
class CreateByCustomer extends CreateByBase
{
    const CREATE_BY = 'customer';

    /**
     * @var Customer
     */
    private $resourceCustomer;

    /**
     * CreateByCustomer constructor.
     * @param Customer $resourceCustomer
     */
    public function __construct(
        Customer $resourceCustomer
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
                ['website' => $entity['website_id']]
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
                'website_id',
                'email',
                'base_entity_id' => $this->resourceCustomer->getEntityIdField()
            ])
            ->where($connection->prepareSqlCondition(
                $this->resourceCustomer->getEntityIdField(),
                ['in' => $entityIds]
            ));

        return $connection->fetchAll($select);
    }
}

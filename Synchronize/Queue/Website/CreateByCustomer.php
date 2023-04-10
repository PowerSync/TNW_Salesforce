<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Queue\Website;

use Magento\Customer\Model\ResourceModel\Customer;
use TNW\Salesforce\Service\Synchronize\Queue\Create\GetEntities;
use TNW\Salesforce\Service\Synchronize\Queue\Website\FilterExisting;

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

    /** @var GetEntities */
    private $getEntities;

    /** @var FilterExisting  */
    private $filterExisting;

    /**
     * CreateByCustomer constructor.
     *
     * @param Customer       $resourceCustomer
     * @param GetEntities    $getEntities
     * @param FilterExisting $filterExisting
     */
    public function __construct(
        Customer $resourceCustomer,
        GetEntities $getEntities,
        FilterExisting $filterExisting
    ) {
        $this->resourceCustomer = $resourceCustomer;
        $this->getEntities = $getEntities;
        $this->filterExisting = $filterExisting;
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
        $entities = $this->getEntities->execute($entityIds, $this, 'base_entity_id');
        $entities = $this->filterExisting->execute($entities, $websiteId);
        
        foreach ($entities as $entity) {
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

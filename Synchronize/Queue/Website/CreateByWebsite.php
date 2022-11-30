<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Queue\Website;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\Store\Model\ResourceModel\Website;
use TNW\Salesforce\Service\Synchronize\Queue\Create\GetEntities;

/**
 * Create By Website
 */
class CreateByWebsite extends CreateByBase
{
    const CREATE_BY = 'website';

    /**
     * @var Order
     */
    private $resourceWebsite;

    /** @var GetEntities */
    private $getEntities;

    /**
     * CreateByWebsite constructor.
     *
     * @param Website     $resourceWebsite
     * @param GetEntities $getEntities
     */
    public function __construct(
        Website $resourceWebsite,
        GetEntities $getEntities
    ) {
        $this->resourceWebsite = $resourceWebsite;
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
     * @return mixed
     * @throws LocalizedException
     */
    public function process(array $entityIds, array $additional, callable $create, $websiteId)
    {
        $queues = [];
        $entities = $this->getEntities->execute($entityIds, $this, 'base_entity_id');
        foreach ($entities as $entity) {
            $queues[] = $create(
                'website',
                $entity['website_id'],
                $entity['base_entity_id'],
                ['website' => $entity['code']]
            );
        }

        return $queues;
    }

    /**
     * Entities
     *
     * @param int[] $entityIds
     * @return array
     * @throws LocalizedException
     */
    public function entities(array $entityIds)
    {
        $connection = $this->resourceWebsite->getConnection();
        $select = $connection->select()
            ->from($this->resourceWebsite->getMainTable(), [
                'website_id',
                'code',
                'base_entity_id' => $this->resourceWebsite->getIdFieldName()
            ])
            ->where($connection->prepareSqlCondition($this->resourceWebsite->getIdFieldName(), ['in' => $entityIds]));

        return $connection->fetchAll($select);
    }
}

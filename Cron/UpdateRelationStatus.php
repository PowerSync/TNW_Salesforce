<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Cron;

use Magento\Framework\App\ResourceConnection;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\ResourceModel\Queue\Collection;
use TNW\Salesforce\Model\ResourceModel\Queue\CollectionFactory;
use TNW\Salesforce\Synchronize\Queue;
use Zend_Db_Expr;

class UpdateRelationStatus
{
    private const CHUNK_SIZE = 500;

    /** @var Config  */
    private $config;

    /** @var CollectionFactory  */
    private $collectionFactory;

    /** @var Queue */
    private $queueService;

    /**
     * @param Config $config
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Config                       $config,
        CollectionFactory          $collectionFactory
    ) {
        $this->config = $config;
        $this->collectionFactory = $collectionFactory;

    }

    /**
     * @return void
     */
    public function execute(): void
    {
        if (!$this->config->getSalesforceStatus()) {
            return;
        }

        if (!$this->config->needUpdateRelationStatus()) {
            return;
        }

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->setLoadCompositeStatus(false);

        $collection->join(['relation' => 'tnw_salesforce_entity_queue_relation'], 'main_table.queue_id = relation.parent_id', []);

        $collection->addFieldToFilter('main_table.status', ['neq' => new Zend_Db_Expr('relation.parent_status')]);

        $collection->setPageSize(self::CHUNK_SIZE);

        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToSelect('queue_id');
        $collection->addFieldToSelect('status');
        $collection->distinct(true);

        Queue::updateRelationStatus($collection);
    }
}

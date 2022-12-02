<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Model\Framework\Indexer\SaveHandler;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexStructureInterface;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\Framework\Indexer\ScopeResolver\FlatScopeResolver;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;
use TNW\Salesforce\Service\Model\Grid\GetGridUpdatersByEntityTypes;
use Traversable;

class Grid extends \Magento\Framework\Indexer\SaveHandler\Grid
{
    /** @var GetGridUpdatersByEntityTypes */
    private $getGridUpdatersByEntityTypes;


    public function __construct(
        IndexStructureInterface $indexStructure,
        ResourceConnection $resource,
        Batch $batch,
        IndexScopeResolver $indexScopeResolver,
        FlatScopeResolver $flatScopeResolver,
        GetGridUpdatersByEntityTypes $getGridUpdatersByEntityTypes,
        array $data,
        $batchSize = 100
    )
    {
        parent::__construct(
            $indexStructure,
            $resource,
            $batch,
            $indexScopeResolver,
            $flatScopeResolver,
            $data,
            $batchSize
        );
        $this->getGridUpdatersByEntityTypes = $getGridUpdatersByEntityTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function saveIndex($dimensions, Traversable $documents)
    {
        $gridUpdaters = $this->getGridUpdatersByEntityTypes->execute()['customer'] ?? [];
        foreach ($this->batch->getItems($documents, $this->batchSize) as $batchDocuments) {
            $this->insertDocumentsForFilterable($batchDocuments, $dimensions);
            $entityIds = [];
            foreach ($batchDocuments as $batchDocument) {
                $entityId = $batchDocument->getId();
                $entityId && $entityIds[] = $entityId;
            }

            foreach ($gridUpdaters as $gridUpdater) {
                $entityIds && $gridUpdater->execute($entityIds);
            }


        }
    }
}

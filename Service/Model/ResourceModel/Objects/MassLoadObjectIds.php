<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Model\ResourceModel\Objects;

use Psr\Log\LoggerInterface;
use Throwable;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\ResourceModel\Objects;

/**
 *  Load salesforce object data
 */
class MassLoadObjectIds implements CleanableInstanceInterface
{
    private const CHUNK_SIZE = ChunkSizeInterface::CHUNK_SIZE;

    /** @var array */
    private $cache = [];

    /** @var array  */
    private $processed = [];

    /** @var Objects */
    private $objectsResource;

    /** @var Config */
    private $config;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Objects         $objectsResource
     * @param Config          $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        Objects         $objectsResource,
        Config          $config,
        LoggerInterface $logger
    ) {
        $this->objectsResource = $objectsResource;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param array  $entityIds
     * @param string $magentoType
     * @param int    $websiteId
     *
     * @return array
     */
    public function execute(array $entityIds, string $magentoType, int $websiteId): array
    {
        try {
            if (!$entityIds) {
                return [];
            }
            $entityIds = array_map('intval', $entityIds);
            $entityIds = array_unique($entityIds);
            $missedEntityIds = [];
            $baseWebsiteId = $this->config->baseWebsiteIdLogin($websiteId);
            foreach ($entityIds as $entityId) {
                if (!isset($this->processed[$baseWebsiteId][$magentoType][$entityId])) {
                    $missedEntityIds[] = $entityId;
                    $this->cache[$baseWebsiteId][$magentoType][$entityId] = [];
                    $this->processed[$baseWebsiteId][$magentoType][$entityId] = 1;
                }
            }
            $connection = $this->objectsResource->getConnection();
            $select = $connection->select();
            $mainTable = $this->objectsResource->getMainTable();
            $select->from($mainTable, ['entity_id', 'object_id', 'salesforce_type']);
            $select->where('magento_type = :magento_type');
            $select->where('website_id IN (:base_website_id, :entity_website_id)');
            $select->order(new \Zend_Db_Expr('FIELD(website_id, :base_website_id, :entity_website_id)'));
            foreach (array_chunk($missedEntityIds, self::CHUNK_SIZE) as $missedEntityIdsChunk) {
                $chunkSelect = clone $select;
                $chunkSelect->where('entity_id IN (?)', $missedEntityIdsChunk);

                $items = $connection->fetchAll(
                    $chunkSelect,
                    [
                        'magento_type' => $magentoType,
                        'entity_website_id' => $websiteId,
                        'base_website_id' => $baseWebsiteId
                    ]
                );
                foreach ($items as $data) {
                    $entityId = $data['entity_id'];
                    $objectId = $data['object_id'];
                    $salesforceType = $data['salesforce_type'];
                    if (!isset($this->cache[$baseWebsiteId][$magentoType][$entityId][$salesforceType])) {
                        $this->cache[$baseWebsiteId][$magentoType][$entityId][$salesforceType] = $objectId;
                    } else {
                        $this->cache[$baseWebsiteId][$magentoType][$entityId][$salesforceType] .= "\n" . $objectId;
                    }
                }
            }
            $result = [];
            foreach ($entityIds as $entityId) {
                $result[$entityId] = $this->cache[$baseWebsiteId][$magentoType][$entityId] ?? [];
            }
        } catch (Throwable $e) {
            $this->logger->critical($e->getMessage());
            $result = [];
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function clearLocalCache(): void
    {
        $this->cache = [];
        $this->processed = [];
    }


    /**
     * @param int    $entityId
     * @param string $magentoType
     * @param int    $websiteId
     *
     * @return array
     */
    public function loadObjectIds($entityId, $magentoType, $websiteId)
    {
        return $this->execute([$entityId], (string)$magentoType, (int)$websiteId)[$entityId] ?? [];
    }

    /**
     * @param array  $entityIds
     * @param string $magentoType
     * @param int    $websiteId
     *
     * @return array
     */
    public function massLoadObjectIds(array $entityIds, string $magentoType, int $websiteId): array
    {
        return $this->execute($entityIds, $magentoType, $websiteId);
    }
}

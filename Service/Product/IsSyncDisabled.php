<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\Product;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;

/**
 * Is product sync disabled service.
 */
class IsSyncDisabled implements CleanableInstanceInterface
{
    private const ATTRIBUTE_CODE = 'sforce_disable_sync';
    private const CHUNK_SIZE = ChunkSizeInterface::CHUNK_SIZE;

    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    private $resourceConnection;

    /** @var Config */
    private $eavConfig;

    /** @var bool[] */
    private $cache = [];

    /** @var array  */
    private $processed = [];

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product $resourceConnection
     * @param EavConfig                                    $eavConfig
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product $resourceConnection,
        EavConfig $eavConfig
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @param array $entityIds
     *
     * @return array
     * @throws LocalizedException
     */
    public function execute(array $entityIds): array
    {
        if (!$entityIds) {
            return [];
        }

        $entityIds = array_map('intval', $entityIds);
        $entityIds = array_unique($entityIds);

        $missedEntityIds = [];
        foreach ($entityIds as $entityId) {
            if (!isset($this->processed[$entityId])) {
                $missedEntityIds[] = $entityId;
                $this->cache[$entityId] = false;
                $this->processed[$entityId] = 1;
            }
        }

        if ($missedEntityIds) {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, self::ATTRIBUTE_CODE);
            if (!$attribute || !$attribute->getId()) {
                return [];
            }

            $connection = $this->resourceConnection->getConnection();
            $table = $attribute->getBackendTable();
            $select = $connection->select();
            $linkField = $this->resourceConnection->getLinkField();
            $select->from($table, [$linkField]);
            $select->where('attribute_id = ?', $attribute->getId());
            $select->where('value = 1');
            $select->where('store_id = 0');

            foreach (array_chunk($missedEntityIds, self::CHUNK_SIZE) as $missedEntityIdsChunk) {
                $batchSelect = clone $select;
                $batchSelect->where($linkField . ' IN (?)', $missedEntityIdsChunk);
                $items = $connection->fetchAll($batchSelect);
                foreach ($items as $item) {
                    $entityId = (int)($item[$linkField] ?? 0);
                    if ($entityId) {
                        $this->cache[$entityId] = true;
                    }
                }
            }
        }

        $result = [];
        foreach ($entityIds as $entityId) {
            $result[$entityId] = $this->cache[$entityId] ?? false;
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
}

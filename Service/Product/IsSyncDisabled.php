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
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

/**
 * Is product sync disabled service.
 */
class IsSyncDisabled implements CleanableInstanceInterface
{
    private const ATTRIBUTE_CODE = 'sforce_disable_sync';
    private const CHUNK_SIZE = ChunkSizeInterface::CHUNK_SIZE;

    /** @var Config */
    private $eavConfig;

    /** @var bool[] */
    private $cache = [];

    /** @var array  */
    private $processed = [];

    /** @var CollectionFactory */
    private $collectionFactory;

    /**
     * @param EavConfig         $eavConfig
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        EavConfig $eavConfig,
        CollectionFactory $collectionFactory
    ) {
        $this->eavConfig = $eavConfig;
        $this->collectionFactory = $collectionFactory;
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
                $result = [];
                foreach ($entityIds as $entityId) {
                    $result[$entityId] = $this->cache[$entityId] ?? false;
                }

                return $result;
            }

            foreach (array_chunk($missedEntityIds, self::CHUNK_SIZE) as $missedEntityIdsChunk) {
                $collection = $this->collectionFactory->create();
                $collection->addAttributeToSelect([self::ATTRIBUTE_CODE], 'left');
                $collection->addIdFilter($entityIds);

                foreach ($collection as $item) {
                    $entityId = $item->getId();
                    $entityId && $this->cache[$entityId] = (bool)$item->getData(self::ATTRIBUTE_CODE);
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

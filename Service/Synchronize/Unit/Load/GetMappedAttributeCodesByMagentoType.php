<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Unit\Load;

use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Model\Mapper;

class GetMappedAttributeCodesByMagentoType implements CleanableInstanceInterface
{
    private const ENTITY_TYPE_MAP = [
        Mapper::MAGENTO_ENTITY_TYPE_CUSTOMER => Customer::ENTITY,
        Mapper::MAGENTO_ENTITY_TYPE_PRODUCT => Product::ENTITY,
    ];

    /** @var array */
    private $cache = [];

    /** @var ResourceConnection */
    private $resource;

    /** @var GetEntityTypeByCode */
    private $getEntityTypeByCode;

    /**
     * @param ResourceConnection  $resource
     * @param GetEntityTypeByCode $getEntityTypeByCode
     */
    public function __construct(
        ResourceConnection  $resource,
        GetEntityTypeByCode $getEntityTypeByCode
    ) {
        $this->resource = $resource;
        $this->getEntityTypeByCode = $getEntityTypeByCode;
    }

    public function execute(array $magentoTypes): array
    {
        if (!$magentoTypes) {
            return [];
        }

        $magentoTypes = array_map('strval', $magentoTypes);
        $magentoTypes = array_unique($magentoTypes);

        $missedMagentoTypes = [];
        foreach ($magentoTypes as $magentoEntityType) {
            if (!isset($this->cache[$magentoEntityType])) {
                $missedMagentoTypes[] = $magentoEntityType;
                $defaultAttributes = [];
                if ($magentoEntityType === Mapper::MAGENTO_ENTITY_TYPE_CUSTOMER) {
                    $defaultAttributes[] = 'sales_representative';
                }
                $this->cache[$magentoEntityType] = $defaultAttributes;
            }
        }

        if ($missedMagentoTypes) {
            $select = $this->getSelect();
            $connection = $this->resource->getConnection();
            $entityTypeCodes = [];
            foreach ($missedMagentoTypes as $magentoType) {
                $entityTypeCode = self::ENTITY_TYPE_MAP[$magentoType] ?? null;
                if ($entityTypeCode) {
                    $entityTypeCodes[] = $entityTypeCode;
                }
            }
            $entityTypeByCode = $this->getEntityTypeByCode->execute($entityTypeCodes);

            foreach (array_chunk($missedMagentoTypes, ChunkSizeInterface::CHUNK_SIZE) as $missedMagentoTypesChunk) {
                $batchSelect = clone $select;
                $batchSelect->where('main_table.magento_entity_type IN(?)', $missedMagentoTypesChunk);
                $entityTypeIds = [];
                foreach ($missedMagentoTypesChunk as $missedMagentoType) {
                    $entityTypeCode = self::ENTITY_TYPE_MAP[$missedMagentoType] ?? null;
                    if ($entityTypeCode) {
                        $entityType = $entityTypeByCode[$entityTypeCode] ?? null;
                        if ($entityType) {
                            $entityTypeIds[] = (int)$entityType->getId();
                        }

                    }
                }
                if ($entityTypeIds) {
                    $batchSelect->where('eav.entity_type_id IN(?)', $entityTypeIds);
                    $items = $connection->fetchAll($batchSelect);
                    $magentoEntityTypes = [];
                    foreach ($items as $item) {
                        $magentoEntityType = $item['magento_entity_type'];
                        $magentoAttributeName = $item['magento_attribute_name'];
                        $this->cache[$magentoEntityType][] = $magentoAttributeName;
                        $magentoEntityTypes[] = $magentoEntityType;
                    }

                    foreach ($magentoEntityTypes as $magentoEntityType) {
                        $attributeCodes = $this->cache[$magentoEntityType] ?? [];
                        if ($attributeCodes) {
                            $this->cache[$magentoEntityType] = array_unique($attributeCodes);
                        }
                    }
                }
            }
        }

        $result = [];
        foreach ($magentoTypes as $magentoEntityType) {
            $result[$magentoEntityType] = $this->cache[$magentoEntityType] ?? [];
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function clearLocalCache(): void
    {
        $this->cache = [];
    }

    /**
     * @return Select
     */
    private function getSelect(): Select
    {
        $resource = $this->resource;
        $connection = $resource->getConnection();
        $select = $connection->select()->from(
            ['main_table' => $resource->getTableName('tnw_salesforce_mapper')],
            ['magento_attribute_name', 'magento_entity_type']
        );
        $select->join(
            ['eav' => $resource->getTableName('eav_attribute')],
            'main_table.magento_attribute_name = eav.attribute_code',
            []
        );

        return $select;
    }
}
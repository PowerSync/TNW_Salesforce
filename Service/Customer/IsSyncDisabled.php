<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\Customer;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Api\Service\Customer\IsSyncDisabledInterface;

/**
 * Is customer sync disabled service.
 */
class IsSyncDisabled implements IsSyncDisabledInterface, CleanableInstanceInterface
{
    private const ATTRIBUTE_CODE = 'sforce_disable_sync';
    private const CHUNK_SIZE = ChunkSizeInterface::CHUNK_SIZE;

    /** @var ResourceConnection */
    private $resourceConnection;

    /** @var Config */
    private $eavConfig;

    /** @var bool[] */
    private $cache = [];

    /** @var array  */
    private $processed = [];

    /**
     * @param ResourceConnection $resourceConnection
     * @param EavConfig          $eavConfig
     */
    public function __construct(ResourceConnection $resourceConnection, EavConfig $eavConfig)
    {
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entityIds): array
    {
        if (!$entityIds) {
            return [];
        }

        $entityIds = array_map('intval', $entityIds);
        $entityIds = array_unique($entityIds);

        $missedCustomerIds = [];
        foreach ($entityIds as $customerId) {
            if (!isset($this->processed[$customerId])) {
                $missedCustomerIds[] = $customerId;
                $this->cache[$customerId] = false;
                $this->processed[$customerId] = 1;
            }
        }

        if ($missedCustomerIds) {
            $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, self::ATTRIBUTE_CODE);
            if (!$attribute || !$attribute->getId()) {
                return [];
            }

            $connection = $this->resourceConnection->getConnection();
            $table = $attribute->getBackendTable();
            $select = $connection->select();
            $select->from($table, ['entity_id']);
            $select->where('attribute_id = ?', $attribute->getId());
            $select->where('value = 1');

            foreach (array_chunk($missedCustomerIds, self::CHUNK_SIZE) as $missedCustomerIdsChunk) {
                $batchSelect = clone $select;
                $batchSelect->where('entity_id IN(?)', $missedCustomerIdsChunk);
                $items = $connection->fetchAll($batchSelect);
                foreach ($items as $item) {
                    $entityId = (int)($item['entity_id'] ?? 0);
                    if ($entityId) {
                        $this->cache[$entityId] = true;
                    }
                }
            }
        }

        $result = [];
        foreach ($entityIds as $customerId) {
            $result[$customerId] = $this->cache[$customerId] ?? false;
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

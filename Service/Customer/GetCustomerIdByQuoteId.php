<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\Customer;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Api\Service\Customer\GetCustomerIdByQuoteIdInterface;

/**
 * Get customer id by quote id service.
 */
class GetCustomerIdByQuoteId implements GetCustomerIdByQuoteIdInterface, CleanableInstanceInterface
{
    private const CHUNK_SIZE = ChunkSizeInterface::CHUNK_SIZE;

    /** @var QuoteResource */
    private $quoteResource;

    /** @var int[] */
    private $cache;

    /**
     * @param QuoteResource $quoteResource
     */
    public function __construct(QuoteResource $quoteResource)
    {
        $this->quoteResource = $quoteResource;
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

        $missedEntityIds = [];
        foreach ($entityIds as $entityId) {
            if (!isset($this->cache[$entityId])) {
                $missedEntityIds[] = $entityId;
                $this->cache[$entityId] = null;
            }
        }

        if ($missedEntityIds) {
            $select = $this->getSelect();
            $connection = $this->quoteResource->getConnection();
            foreach (array_chunk($missedEntityIds, self::CHUNK_SIZE) as $missedEntityIdsChunk) {
                $batchSelect = clone $select;
                $batchSelect->where('entity_id IN(?)', $missedEntityIdsChunk);

                $items = $connection->fetchAll($batchSelect);
                foreach ($items as $item) {
                    $entityId = $item['entity_id'];
                    $value = $item['customer_id'];
                    $this->cache[$entityId] = $value;
                }
            }
        }

        $result = [];
        foreach ($entityIds as $entityId) {
            $result[$entityId] = $this->cache[$entityId] ?? null;
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
     * @throws LocalizedException
     */
    private function getSelect(): Select
    {
        $connection = $this->quoteResource->getConnection();
        $table = $this->quoteResource->getMainTable();

        return $connection->select()->from($table, ['entity_id', 'customer_id']);
    }
}

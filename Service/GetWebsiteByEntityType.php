<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service;

use Magento\Framework\Exception\LocalizedException;
use RuntimeException;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Api\Service\GetWebsiteByEntityType\GetWebsiteIdByEntityIdsInterface;

/**
 *  Class GetWebsiteByEntityType
 */
class GetWebsiteByEntityType implements CleanableInstanceInterface
{
    private const PROCESSED = 1;

    /** @var GetWebsiteIdByEntityIdsInterface[] */
    private $processors;

    /** @var array */
    private $cache = [];

    /** @var array */
    private $processed = [];

    /**
     * @param GetWebsiteIdByEntityIdsInterface[] $processors
     */
    public function __construct(
        array $processors
    ) {
        $this->processors = $processors;
    }

    /**
     * @param array $entityIds
     * @param string $entityType
     *
     * @return array
     * @throws LocalizedException
     */
    public function execute(array $entityIds, string $entityType): array
    {
        $processor = $this->processors[$entityType] ?? null;
        if (!$processor) {
            throw new LocalizedException(
                __("Processor for Entity load: '%1' is not defined!", $entityType)
            );
        }
        $entityIds = array_map('intval', $entityIds);
        $entityIds = array_unique($entityIds);

        if (!$entityIds) {
            return [];
        }

        $missedEntityIds = [];
        foreach ($entityIds as $entityId) {
            if (!isset($this->processed[$entityType][$entityId])) {
                $missedEntityIds[] = $entityId;
                $this->processed[$entityType][$entityId] = self::PROCESSED;
            }
        }

        if ($missedEntityIds) {
            $items = $processor->execute($missedEntityIds);
            foreach ($missedEntityIds as $missedEntityId) {
                $this->cache[$entityType][$missedEntityId] = $items[$missedEntityId] ?? 0;
            }
        }

        $result = [];
        foreach ($entityIds as $entityId) {
            $result[$entityId] = $this->cache[$entityType][$entityId] ?? 0;
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

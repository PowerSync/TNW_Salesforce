<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service;

use RuntimeException;
use TNW\Salesforce\Api\Service\GetWebsiteByEntityType\GetWebsiteIdByEntityIdsInterface;

/**
 *  Class GetWebsiteByEntityType
 */
class GetWebsiteByEntityType
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
     * @param array  $entityIds
     * @param string $entityType
     *
     * @return array
     */
    public function execute(array $entityIds, string $entityType): array
    {
        $processor = $this->processors[$entityType] ?? null;
        if (!$processor) {
            $format = "Processor for Entity load: '%s' is not defined!";
            throw new RuntimeException(
                sprintf($format, $entityType)
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
}

<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Model\Grid;

use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Api\Model\Grid\GetColumnsDataItems\ExecutorInterface;
use TNW\Salesforce\Model\CleanLocalCache\CleanableObjectsList;

/**
 *  Class GetColumnsData
 */
class GetColumnsDataItems
{
    /** @var ExecutorInterface[] */
    private $executors;

    /**
     * @param CleanableObjectsList $cleanableObjectsList
     * @param array                $executors
     */
    public function __construct(
        CleanableObjectsList $cleanableObjectsList,
        array                $executors
    ) {
        $this->executors = $executors;
        foreach ($executors as $executor) {
            if ($executor instanceof CleanableInstanceInterface) {
                $cleanableObjectsList->add($executor);
            }
        }
    }


    /**
     * @param array|null $entityIds
     *
     * @return array
     */
    public function execute(array $entityIds = null): array
    {
        $result = [];
        foreach ($this->executors as $columnName => $executor) {
            $items = $executor->execute($columnName, $entityIds);
            if ($entityIds === null) {
                $entityIds = array_keys($items);
            }
            foreach ($entityIds as $entityId) {
                $result[$entityId][$columnName] = $items[$entityId] ?? null;
            }
        }

        return $result;
    }
}

<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Queue\Unit\CreateQueue;

class UnsetPendingStatusPool
{
    /** @var array  */
    private $items = [];

    /**
     * @param int    $entityId
     * @param string $entityType
     * @param int    $websiteId
     * @param string $objectType
     *
     * @return void
     */
    public function addItem(string $objectType, string $entityType, int $websiteId, int $entityId): void
    {
        $this->items[$objectType][$entityType][$websiteId][$entityId] = $entityId;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return void
     */
    public function clear():  void
    {
        $this->items = [];
    }
}

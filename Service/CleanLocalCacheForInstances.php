<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service;

use TNW\Salesforce\Api\CleanableInstanceInterface;

/**
 *  Class CleanLocalCacheForInstances
 */
class CleanLocalCacheForInstances
{
    /** @var CleanableInstanceInterface[] */
    private $instances;

    /**
     * @param CleanableInstanceInterface[] $instances
     */
    public function __construct(
        array $instances = []
    ) {
        $this->instances = $instances;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        foreach ($this->instances as $instance) {
            $instance->clearLocalCache();
        }
    }
}

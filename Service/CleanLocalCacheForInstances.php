<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service;

use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Model\CleanLocalCache\CleanableObjectsList;

/**
 *  Class CleanLocalCacheForInstances
 */
class CleanLocalCacheForInstances
{
    /** @var CleanableInstanceInterface[] */
    private $instances;

    /** @var CleanableObjectsList */
    private $objectsList;

    /**
     * @param CleanableObjectsList $objectsList
     * @param array                $instances
     */
    public function __construct(
        CleanableObjectsList $objectsList,
        array                $instances = []
    ) {
        $this->objectsList = $objectsList;
        $this->instances = $instances;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        foreach ($this->objectsList->getList() as $instance) {
            $instance->clearLocalCache();
        }

        foreach ($this->instances as $instance) {
            $instance->clearLocalCache();
        }
    }
}

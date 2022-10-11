<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Queue\Skip;

use TNW\Salesforce\Model\Queue;

/**
 * Batch preload data for skip rules
 */
interface PreloadQueuesDataInterface
{
    /**
     * @param Queue[] $queues
     */
    public function preload(array $queues): void;
}

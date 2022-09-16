<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Unit\Load\EntityLoader;

/**
 * Interface PreLoaderInterface
 */
interface EntityPreLoaderInterface
{
    /**
     * @param array $entities
     *
     * @return array
     */
    public function preload(array $entities): array;
}

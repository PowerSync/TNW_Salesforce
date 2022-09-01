<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Api\Service;

/**
 * Use in preload data for creators or skip rules
 */
interface PreloaderEntityIdsInterface
{
    /**
     * Call service with batch ids and fill cache
     *
     * @param array $entityIds
     *
     * @return void
     */
    public function execute(array $entityIds): void;
}

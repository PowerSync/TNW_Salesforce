<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Api;

/**
 * Clear instance cache
 */
interface CleanableInstanceInterface
{
    /**
     * @return void
     */
    public function clearLocalCache(): void;
}

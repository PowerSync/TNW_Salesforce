<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Unit\Load\PreLoader;

use TNW\Salesforce\Service\Synchronize\Unit\Load\PreloadEntities\AfterLoadExecutorInterface;

/**
 * Interface AfterPreLoadExecutorsInterface
 */
interface AfterPreLoadExecutorsInterface
{
    /**
     * @return AfterLoadExecutorInterface[]
     */
    public function getAfterPreLoadExecutors(): array;
}

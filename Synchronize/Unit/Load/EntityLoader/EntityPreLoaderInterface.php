<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Unit\Load\EntityLoader;

use TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities\Load\AfterLoadExecutorInterface;
use TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities\Load\LoaderInterface;

/**
 * Interface PreLoaderInterface
 */
interface EntityPreLoaderInterface
{
    /**
     * @return LoaderInterface[]
     */
    public function getLoaders(): array;

    /**
     * @return AfterLoadExecutorInterface[]
     */
    public function getAfterPreloadExecutors(): array;
}

<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Unit\Load\EntityLoader;

use Magento\Framework\Model\AbstractModel;
use TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities\Load\AfterLoadExecutorInterface;
use TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities\Load\BeforeLoadExecutorInterface;
use TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities\Load\LoaderInterface;

/**
 * Interface PreLoaderInterface
 */
interface EntityPreLoaderInterface
{
    /**
     * @return AbstractModel| null
     */
    public function createEmptyEntity(): ?AbstractModel;

    /**
     * @return LoaderInterface
     */
    public function getLoader(): LoaderInterface;

    /**
     * @return BeforeLoadExecutorInterface[]
     */
    public function getBeforePreloadExecutors(): array;

    /**
     * @return AfterLoadExecutorInterface[]
     */
    public function getAfterPreloadExecutors(): array;
}

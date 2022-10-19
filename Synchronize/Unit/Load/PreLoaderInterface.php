<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Unit\Load;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use TNW\Salesforce\Service\Synchronize\Unit\Load\PreloadEntities\AfterLoadExecutorInterface;

/**
 * Interface PreLoaderInterface
 */
interface PreLoaderInterface
{
    /**
     * @return AbstractDb|null
     */
    public function createCollectionInstance(): ?AbstractDb;

    /**
     * @return AbstractModel
     */
    public function createEmptyEntity(): AbstractModel;

    /**
     * @return AfterLoadExecutorInterface[]
     */
    public function getAfterPreLoadExecutors(): array;
}

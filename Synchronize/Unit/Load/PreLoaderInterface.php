<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Unit\Load;

/**
 * Interface PreLoaderInterface
 */
interface PreLoaderInterface
{
    /**
     * @return \Magento\Framework\Data\Collection\AbstractDb
     */
    public function getCollection(): \Magento\Framework\Data\Collection\AbstractDb;
}

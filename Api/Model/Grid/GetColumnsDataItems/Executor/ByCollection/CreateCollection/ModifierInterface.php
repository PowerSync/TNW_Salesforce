<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Api\Model\Grid\GetColumnsDataItems\Executor\ByCollection\CreateCollection;

use Magento\Framework\Data\Collection;

interface ModifierInterface
{
    /**
     * @param Collection $collection
     *
     * @return void
     */
    public function execute(Collection $collection): void;
}

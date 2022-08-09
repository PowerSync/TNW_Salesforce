<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit;

interface LoadLoaderInterface
{
    /**
     * Load Type
     *
     * @return string
     */
    public function loadBy();

    /**
     * Load
     *
     * @param int $entityId
     * @param array $additional
     * @return \Magento\Framework\Model\AbstractModel
     */
    public function load($entityId, array $additional);
}

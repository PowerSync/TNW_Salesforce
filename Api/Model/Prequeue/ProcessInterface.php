<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Api\Model\Prequeue;

interface ProcessInterface
{
    /**
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute();
}

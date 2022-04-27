<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Api;

/**
 * Website interface
 */
interface WebsiteInterface
{
    /**
     * Do Magento Websites syncronization to Salesforce custom object
     *
     * @param \Magento\Store\Model\Website[] $websites
     */
    public function syncWebsites($websites);
}

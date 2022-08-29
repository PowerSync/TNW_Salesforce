<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit\Customer;

use TNW\Salesforce\Synchronize;

/**
 * Customer Hash
 */
class Hash implements Synchronize\Unit\HashInterface
{
    /**
     * @var \Magento\Customer\Model\Config\Share
     */
    private $customerConfigShare;

    /**
     * Hash constructor.
     * @param \Magento\Customer\Model\Config\Share $customerConfigShare
     */
    public function __construct(
        \Magento\Customer\Model\Config\Share $customerConfigShare
    ) {
        $this->customerConfigShare = $customerConfigShare;
    }

    /**
     * Calculate
     *
     * @param \Magento\Customer\Model\Customer $entity
     * @return string
     */
    public function calculateEntity($entity)
    {
        $hash = $entity->getEmail();
        if ($this->customerConfigShare->isWebsiteScope()) {
            $hash .= ":{$entity->getWebsiteId()}";
        }

        return strtolower((string)$hash);
    }
}

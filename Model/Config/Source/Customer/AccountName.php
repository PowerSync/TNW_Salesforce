<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\Config\Source\Customer;

/**
 * Class AccountName
 * @package TNW\Salesforce\Model\Config\Source\Customer
 */
class AccountName implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Don\'t modify Account name if exists')],
            ['value' => 1, 'label' => __('Overwrite Account name from Magento')]
        ];
    }
}

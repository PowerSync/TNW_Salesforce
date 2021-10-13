<?php
declare(strict_types=1);

namespace TNW\Salesforce\Model\Config\Source\Customer;

/**
 * ContactAssignee configuration field select source model
 * Class ContactAssignee
 * @package TNW\Salesforce\Model\Config\Source\Customer
 */
class ContactAssignee implements \Magento\Framework\Option\ArrayInterface
{
    const DEFAULT_OWNER = 1;
    const RETAIN_FROM_ACCOUNT = 0;

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::DEFAULT_OWNER,
                'label' => __('Use Default Owner')
            ],
            [
                'value' => self::RETAIN_FROM_ACCOUNT,
                'label' => __('Retain Owner from Existing Account')
            ]
        ];
    }
}

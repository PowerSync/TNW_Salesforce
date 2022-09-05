<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\Config\Source\Customer;

use Magento\Customer\Model\Config\Source\Group\Multiselect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * All customer groups config source.
 */
class AllGroups extends Multiselect
{
    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function toOptionArray()
    {
        $loggedCustomerGroups = parent::toOptionArray();

        return $this->addNotLoggedCustomerGroup($loggedCustomerGroups);
    }

    /**
     * Add not logged customer group to options.
     *
     * @param array $loggedCustomerGroups
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function addNotLoggedCustomerGroup(array $loggedCustomerGroups): array
    {
        $customerGroups = reset($loggedCustomerGroups);
        if (is_array($customerGroups['value'])) {
            $notLoggedCustomerGroup = $this->_groupManagement->getNotLoggedInGroup();
            array_unshift(
                $loggedCustomerGroups[key($loggedCustomerGroups)]['value'],
                [
                    'value' => $notLoggedCustomerGroup->getId(),
                    'label' => $notLoggedCustomerGroup->getCode(),
                ]
            );
        }

        return $loggedCustomerGroups;
    }
}

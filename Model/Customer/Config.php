<?php

namespace TNW\Salesforce\Model\Customer;

use Magento\Framework\DataObject;
use Magento\Store\Model\ScopeInterface;

/**
 * Configuration for customer Salesforce client
 *
 * Class Config
 * @package TNW\Salesforce\Model
 */
class Config extends \TNW\Salesforce\Model\Config
{
    /**
     * Get Customer Integration status
     *
     * @param int|null $websiteId
     * @return bool
     */
    public function getCustomerStatus($websiteId = null)
    {
        $value = $this->getStoreConfig('tnwsforce_customer/general/active');

        return $value ? true : false;
    }

    /**
     * Checks if Magento can rename account names on Salesforce
     * @param null $websiteId
     * @return bool
     */
    public function canRenameAccount($websiteId = null)
    {

        $value = $this->getStoreConfig('tnwsforce_customer/general/account_name');


        return $value ? true : false;
    }

    /**
     * Get Customer sync for all customer groups or not
     *
     * @param int|null $websiteId
     * @return bool
     */
    public function getCustomerAllGroups($websiteId = null)
    {

        $value = $this->getStoreConfig('tnwsforce_customer/general/sync_groups');

        return $value ? true : false;
    }

    /**
     * Get Customer groups ids that allowed to sync
     *
     * @param int|null $websiteId
     * @return array
     */
    public function getCustomerSyncGroups($websiteId = null)
    {

        $value = $this->getStoreConfig('tnwsforce_customer/general/customer_group');

        $result = [];
        $value = trim($value);
        if (strlen($value) > 0) {
            $result = array_unique(array_filter(array_map('intval', explode(',', $value))));
        }

        return $result;
    }

    /**
     * Default owner for Accounts and Contacts
     * This default owner will be assigned to Contact and/or Account when created.
     *
     * @param null $websiteId
     * @return string
     */
    public function defaultOwner($websiteId = null)
    {
        $value = $this->getStoreConfig('tnwsforce_customer/general/default_owner');

        return $value;
    }

    /**
     * Contact assignee
     * Use Default Owner - when a new Contact is created, the Contact will be assigned to the 'Default Owner' value set above.
     * Retain Owner from Existing Account - If a matching Account already exists in Salesforce, Magento will assign
     * a new Contact to whomever owns the Account. Otherwise Magento will fall back to 'Default Owner' value set above.
     *
     * @param null $websiteId
     * @return string
     */
    public function contactAssignee($websiteId = null)
    {

        $value = $this->getStoreConfig('tnwsforce_customer/general/contact_assignee');

        return $value;
    }

    /**
     * Get is customer accounts shared between Websites or accounts defined per every website
     *
     * @return int 0 - Global, 1 - Per Website
     */
    public function accountShareScope()
    {
        return (int)$this->getStoreConfig(
            'customer/account_share/scope'
        );
    }
}

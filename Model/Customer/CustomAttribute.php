<?php

namespace TNW\Salesforce\Model\Customer;

use Magento\Customer\Model\ResourceModel\Customer as ResourceCustomer;
use Magento\Customer\Model\Backend\Customer as BackendCustomer;

class CustomAttribute
{
    const ATTRIBUTE_CODE_SF_ID = 'sforce_id';

    const ATTRIBUTE_CODE_SF_ACCOUNT_ID = 'sforce_account_id';

    const ATTRIBUTR_CODE_SYNC_STATUS = 'sforce_sync_status';

    const GROUP_LABEL = 'group_label';

    /** @var \Magento\Customer\Model\ResourceModel\Customer */
    protected $resourceCustomer;

    /** @var \Magento\Customer\Model\Backend\Customer */
    protected $backendCustomer;

    public function __construct(
        ResourceCustomer $resourceCustomer,
        BackendCustomer $backendCustomer
    ) {
        $this->resourceCustomer = $resourceCustomer;
        $this->backendCustomer = $backendCustomer;
    }

    /**
     * Get attributes list to handle
     * @return array
     */
    protected function getAttributesList()
    {
        return [
            self::ATTRIBUTE_CODE_SF_ID,
            self::ATTRIBUTE_CODE_SF_ACCOUNT_ID,
            self::ATTRIBUTR_CODE_SYNC_STATUS,
            self::GROUP_LABEL
        ];
    }

    /**
     * Update custom Salesforce attributes for customer
     *
     * @param \Magento\Customer\Model\Data\Customer $customer
     */
    public function saveSalesforceAttribute(\Magento\Customer\Model\Data\Customer $customer)
    {
        $this->backendCustomer->reset();
        $backendCustomerObject = $this->backendCustomer->load($customer->getId());

        $attributesList = $this->getAttributesList();

        foreach ($attributesList as $attributeCode) {
            $value = $customer->getCustomAttribute($attributeCode)->getValue();
            if ($backendCustomerObject->getData($attributeCode) != $value) {
                $backendCustomerObject->setData($attributeCode, $value);
                $this->resourceCustomer->saveAttribute($backendCustomerObject, $attributeCode);
            }
        }

        $backendCustomerObject->reindex();
    }

    /**
     * Update custom Salesforce attributes for backend customer
     *
     * @param \Magento\Customer\Model\Backend\Customer $customer
     */
    public function saveSalesforceAttributeBackend(\Magento\Customer\Model\Backend\Customer $customer)
    {
        $attributesList = $this->getAttributesList();
        foreach ($attributesList as $attributeCode) {
            $this->resourceCustomer->saveAttribute($customer, $attributeCode);
        }
        $customer->reindex();
    }
}

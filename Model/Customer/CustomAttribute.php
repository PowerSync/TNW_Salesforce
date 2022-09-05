<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

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

    /** @var \TNW\Salesforce\Model\ResourceModel\Objects  */
    protected $resourceObjects;

    /**
     * CustomAttribute constructor.
     * @param ResourceCustomer $resourceCustomer
     * @param BackendCustomer $backendCustomer
     * @param \TNW\Salesforce\Model\ResourceModel\Objects $resourceObjects
     */
    public function __construct(
        ResourceCustomer $resourceCustomer,
        BackendCustomer $backendCustomer,
        \TNW\Salesforce\Model\ResourceModel\Objects $resourceObjects
    ) {
        $this->resourceCustomer = $resourceCustomer;
        $this->backendCustomer = $backendCustomer;
        $this->resourceObjects = $resourceObjects;
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
            self::ATTRIBUTR_CODE_SYNC_STATUS
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
        $records = [];

        $records[] = [
            'entity_id' => $customer->getId(),
            'object_id' => $customer->getData(self::ATTRIBUTE_CODE_SF_ID),
            'magento_type' => 'Customer',
            'salesforce_type' => 'Contact',
            'status' => $customer->getData(self::ATTRIBUTR_CODE_SYNC_STATUS)
        ];

        $records[] = [
            'entity_id' => $customer->getId(),
            'object_id' => $customer->getData(self::ATTRIBUTE_CODE_SF_ACCOUNT_ID),
            'magento_type' => 'Customer',
            'salesforce_type' => 'Account',
            'status' => $customer->getData(self::ATTRIBUTR_CODE_SYNC_STATUS)
        ];

        $this->resourceObjects->saveRecords($records);

        $customer->reindex();
    }
}

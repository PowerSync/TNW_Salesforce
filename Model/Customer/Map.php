<?php

namespace  TNW\Salesforce\Model\Customer;

class Map
{
    /**
     * @var \TNW\Salesforce\Model\ResourceModel\Customer\Mapper\Repository
     */
    protected $repository;

    /**
     * @var array
     */
    protected $map;

    /** @var \Magento\Framework\ObjectManagerInterface  */
    protected $objectManager;

    /** @var \TNW\Salesforce\Model\Logger */
    protected $logger;

    /**
     * Default map
     * @var array
     */
    protected $defaultMap = [];

    //TODO::Check if we need this here
    const SFORCE_BASIC_PREFIX = 'tnw_mage_basic__';
    const SFORCE_ENTERPRISE_PREFIX = 'tnw_mage_enterp__';
    const SFORCE_MAGENTO_ID = 'Magento_ID__c';

    /**
     * @param \TNW\Salesforce\Model\ResourceModel\Customer\Mapper\Repository
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \TNW\Salesforce\Model\ResourceModel\Customer\Mapper\Repository $repository,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \TNW\Salesforce\Model\Logger $logger
    ) {
        $this->objectManager = $objectManager;
        $this->repository = $repository;
        $this->logger = $logger;
        $this->defaultMap = ['AccountId' => 'sforce_account_id', 'Id' => 'sforce_id'];
    }

    /**
     * Get customer map as associate array [magento_attribute => salesforce_attribute]
     *
     * @param string $objectType
     * @return array
     */
    public function getMapArray($objectType = \TNW\Salesforce\Model\Customer\Mapper::OBJECT_TYPE_CONTACT)
    {
        if (isset($this->map[$objectType])) {
            return $this->map[$objectType];
        }

        /**
         * @var \TNW\Salesforce\Model\Customer\Mapper[] $items
         */
        $items = $this->repository->getResultCollection($objectType)->getItems();
        $resultArray = [];
        foreach ($items as $item) {
            $resultArray[$item->getMagentoEntityType()][$item->getSalesforceAttributeName()] =
                $item->getMagentoAttributeName();
        }

        $this->map[$objectType] = $resultArray;
        return $this->map[$objectType];
    }

    /**
     * Create Contact Transfer object from Customer and Customer Address
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return \stdClass
     */
    public function getContactTransferObjectFromCustomer(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {

        $customerArray = $this->getCustomerDataAsArray($customer);

        $transferObject = new \stdClass();

        $mapArray = $this->getMapArray();
        $mapArray['customer'] = array_merge($mapArray['customer'],$this->defaultMap);

        $this->logger->messageDebug("Mapping Customer, email: %s -> Contact. Data:\n%s",
            $customer->getEmail(), $mapArray);

        foreach ($mapArray['customer'] as $sfAttr => $mAttr) {
            if (!isset($customerArray[$mAttr]) || $customerArray[$mAttr] == "" || $customerArray[$mAttr] == null) {
                continue;
            }
            $value = $customerArray[$mAttr];
            $transferObject->$sfAttr = $value;
        }

        // billing address
        if($billingAddress = $this->getDefaultBillingAddress($customer)){
            $this->addAddressDataToTransferObject($billingAddress,
                $mapArray['customer_address/billing'], $transferObject);
        }

        // shipping address
        if ($shippingAddress = $this->getDefaultShippingAddress($customer)) {
            $this->addAddressDataToTransferObject($shippingAddress,
                $mapArray['customer_address/shipping'], $transferObject);
        }

        if (isset($customerArray['sforce_id'])) {
            $transferObject->Id = $customerArray['sforce_id'];
        }

        $transferObject->{self::SFORCE_BASIC_PREFIX.self::SFORCE_MAGENTO_ID} = $customer->getId();

        $websiteRepository = $this->objectManager->create('Magento\Store\Api\WebsiteRepositoryInterface');
        $transferObject->{\TNW\Salesforce\Client\Website::SFORCE_WEBSITE_OBJECT} =
            $websiteRepository->getById($customer->getWebsiteId())->getSalesforceId();

        return $transferObject;
    }

    /**
     * Create Contact Transfer object from Customer and Customer Address
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return \stdClass
     */
    public function getLeadTransferObjectFromCustomer(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        $customerArray = $this->getCustomerDataAsArray($customer);

        $transferObject = new \stdClass();

        $mapArray = $this->getMapArray();
        //$mapArray['customer'] = array_merge($mapArray['customer'],$this->defaultMap);

        $this->logger->messageDebug("Mapping Customer, email: %s -> Lead. Data:\n%s",
            $customer->getEmail(), $mapArray);

        foreach ($mapArray['customer'] as $sfAttr => $mAttr) {
            if (!isset($customerArray[$mAttr]) || $customerArray[$mAttr] == "" || $customerArray[$mAttr] == null) {
                continue;
            }
            $value = $customerArray[$mAttr];
            $transferObject->$sfAttr = $value;
        }

        // billing address
        if($billingAddress = $this->getDefaultBillingAddress($customer)){
            $this->addAddressDataToTransferObject($billingAddress,
                $mapArray['customer_address/billing'], $transferObject);
        }

        // shipping address
        if ($shippingAddress = $this->getDefaultShippingAddress($customer)) {
            $this->addAddressDataToTransferObject($shippingAddress,
                $mapArray['customer_address/shipping'], $transferObject);
        }

        //Check if there is NO Billing Company Name
        if (!isset($transferObject->Company)) {
            $transferObject->Company = $customerArray['firstname'] . ' ' . $customerArray['lastname'];
        }
        if (isset($customerArray['sforce_lead_id'])) {
            $transferObject->Id = $customerArray['sforce_lead_id'];
        }

        $transferObject->{self::SFORCE_BASIC_PREFIX.self::SFORCE_MAGENTO_ID} = $customer->getId();

        $websiteRepository = $this->objectManager->create('Magento\Store\Api\WebsiteRepositoryInterface');
        $transferObject->{\TNW\Salesforce\Client\Website::SFORCE_WEBSITE_OBJECT} =
            $websiteRepository->getById($customer->getWebsiteId())->getSalesforceId();

        return $transferObject;
    }

    /**
     * Create Account Transfer Object from Customer
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return \stdClass
     */
    public function getAccountTransferObjectFromCustomer(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        $transferObject = new \stdClass();
        $customerArray = $this->getCustomerDataAsArray($customer);
        $mapArray = $this->getMapArray(\TNW\Salesforce\Model\Customer\Mapper::OBJECT_TYPE_ACCOUNT);

        $this->logger->messageDebug("Mapping Customer, email: %s -> Account. Data:\n%s",
            $customer->getEmail(), $mapArray);

        /** @var \TNW\Salesforce\Model\Customer\Mapper[] $items */
        $items = $this->repository->getResultCollection(\TNW\Salesforce\Model\Customer\Mapper::OBJECT_TYPE_ACCOUNT)->getItems();
        foreach ($items as $item) {
            switch ($item->getMagentoEntityType()){
                case 'customer':
                    if (!empty($customerArray[$item->getMagentoAttributeName()])) {
                        $transferObject->{$item->getSalesforceAttributeName()}
                            = $customerArray[$item->getMagentoAttributeName()];
                    }
                    break;

                case 'customer_address/billing':
                    if($billingAddress = $this->getDefaultBillingAddress($customer)){
                        $this->addAddressDataToTransferObject($billingAddress,
                            [$item->getSalesforceAttributeName() => $item->getMagentoAttributeName()], $transferObject);
                    }
                    break;

                case 'customer_address/shipping':
                    if ($shippingAddress = $this->getDefaultShippingAddress($customer)) {
                        $this->addAddressDataToTransferObject($shippingAddress,
                            [$item->getSalesforceAttributeName() => $item->getMagentoAttributeName()], $transferObject);
                    }
                    break;

                default:
                    $transferObject->{$item->getSalesforceAttributeName()}
                        = $item->getDefaultValue();
                    break;
            }
        }

        //Check if there is NO Billing Company Name
        if (!isset($transferObject->Name)) {
            $transferObject->Name = $customerArray['firstname'] . ' ' . $customerArray['lastname'];
        }

        if (isset($customerArray['sforce_account_id'])) {
            $transferObject->Id = $customerArray['sforce_account_id'];
        }

        return $transferObject;
    }


    /**
     * get Customer as Array
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return Array
     */
    protected function getCustomerDataAsArray(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        /** @var \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor */
        $dataObjectProcessor =  $this->objectManager->get('\Magento\Framework\Reflection\DataObjectProcessor');
        $customerArray =
            $dataObjectProcessor->buildOutputDataArray($customer, '\Magento\Customer\Api\Data\CustomerInterface');

        $customerArray = $this->mergeCustomerAttributes($customerArray);

        return $customerArray;
    }

    /**
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return Array
     */
    protected function getAddressDataAsArray(\Magento\Customer\Api\Data\AddressInterface $address)
    {
        $dataObjectProcessor =  $this->objectManager->get('\Magento\Framework\Reflection\DataObjectProcessor');
        $addressArray =
            $dataObjectProcessor->buildOutputDataArray($address, '\Magento\Customer\Api\Data\AddressInterface');

        $addressArray = $this->mergeCustomerAttributes($addressArray);

        return $addressArray;
    }


    /**
     * Merge Customer DataArray with custom attribute sub-array
     *
     * @param Array $customerArray
     * @return Array
     */
    private function mergeCustomerAttributes($customerArray)
    {
        // Make customerArray flat, by merging with custom_attribute subarray
        if (isset($customerArray['custom_attributes']) && is_array($customerArray['custom_attributes'])) {
            foreach ($customerArray['custom_attributes'] as $item) {
                $customerArray[$item['attribute_code']] = $item['value'];
            }
        }

        return $customerArray;
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     */
    public function getDefaultShippingAddress(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        $addresses = $customer->getAddresses();
        $shippingAddressId = $customer->getDefaultShipping();
        $shippingAddress = null;
        if ($shippingAddressId) {
            foreach ($addresses as $address) {
                if (($shippingAddressId == $address->getId()) || $address->isDefaultShipping()) {
                    $shippingAddress = $address;
                    break;
                }
            }
        }
        return $shippingAddress;

    }

    /**
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     */
    public function getDefaultBillingAddress(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        $addresses = $customer->getAddresses();
        $billingAddressId = $customer->getDefaultBilling();
        $billingAddress = null;
        if ($billingAddressId) {
            foreach ($addresses as $address) {
                if (($billingAddressId == $address->getId()) || $address->isDefaultBilling()) {
                    $billingAddress = $address;
                    break;
                }
            }
        }
        return $billingAddress;
    }

    /**
     * @param $address
     * @param array $mapArrayAddress
     * @param \stdClass $transferObject
     * @return array
     */
    protected function addAddressDataToTransferObject(
        $address,
        array $mapArrayAddress,
        \stdClass &$transferObject
    ) {
        $addressArray = $this->getAddressDataAsArray($address);
        foreach ($mapArrayAddress as $sfAttr => $mAttr) {
            if (!isset($addressArray[$mAttr])) {
                continue;
            }
            if (is_array($addressArray[$mAttr])) {
                $value = implode(',', $addressArray[$mAttr]);
            } else {
                $value = $addressArray[$mAttr];
            }
            $transferObject->$sfAttr = $value;
        }

    }
}

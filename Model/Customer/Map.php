<?php

namespace  TNW\Salesforce\Model\Customer;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use stdClass;
use TNW\Salesforce\Client\Website;
use TNW\Salesforce\Model\Logger;
use TNW\Salesforce\Model\ResourceModel\Customer\Mapper\Repository;
use TNW\Salesforce\Service\Map\AllowBlankValue;

/**
 * Class Map
 *
 * @deprecated
 * TODO: Remove
 */
class Map
{
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var array
     */
    protected $map;

    /** @var ObjectManagerInterface  */
    protected $objectManager;

    /** @var Logger */
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

    /** @var AllowBlankValue */
    private $allowBlankValue;

    /**
     * @param Repository
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Repository $repository,
        ObjectManagerInterface $objectManager,
        Logger $logger,
        AllowBlankValue $allowBlankValue
    ) {
        $this->objectManager = $objectManager;
        $this->repository = $repository;
        $this->logger = $logger;
        $this->defaultMap = ['AccountId' => 'sforce_account_id', 'Id' => 'sforce_id'];
        $this->allowBlankValue = $allowBlankValue;
    }

    /**
     * Get customer map as associate array [magento_attribute => salesforce_attribute]
     *
     * @param string $objectType
     * @return array
     */
    public function getMapArray($objectType = Mapper::OBJECT_TYPE_CONTACT)
    {
        if (isset($this->map[$objectType])) {
            return $this->map[$objectType];
        }

        /**
         * @var Mapper[] $items
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
     * @param CustomerInterface $customer
     * @return stdClass
     */
    public function getContactTransferObjectFromCustomer(CustomerInterface $customer)
    {

        $customerArray = $this->getCustomerDataAsArray($customer);

        $transferObject = new stdClass();

        $objectType = Mapper::OBJECT_TYPE_CONTACT;
        $mapArray = $this->getMapArray($objectType);
        $mapArray['customer'] = array_merge($mapArray['customer'],$this->defaultMap);

        $this->logger->messageDebug("Mapping Customer, email: %s -> Contact. Data:\n%s",
            $customer->getEmail(), $mapArray);

        foreach ($mapArray['customer'] ?? [] as $sfAttr => $mAttr) {
            if (!isset($customerArray[$mAttr]) || $customerArray[$mAttr] == "" || $customerArray[$mAttr] == null) {
                if (!$this->allowBlankValue->execute(
                    Mapper::OBJECT_TYPE_CONTACT,
                    $sfAttr
                )) {
                    continue;
                }
            }
            $value = $customerArray[$mAttr];
            $transferObject->$sfAttr = $value;
        }

        // billing address
        if ($billingAddress = $this->getDefaultBillingAddress($customer)) {
            $this->addAddressDataToTransferObject(
                $billingAddress,
                $mapArray['customer_address/billing'] ?? [],
                $transferObject,
                $objectType
            );
        }

        // shipping address
        if ($shippingAddress = $this->getDefaultShippingAddress($customer)) {
            $this->addAddressDataToTransferObject(
                $shippingAddress,
                $mapArray['customer_address/shipping'] ?? [],
                $transferObject,
                $objectType
            );
        }

        if (isset($customerArray['sforce_id'])) {
            $transferObject->Id = $customerArray['sforce_id'];
        }

        $transferObject->{self::SFORCE_BASIC_PREFIX.self::SFORCE_MAGENTO_ID} = $customer->getId();

        $websiteRepository = $this->objectManager->create('Magento\Store\Api\WebsiteRepositoryInterface');
        $transferObject->{Website::SFORCE_WEBSITE_OBJECT} =
            $websiteRepository->getById($customer->getWebsiteId())->getSalesforceId();

        return $transferObject;
    }

    /**
     * Create Contact Transfer object from Customer and Customer Address
     *
     * @param CustomerInterface $customer
     * @return stdClass
     */
    public function getLeadTransferObjectFromCustomer(CustomerInterface $customer)
    {
        $customerArray = $this->getCustomerDataAsArray($customer);

        $transferObject = new stdClass();

        $objectType = Mapper::OBJECT_TYPE_CONTACT;
        $mapArray = $this->getMapArray($objectType);
        //$mapArray['customer'] = array_merge($mapArray['customer'],$this->defaultMap);

        $this->logger->messageDebug("Mapping Customer, email: %s -> Lead. Data:\n%s",
            $customer->getEmail(), $mapArray);

        foreach ($mapArray['customer'] as $sfAttr => $mAttr) {
            if (!isset($customerArray[$mAttr]) || $customerArray[$mAttr] == "" || $customerArray[$mAttr] == null) {
                if (!$this->allowBlankValue->execute($objectType, $sfAttr)) {
                    continue;
                }
            }
            $value = $customerArray[$mAttr];
            $transferObject->$sfAttr = $value;
        }

        // billing address
        if($billingAddress = $this->getDefaultBillingAddress($customer)){
            $this->addAddressDataToTransferObject(
                $billingAddress,
                $mapArray['customer_address/billing'],
                $transferObject,
                $objectType
            );
        }

        // shipping address
        if ($shippingAddress = $this->getDefaultShippingAddress($customer)) {
            $this->addAddressDataToTransferObject(
                $shippingAddress,
                $mapArray['customer_address/shipping'],
                $transferObject,
                $objectType
            );
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
        $transferObject->{Website::SFORCE_WEBSITE_OBJECT} =
            $websiteRepository->getById($customer->getWebsiteId())->getSalesforceId();

        return $transferObject;
    }

    /**
     * Create Account Transfer Object from Customer
     *
     * @param CustomerInterface $customer
     * @return stdClass
     */
    public function getAccountTransferObjectFromCustomer(CustomerInterface $customer)
    {
        $transferObject = new stdClass();
        $customerArray = $this->getCustomerDataAsArray($customer);
        $objectType = Mapper::OBJECT_TYPE_ACCOUNT;
        $mapArray = $this->getMapArray($objectType);

        $this->logger->messageDebug("Mapping Customer, email: %s -> Account. Data:\n%s",
            $customer->getEmail(), $mapArray);

        /** @var Mapper[] $items */
        $items = $this->repository->getResultCollection($objectType)->getItems();
        foreach ($items as $item) {
            $salesforceAttributeName = $item->getSalesforceAttributeName();
            $magentoAttributeName = $item->getMagentoAttributeName();
            switch ($item->getMagentoEntityType()){
                case 'customer':
                    if (!empty($customerArray[$magentoAttributeName]) ||
                        $this->allowBlankValue->execute($objectType, $salesforceAttributeName)
                    ) {
                        $transferObject->{$salesforceAttributeName}
                            = $customerArray[$magentoAttributeName];
                    }
                    break;

                case 'customer_address/billing':
                    if($billingAddress = $this->getDefaultBillingAddress($customer)){
                        $this->addAddressDataToTransferObject(
                            $billingAddress,
                            [$salesforceAttributeName => $magentoAttributeName],
                            $transferObject,
                            $objectType
                        );
                    }
                    break;

                case 'customer_address/shipping':
                    if ($shippingAddress = $this->getDefaultShippingAddress($customer)) {
                        $this->addAddressDataToTransferObject(
                            $shippingAddress,
                            [$salesforceAttributeName => $magentoAttributeName],
                            $transferObject,
                            $objectType
                        );
                    }
                    break;

                default:
                    $transferObject->{$salesforceAttributeName}
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
     * @param CustomerInterface $customer
     * @return Array
     */
    protected function getCustomerDataAsArray(CustomerInterface $customer)
    {
        /** @var DataObjectProcessor $dataObjectProcessor */
        $dataObjectProcessor =  $this->objectManager->get('\Magento\Framework\Reflection\DataObjectProcessor');
        $customerArray =
            $dataObjectProcessor->buildOutputDataArray($customer, '\Magento\Customer\Api\Data\CustomerInterface');

        $customerArray = $this->mergeCustomerAttributes($customerArray);

        return $customerArray;
    }

    /**
     * @param AddressInterface $address
     * @return Array
     */
    protected function getAddressDataAsArray(AddressInterface $address)
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
     * @param CustomerInterface $customer
     * @return AddressInterface|null
     */
    public function getDefaultShippingAddress(CustomerInterface $customer)
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
     * @param CustomerInterface $customer
     * @return AddressInterface|null
     */
    public function getDefaultBillingAddress(CustomerInterface $customer)
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
     * @param          $address
     * @param array    $mapArrayAddress
     * @param stdClass $transferObject
     * @param string   $objectType
     *
     * @return array
     */
    protected function addAddressDataToTransferObject(
        $address,
        array $mapArrayAddress,
        stdClass $transferObject,
        string $objectType
    ) {
        $addressArray = $this->getAddressDataAsArray($address);
        foreach ($mapArrayAddress as $sfAttr => $mAttr) {
            $value = $addressArray[$mAttr] ?? null;
            if (!isset($value) && !$this->allowBlankValue->execute($objectType, $sfAttr)) {
                continue;
            }
            if (is_array($value)) {
                $value = implode(',', $value);
            }

            $transferObject->$sfAttr = $value;
        }

    }
}

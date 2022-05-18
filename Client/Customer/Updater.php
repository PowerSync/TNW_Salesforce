<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Client\Customer;

use TNW\Salesforce\Model\Config\Source\Customer\ContactAssignee;

class Updater
{
    /** @var \TNW\Salesforce\Model\Customer\Config */
    protected $config;
    /** @var \TNW\Salesforce\Client\Customer */
    protected $client;
    /** @var \Magento\Customer\Model\ResourceModel\CustomerRepository  */
    protected $customerRepository;
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;
    /** @var \TNW\Salesforce\Model\Customer\Map  */
    protected $map;
    /** @var \TNW\Salesforce\Model\Customer\CustomAttribute */
    protected $customAttribute;
    /** @var \TNW\Salesforce\Client\Customer\LookupInfo */
    protected $lookupInfo;
    /** @var \stdClass[] */
    protected $results;
    /** @var \TNW\Salesforce\Model\Logger */
    protected $logger;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    public function __construct(
        \TNW\Salesforce\Model\Customer\Config $config,
        \TNW\Salesforce\Client\Customer $client,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \TNW\Salesforce\Model\Customer\Map $map,
        \TNW\Salesforce\Model\Customer\CustomAttribute $customAttribute,
        \TNW\Salesforce\Client\Customer\LookupInfo $lookupInfo,
        \TNW\Salesforce\Model\Logger $logger,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->config = $config;
        $this->client = $client;
        $this->customerRepository = $customerRepository;
        $this->objectManager = $objectManager;
        $this->map = $map;
        $this->customAttribute = $customAttribute;
        $this->lookupInfo = $lookupInfo;
        $this->logger = $logger;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Do Magento Customer syncronization to Salesforce Contact and Account
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface[] $customers
     * @param bool $is_observer
     * @return bool
     */
    public function syncCustomers($customerIds, $is_observer = false)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $customerIds, 'in')
            ->create();

        $customers = $this->customerRepository->getList($searchCriteria)->getItems();

        $this->logger->messageDebug("Start customer synchronization, Data:\n%s", array_map(function ($customer) {
            return "{$customer->getId()}:{$customer->getEmail()}";
        }, $customers));

        // Init lookup info
        $this->lookupInfo->initLookups($customers);

        #region Prepare hashes and indexes
        $countFalse = 0;
        $transferAccountObjects = [];
        $transferContactObjects = [];
        // ObjectIds array used for storing response data between Account and Contact requests
        $transferObjectsIds = [];
        $transferObjectMap = []; // Mapping between $transferAccountObjects and $transferObjectsIds
        $i = 0; // index for $transferObjectsIds
        $j = 0; // index for $transferAccountObjects
        #endregion
        foreach ($customers as $customer) {
            #region Create object for store response data
            $stdObj = new \stdClass();
            $stdObj->id = $customer->getId();
            $transferObjectsIds[$i] = $stdObj;
            #endregion

            #region Get Default owner settings
            $defaultOwner = null;
            if ($this->config->defaultOwner($customer->getWebsiteId())) {
                $defaultOwner = $this->config->defaultOwner($customer->getWebsiteId());
            }
            #endregion

            #region Fill Contact transfer object
            $this->logger->messageDebug('Process. Mapping Contact');
            // This needs to check if we need to create Account for newly synced Contact or may use existing one
            $contactObject = $this->map->getContactTransferObjectFromCustomer($customer);
            // Check lookup results and update transfer object for Account
            $contactObject = $this->lookupInfo->findContactObjFromLookupResult($contactObject, $this->config->accountShareScope());
            if (!empty($contactObject->Id)) {
                $this->logger->messageDebug('Mapping Contact Find: %s', $contactObject->Id);
            }

            $transferContactObjects[$i] = $contactObject;
            #endregion

            #region Fill Account transfer object
            $this->logger->messageDebug('Process. Mapping Account');
            $transferAccountObject = $this->map->getAccountTransferObjectFromCustomer($customer);
            if ($this->checkProperty($contactObject, 'AccountId') &&
                (!isset($transferAccountObject->Id) ||
                    $transferAccountObject->Id != $contactObject->AccountId)
            ) {
                $transferAccountObject->Id = $contactObject->AccountId;
            }
            // Check lookup results and update transfer object for Account
            //$transferAccountObject = $this->findAccountObjFromLookupResult($lookupRecordsAccounts, $transferAccountObject);
            $transferAccountObject = $this->lookupInfo->lookupAccountObj($transferAccountObject);
            if (!empty($transferAccountObject->Id)) {
                $this->logger->messageDebug('Mapping Account Find: %s', $transferAccountObject->Id);
            }

            if (!$this->checkProperty($contactObject, 'Id')) {
                $addAccountToSync = $this->initNewContactAccount($contactObject, $transferAccountObject, $stdObj, $defaultOwner, $customer->getWebsiteId());
                if ($addAccountToSync) {
                    $transferAccountObjects[$j] = $transferAccountObject;
                    $transferObjectMap[$j] = $i;
                    $j++;
                }
                if ($this->config->contactAssignee($customer->getWebsiteId()) == ContactAssignee::DEFAULT_OWNER
                    && $defaultOwner
                ) {
                    $contactObject->OwnerId = $defaultOwner;
                }
            } else {
                if ($this->checkProperty($transferAccountObject, 'Id'))
                {
                    if (!$this->config->canRenameAccount($customer->getWebsiteId())
                    ) {
                        unset($transferAccountObject->Name);
                    }
                } else {
                    if ($defaultOwner) {
                        $transferAccountObject->OwnerId = $defaultOwner;
                    }
                }
                $transferAccountObjects[$j] = $transferAccountObject;
                $transferObjectMap[$j] = $i;
                $j++;
            }
            #endregion

            $i++;
        }

        #region Upsert call for Accounts
        $this->logger->messageDebug('Process. Upsert Accounts');
        /** @var \Tnw\SoapClient\Result\UpsertResult[] $resultsAccountUpsert */
        $resultsAccountUpsert = $this->client->upsertAccounts('Id', $transferAccountObjects);

        // Parse response from upsert call
        $i = 0;
        foreach ($resultsAccountUpsert as $result) {
            $sync_status = 0;
            if ($result->isSuccess()) {
                $sync_status = 1;
                $transferObjectsIds[$transferObjectMap[$i]]->sforce_account_id = $result->getId();
            } else {
                $countFalse++;
                $transferObjectsIds[$transferObjectMap[$i]]->errors = $result->getErrors();

                $this->logger->messageError("Upsert Customer (id: %s) -> Account. Message:\n%s",
                    $transferObjectsIds[$transferObjectMap[$i]]->id, $result->getErrors());
            }
            $transferObjectsIds[$transferObjectMap[$i]]->sync_status = $sync_status;
            $i++;
        }
        #endregion

        #region Prepare $transferContactObjects[] from Contacts
        $i = 0;
        $j = 0;
        $transferContactObjects2 = [];
        $mapToContactObjects2 = [];
        foreach ($customers as $customer) {
            $contactObject = $transferContactObjects[$i];
            // Transfer only objects that has successfully transferred accounts
            // If there is error in Account transferring for this customer - skip it
            if (!property_exists($transferObjectsIds[$i], 'sync_status')
                || $transferObjectsIds[$i]->sync_status == 1
            ) {
                if (!$this->checkProperty($contactObject, 'Id')) {
                    // Add AccountId for Contact transfer object from $transferObjectsIds
                    if ($this->checkProperty($transferObjectsIds[$i], 'sforce_account_id')) {
                        $contactObject->AccountId = $transferObjectsIds[$i]->sforce_account_id;
                    }
                }
                $transferContactObjects2[$j] = $contactObject;
                $mapToContactObjects2[$j] = $i;
                $j++;
            }
            $i++;
        }
        #endregion

        #region Upsert call for Contacts
        $this->logger->messageDebug('Process. Upsert Contacts');
        /** @var \Tnw\SoapClient\Result\UpsertResult[] $resultsContactUpsert */
        $resultsContactUpsert = $this->client->upsertContacts('Id', $transferContactObjects2);

        // Parse response from upsert call
        $i = 0;
        foreach ($resultsContactUpsert as $result) {
            $sync_status = 0;
            if ($result->isSuccess()) {
                $sync_status = 1;
                $transferObjectsIds[$mapToContactObjects2[$i]]->sforce_id = $result->getId();
            } else {
                $countFalse++;
                $transferObjectsIds[$mapToContactObjects2[$i]]->errors = $result->getErrors();

                $this->logger->messageError("Upsert Customer (id: %s) -> Contact. Message:\n%s",
                    $transferObjectsIds[$mapToContactObjects2[$i]]->id, $result->getErrors());
            }
            $transferObjectsIds[$i]->sync_status = $sync_status;
            $i++;
        }

        $syncResult = $countFalse == 0;
        #endregion

        #region Update products with sync_status, sforce_id ans sforce_account_id
        $i = 0;
        $this->logger->messageDebug('Process. Save Customers');
        foreach ($customers as $customer) {
            if (!$this->checkProperty($transferObjectsIds[$i], 'sync_status')) {
                continue;
            }
            $customer->setCustomAttribute('sforce_sync_status', $transferObjectsIds[$i]->sync_status);
            if ($this->checkProperty($transferObjectsIds[$i], 'sforce_id')) {
                $customer->setCustomAttribute('sforce_id', $transferObjectsIds[$i]->sforce_id);
            }
            if ($this->checkProperty($transferObjectsIds[$i], 'sforce_account_id')) {
                $customer->setCustomAttribute('sforce_account_id', $transferObjectsIds[$i]->sforce_account_id);
            }
            if (!$is_observer) {
                $this->customerRepository->save($customer);
            } else {
                $this->customAttribute->saveSalesforceAttribute($customer);
            }

            $this->logger->messageDebug("Save attribute. Customer, email: %s. Data:\n%s", $customer->getEmail(), [
                'sforce_sync_status' => $customer->getCustomAttribute('sforce_sync_status')->getValue(),
                'sforce_id' => $customer->getCustomAttribute('sforce_id')->getValue(),
                'sforce_account_id' => $customer->getCustomAttribute('sforce_account_id')->getValue(),
            ]);

            $i++;
        }
        #endregion

        $this->results = $transferObjectsIds;

        $this->logger->messageDebug('Stop customer synchronization');
        return $syncResult;
    }

    /**
     * Fill $transferAccountObject parameters
     *
     * @param $contactObject
     * @param $transferAccountObject
     * @param $stdObj
     * @param $defaultOwner
     * @param $websiteId
     * @return bool Add it or not to sync
     */
    protected function initNewContactAccount($contactObject, $transferAccountObject, $stdObj, $defaultOwner, $websiteId)
    {
        if ($this->checkProperty($transferAccountObject, 'Id'))
        {
            if (!$this->config->canRenameAccount($websiteId)
            ) {
                unset($transferAccountObject->Name);
            }
        } else {
            if ($defaultOwner) {
                $transferAccountObject->OwnerId = $defaultOwner;
            }
        }

        return true;
    }

    /**
     * Get last sync results
     *
     * @return \stdClass
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Get sync errors
     * @return array
     */
    public function getErrors()
    {
        $errors = [];
        if (is_array($this->results)) {
            foreach ($this->results as $i => $syncResult) {
                if (property_exists($syncResult, 'errors')) {
                    /** @var \Tnw\SoapClient\Result\Error $error */
                    foreach ($syncResult->errors as $error) {
                        if (is_string($error)) {
                            $errors[] = $error;
                        } else {
                            $errors[] = $error->getMessage();
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Get sync process warnings
     * @return array
     */
    public function getWarnings()
    {
        return [];
    }

    /**
     * Get total count of items were synchronized
     * @return int
     */
    public function getResultsTotalCount()
    {
        if (is_array($this->results)) {
            return count($this->results);
        }

        return 0;
    }

    /**
     * Calculate count of successfully synchronized items
     * @return int
     */
    public function getResultsSuccessCount()
    {
        $count = 0;
        if (is_array($this->results)) {
            foreach ($this->results as $i => $syncResult) {
                if (!property_exists($syncResult, 'errors')) {
                    $count++;
                }
            }
        }

        return $count;
    }

    #region Misc
    /**
     * Check if property exists and set
     *
     * @param $object
     * @param $property
     * @return bool
     */
    protected function checkProperty($object, $property)
    {
        return
            is_object($object)
            && property_exists($object, $property)
            && $object->$property
            ||
            is_array($object)
            && array_key_exists($property, $object)
            && $object[$property];
    }

    /**
     * Define property if it undefined.
     *
     * @param \stdClass $object
     * @param array $property
     *
     * @return \stdClass $object
     */
    public function defineProperty($object, $property)
    {
        foreach ($property as $_property) {
            if (!array_key_exists($_property, $object)) {
                $object->{$_property} = null;
            }
        }

        return $object;
    }
    #endregion Misc
}

<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Client\Customer;

class LookupInfo
{
    /** @var \TNW\Salesforce\Model\Customer\Config */
    protected $config;
    /** @var \TNW\Salesforce\Client\Customer */
    protected $client;
    /** @var \TNW\Salesforce\Client\Customer\Merge */
    protected $merge;

    protected $contactsLookupRecords;

    /**
     * @var \TNW\Salesforce\Model\Logger
     */
    protected $logger;

    public function __construct(
        \TNW\Salesforce\Model\Customer\Config $config,
        \TNW\Salesforce\Client\Customer $client,
        \TNW\Salesforce\Client\Customer\Merge $merge,
        \TNW\Salesforce\Model\Logger $logger
    ) {
        $this->config = $config;
        $this->client = $client;
        $this->merge = $merge;
        $this->logger = $logger;
    }

    public function initLookups($customers)
    {
        $this->contactsLookupRecords = $this->getContactsForLookup($customers);
    }

    public function getContactLookupRecords()
    {
        if (is_null($this->contactsLookupRecords)) {
            throw new \Exception('Need to init lookups first');
        }

        return $this->contactsLookupRecords;
    }

    #region Lookup Records from SF
    /**
     * Get Contacts for lookup
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface[] $customers
     * @return \Tnw\SoapClient\Result\SObject[]
     */
    protected function getContactsForLookup($customers)
    {
        $magentoIdField = $this->config->getMagentoIdField();
        $websiteIdField = $this->config->getWebsiteIdField();
        $lookupQuery = 'SELECT ID,FirstName, LastName, Email, AccountId, OwnerId, '
            . 'Account.OwnerId, Account.Name, '
            . $websiteIdField . ', ' . $magentoIdField
            . ' FROM Contact WHERE';
        $queryWhereArray = [];
        foreach ($customers as $customer) {
            $email = $customer->getEmail();
            $expression = " (Email = '{$email}' OR {$magentoIdField} = '{$customer->getId()}') ";
            $queryWhereArray[] = $expression;
        }
        if (count($queryWhereArray) > 1) {
            $lookupQuery .= implode(' OR ', $queryWhereArray);
        } else {
            $lookupQuery .= $queryWhereArray[0];
        }

        $this->logger->messageDebug("Lookup Contact:\n%s", $lookupQuery);
        $result = $this->client->getClient()->query($lookupQuery);
        $this->logger->messageDebug("Lookup Contact, Data:\n%s", $result);

        return $result->getQueryResult()->getRecords();
    }
    #endregion Lookup Records from SF

    #region Find object in Lookup Records
    /**
     * @param \stdClass $contactObj
     * @return \stdClass
     */
    public function findContactObjFromLookupResult($contactObj, $perWebsite = false)
    {
        $lookupResult = $this->getContactLookupRecords();
        $magentoIdBusiness = $this->config->getMagentoIdField();
        $websiteIdBusiness = $this->config->getWebsiteIdField();
        $found = false;
        // Lookup by MagentoID
        foreach ($lookupResult as $result) {
            $result = $this->defineProperty($result,
                array($magentoIdBusiness, $websiteIdBusiness, 'Email', 'AccountId', 'Id'));
            if (isset($contactObj->{$magentoIdBusiness}) && $result->{$magentoIdBusiness} == $contactObj->{$magentoIdBusiness}) {
                $contactObj->Id = $result->Id;
                $contactObj->AccountId = $result->AccountId;
                $found = true;
                break;
            }
        }
        // If not found by MagentoId try to lookup by email and website
        if (!$found) {
            foreach ($lookupResult as $result) {
                if (
                    (property_exists($contactObj, 'Email') && property_exists($result, 'Email'))
                    && strtolower((string)$result->Email) == strtolower((string)$contactObj->Email)
                    && (!$perWebsite || (!$result->{$websiteIdBusiness}
                            || $result->{$websiteIdBusiness} == $contactObj->{$websiteIdBusiness}))
                ) {
                    $contactObj->Id = $result->Id;
                    $contactObj->AccountId = $result->AccountId;
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            // If Contact was not found in SF we must to recreate new Contact
            unset($contactObj->Id);
        }

        return $contactObj;
    }

    /**
     * Lookup account object in Salesforce
     * First try to find by Id (if defined)
     * Second try to find by name
     * @param \stdClass $accountObj
     * @return \stdClass
     * @throws \Exception
     */
    public function lookupAccountObj($accountObj)
    {
        $lookupQuery = "SELECT Id, Name, OwnerId FROM Account WHERE ";
        $found = false;
        $query = '';
        if ($this->checkProperty($accountObj, 'Id')) {
            $id = $accountObj->Id;
            $query = $lookupQuery . " Id = '{$id}' OR ";
        }
        $name = $accountObj->Name;
        $query = empty($query) ?
            $lookupQuery . " Name = {$this->soqlQuote($name)}" :
            $query . " Name = {$this->soqlQuote($name)}";

        $this->logger->messageDebug("Lookup Account:\n%s", $query);
        $result = $this->client->getClient()->query($query);
        $this->logger->messageDebug("Lookup Account, Data:\n%s", $result);

        $resultRows = $result->getQueryResult()->getRecords();
        $resultRows = $this->merge->mergeDuplicateRecords($resultRows, $this->client->salesforceAccountObjectName());
        $resultRows = array_values($resultRows);

        if (count($resultRows) > 0) {
            $accountObj->Id = $resultRows[0]->Id;
            $found = true;
        }

        if (!$found) {
            unset($accountObj->Id);
        }

        return $accountObj;
    }

    /**
     * Lookup contact object in SF by Email or Magento ID or SalesForce Id
     * If more then one parameter set it will combine them by OR operator
     * @param string $email
     * @param string $magentoId
     * @param string $salesForceId
     * @param string $websiteSforceId
     * @return \Tnw\SoapClient\Result\SObject[]
     * @throws \Exception
     */
    public function lookupContactObj($email, $magentoId = null, $salesForceId = null, $websiteSforceId = null)
    {
        if (!$email && !$magentoId && !$salesForceId) {
            throw new \Exception("'email' or 'magentoId' or 'salesForceId' must be set");
        }
        $magentoIdField = $this->config->getMagentoIdField();
        $websiteIdField = $this->config->getWebsiteIdField();
        $where = [];
        if ($email) {
            $where[] = "Email = '{$email}'";
        }
        if ($magentoId) {
            $where[] = "{$magentoIdField} = '{$magentoId}'";
        }
        if ($salesForceId) {
            $where[] = "Id = '{$salesForceId}'";
        }

        $lookupQuery = sprintf('SELECT Id, FirstName, LastName, Email, AccountId, OwnerId, Account.OwnerId, Account.Name, %s, %s FROM %s WHERE %s',
            $websiteIdField,
            $magentoIdField,
            $this->client->salesforceContactObjectName(),
            implode(' OR ', $where)
        );

        $this->logger->messageDebug("Lookup Contact:\n%s", $lookupQuery);
        /** @var \Tnw\SoapClient\Result\RecordIterator $response */
        $response = $this->client->getClient()->query($lookupQuery);
        $this->logger->messageDebug("Lookup Contact, Data:\n%s", $response);

        $results = $this->lookupFindInResults(
            $response,
            'Email',
            $magentoIdField,
            $websiteIdField,
            $email,
            $magentoId,
            $salesForceId,
            $websiteSforceId
        );

        $results = $this->merge->mergeDuplicateRecords($results,
            $this->client->salesforceContactObjectName());

        return $results;
    }

    /**
     * Find exact record(s) in lookup results
     *
     * @param \Tnw\SoapClient\Result\RecordIterator $response
     * @param $emailField
     * @param $magentoIdField
     * @param $websiteIdField
     * @param $email
     * @param null $magentoId
     * @param null $salesForceId
     * @param null $websiteSforceId
     * @param null $source
     * @return array
     */
    protected function lookupFindInResults(\Tnw\SoapClient\Result\RecordIterator $response,
        $emailField, $magentoIdField, $websiteIdField,
        $email, $magentoId = null, $salesForceId = null, $websiteSforceId = null, $source = null
    )
    {
        $results = [];
        if ($response->count() > 1) {
            $fieldsToDefine = [$emailField];
            if ($magentoIdField) {
                $fieldsToDefine[] = $magentoIdField;
            }
            if ($websiteIdField) {
                $fieldsToDefine[] = $websiteIdField;
            }
            if ($source) {
                $fieldsToDefine[] = 'LeadSource';
            }

            $resultsEmail = [];
            $resultsId = [];
            $resultsMagentoId = [];

            // Search by email
            if ($email) {
                $email = strtolower((string)$email);
                // Search for exact website first
                if ($websiteSforceId) {
                    foreach ($response as $result) {
                        $this->defineProperty($result, $fieldsToDefine);
                        if (strtolower((string)$result->{$emailField}) == $email
                            && $result->{$websiteIdField} == $websiteSforceId
                            && (!$source || $result->LeadSource == $source)
                        ) {
                            $resultsEmail[$result->Id] = $result;
                        }
                    }
                }
                // If no results - search for empty website
                if (empty($resultsEmail)) {
                    foreach ($response as $result) {
                        $this->defineProperty($result, $fieldsToDefine);
                        if (strtolower((string)$result->{$emailField}) == $email
                            && (!$websiteSforceId || !$result->{$websiteIdField})
                            && (!$source || $result->LeadSource == $source)
                        ) {
                            $resultsEmail[$result->Id] = $result;
                        }
                    }
                }
            }

            // Search by Id
            if ($salesForceId) {
                foreach ($response as $result) {
                    if ($result->Id == $salesForceId) {
                        $resultsId[$result->Id] = $result;
                        break;
                    }
                }
            }

            // Search by MagentoId
            if ($magentoId) {
                foreach ($response as $result) {
                    $this->defineProperty($result, $fieldsToDefine);
                    if ($result->{$magentoIdField} == $magentoId) {
                        $resultsMagentoId[$result->Id] = $result;
                    }
                }
            }
            // Merge all results in one array
            // Found by Email - first
            // Found by Id - second
            // Found by MagentoId - third
            $results = array_merge($resultsEmail, $resultsId, $resultsMagentoId);
        } else {
            $results = $response->getQueryResult()->getRecords();
        }

        return $results;
    }

    #endregion Find object in Lookup Records

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

    /**
     * @param $value
     * @return string
     */
    public function soqlQuote($value)
    {
        $value = str_replace("'", "\'", $value);
        return "'$value'";
    }
    #endregion Misc
}

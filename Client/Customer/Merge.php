<?php
namespace TNW\Salesforce\Client\Customer;

class Merge
{
    /** @var \TNW\Salesforce\Model\Customer\Config */
    protected $config;
    /** @var \TNW\Salesforce\Client\Customer */
    protected $client;

    public function __construct(
        \TNW\Salesforce\Model\Customer\Config $config,
        \TNW\Salesforce\Client\Customer $client
    ) {
        $this->config = $config;
        $this->client = $client;
    }

    /**
     * Merge Duplicate Records
     * @param \Tnw\SoapClient\Result\SObject[]|null $lookupResult
     * @param string $type
     * @param bool $personAccount
     * @return \Tnw\SoapClient\Result\SObject[]|null
     */
    public function mergeDuplicateRecords($lookupResult, $type, $personAccount = false)
    {
        return $lookupResult;
    }
}

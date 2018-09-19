<?php

namespace TNW\Salesforce\Synchronize\Transport\Soap;

use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Lib\Tnw\SoapClient\ClientBuilder;

class ClientFactory
{
    /**
     * @var \TNW\Salesforce\Lib\Tnw\SoapClient\Client[]
     */
    private static $clients = [];

    /** @var Config  */
    protected $salesforceConfig;

    /**
     * @param Config $salesforceConfig
     */
    public function __construct(Config $salesforceConfig)
    {
        $this->salesforceConfig = $salesforceConfig;
    }

    /**
     * @param null $websiteId
     * @return \TNW\Salesforce\Lib\Tnw\SoapClient\Client
     */
    public function client($websiteId = null)
    {
        if (empty(self::$clients[$websiteId])) {
            self::$clients[$websiteId] = $this->create($websiteId);
        }

        return self::$clients[$websiteId];
    }

    /**
     * @param null $websiteId
     * @return \TNW\Salesforce\Lib\Tnw\SoapClient\Client
     */
    public function create($websiteId = null)
    {
        $builder = new ClientBuilder(
            $this->salesforceConfig->getSalesforceWsdl($websiteId),
            $this->salesforceConfig->getSalesforceUsername($websiteId),
            $this->salesforceConfig->getSalesforcePassword($websiteId),
            $this->salesforceConfig->getSalesforceToken($websiteId)
        );

        return $builder->build();
    }
}
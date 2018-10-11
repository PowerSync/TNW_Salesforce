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

    /** @var Config\WebsiteDetector  */
    protected $websiteDetector;

    /**
     * @param Config $salesforceConfig
     */
    public function __construct(
        Config $salesforceConfig,
        \TNW\Salesforce\Model\Config\WebsiteDetector $websiteDetector
    )
    {
        $this->salesforceConfig = $salesforceConfig;
        $this->websiteDetector = $websiteDetector;
    }

    /**
     * @param null $websiteId
     * @return \TNW\Salesforce\Lib\Tnw\SoapClient\Client
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function client($websiteId = null)
    {
        $websiteId = $this->websiteDetector->detectCurrentWebsite($websiteId);
        if (empty(self::$clients[$websiteId])) {
            self::$clients[$websiteId] = $this->create($websiteId);
        }

        return self::$clients[$websiteId];
    }

    /**
     * @param null $websiteId
     * @return \TNW\Salesforce\Lib\Tnw\SoapClient\Client
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create($websiteId = null)
    {
        $websiteId = $this->websiteDetector->detectCurrentWebsite($websiteId);

        $builder = new ClientBuilder(
            $this->salesforceConfig->getSalesforceWsdl($websiteId),
            $this->salesforceConfig->getSalesforceUsername($websiteId),
            $this->salesforceConfig->getSalesforcePassword($websiteId),
            $this->salesforceConfig->getSalesforceToken($websiteId)
        );

        return $builder->build();
    }
}
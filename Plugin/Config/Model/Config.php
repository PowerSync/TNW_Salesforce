<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Plugin\Config\Model;

use Magento\Config\Model\Config as Subject;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Api\MessageQueue\PublisherAdapter;
use TNW\Salesforce\Service\MessageQueue\RestartConsumers as RestartConsumersService;
use TNW\SForceEnterprise\Model\Prequeue\Process as PrequeueProcess;
use TNW\Salesforce\Api\Model\Synchronization\ConfigInterface as SalesforceConfig;

class Config
{
    /**
     * @var PublisherAdapter
     */
    private $publisher;

    /**
     * @var ConfigInterface
     */
    private $configResource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SalesforceConfig
     */
    private $config;

    /**
     * @var array
     */
    private $configScope;

    /** @var RestartConsumersService  */
    protected $restartConsumersService;

    /**
     * @param PublisherAdapter $publisher
     * @param ConfigInterface $configResource
     * @param StoreManagerInterface $storeManager
     * @param SalesforceConfig $config
     */
    public function __construct(
        PublisherAdapter $publisher,
        ConfigInterface $configResource,
        StoreManagerInterface $storeManager,
        SalesforceConfig $config,
        RestartConsumersService $restartConsumersService
    ) {
        $this->publisher = $publisher;
        $this->configResource = $configResource;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->restartConsumersService = $restartConsumersService;
    }

    /**
     * Retrieve configuration scope and save it into internal cache.
     * If the scope was already retrieved get it from the cache.
     *
     * @param \Magento\Config\Model\Config $config
     * @return array
     */
    private function retrieveConfigScope(\Magento\Config\Model\Config $config)
    {
        if (!isset($this->configScope)) {
            $this->configScope = [];
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeCode = null;
            $scopeId = 0;

            if ($config->getStore()) {
                $scope = 'stores';
                $store = $this->storeManager->getStore($config->getStore());
                $scopeCode = (string)$store->getCode();
                $scopeId = (int)$store->getId();
            } elseif ($config->getWebsite()) {
                $scope = 'websites';
                $website = $this->storeManager->getWebsite($config->getWebsite());
                $scopeCode = (string)$website->getCode();
                $scopeId = (int)$website->getId();
            }
            $this->configScope['scope'] = $scope;
            $this->configScope['scopeCode'] = $scopeCode;
            $this->configScope['scopeId'] = $scopeId;
        }

        return $this->configScope;
    }

    /**
     * Run prequeue proccess if Salesforce Module enabled.
     *
     * @param Subject $subject
     * @param \Closure $proceed
     *
     * @return \Magento\Config\Model\Config
     */
    public function aroundSave(Subject $subject, \Closure $proceed)
    {
        $configScope = $this->retrieveConfigScope($subject);
        $salesforceStatusBefore = $this->config->getSalesforceStatus();
        $result = $proceed();
        $salesforceStatusAfter = $this->config->getSalesforceStatus();
        $mqMode = $this->config->getMQMode();

        if ($salesforceStatusBefore === false && $salesforceStatusAfter === true && $mqMode == 'amqp') {
            $this->publisher->publish(PrequeueProcess::MQ_TOPIC_NAME, false);
        }
        $this->restartConsumersService->execute();

        return $result;
    }
}

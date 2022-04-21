<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Client;

use Magento\Framework\App\Cache\State;
use Magento\Framework\App\Cache\Type\Collection;
use Magento\Framework\FlagManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use TNW\Salesforce\Api\Client\ContextInterface;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\Config\WebsiteDetector;
use TNW\Salesforce\Model\Logger;
use TNW\Salesforce\Service\ObjectConvertor;

/**
 * Salesforce client context.
 */
class Context implements ContextInterface
{
    /** @var Config */
    private $salesForceConfig;

    /** @var Collection */
    private $cacheCollection;

    /** @var State */
    private $cacheState;

    /** @var Logger */
    private $logger;

    /** @var WebsiteDetector */
    private $websiteDetector;

    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var SerializerInterface */
    private $serializer;

    /** @var ObjectConvertor */
    private $objectConvertor;

    /** @var FlagManager */
    private $flagManager;

    /**
     * @param Config                 $salesForceConfig
     * @param Collection             $cacheCollection
     * @param State                  $cacheState
     * @param Logger                 $logger
     * @param WebsiteDetector        $websiteDetector
     * @param SerializerInterface    $serializer
     * @param ObjectConvertor        $objectConvertor
     * @param FlagManager            $flagManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Config $salesForceConfig,
        Collection $cacheCollection,
        State $cacheState,
        Logger $logger,
        WebsiteDetector $websiteDetector,
        SerializerInterface $serializer,
        ObjectConvertor $objectConvertor,
        FlagManager $flagManager,
        ObjectManagerInterface $objectManager
    ) {
        $this->salesForceConfig = $salesForceConfig;
        $this->cacheCollection = $cacheCollection;
        $this->cacheState = $cacheState;
        $this->logger = $logger;
        $this->websiteDetector = $websiteDetector;
        $this->objectManager = $objectManager;
        $this->serializer = $serializer;
        $this->objectConvertor = $objectConvertor;
        $this->flagManager = $flagManager;
    }

    public function getSalesforceConfig(): Config
    {
        return $this->salesForceConfig;
    }

    public function getCacheCollection(): Collection
    {
        return $this->cacheCollection;
    }

    public function getCacheState(): State
    {
        return $this->cacheState;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function getWebsiteDetector(): WebsiteDetector
    {
        return $this->websiteDetector;
    }

    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    public function getObjectConvertor(): ObjectConvertor
    {
        return $this->objectConvertor;
    }

    public function getFlagManager(): FlagManager
    {
        return $this->flagManager;
    }

    public function getObjectManager(): ObjectManagerInterface
    {
        return $this->objectManager;
    }
}

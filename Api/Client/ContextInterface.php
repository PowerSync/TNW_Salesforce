<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Api\Client;

use Magento\Framework\App\Cache\State;
use Magento\Framework\App\Cache\Type\Collection;
use Magento\Framework\FlagManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\Config\WebsiteDetector;
use TNW\Salesforce\Model\Logger;
use TNW\Salesforce\Service\ObjectConvertor;

/**
 * Salesforce client ContextInterface
 */
interface ContextInterface
{
    /**
     * @return Config
     */
    public function getSalesforceConfig(): Config;

    /**
     * @return Collection
     */
    public function getCacheCollection(): Collection;

    /**
     * @return State
     */
    public function getCacheState(): State;

    /**
     * @return Logger
     */
    public function getLogger(): Logger;

    /**
     * @return WebsiteDetector
     */
    public function getWebsiteDetector(): WebsiteDetector;

    /**
     * @return SerializerInterface
     */
    public function getSerializer(): SerializerInterface;

    /**
     * @return ObjectConvertor
     */
    public function getObjectConvertor(): ObjectConvertor;

    /**
     * @return FlagManager
     */
    public function getFlagManager(): FlagManager;

    /**
     * @return ObjectManagerInterface
     */
    public function getObjectManager(): ObjectManagerInterface;
}

<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\MessageQueue;

use Exception;
use Magento\Framework\Amqp\Config as AmqpConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\MessageQueue\Publisher\ConfigInterface as PublisherConfig;
use Magento\Framework\MessageQueue\PublisherInterface;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\Config\Source\MQ\Mode;

/**
 * Created for the Plugin only
 */
class PublisherAdapter implements \TNW\Salesforce\Api\MessageQueue\PublisherAdapter
{

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /** @var Config  */
    protected $config;

    /**
     * Publisher constructor.
     * @param PublisherInterface $publisher
     * @param Config $config
     */
    public function __construct(
        PublisherInterface $publisher,
        Config $config
    ) {
        $this->publisher = $publisher;
        $this->config = $config;
    }

    /**
     * @param $initTopicName
     * @return string
     */
    public function detectConnectionName($initTopicName)
    {
        if (!empty($this->config->getMQMode())) {
            $connectionName = $this->config->getMQMode();
        } else {
            $connectionName = 'db';
        }

        return $connectionName;
    }

    /**
     * @param $topicName
     * @return string
     */
    public function adaptTopic($topicName)
    {
        $connectionName = $this->detectConnectionName($topicName);
        $topicName .= '.' . $connectionName;
        return $topicName;
    }

    /**
     * @param $topicName
     * @param $data
     */
    public function publish($topicName, $data)
    {
        $topicName = $this->adaptTopic($topicName);

        if (!$this->config->getSalesforceStatus()) {
            return;
        }

        $this->publisher->publish($topicName, $data);
    }
}

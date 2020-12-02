<?php
namespace TNW\Salesforce\MessageQueue;

use Exception;
use Magento\Framework\Amqp\Config as AmqpConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\MessageQueue\Publisher\ConfigInterface as PublisherConfig;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Created for the Plugin only
 */
class PublisherAdapter
{

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * @var PublisherConfig
     */
    protected $publisherConfig;

    /**
     * Help check whether Amqp is configured.
     *
     * @var AmqpConfig
     */
    protected $amqpConfig;

    /**
     * Publisher constructor.
     * @param PublisherInterface $publisher
     */
    public function __construct(
        PublisherInterface $publisher
    ) {
        $this->publisher = $publisher;
    }

    /**
     * Get publisher config.
     *
     * @return PublisherConfig
     *
     * @deprecated 102.0.5
     */
    protected function getPublisherConfig()
    {
        if ($this->publisherConfig === null) {
            $this->publisherConfig = ObjectManager::getInstance()->get(PublisherConfig::class);
        }
        return $this->publisherConfig;
    }

    /**
     * Get Amqp config instance.
     *
     * @return AmqpConfig
     *
     * @deprecated 102.0.5
     */
    protected function getAmqpConfig()
    {
        if ($this->amqpConfig === null) {
            $this->amqpConfig = ObjectManager::getInstance()->get(AmqpConfig::class);
        }

        return $this->amqpConfig;
    }

    /**
     * Check Amqp is configured.
     *
     * @return bool
     */
    protected function isAmqpConfigured()
    {
        return $this->getAmqpConfig()->getValue(AmqpConfig::HOST) ? true : false;
    }

    /**
     * @param $topicName
     * @return string
     */
    public function detectConnectionName($topicName)
    {
        try {
            $connectionName = $this->getPublisherConfig()->getPublisher($topicName)->getConnection()->getName();
            $connectionName = ($connectionName === 'amqp' && !$this->isAmqpConfigured()) ? 'db' : $connectionName;
        } catch (Exception $e) {
            $connectionName = 'db';
        }
        return $connectionName;
    }

    /**
     * @param $topicName
     * @return mixed
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

        $this->publisher->publish($topicName, $data);
    }
}

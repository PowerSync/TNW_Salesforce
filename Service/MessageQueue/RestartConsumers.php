<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\MessageQueue;

use Magento\Framework\MessageQueue\PoisonPill\PoisonPillPutInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use TNW\Salesforce\Model\Config\Source\MQ\Mode;

/**
 *  Put poison pill and restart consumers
 */
class RestartConsumers
{
    /** @var PoisonPillPutInterface */
    private $pillPut;

    /** @var LoggerInterface */
    private $logger;

    /** @var array  */
    private $topics = [];

    private $mqModeModel;

    /** @var PublisherInterface  */
    private $publisher;


    public function __construct(
        PoisonPillPutInterface $pillPut,
        PublisherInterface $publisher,
        $topics,
        Mode $mqModeModel,
        LoggerInterface        $logger
    ) {
        $this->pillPut = $pillPut;
        $this->publisher = $publisher;
        $this->topics = $topics;
        $this->mqModeModel = $mqModeModel;
        $this->logger = $logger;
    }

    /**
     * publish messages to force consumers  restart
     * @return void
     */
    public function publishMessages()
    {
        foreach ($this->topics as $topic) {
            foreach ($this->mqModeModel->toOptionArray() as $item) {
                if (in_array($topic, ['tnw_salesforce.prequeue.process','tnw_salesforce.sync.quote'])) {

                    $data = false;
                } else {
                    $data = '';
                }
                $topicName = $topic .'.'. $item['value'];

                try {
                    $this->publisher->publish($topicName, $data);
                } catch (\LogicException $e) {
                    // avoid error messages if an amqp is not installed:
                    // we should be able to publish message to stop/restart consumer
                }

            }
        }
    }

    /**
     * Put poison pill
     *
     * @return void
     * @throws Throwable
     */
    public function execute(): void
    {
        try {
            $this->pillPut->put();
            $this->logger->info('Restart consumers!');
            $this->publishMessages();

        } catch (Throwable $e) {
            $this->logger->critical($e->getMessage());
            throw $e;
        }
    }
}

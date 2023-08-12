<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Queue;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Data\Collection;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Psr\Log\LoggerInterface;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\Config\WebsiteEmulator;
use TNW\Salesforce\Model\ResourceModel\Queue\CollectionFactory;
use TNW\Salesforce\Synchronize\Queue;

/**
 * Entity Synchronize
 */
class Synchronize
{
    /**
     * @var int
     */
    private $type;

    /**
     * @var boolean
     */
    private $isCheck = false;

    /**
     * @var Queue
     */
    private $synchronizeQueue;

    /**
     * @var CollectionFactory
     */
    private $collectionQueueFactory;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var WebsiteEmulator
     */
    private $websiteEmulator;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var Config
     */
    private $salesforceConfig;

    /** @var State */
    private $state;

    /** @var LoggerInterface */
    private $logger;

    /**
     * Queue constructor.
     *
     * @param int                        $type
     * @param Queue                      $synchronizeQueue
     * @param CollectionFactory          $collectionQueueFactory
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param WebsiteEmulator            $websiteEmulator
     * @param ManagerInterface           $messageManager
     * @param Config                     $salesforceConfig
     * @param State                      $state
     * @param LoggerInterface            $logger
     * @param bool                       $isCheck
     */
    public function __construct(
        $type,
        Queue $synchronizeQueue,
        CollectionFactory $collectionQueueFactory,
        WebsiteRepositoryInterface $websiteRepository,
        WebsiteEmulator $websiteEmulator,
        ManagerInterface $messageManager,
        Config $salesforceConfig,
        State $state,
        LoggerInterface $logger,
        bool $isCheck = false
    ) {
        $this->type = $type;
        $this->synchronizeQueue = $synchronizeQueue;
        $this->collectionQueueFactory = $collectionQueueFactory;
        $this->websiteRepository = $websiteRepository;
        $this->websiteEmulator = $websiteEmulator;
        $this->messageManager = $messageManager;
        $this->salesforceConfig = $salesforceConfig;
        $this->state = $state;
        $this->setIsCheck($isCheck);
        $this->logger = $logger;
    }

    /**
     * @return Queue
     */
    public function getSynchronizeQueue()
    {
        return $this->synchronizeQueue;
    }

    /**
     * @return bool
     */
    public function isCheck()
    {
        return $this->isCheck;
    }

    /**
     * @param bool $isCheck
     */
    public function setIsCheck(bool $isCheck)
    {
        $this->isCheck = $isCheck;
    }

    /**
     * Sync Type
     *
     * @return int
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Synchronize
     *
     * @throws Exception
     */
    public function synchronize($syncJobs = [])
    {
        foreach ($this->websiteRepository->getList() as $website) {
            $this->websiteEmulator->wrapEmulationWebsite([$this, 'synchronizeToWebsite'], $website->getId(), ['syncJobs' => $syncJobs]);
        }
    }

    /**
     * Synchronize To Website
     *
     * @param int $websiteId
     *
     * @throws Exception
     */
    public function synchronizeToWebsite($websiteId, $syncJobs = [])
    {
        $collection = $this->collectionQueueFactory->create()
            ->addFilterToSyncType($this->type);

        $collection->addFieldToFilter(
            'main_table.sync_attempt',
            ['lt' => $this->salesforceConfig->getMaxAdditionalAttemptsCount($this->getSynchronizeQueue()->isCheck())]
        );

        $collection->addOrder('priority');
        $collection->addOrder('sync_at', Collection::SORT_ORDER_ASC);
        $collection->addOrder('sync_attempt', Collection::SORT_ORDER_ASC);
        $collection->addOrder($collection->getIdFieldName(), Collection::SORT_ORDER_ASC);

        try {
            $this->synchronizeQueue->synchronize($collection, $websiteId, $syncJobs);
        } catch (\Throwable $e) {
            $message = implode(PHP_EOL, [$e->getMessage(), $e->getTraceAsString()]);
            $this->logger->critical($message);
            if ($this->state->getAreaCode() == Area::AREA_ADMINHTML) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
    }
}

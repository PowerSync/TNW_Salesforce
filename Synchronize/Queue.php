<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Throwable;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Model;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\ResourceModel\Queue\Collection;
use TNW\Salesforce\Service\CleanLocalCacheForInstances;
use TNW\Salesforce\Service\Synchronize\Queue\Collection\UpdateLock;
use TNW\Salesforce\Synchronize\Exception as SalesforceException;
use TNW\Salesforce\Synchronize\Queue\PushMqMessage;

/**
 * Queue
 */
class Queue
{
    const MAX_SYNC_ITERATIONS = 1000;
    const MAX_SYNC_ENTITIES_PER_RUN = 1000;
    /**
     * @var Group[]
     */
    protected $groups;

    /**
     * @var string[]
     */
    protected $phases;

    /**
     * @var Model\Config
     */
    protected $salesforceConfig;

    /**
     * @var Model\ResourceModel\Queue
     */
    protected $resourceQueue;

    /** @var [] */
    protected $sortGroups;

    /** @var Queue\PushMqMessage */
    protected $pushMqMessage;

    /**
     * @var boolean
     */
    protected $isCheck = false;

    /** @var CleanLocalCacheForInstances */
    protected $cleanLocalCacheForInstances;

    /** @var LoggerInterface */
    protected $logger;

    protected $updateLock;

    /**
     * @param array $groups
     * @param array $phases
     * @param Config $salesforceConfig
     * @param Model\ResourceModel\Queue $resourceQueue
     * @param PushMqMessage $pushMqMessage
     * @param CleanLocalCacheForInstances $cleanLocalCacheForInstances
     * @param LoggerInterface $logger
     * @param UpdateLock $updateLock
     * @param $isCheck
     */
    public function __construct(
        array                               $groups,
        array                               $phases,
        Model\Config                        $salesforceConfig,
        Model\ResourceModel\Queue           $resourceQueue,
        PushMqMessage                       $pushMqMessage,
        CleanLocalCacheForInstances         $cleanLocalCacheForInstances,
        LoggerInterface                     $logger,
        UpdateLock                          $updateLock,
        $isCheck = false
    ) {
        $this->groups = $groups;
        $this->phases = array_filter($phases);
        $this->salesforceConfig = $salesforceConfig;
        $this->resourceQueue = $resourceQueue;
        $this->pushMqMessage = $pushMqMessage;
        $this->setIsCheck($isCheck);
        $this->cleanLocalCacheForInstances = $cleanLocalCacheForInstances;
        $this->logger = $logger;
        $this->updateLock = $updateLock;
    }

    /**
     * @param $groupCollection Collection
     *
     * @return mixed
     */
    public function getSyncType($groupCollection)
    {
        if ($groupCollection->isLoaded()) {
            $tmpCollection = $groupCollection;
        } else {
            $tmpCollection = clone $groupCollection;
            $tmpCollection->setPageSize(1);
            $tmpCollection->setCurPage(1);
        }

        return $tmpCollection->getFirstItem()->getSyncType();
    }

    /**
     * Synchronize
     *
     * @param Collection $collection
     * @param            $websiteId
     * @param array $syncJobs
     *
     * @throws LocalizedException
     * @throws Throwable
     */
    public function synchronize($collection, $websiteId, $syncJobs = [])
    {
        if (!$this->salesforceConfig->getSalesforceStatus()) {
            return;
        }

        // Collection Clear
        $collection->clear();

        // Filter To Website
        $collection->addFilterToWebsiteId($websiteId);

        // Check not empty
        if ($collection->getSize() === 0) {
            return;
        }
        // Collection Clear to reset getSize() update
        $collection->clear();

        ksort($this->phases);

        $codesAndStatuses = $this->fillCodesAndStatuses($collection);

        foreach ($this->sortGroup($syncJobs) as $groupKey => $group) {
            $groupCode = $group->code();
            $allowedStatuses = $codesAndStatuses[$groupCode] ?? [];
            if (!$allowedStatuses) {
                continue;
            }

            foreach ($this->phases as $phase) {
                $startStatus = $phase['startStatus'] ?? '';
                if (!in_array($startStatus, $allowedStatuses, true)) {
                    continue;
                }

                $lockCollection = clone $collection;
                $lockCollection->addFilterToCode($groupCode);
                $lockCollection->addFilterToStatus($startStatus);

                $syncType = $this->getSyncType($lockCollection);

                $iterations = 0;
                $pageSize = $this->salesforceConfig->getPageSizeFromMagento(null, $syncType);

                $queueIds = $this->getQueueIds($lockCollection);

                if (count($queueIds) == 0) {
                    continue;
                }

                foreach (array_chunk($queueIds, $pageSize) as $chunkedQueueIds) {
                    $this->updateLock->updateLock($chunkedQueueIds, $phase, $lockCollection->getResource());

                    $iterations++;
                    $groupCollection = clone $collection;
                    $groupCollection->addFilterToIds($chunkedQueueIds);

                    $group->messageDebug(
                        'Start job "%s", phase "%s" for website %s',
                        $groupCode,
                        $phase['phaseName'],
                        $websiteId
                    );

                    try {
                        $this->syncBatch($group, $groupCollection);
                    } catch (Throwable $e) {
                        $this->processError($e, $groupCollection, $group, $phase);
                    }

                    $group->messageDebug(
                        'Stop job "%s", phase "%s" for website %s',
                        $groupCode,
                        $phase['phaseName'],
                        $websiteId
                    );

                    // Save change status
                    $groupCollection->save();
                    $this->cleanLocalCacheForInstances->execute();

                    $this->pushMqMessage->sendMessage($syncType);
                    if ($iterations > self::MAX_SYNC_ITERATIONS) {
                        // to avoid infinity loop
                        break;
                    }
                }

                $this->checkConsumerMemoryUsage();
            }
        }
    }

    /**
     * @return void
     */
    public function checkConsumerMemoryUsage()
    {
        $memory = memory_get_usage(true);
        if ($memory > $this->salesforceConfig->getMemoryLimitByte()) {
            exit(0);
        }
    }

    /**
     * @param $lockCollection
     * @return array
     * @throws LocalizedException
     */
    public function getQueueIds($lockCollection)
    {
        $queueIds = [];
        $lockCollection->setPageSize(ChunkSizeInterface::CHUNK_SIZE_200);
        $lastPageNumber = (int)$lockCollection->getLastPageNumber();

        for ($i = 1; $i <= $lastPageNumber; $i++) {
            $lockCollection->clear();
            $ids = $this->updateLock->getIdsBatch($lockCollection, $i);
            $queueIds = array_merge($queueIds, $ids);
        }

        return $queueIds;
    }

    /**
     * @param $group
     * @param $groupCollection
     * @return false|void
     */
    public function syncBatch($group, $groupCollection)
    {
        if ($groupCollection->getSize() == 0) {
            return false;
        }

        $groupCollection->clear();

        $groupCollection->each('incSyncAttempt');
        $groupCollection->each('setData', ['_is_last_page', true]);
        $queues = $groupCollection->getItems();
        if (!$queues) {
            return;
        }

        $group->synchronize($queues);
    }

    /**
     * @param $e
     * @param $groupCollection
     * @param $group
     * @param $phase
     * @return void
     */
    public function processError($e, $groupCollection, $group, $phase)
    {
        if ($e instanceof SalesforceException) {
            $status = $e->getQueueStatus();
        } else {
            $status = $phase['errorStatus'];
        }

        $groupCollection->each('addData', [[
            'status' => $status,
            'message' => $e->getMessage()
        ]]);

        $group->messageError($e);

        $message = implode(PHP_EOL, [$e->getMessage(), $e->getTraceAsString()]);
        $this->logger->critical($message);
    }

    /**
     * Sort Group
     *
     * @param null $syncJobs
     *
     * @return Group[]
     * @throws LocalizedException
     */
    public function sortGroup($syncJobs = null)
    {
        $addGroup = function (array &$sortGroups, Group $group) use (&$addGroup, &$description) {
            $description[] = sprintf('%s;', $group->code());
            foreach ($this->resourceQueue->getDependenceByCode($group->code()) as $type => $dependent) {
                if (empty($this->groups[$dependent])) {
                    continue;
                }

                $description[] = sprintf('%s <- %s;', $group->code(), $dependent);

                if ($group->code() == $dependent) {
                    continue;
                }
                if (isset($sortGroups[$dependent])) {
                    continue;
                }

                $addGroup($sortGroups, $this->groups[$dependent]);
            }

            $sortGroups[$group->code()] = $group;
        };

        if (empty($this->sortGroups)) {
            $sortGroups = [];
//        $i=0;

            foreach ($this->groups as $unit) {
                $description = [
                    sprintf('digraph %s {', $unit->code())
                ];

                $description[] = sprintf('label = "process %s";', $unit->code());

                $addGroup($sortGroups, $unit);

                $description[] = '}';
//            file_put_contents( 'dot/' . $unit->code() . '.dot', implode("\n", $description));
            }
            $this->sortGroups = $sortGroups;
        } else {
            $sortGroups = $this->sortGroups;
        }

        if (!empty($syncJobs)) {
            foreach ($sortGroups as $key => $group) {
                if (!in_array($key, $syncJobs)) {
                    unset($sortGroups[$key]);
                }
            }
        }

        return $sortGroups;
    }

    /**
     * @param bool $isCheck
     */
    public function setIsCheck(bool $isCheck)
    {
        $this->isCheck = $isCheck;
    }

    /**
     * @return bool
     */
    public function isCheck()
    {
        return $this->isCheck;
    }

    /**
     * @param Collection $collection
     *
     * @return array
     */
    public function fillCodesAndStatuses(Collection $collection): array
    {
        $statusesCollection = clone $collection;

        $select = $statusesCollection->getSelect();
        $select->reset(Select::COLUMNS);
        $select->group('main_table.code');
        $select->group('main_table.status');
        $select->columns(
            [
                'code' => 'main_table.code',
                'status' => 'main_table.status',
            ]
        );
        $allowedStatuses = [];
        foreach ($this->phases as $phase) {
            $startStatus = $phase['startStatus'] ?? '';
            if ($startStatus) {
                $allowedStatuses[] = $startStatus;
            }
        }
        $select->where('main_table.status IN (?)', $allowedStatuses);

        $connection = $collection->getResource()->getConnection();

        $items = $connection->fetchAll($select);
        $result = [];
        foreach ($items as $item) {
            $code = $item['code'] ?? '';
            $status = (string)($item['status'] ?? '');
            $result[$code][] = $status;
        }

        return $result;
    }
}

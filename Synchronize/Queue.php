<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use TNW\Salesforce\Model;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\Logger\Processor\UidProcessor;
use TNW\Salesforce\Model\ResourceModel\Queue\Collection;
use TNW\Salesforce\Service\CleanLocalCacheForInstances;
use TNW\Salesforce\Synchronize\Exception as SalesforceException;
use TNW\Salesforce\Synchronize\Queue\PushMqMessage;
use Zend_Db_Expr;
use \TNW\Salesforce\Service\Synchronize\Queue\Collection\UpdateLock;

/**
 * Queue
 */
class Queue
{
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

    /**
     * @var Model\Logger\Processor\UidProcessor
     */
    protected $uidProcessor;

    /** @var [] */
    protected $sortGroups;

    /** @var Queue\PushMqMessage */
    protected $pushMqMessage;

    /**
     * @var boolean
     */
    protected $isCheck = false;

    /** @var DateTime */
    protected $dateTime;

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
     * @param UidProcessor $uidProcessor
     * @param PushMqMessage $pushMqMessage
     * @param DateTime $dateTime
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
        Model\Logger\Processor\UidProcessor $uidProcessor,
        PushMqMessage                       $pushMqMessage,
        DateTime                            $dateTime,
        CleanLocalCacheForInstances         $cleanLocalCacheForInstances,
        LoggerInterface                     $logger,
        UpdateLock                          $updateLock,
                                            $isCheck = false
    ) {
        $this->groups = $groups;
        $this->phases = array_filter($phases);
        $this->salesforceConfig = $salesforceConfig;
        $this->resourceQueue = $resourceQueue;
        $this->uidProcessor = $uidProcessor;
        $this->pushMqMessage = $pushMqMessage;
        $this->setIsCheck($isCheck);
        $this->dateTime = $dateTime;
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
     * @param array      $syncJobs
     *
     * @throws LocalizedException
     * @throws \Throwable
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
            // refresh uid
            $this->uidProcessor->refresh();

            foreach ($this->phases as $phase) {
                $startStatus = $phase['startStatus'] ?? '';
                if (!in_array($startStatus, $allowedStatuses, true)) {
                    continue;
                }

                $lockCollection = clone $collection;
                $lockCollection->addFilterToCode($groupCode);
                $lockCollection->addFilterToStatus($startStatus);

                $lockData = [
                    'status' => $phase['processStatus'],
                    'transaction_uid' => $this->uidProcessor->uid(),
                    'identify' => new Zend_Db_Expr('queue_id')
                ];

                if ($startStatus === Model\Queue::STATUS_NEW) {
                    $lockData['sync_at'] = $this->dateTime->gmtDate('c');
                }
                $syncType = $this->getSyncType($lockCollection);

                // Mark work
                $countUpdate = $this->updateLock->execute($lockCollection, $lockData, $this->salesforceConfig->getPageSizeFromMagento(null, $syncType));

                if (0 === $countUpdate) {
                    continue;
                }

                $groupCollection = clone $collection;
                $groupCollection->addFilterToStatus($phase['processStatus']);
                $groupCollection->addFilterToCode($groupCode);

                $groupCollection->setPageSize($this->salesforceConfig->getPageSizeFromMagento(null, $syncType));

                $group->messageDebug(
                    'Start job "%s", phase "%s" for website %s',
                    $groupCode,
                    $phase['phaseName'],
                    $websiteId
                );

                try {
                    if ($groupCollection->getSize() == 0) {
                        continue;
                    }

                    $groupCollection->clear();

                    $groupCollection->each('incSyncAttempt');
                    $groupCollection->each('setData', ['_is_last_page', true]);
                    $queues = $groupCollection->getItems();
                    if (!$queues) {
                        continue;
                    }
                    $groupInstance = clone $group;
                    $groupInstance->synchronize($queues);
                    unset($groupInstance);
                } catch (\Throwable $e) {
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
            }
        }
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

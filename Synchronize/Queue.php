<?php
namespace TNW\Salesforce\Synchronize;

use TNW\Salesforce\Model;

/**
 * Queue
 */
class Queue
{
    /**
     * Page Size
     */
    const PAGE_SIZE = 200;

    /**
     * @var Group[]
     */
    private $groups;

    /**
     * @var string[]
     */
    private $phases;

    /**
     * @var Model\ResourceModel\Queue
     */
    private $resourceQueue;

    /**
     * @var Model\Logger\Processor\UidProcessor
     */
    private $uidProcessor;

    /**
     * Queue constructor.
     * @param Group[] $groups
     * @param array $phases
     * @param Model\ResourceModel\Queue $resourceQueue
     * @param Model\Logger\Processor\UidProcessor $uidProcessor
     */
    public function __construct(
        array $groups,
        array $phases,
        Model\ResourceModel\Queue $resourceQueue,
        Model\Logger\Processor\UidProcessor $uidProcessor
    ) {
        $this->groups = $groups;
        $this->phases = $phases;
        $this->resourceQueue = $resourceQueue;
        $this->uidProcessor = $uidProcessor;
    }

    /**
     * Synchronize
     *
     * @param \TNW\Salesforce\Model\ResourceModel\Queue\Collection $collection
     * @param int $websiteId
     * @throws \Exception
     */
    public function synchronize($collection, $websiteId)
    {
        // Collection Clear
        $collection->clear();

        // Filter To Website
        $collection->addFilterToWebsiteId($websiteId);

        // Check not empty
        if ($collection->getSize() === 0) {
            return;
        }

        ksort($this->phases);

        foreach ($this->sortGroup() as $group) {
            // refresh uid
            $this->uidProcessor->refresh();

            foreach ($this->phases as $phase) {
                $lockCollection = clone $collection;
                $lockCollection->addFilterToCode($group->code());
                $lockCollection->addFilterToStatus($phase['startStatus']);
                $lockCollection->addFilterToNotTransactionUid($this->uidProcessor->uid());
                $lockCollection->addFilterDependent();

                // Mark work
                $countUpdate = $lockCollection->updateLock([
                    'status' => $phase['processStatus'],
                    'transaction_uid' => $this->uidProcessor->uid()
                ]);

                if (0 === $countUpdate) {
                    continue;
                }

                $groupCollection = clone $collection;
                $groupCollection->addFilterToStatus($phase['processStatus']);
                $groupCollection->addFilterToTransactionUid($this->uidProcessor->uid());

                $groupCollection->setPageSize(self::PAGE_SIZE);

                $lastPageNumber = $groupCollection->getLastPageNumber();
                for ($i = 1; $i <= $lastPageNumber; $i++) {
                    $groupCollection->clear();
                    $groupCollection->setCurPage($i);

                    $group->messageDebug(
                        'Start job "%s", phase "%s" for website %s',
                        $group->code(),
                        $phase['phaseName'],
                        $websiteId
                    );

                    try {
                        $groupCollection->each('incSyncAttempt');
                        $group->synchronize($groupCollection->getItems());
                    } catch (\Exception $e) {
                        $groupCollection->each('addData', [[
                            'status' => $phase['errorStatus'],
                            'message' => $e->getMessage()
                        ]]);

                        $group->messageError($e);
                    }

                    $group->messageDebug(
                        'Stop job "%s", phase "%s" for website %s',
                        $group->code(),
                        $phase['phaseName'],
                        $websiteId
                    );

                    // Save change status
                    $groupCollection->each([$groupCollection->getResource(), 'save']);

                    gc_collect_cycles();
                }
            }
        }
    }

    /**
     * Sort Group
     *
     * @return Group[]
     */
    public function sortGroup()
    {
        $addGroup = function (array &$sortGroups, Group $group) use (&$addGroup) {
            foreach ($this->resourceQueue->getDependenceByCode($group->code()) as $dependent) {
                if (empty($this->groups[$dependent])) {
                    continue;
                }

                if (isset($sortGroups[$dependent])) {
                    continue;
                }

                $addGroup($sortGroups, $this->groups[$dependent]);
            }

            $sortGroups[$group->code()] = $group;
        };

        $sortGroups = [];
        foreach ($this->groups as $unit) {
            $addGroup($sortGroups, $unit);
        }

        return $sortGroups;
    }
}

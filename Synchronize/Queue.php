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
     * Queue constructor.
     * @param Group[] $groups
     * @param array $phases
     * @param Model\ResourceModel\Queue $resourceQueue
     */
    public function __construct(
        array $groups,
        array $phases,
        Model\ResourceModel\Queue $resourceQueue
    ) {
        $this->groups = $groups;
        $this->phases = $phases;
        $this->resourceQueue = $resourceQueue;
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

        ksort($this->phases);

        foreach ($this->sortGroup() as $group) {
            foreach ($this->phases as $phase) {
                $groupCollection = clone $collection;
                $groupCollection->addFilterToCode($group->code());
                $groupCollection->addFilterToWebsiteId($websiteId);
                $groupCollection->addFilterToStatus($phase['startStatus']);
                $groupCollection->addFilterDependent();

                $groupCollection->setPageSize(self::PAGE_SIZE);

                for ($i = 1; true; $i++) {
                    $groupCollection->setPageSize($i);
                    $groupCollection->clear();

                    $groupCollection->updateAllStatus($phase['processStatus']);
                    if (0 === $groupCollection->count()) {
                        break;
                    }

                    $group->messageDebug(
                        'Start entity "%s" synchronize for website %s',
                        $group->code(),
                        $websiteId
                    );

                    try {
                        $group->synchronize($groupCollection->getItems());
                    } catch (\Exception $e) {
                        $group->messageError($e);
                    }

                    $group->messageDebug(
                        'Stop entity "%s" synchronize for website %s',
                        $group->code(),
                        $websiteId
                    );

                    // Save change status
                    foreach ($groupCollection as $queue) {
                        $groupCollection->getResource()->save($queue);
                    }

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

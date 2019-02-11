<?php
namespace TNW\Salesforce\Synchronize;

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
     * @var \TNW\Salesforce\Model\ResourceModel\Queue
     */
    private $resourceQueue;

    /**
     * Queue constructor.
     * @param Group[] $groups
     * @param \TNW\Salesforce\Model\ResourceModel\Queue $resourceQueue
     */
    public function __construct(
        array $groups,
        \TNW\Salesforce\Model\ResourceModel\Queue $resourceQueue
    ) {
        $this->groups = $groups;
        $this->resourceQueue = $resourceQueue;
    }

    /**
     * Synchronize
     *
     * @param \TNW\Salesforce\Model\ResourceModel\Queue\Collection $collection
     * @param int $websiteId
     */
    public function synchronize($collection, $websiteId)
    {
        // Collection Clear
        $collection->clear();

        foreach ($this->sortGroup() as $group) {
            $groupCollection = clone $collection;
            $groupCollection->addFilterToCode($group->code());
            $groupCollection->addFilterToWebsiteId($websiteId);
            $groupCollection->addFilterDependent();

            if (0 === $groupCollection->getSize()) {
                continue;
            }

            $groupCollection->setPageSize(self::PAGE_SIZE);
            $lastPageNumber = $groupCollection->getLastPageNumber();

            $group->messageDebug('Start entity "%s" synchronize for website %s', $group->code(), $websiteId);

            try {
                for ($i = 1; $i <= $lastPageNumber; $i++) {
                    $groupCollection->setPageSize($i);
                    $groupCollection->clear();

                    $group->synchronize($groupCollection->getItems());
                }
            } catch (\Exception $e) {
                $group->messageError($e);
            }

            $group->messageDebug('Stop entity "%s" synchronize for website %s', $group->code(), $websiteId);
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

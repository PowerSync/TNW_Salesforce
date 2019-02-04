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
        if ($collection->isLoaded()) {
            $collection->clear();
        }

        usort($this->groups, [$this, 'sortGroup']);
        foreach ($this->groups as $group) {
            $groupCollection = clone $collection;
            $groupCollection->addFilterToCode($group->code());
            $groupCollection->addFilterToWebsiteId($websiteId);

            $size = $groupCollection->getSize();
            if ($size === 0) {
                continue;
            }

            $groupCollection->setPageSize(self::PAGE_SIZE);
            $lastPageNumber = $groupCollection->getLastPageNumber();

            $group->messageDebug('Start entity "%s" synchronize for website %s', $group->code(), $websiteId);

            for ($i = 1; $i <= $lastPageNumber; $i++) {
                $groupCollection->setPageSize($i);
                $groupCollection->clear();

                $group->synchronize($groupCollection->getItems());
            }

            $group->messageDebug('Stop entity "%s" synchronize for website %s', $group->code(), $websiteId);
        }
    }

    /**
     * Sort Group
     *
     * @param Group $a
     * @param Group $b
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sortGroup($a, $b)
    {
        $dependenceA = $this->resourceQueue->getDependenceByCode($a->code());
        if (in_array($b->code(), $dependenceA, true)) {
            return -1;
        }

        $dependenceB = $this->resourceQueue->getDependenceByCode($b->code());
        if (in_array($a->code(), $dependenceB, true)) {
            return 1;
        }

        return 0;
    }
}

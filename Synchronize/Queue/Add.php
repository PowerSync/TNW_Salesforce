<?php
namespace TNW\Salesforce\Synchronize\Queue;

use TNW\Salesforce\Model\Config;

/**
 * Class Entity
 */
class Add
{
    /**
     * @var string
     */
    private $entityType;

    /**
     * @var Unit[]
     */
    private $resolves;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg\Pool
     */
    private $dividerPool;

    /**
     * @var \TNW\Salesforce\Model\Config\WebsiteEmulator
     */
    private $websiteEmulator;

    /**
     * @var \TNW\Salesforce\Synchronize\Queue\Synchronize
     */
    private $synchronizeEntity;

    /**
     * @var \TNW\Salesforce\Model\ResourceModel\Queue
     */
    private $resourceQueue;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * Entity constructor.
     * @param string $entityType
     * @param Unit[] $resolves
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg\Pool $dividerPool
     * @param \TNW\Salesforce\Model\Config\WebsiteEmulator $websiteEmulator
     * @param \TNW\Salesforce\Synchronize\Queue\Synchronize $synchronizeEntity
     * @param \TNW\Salesforce\Model\ResourceModel\Queue $resourceQueue
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        $entityType,
        array $resolves,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg\Pool $dividerPool,
        \TNW\Salesforce\Model\Config\WebsiteEmulator $websiteEmulator,
        \TNW\Salesforce\Synchronize\Queue\Synchronize $synchronizeEntity,
        \TNW\Salesforce\Model\ResourceModel\Queue $resourceQueue,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->resolves = $resolves;
        $this->entityType = $entityType;
        $this->storeManager = $storeManager;
        $this->dividerPool = $dividerPool;
        $this->websiteEmulator = $websiteEmulator;
        $this->synchronizeEntity = $synchronizeEntity;
        $this->resourceQueue = $resourceQueue;
        $this->messageManager = $messageManager;
    }

    /**
     * Add To Queue
     *
     * @param int[] $entityIds
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addToQueue(array $entityIds)
    {
        $entitiesByWebsite = $this->dividerPool
            ->getDividerByGroupCode($this->entityType)
            ->process($entityIds);

        array_walk($entitiesByWebsite, [$this, 'addToQueueByWebsite']);
    }

    /**
     * Add To Queue By Website
     *
     * @param int[] $entityIds
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $website
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function addToQueueByWebsite(array $entityIds, $website = null)
    {
        $websiteId = $this->storeManager->getWebsite($website)->getId();
        $syncType = $this->syncType(count($entityIds), $websiteId);

        foreach ($this->resolves as $resolve) {
            $this->create($resolve, $this->entityType, $entityIds, [], $websiteId, $syncType);
        }

        if ($syncType === Config::DIRECT_SYNC_TYPE_REALTIME) {
            // Sync realtime type
            $this->websiteEmulator->wrapEmulationWebsite(
                [$this->synchronizeEntity, 'synchronizeToWebsite'],
                $websiteId
            );
            return;
        }

        $this->messageManager->addSuccessMessage('All records were added to the synchronization queue');
    }

    /**
     * Create
     *
     * @param Unit $unit
     * @param string $loadBy
     * @param int[] $entityIds
     * @param array $loadAdditional
     * @param int $websiteId
     * @param string $syncType
     * @param array $cacheQueue
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create(
        Unit $unit,
        $loadBy,
        $entityIds,
        array $loadAdditional,
        $websiteId,
        $syncType,
        array &$cacheQueue = []
    ) {
        $queues = $groupQueues = [];
        foreach ($unit->generateQueues($loadBy, $entityIds, $loadAdditional, $websiteId) as $queue) {
            $key = sprintf(
                '%s/%s/%s/%s',
                $queue->getEntityLoad(),
                $queue->getEntityId(),
                $unit->code(),
                serialize($queue->getEntityLoadAdditional())
            );

            $queue->setData('website_id', $websiteId);
            $queue->setData('sync_type', $syncType);

            switch (true) {
                case isset($cacheQueue[$key]):
                    $queue = (clone $cacheQueue[$key])->setData('_base_entity_id', $queue->getData('_base_entity_id'));
                    break;

                case $unit->skipQueue($queue):
                    $queue = $cacheQueue[$key] = null;
                    break;

                default:
                    // Add cache
                    $cacheQueue[$key] = $queue;

                    // Save queue
                    $this->resourceQueue->merge($queue);

                    $groupName = sprintf(
                        '%s/%s',
                        $queue->getEntityLoad(),
                        serialize($queue->getEntityLoadAdditional())
                    );

                    $groupQueues[$groupName][] = $queue;
                    break;
            }

            $queues[] = $queue;
        }

        /** @var \TNW\Salesforce\Model\Queue[] $baseQueues */
        foreach ($groupQueues as $baseQueues) {
            $baseEntityLoad = reset($baseQueues)->getEntityLoad();
            $baseEntityLoadAdditional = reset($baseQueues)->getEntityLoadAdditional();
            $baseEntityIds = array_map(static function ($queue) {
                return $queue->getEntityId();
            }, $baseQueues);

            // Generate Parents
            $parents = [];
            foreach ($unit->parents() as $parent) {
                $parentQueues = $this->create(
                    $parent,
                    $baseEntityLoad,
                    $baseEntityIds,
                    $baseEntityLoadAdditional,
                    $websiteId,
                    $syncType,
                    $cacheQueue
                );

                if (empty($parentQueues)) {
                    continue;
                }

                array_push($parents, ...$parentQueues);
            }

            foreach ($baseQueues as $queue) {
                $filter = static function ($parent) use ($queue) {
                    return $parent->getData('_base_entity_id') === $queue->getEntityId();
                };

                $dependence = array_filter($parents, $filter);
                if (empty($dependence)) {
                    continue;
                }

                $queue->setDependence($dependence);
                $this->resourceQueue->merge($queue);
            }

            // Generate Children
            $children = [];
            foreach ($unit->children() as $child) {
                $childQueues = $this->create(
                    $child,
                    $baseEntityLoad,
                    $baseEntityIds,
                    $baseEntityLoadAdditional,
                    $websiteId,
                    $syncType,
                    $cacheQueue
                );

                if (empty($childQueues)) {
                    continue;
                }

                array_push($children, ...$childQueues);
            }

            foreach ($children as $child) {
                $filer = static function ($queue) use ($child) {
                    return $child->getData('_base_entity_id') === $queue->getEntityId();
                };

                foreach (array_filter($baseQueues, $filer) as $queue) {
                    $child->addDependence($queue);
                }

                $this->resourceQueue->merge($child);
            }
        }

        return array_filter($queues);
    }

    /**
     * Sync Type
     *
     * @param int $count
     * @param int $websiteId
     * @return int
     */
    public function syncType($count, $websiteId)
    {
        return Config::DIRECT_SYNC_TYPE_REALTIME;
    }
}

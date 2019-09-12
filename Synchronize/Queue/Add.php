<?php

namespace TNW\Salesforce\Synchronize\Queue;

use TNW\Salesforce\Model\Config;

/**
 * Class Entity
 */
class Add
{
    const DIRECT_ADD_TO_QUEUE_COUNT_LIMIT = 200;
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
     * @var \TNW\Salesforce\Model\ResourceModel\PreQueue
     */
    private $resourcePreQueue;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /** @var \Magento\Framework\App\State  */
    private $state;

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
        \TNW\Salesforce\Model\ResourceModel\PreQueue $resourcePreQueue,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\State $state
    )
    {
        $this->resolves = $resolves;
        $this->entityType = $entityType;
        $this->storeManager = $storeManager;
        $this->dividerPool = $dividerPool;
        $this->websiteEmulator = $websiteEmulator;
        $this->synchronizeEntity = $synchronizeEntity;
        $this->resourceQueue = $resourceQueue;
        $this->resourcePreQueue = $resourcePreQueue;
        $this->messageManager = $messageManager;
        $this->state = $state;
    }

    /**
     * Add To Queue
     *
     * @param int[] $entityIds
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addToQueue(array $entityIds)
    {
        $this->addToQueueDirectly($entityIds);
    }

    /**
     * @param array $entityIds
     */
    public function addToPreQueue(array $entityIds)
    {
        foreach (array_chunk($entityIds, self::DIRECT_ADD_TO_QUEUE_COUNT_LIMIT) as $ids) {
            $this->resourcePreQueue->saveEntityIds($ids, $this->entityType);
        }

        if ($this->state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML ) {
            $this->messageManager->addSuccessMessage('Records were scheduled to be added to the sync queue');
        }
    }

    /**
     * @param array $entityIds
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addToQueueDirectly(array $entityIds)
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

        $this->create($this->resolves, $this->entityType, $entityIds, [], $websiteId, $syncType);

        if ($syncType === Config::DIRECT_SYNC_TYPE_REALTIME) {
            // Sync realtime type
            $this->websiteEmulator->wrapEmulationWebsite(
                [$this->synchronizeEntity, 'synchronizeToWebsite'],
                $websiteId
            );
            return;
        }

        if ($this->state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML ) {
            $this->messageManager->addSuccessMessage('All records were added to the synchronization queue');
        }
    }

    /**
     * @param $current
     * @param $parents
     * @param $children
     * @return array
     */
    public function buildDependency($current, $parents, $unitCode)
    {
        $dependency = [];
        foreach ($current as $queue) {
            foreach ($parents as $parent) {
                if (
                    $parent->getIdentify() != $queue->getIdentify() &&
                    $parent->getData('_base_entity_id/' . $unitCode) &&
                    in_array($queue->getEntityId(), $parent->getData('_base_entity_id/' . $unitCode))
                ) {
                    $dependency[] = [
                        'parent_id' => $parent->getId(),
                        'queue_id' => $queue->getId()
                    ];
                }
            }
        }
        return $dependency;
    }

    /**
     * @param $unitsList \TNW\Salesforce\Synchronize\Queue\Unit[]
     * @param $loadBy
     * @param $entityIds
     * @param $loadAdditional
     * @param $websiteId
     * @param $dependencies
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateQueueObjects(
        $unitsList,
        $loadBy,
        $entityIds,
        $loadAdditional,
        $websiteId,
        &$dependencies,
        &$queuesUnique = [],
        $relatedUnitCode = null
    )
    {

        $queues = [];
        $parents = $children = [];

        /**
         * save related entities to the Queue
         */
        foreach ($unitsList as $key => $unit) {
            $key = (sprintf(
                '%s',
                $unit->code()
            ));
            $relatedUnitCode = $relatedUnitCode ?? $unit->code();

            $current = $unit->generateQueues($loadBy, $entityIds, $loadAdditional, $websiteId, $relatedUnitCode);

            if (empty($current)) {
                continue;
            }

            /**
             * This unit already processed higher in recursion stack
             */
            if (isset($queuesUnique[$key])) {
                $current = $unit->baseByUnique($queuesUnique[$key], $current);
                $parents = $children = [];

                /**
                 * add if items were added partially (regular/guest customers for example)
                 */
                foreach ([$current] as $relation) {
                    foreach ($relation as $relationItem) {
                        $queuesUnique[$key][$relationItem->getId()] = $relationItem;
                    }
                }
            } else {

                $currentEntityIds = [];
                $currentByEntityLoad = [];
                foreach ([$current] as $relation) {
                    foreach ($relation as $relationItem) {
                        $queuesUnique[$key][$relationItem->getId()] = $relationItem;
                        $currentEntityIds[$relationItem->getEntityLoad()][] = $relationItem->getEntityId();
                        $currentByEntityLoad[$relationItem->getEntityLoad()][] = $relationItem;
                    }
                }

                foreach ($currentByEntityLoad as $baseEntityLoad => $itemByEntityLoad) {
                    /** @var \TNW\Salesforce\Model\Queue */
                    $currentItem = reset($itemByEntityLoad);
                    $baseEntityLoadAdditional = $currentItem->getEntityLoadAdditional();

                    $parentsTmp = $this->generateQueueObjects(
                        $unit->parents(),
                        $baseEntityLoad,
                        $currentEntityIds[$baseEntityLoad],
                        $baseEntityLoadAdditional,
                        $websiteId,
                        $dependencies,
                        $queuesUnique,
                        $unit->code()
                    );

                    foreach ($parentsTmp as $item) {
                        $parents[] = $item;
                    }

                    $childrenTmp = $this->generateQueueObjects(
                        $unit->children(),
                        $baseEntityLoad,
                        $currentEntityIds[$baseEntityLoad],
                        $baseEntityLoadAdditional,
                        $websiteId,
                        $dependencies,
                        $queuesUnique,
                        $unit->code()
                    );

                    foreach ($childrenTmp as $item) {
                        $children[] = $item;
                    }
                }

                /**
                 * add parent dependency only, child has own relations
                 * and will be created as parent dependency deeper in recursion generateQueueObjects
                 */
                foreach ($unit->parents() as $parent) {
                    $newDependencies = $this->buildDependency($current, $parent->getQueues(), $unit->code());
                    if (!empty($newDependencies)) {
                        array_push($dependencies, ...$newDependencies);
                    }
                }
            }

            $unit->setQueues($current);

            foreach ([$current, $parents, $children] as $relation) {
                foreach ($relation as $relationItem) {
                    $queues[$relationItem->getId()] = $relationItem;
                }
            }
        }

        return $queues;
    }

    /**
     * @param $queues
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getInsertArray($queues, $syncType, $websiteId)
    {
        $queueDataToSave = [];

        foreach ($queues as $queue) {
            $queue->setData('website_id', $websiteId);
            $queue->setData('sync_type', $syncType);
            $queueDataToSave[] = $this->resourceQueue->convertToArray($queue);
        }

        return $queueDataToSave;
    }

    /**
     * Create
     *
     * @param $unitsList \TNW\Salesforce\Synchronize\Queue\Unit[]
     * @param $loadBy
     * @param $entityIds
     * @param array $loadAdditional
     * @param $websiteId
     * @param $syncType
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create(
        $unitsList,
        $loadBy,
        $entityIds,
        array $loadAdditional,
        $websiteId,
        $syncType
    )
    {
        $dependencies = [];
        /**
         * collect all queue objects and build dependencies
         */
        $queues = $this->generateQueueObjects(
            $unitsList,
            $loadBy,
            $entityIds,
            $loadAdditional,
            $websiteId,
            $dependencies
        );

        $queueDataToSave = $this->getInsertArray($queues, $syncType, $websiteId);

        $this->saveData($queueDataToSave, $dependencies);
    }

    /**
     * @throws \Exception
     */
    public function saveData($queueDataToSave, $dependencies)
    {
        $connection = $this->resourceQueue->getConnection();

        $connection->beginTransaction();
        try {
            $this->saveQueue($queueDataToSave);
            $this->saveDependency($dependencies);

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param $queueDataToSave
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveQueue($queueDataToSave)
    {

        if (!empty($queueDataToSave)) {

            $this
                ->resourceQueue
                ->getConnection()
                ->insertOnDuplicate(
                    $this->resourceQueue->getMainTable(),
                    $queueDataToSave,
                    array_keys(reset($queueDataToSave))
                );
        }
    }

    /**
     * @param $dependencies
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveDependency($dependencies)
    {
        if (!empty($dependencies)) {
            $this
                ->resourceQueue
                ->getConnection()
                ->insertArray(
                    $this->resourceQueue->getTable('tnw_salesforce_entity_queue_relation'),
                    array_keys(reset($dependencies)),
                    $dependencies,
                    \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_IGNORE
                );
        }
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

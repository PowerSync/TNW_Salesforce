<?php

namespace TNW\Salesforce\Synchronize\Queue;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\Config\WebsiteEmulator;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Model\ResourceModel\PreQueue;
use TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg\Pool;

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
    public $resolves;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Pool
     */
    private $dividerPool;

    /**
     * @var WebsiteEmulator
     */
    private $websiteEmulator;

    /**
     * @var Synchronize
     */
    private $synchronizeEntity;

    /**
     * @var \TNW\Salesforce\Model\ResourceModel\Queue
     */
    private $resourceQueue;

    /**
     * @var PreQueue
     */
    private $resourcePreQueue;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /** @var State */
    private $state;

    /**
     * Entity constructor.
     * @param string $entityType
     * @param Unit[] $resolves
     * @param StoreManagerInterface $storeManager
     * @param Pool $dividerPool
     * @param WebsiteEmulator $websiteEmulator
     * @param Synchronize $synchronizeEntity
     * @param \TNW\Salesforce\Model\ResourceModel\Queue $resourceQueue
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        $entityType,
        array $resolves,
        StoreManagerInterface $storeManager,
        Pool $dividerPool,
        WebsiteEmulator $websiteEmulator,
        Synchronize $synchronizeEntity,
        \TNW\Salesforce\Model\ResourceModel\Queue $resourceQueue,
        PreQueue $resourcePreQueue,
        ManagerInterface $messageManager,
        State $state
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
     * @throws LocalizedException
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
            $syncType = $this->syncType(count($entityIds), 0);
            $this->resourcePreQueue->saveEntityIds($ids, $this->entityType, $syncType);
        }

        if ($this->state->getAreaCode() == Area::AREA_ADMINHTML) {
            $this->messageManager->addSuccessMessage('Records were scheduled to be added to the sync queue');
        }
    }

    /**
     * @param array $entityIds
     * @throws LocalizedException
     */
    public function addToQueueDirectly(array $entityIds, $syncType = null)
    {
        $entitiesByWebsite = $this->dividerPool
            ->getDividerByGroupCode($this->entityType)
            ->process($entityIds);

        array_walk($entitiesByWebsite, [$this, 'addToQueueByWebsite'], $syncType);
    }

    /**
     * Add To Queue By Website
     *
     * @param int[] $entityIds
     * @param null|bool|int|string|WebsiteInterface $website
     * @throws LocalizedException
     * @throws Exception
     */
    public function addToQueueByWebsite(array $entityIds, $website = null, $syncType = null)
    {
        $websiteId = $this->storeManager->getWebsite($website)->getId();

        if ($syncType === null) {
            $syncType = $this->syncType(count($entityIds), $websiteId);
        }

        $this->create($this->resolves, $this->entityType, $entityIds, [], $websiteId, $syncType);

        if ($syncType === Config::DIRECT_SYNC_TYPE_REALTIME) {
            // Sync realtime type
            $this->websiteEmulator->wrapEmulationWebsite(
                [$this->synchronizeEntity, 'synchronizeToWebsite'],
                $websiteId
            );
            return;
        }

        if ($this->state->getAreaCode() == Area::AREA_ADMINHTML) {
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
     * @param $unitsList Unit[]
     * @param $loadBy
     * @param $entityIds
     * @param $loadAdditional
     * @param $websiteId
     * @param $dependencies
     * @return array
     * @throws LocalizedException
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
                    /** @var Queue */
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

            $unit->addQueues($current);

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
     * @throws LocalizedException
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
     * @param $unitsList Unit[]
     * @param $loadBy
     * @param $entityIds
     * @param array $loadAdditional
     * @param $websiteId
     * @param $syncType
     * @return array
     * @throws LocalizedException
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
     * @throws Exception
     */
    public function saveData($queueDataToSave, $dependencies)
    {
        $connection = $this->resourceQueue->getConnection();

        $connection->beginTransaction();
        try {
            $this->saveQueue($queueDataToSave);
            $this->saveDependency($dependencies);

//            $this->buildGraph($queueDataToSave, $dependencies);

            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param $queueDataToSave
     * @param $dependencies
     */
    public function buildGraph($queueDataToSave, $dependencies)
    {
        $graph = [
            '@startdot',
            sprintf('digraph %s {', $this->entityType),
            sprintf("label = <<font color='green'><b>%s</b></font>>;", $this->entityType),
            'labelloc = "t";'
        ];

        foreach ($queueDataToSave as $queue) {
            $graph[] = sprintf('a%s [label="%s"];', str_replace('.', '', $queue['queue_id']), $queue['code']);
        }

        foreach ($dependencies as $queue) {
            $graph[] = sprintf('a%s -> a%s ;', str_replace('.', '', $queue['parent_id']), str_replace('.', '', $queue['queue_id']));
        }

        $graph[] = '}';
        $graph[] = '@enddot';

        file_put_contents($this->entityType . '.dot', implode("\n", $graph));
    }

    /**
     * @param $queueDataToSave
     * @throws LocalizedException
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
     * @throws LocalizedException
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
                    AdapterInterface::INSERT_IGNORE
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

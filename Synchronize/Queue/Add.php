<?php

namespace TNW\Salesforce\Synchronize\Queue;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\MessageInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Api\MessageQueue\PublisherAdapter;
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

    const TOPIC_NAME = 'tnw_salesforce.sync.realtime';
    /**
     * @var string
     */
    protected $entityType;

    /**
     * @var Unit[]
     */
    public $resolves;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Pool
     */
    protected $dividerPool;

    /**
     * @var WebsiteEmulator
     */
    protected $websiteEmulator;

    /**
     * @var Synchronize
     */
    protected $synchronizeEntity;

    /**
     * @var \TNW\Salesforce\Model\ResourceModel\Queue
     */
    protected $resourceQueue;

    /**
     * @var PreQueue
     */
    protected $resourcePreQueue;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /** @var State */
    protected $state;

    /**
     * @var PublisherAdapter
     */
    protected $publisher;

    /**
     * Add constructor.
     * @param $entityType
     * @param array $resolves
     * @param StoreManagerInterface $storeManager
     * @param Pool $dividerPool
     * @param WebsiteEmulator $websiteEmulator
     * @param Synchronize $synchronizeEntity
     * @param \TNW\Salesforce\Model\ResourceModel\Queue $resourceQueue
     * @param PreQueue $resourcePreQueue
     * @param ManagerInterface $messageManager
     * @param State $state
     * @param PublisherAdapter $publisher
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
        State $state,
        PublisherAdapter $publisher
    ) {
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
        $this->publisher = $publisher;
    }

    /**
     * Add To Queue
     *
     * @param int[] $entityIds
     * @throws LocalizedException
     */
    public function addToQueue(array $entityIds)
    {
        if (empty($entityIds)) {
            return;
        }
        $this->addToQueueDirectly($entityIds);

        if ($this->state->getAreaCode() == Area::AREA_ADMINHTML) {

            /** @var MessageInterface $message */
            $message = $this->messageManager
                ->createMessage(MessageInterface::TYPE_SUCCESS)
                ->setText('Item(s) were added to the Salesforce sync queue');

            $this->messageManager->addUniqueMessages([$message]);
        }
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

            /** @var MessageInterface $message */
            $message = $this->messageManager
                ->createMessage(MessageInterface::TYPE_SUCCESS)
                ->setText('Records are being added to the synchronization queue');

            $this->messageManager->addUniqueMessages([$message]);
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
            $this->publisher->publish(self::TOPIC_NAME, (string)$websiteId);
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
    ) {
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
//            $this->appendGraph($current);

            if (empty($current)) {
                continue;
            }

            // merge already created records
            if (isset($queuesUnique[$key])) {
                $current = $unit->baseByUnique($queuesUnique[$key], $current);
            }

            $currentEntityIds = [];
            $currentByEntityLoad = [];
            foreach ([$current] as $relation) {
                foreach ($relation as $relationItem) {
                    if (empty($queuesUnique[$key][$relationItem->getId()])) {
                        $currentEntityIds[$relationItem->getEntityLoad()][] = $relationItem->getEntityId();
                        $currentByEntityLoad[$relationItem->getEntityLoad()][] = $relationItem;
                        $queuesUnique[$key][$relationItem->getId()] = $relationItem;
                    }
                }
            }

            foreach ($currentByEntityLoad as $baseEntityLoad => $itemByEntityLoad) {
                /** @var Queue */
                $currentItem = reset($itemByEntityLoad);
                $baseEntityLoadAdditional = $currentItem->getEntityLoadAdditional();

                $closure = function ($unitsList, &$result) use (
                    $unit,
                    $baseEntityLoad,
                    $currentEntityIds,
                    $baseEntityLoadAdditional,
                    $websiteId,
                    &$dependencies,
                    &$queuesUnique
                ) {
                    $tmp = $this->generateQueueObjects(
                        $unitsList,
                        $baseEntityLoad,
                        $currentEntityIds[$baseEntityLoad],
                        $baseEntityLoadAdditional,
                        $websiteId,
                        $dependencies,
                        $queuesUnique,
                        $unit->code()
                    );

                    if (!empty($tmp)) {
                        $tmp = array_values($tmp);
                        array_push($result, ...$tmp);
                    }
                };

                $closure($unit->parents(), $parents);
                $closure($unit->children(), $children);

            }

            $dependencies = $this->addDependencies($unit, $current, $dependencies);

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
     * @param $unit
     * @param $current
     * @param $dependencies
     * @return mixed
     */
    public function addDependencies($unit, $current, $dependencies)
    {
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
        return $dependencies;
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
    ) {
        $dependencies = [];

//        $this->openGraph();
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

//        $this->closeGraph();
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
            $this->updateRelationsWithErrors($queueDataToSave);

//            $this->buildGraph($queueDataToSave, $dependencies);

            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     *
     */
    public function openGraph()
    {
        $this->relation = [];
        $graph = [
            '@startdot',
            sprintf('strict digraph %s {', $this->entityType),
            sprintf("label = <<font color='green'><b>%s</b></font>>;", $this->entityType),
            'labelloc = "t";'
        ];

        file_put_contents('dot/' . $this->entityType . '.dot', implode("\n", $graph));
    }

    protected $relation = [];

    /**
     * @param $current
     */
    public function appendGraph($current)
    {
        $fillcolor = 'green';
        if (!empty($this->relation)) {
            $fillcolor = 'white';
        }

        $graph = [];
        foreach ($current as $queue) {
            if (!in_array($queue['code'], $this->relation)) {
                $graph[] = sprintf('%s [label="%s", style=filled, fillcolor=%s];', $queue['code'], $queue['code'], $fillcolor);
            }
            $this->relation[$queue['queue_id']] = $queue['code'];
        }

        file_put_contents('dot/' . $this->entityType . '.dot', implode("\n", $graph) . "\n", FILE_APPEND);

        $first = false;
    }

    /**
     *
     */
    public function closeGraph()
    {
        $graph[] = '}';
        $graph[] = '@enddot';

        file_put_contents('dot/' . $this->entityType . '.dot', implode("\n", $graph) . "\n", FILE_APPEND);
    }

    /**
     * @param $queueDataToSave
     * @param $dependencies
     */
    public function buildGraph($queueDataToSave, $dependencies)
    {
        $graph = [];

        foreach ($dependencies as $queue) {
            $parent_id = $this->relation[$queue['parent_id']];
            $queue_id = $this->relation[$queue['queue_id']];

            $graph[] = sprintf('%s -> %s ;', $parent_id, $queue_id);
        }

        file_put_contents('dot/' . $this->entityType . '.dot', implode("\n", $graph) . "\n", FILE_APPEND);
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

    /**
     * If any of saved queues have some parent queue with 'error' status,
     * make such parent queue 'new' to unblock newly added queues.
     *
     * @param array $queueDataToSave
     * @return void
     */
    private function updateRelationsWithErrors(array $queueDataToSave): void
    {
        if (!empty($queueDataToSave)) {
            $orWhere = array_map(function (array $queue) {
                return sprintf(
                    '(old_queue.identify = \'%s\' AND old_queue.website_id= \'%s\')',
                    $queue['identify'],
                    $queue['website_id']
                );
            },
                $queueDataToSave
            );

            $connection = $this->resourceQueue->getConnection();
            $queueTable = $connection->getTableName('tnw_salesforce_entity_queue');
            $relationTable = $connection->getTableName('tnw_salesforce_entity_queue_relation');
            $select = $connection->select()
                ->distinct()
                ->join(
                    ['relation' => $relationTable],
                    'relation.parent_id = queue.queue_id',
                    [
                        'composite_status' => new \Zend_Db_Expr('\'new\''),
                        'sync_attempt' => new \Zend_Db_Expr(0),
                    ]
                )
                ->join(
                    ['old_queue' => $queueTable],
                    'relation.queue_id = old_queue.queue_id',
                    []
                )
                ->where(
                    'queue.composite_status = \'error\''
                )
                ->where(
                    implode(' OR ', $orWhere)
                );
            $query = $connection->updateFromSelect($select, ['queue' => $queueTable]);
            $connection->query($query);
        }
    }
}

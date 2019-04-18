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
    )
    {
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

        $this->create($this->resolves, $this->entityType, $entityIds, [], $websiteId, $syncType);

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
     * @param $unitsList
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
        $queueDataToSave = $queues = [];

        /**
         * collect all units responsible for the dependency logic
         */
        foreach ($unitsList as $unit) {
            foreach ($unit->parents() as $key => $parent) {
                $unitsList[$key] = $parent;
            }

            foreach ($unit->children() as $key => $child) {
                $unitsList[$key] = $child;
            }
        }

        /**
         * save related entities to the Queue
         */
        foreach ($unitsList as $key => $unit) {
            foreach ($unit->generateQueues($loadBy, $entityIds, $loadAdditional, $websiteId) as $queue) {

                // Save queue
                $queueDataToSave[] = $this->resourceQueue->merge($queue);
            }
        }

        if (!empty($queueDataToSave)) {
            $this
                ->resourceQueue
                ->getConnection()
                ->insertArray(
                    $this->resourceQueue->getMainTable(),
                    array_keys(reset($queueDataToSave)),
                    $queueDataToSave,
                    \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_ON_DUPLICATE
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

<?php
namespace TNW\Salesforce\Synchronize\Queue;

use Magento\Framework\Exception\LocalizedException;

/**
 * Sync Unit
 */
class Unit
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $entityType;

    /**
     * @var string
     */
    private $objectType;

    /**
     * @var CreateInterface[]
     */
    private $generators;

    /**
     * @var \TNW\Salesforce\Model\QueueFactory
     */
    private $queueFactory;

    /**
     * @var \TNW\Salesforce\Model\ResourceModel\Queue
     */
    private $resourceQueue;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var string[]
     */
    private $children;

    /**
     * @var string[]
     */
    private $parents;

    /**
     * @var array
     */
    private $skipRules;

    /**
     * Queue constructor.
     * @param string $code
     * @param string $description
     * @param string $entityType
     * @param string $objectType
     * @param array $generators
     * @param \TNW\Salesforce\Model\QueueFactory $queueFactory
     * @param \TNW\Salesforce\Model\ResourceModel\Queue $resourceQueue
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param SkipInterface[] $skipRules
     * @param string[] $parents
     * @param string[] $children
     */
    public function __construct(
        $code,
        $description,
        $entityType,
        $objectType,
        array $generators,
        \TNW\Salesforce\Model\QueueFactory $queueFactory,
        \TNW\Salesforce\Model\ResourceModel\Queue $resourceQueue,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $skipRules = [],
        array $parents = [],
        array $children = []
    ) {
        $this->code = $code;
        $this->entityType = $entityType;
        $this->objectType = $objectType;
        $this->description = $description;
        $this->generators = $generators;
        $this->queueFactory = $queueFactory;
        $this->resourceQueue = $resourceQueue;
        $this->objectManager = $objectManager;
        $this->skipRules = $skipRules;
        $this->parents = $parents;
        $this->children = $children;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * Get entity type
     *
     * @return string
     */
    public function entityType()
    {
        return $this->entityType;
    }

    /**
     * Get parents
     *
     * @return Unit[]
     */
    public function parents()
    {
        return array_map([$this->objectManager, 'get'], $this->parents);
    }

    /**
     * Get children
     *
     * @return Unit[]
     */
    public function children()
    {
        return array_map([$this->objectManager, 'get'], $this->children);
    }

    /**
     * Create Queue
     *
     * @param string $loadBy
     * @param int $entityId
     * @param int $websiteId
     * @param string $syncType
     * @param Unit[] $resolves
     * @return \TNW\Salesforce\Model\Queue[]
     * @throws LocalizedException
     */
    public function createQueue($loadBy, $entityId, $websiteId, $syncType, array $resolves = [])
    {
        // Add parent
        $resolves[] = $this;

        $queues = $this->generator($loadBy)->process($entityId, [$this, 'create'], $websiteId);
        foreach ($queues as $queue) {
            $queue->setData('website_id', $websiteId);
            $queue->setData('sync_type', $syncType);

            if ($this->skip($queue)) {
                return [];
            }

            $loadBy = $queue->getEntityLoad();
            $entityId = $queue->getEntityId();

            // Generate Parents
            $parents = [];
            foreach ($this->parents() as $parent) {
                if ($this->isUsed($parent->code, $resolves)) {
                    continue;
                }

                $parentQueues = $parent->createQueue($loadBy, $entityId, $websiteId, $syncType, $resolves);
                if (empty($parentQueues)) {
                    continue;
                }

                array_push($parents, ...$parentQueues);
            }

            $queue->setDependence($parents);
            $this->resourceQueue->merge($queue);

            // Generate Children
            $children = [];
            foreach ($this->children() as $child) {
                if ($this->isUsed($child->code, $resolves)) {
                    continue;
                }

                $childQueues = $child->createQueue($loadBy, $entityId, $websiteId, $syncType, $resolves);
                if (empty($childQueues)) {
                    continue;
                }

                array_push($children, ...$childQueues);
            }

            foreach ($children as $child) {
                $child->addDependence($queue);
                $this->resourceQueue->merge($child);
            }
        }

        return $queues;
    }

    /**
     * Is used
     *
     * @param string $code
     * @param array $resolves
     * @return bool
     */
    private function isUsed($code, array $resolves)
    {
        foreach ($resolves as $resolve) {
            if (strcasecmp($resolve->code, $code) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Skip
     *
     * @param \TNW\Salesforce\Model\Queue $queue
     * @return bool
     */
    private function skip($queue)
    {
        foreach ($this->skipRules as $rule) {
            if ($rule->apply($queue)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create
     *
     * @param string $loadBy
     * @param int $entityId
     * @param array $additionalLoad
     * @return \TNW\Salesforce\Model\Queue
     */
    public function create($loadBy, $entityId, array $additionalLoad = [])
    {
        return $this->queueFactory->create(['data' => [
            'code' => $this->code,
            'description' => $this->description,
            'entity_type' => $this->entityType,
            'entity_id' => $entityId,
            'entity_load' => $loadBy,
            'entity_load_additional' => $additionalLoad,
            'object_type' => $this->objectType,
            'status' => 'new'
        ]]);
    }

    /**
     * Get generator
     *
     * @param string $type
     * @return CreateInterface
     * @throws LocalizedException
     */
    private function generator($type)
    {
        foreach ($this->generators as $generator) {
            if (strcasecmp($generator->createBy(), $type) !== 0) {
                continue;
            }

            return $generator;
        }

        throw new LocalizedException(__(
            'Unknown %3 generating method for %1 entity. Resolver code %2',
            $this->entityType,
            $this->code,
            $type
        ));
    }
}

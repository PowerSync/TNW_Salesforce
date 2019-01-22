<?php
namespace TNW\Salesforce\Synchronize\Queue;

use Magento\Framework\Exception\LocalizedException;

class Resolve
{
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
     * @var Resolve[]
     */
    private $children;

    /**
     * @var Resolve[]
     */
    private $parents;

    /**
     * @var array
     */
    private $skipRules;

    /**
     * Queue constructor.
     * @param string $entityType
     * @param string $objectType
     * @param array $generators
     * @param \TNW\Salesforce\Model\QueueFactory $queueFactory
     * @param \TNW\Salesforce\Model\ResourceModel\Queue $resourceQueue
     * @param SkipInterface[] $skipRules
     * @param Resolve[] $parents
     * @param Resolve[] $children
     */
    public function __construct(
        $entityType,
        $objectType,
        array $generators,
        \TNW\Salesforce\Model\QueueFactory $queueFactory,
        \TNW\Salesforce\Model\ResourceModel\Queue $resourceQueue,
        array $skipRules = [],
        array $parents = [],
        array $children = []
    ) {
        $this->entityType = $entityType;
        $this->objectType = $objectType;
        $this->generators = $generators;
        $this->queueFactory = $queueFactory;
        $this->resourceQueue = $resourceQueue;
        $this->skipRules = $skipRules;
        $this->parents = $parents;
        $this->children = $children;
    }

    /**
     * @return string
     */
    public function entityType()
    {
        return $this->entityType;
    }

    /**
     * @param string $loadBy
     * @param int $entityId
     * @param int $websiteId
     * @return \TNW\Salesforce\Model\Queue[]
     * @throws LocalizedException
     */
    public function generate($loadBy, $entityId, $websiteId)
    {
        if ($this->skip($websiteId)) {
            return [];
        }

        $queues = $this->generator($loadBy)->process($entityId, [$this, 'create']);
        foreach ($queues as $queue) {
            $queue->setData('website_id', $websiteId);

            // Generate Parents
            $parents = [];
            foreach ($this->parents as $dependency) {
                $parents += $dependency->parentGenerate($loadBy, $entityId, $websiteId);
            }
            $queue->setDependence($parents);
            $this->resourceQueue->save($queue);

            // Generate Children
            $children = [];
            foreach ($this->children as $child) {
                $children += $child->childrenGenerate($loadBy, $entityId, $websiteId);
            }

            foreach ($children as $child) {
                $child->addDependence($queue);
                $this->resourceQueue->save($child);
            }
        }

        return $queues;
    }

    /**
     * @param int $websiteId
     * @return bool
     */
    private function skip($websiteId)
    {
        foreach ($this->skipRules as $rule) {
            if ($rule->apply($this, $websiteId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $loadBy
     * @param int $entityId
     * @param int $websiteId
     * @return \TNW\Salesforce\Model\Queue[]
     * @throws LocalizedException
     */
    private function parentGenerate($loadBy, $entityId, $websiteId)
    {
        $queues = $this->generator($loadBy)->process($entityId, [$this, 'create']);
        foreach ($queues as $queue) {
            $queue->setData('website_id', $websiteId);

            $parents = [];
            foreach ($this->parents as $dependency) {
                $parents += $dependency->parentGenerate($loadBy, $entityId, $websiteId);
            }
            $queue->setDependence($parents);
            $this->resourceQueue->save($queue);
        }

        return $queues;
    }

    /**
     * @param string $loadBy
     * @param int $entityId
     * @param int $websiteId
     * @return \TNW\Salesforce\Model\Queue[]
     * @throws LocalizedException
     */
    private function childrenGenerate($loadBy, $entityId, $websiteId)
    {
        $queues = $this->generator($loadBy)->process($entityId, [$this, 'create']);
        foreach ($queues as $queue) {
            $queue->setData('website_id', $websiteId);

            $children = [];
            foreach ($this->children as $child) {
                $children += $child->childrenGenerate($loadBy, $entityId, $websiteId);
            }

            foreach ($children as $child) {
                $child->addDependence($queue);
                $this->resourceQueue->save($child);
            }
        }

        return $queues;
    }

    /**
     * @param string $loadBy
     * @param int $entityId
     * @return \TNW\Salesforce\Model\Queue
     */
    public function create($loadBy, $entityId)
    {
        return $this->queueFactory->create(['data' => [
            'entity_type' => $this->entityType,
            'entity_id' => $entityId,
            'entity_load' => $loadBy,
            'object_type' => $this->objectType,
        ]]);
    }

    /**
     * @param $type
     * @return CreateInterface
     * @throws LocalizedException
     */
    private function generator($type)
    {
        if (empty($this->generators[$type])) {
            throw new LocalizedException(__('Undefined type %1', $type));
        }

        return $this->generators[$type];
    }
}

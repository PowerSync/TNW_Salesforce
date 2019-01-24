<?php
namespace TNW\Salesforce\Synchronize\Queue;

use Magento\Framework\Exception\LocalizedException;

class Resolve
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
     * @return string
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function entityType()
    {
        return $this->entityType;
    }

    /**
     * @return Resolve[]
     */
    public function parents()
    {
        return array_map([$this->objectManager, 'get'], $this->parents);
    }

    /**
     * @return Resolve[]
     */
    public function children()
    {
        return array_map([$this->objectManager, 'get'], $this->children);
    }

    /**
     * @param string $loadBy
     * @param int $entityId
     * @param int $websiteId
     * @param Resolve|null $parent
     * @return \TNW\Salesforce\Model\Queue[]
     * @throws LocalizedException
     */
    public function generate($loadBy, $entityId, $websiteId, Resolve $parent = null)
    {
        if ($this->skip($websiteId)) {
            return [];
        }

        $queues = $this->generator($loadBy)->process($entityId, [$this, 'create']);
        foreach ($queues as $queue) {
            $queue->setData('website_id', $websiteId);

            $loadBy = $queue->getEntityLoad();
            $entityId = $queue->getEntityId();

            // Generate Parents
            $parents = [];
            foreach ($this->parents() as $dependency) {
                if ($parent && strcasecmp($parent->code, $dependency->code) === 0) {
                    continue;
                }

                $parents += $dependency->generate($loadBy, $entityId, $websiteId, $this);
            }
            $queue->setDependence($parents);
            $this->resourceQueue->merge($queue);

            // Generate Children
            $children = [];
            foreach ($this->children() as $child) {
                if ($parent && strcasecmp($this->code, $child->code) === 0) {
                    continue;
                }

                $children += $child->generate($loadBy, $entityId, $websiteId, $this);
            }

            foreach ($children as $child) {
                $child->addDependence($queue);
                $this->resourceQueue->merge($child);
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
     * @param $type
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

        throw new LocalizedException(__('Undefined type %1', $type));
    }
}

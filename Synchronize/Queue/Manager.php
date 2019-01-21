<?php
namespace TNW\Salesforce\Synchronize\Queue;

use Magento\Framework\Exception\LocalizedException;

class Manager
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
     * @var GeneratorInterface[]
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
     * @var Manager[]
     */
    private $children;

    /**
     * @var Manager[]
     */
    private $parents;

    /**
     * Queue constructor.
     * @param string $entityType
     * @param string $objectType
     * @param array $generators
     * @param \TNW\Salesforce\Model\QueueFactory $queueFactory
     * @param \TNW\Salesforce\Model\ResourceModel\Queue $resourceQueue
     * @param Manager[] $parents
     * @param Manager[] $children
     */
    public function __construct(
        $entityType,
        $objectType,
        array $generators,
        \TNW\Salesforce\Model\QueueFactory $queueFactory,
        \TNW\Salesforce\Model\ResourceModel\Queue $resourceQueue,
        array $parents = [],
        array $children = []
    ) {
        $this->entityType = $entityType;
        $this->objectType = $objectType;
        $this->generators = $generators;
        $this->queueFactory = $queueFactory;
        $this->resourceQueue = $resourceQueue;
        $this->children = $children;
        $this->parents = $parents;
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
     * @throws LocalizedException
     */
    public function generate($loadBy, $entityId, $websiteId)
    {
        foreach ($this->generator($loadBy)->process($entityId, [$this, 'create']) as $queue) {
            $queue->setData('website_id', $websiteId);

            // Generate Parents
            $parents = [];
            foreach ($this->parents as $dependency) {
                $parents += $dependency->parentGenerate($loadBy, $entityId, $websiteId);
            }
            $queue->setParents($parents);

            // Generate Children
            $children = [];
            foreach ($this->children as $child) {
                $children += $child->childrenGenerate($loadBy, $entityId, $websiteId);
            }
            $queue->setChildren($children);

            // Save queue
            $this->resourceQueue->save($queue);
        }
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
        $queues = [];
        foreach ($this->generator($loadBy)->process($entityId, [$this, 'create']) as $queue) {
            $queue->setData('website_id', $websiteId);

            $parents = [];
            foreach ($this->parents as $dependency) {
                $parents += $dependency->parentGenerate($loadBy, $entityId, $websiteId);
            }
            $queue->setParents($parents);
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
        $queues = [];
        foreach ($this->generator($loadBy)->process($entityId, [$this, 'create']) as $queue) {
            $queue->setData('website_id', $websiteId);

            $children = [];
            foreach ($this->children as $child) {
                $children += $child->childrenGenerate($loadBy, $entityId, $websiteId);
            }
            $queue->setChildren($children);
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
     * @return GeneratorInterface
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

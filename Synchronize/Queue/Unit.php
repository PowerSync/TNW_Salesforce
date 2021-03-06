<?php

namespace TNW\Salesforce\Synchronize\Queue;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Model\ResourceModel\Objects;

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
     * @var bool
     */
    private $ignoreFindGeneratorException;

    /**
     * @var \TNW\Salesforce\Model\Queue[]
     */
    private $queues = [];

    /**
     * @var Objects
     */
    protected $resourceObjects;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Queue constructor.
     * @param string $code
     * @param string $description
     * @param string $entityType
     * @param string $objectType
     * @param array $generators
     * @param \TNW\Salesforce\Model\QueueFactory $queueFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param Objects $resourceObjects
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     * @param SkipInterface[] $skipRules
     * @param string[] $parents
     * @param string[] $children
     * @param bool $ignoreFindGeneratorException
     */
    public function __construct(
        $code,
        $description,
        $entityType,
        $objectType,
        array $generators,
        \TNW\Salesforce\Model\QueueFactory $queueFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        Objects $resourceObjects,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        array $skipRules = [],
        array $parents = [],
        array $children = [],
        $ignoreFindGeneratorException = false
    ) {
        $this->code = $code;
        $this->entityType = $entityType;
        $this->objectType = $objectType;
        $this->description = $description;
        $this->generators = $generators;
        $this->queueFactory = $queueFactory;
        $this->objectManager = $objectManager;
        $this->resourceObjects = $resourceObjects;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->skipRules = $skipRules;
        $this->parents = $parents;
        $this->children = $children;
        $this->ignoreFindGeneratorException = $ignoreFindGeneratorException;
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
     * Get object type
     *
     * @return string
     */
    public function objectType()
    {
        return $this->objectType;
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
     * Skip
     *
     * @param \TNW\Salesforce\Model\Queue $queue
     * @return bool
     */
    public function skipQueue($queue)
    {
        foreach ($this->skipRules as $rule) {
            if ($rule->apply($queue) !== false) {
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
     * @param int $baseEntityId
     * @param array $identifiers
     * @param array $additionalLoad
     * @return \TNW\Salesforce\Model\Queue
     * @throws LocalizedException
     */
    public function createQueue($loadBy, $entityId, $baseEntityId, array $identifiers, array $additionalLoad = [])
    {
        $queue = $this->queueFactory->create(['data' => [
            'queue_id' => uniqid('', true),
            'code' => $this->code,
            'description' => $this->description($identifiers),
            'entity_type' => $this->entityType,
            'entity_id' => $entityId,
            'entity_load' => $loadBy,
            'entity_load_additional' => $additionalLoad,
            'object_type' => $this->objectType,
            'status' => 'new',
            'transaction_uid' => '0',
            'sync_attempt' => 0,
            '_base_entity_id' => [$baseEntityId],
            'identify' => md5(sprintf(
                '%s/%s/%s/%s/%s',
                $this->code(),
                $this->entityType,
                $entityId,
                serialize($additionalLoad),
                $this->objectType
            ))
        ]]);

        if ($this->skipQueue($queue)) {
            $storeId = (int) $this->request->getParam('store', 0);
            $store = $this->storeManager->getStore($storeId);
            $websiteId = $store->getWebsiteId();
            if (count($this->resourceObjects->loadObjectIds($entityId, $this->entityType, $websiteId))) {
                $this->resourceObjects
                    ->unsetPendingStatus($entityId, $this->entityType, $websiteId, $this->objectType);
            }

            return [];
        }

        return $queue;
    }

    /**
     * Prepare Description
     *
     * @param array $identifiers
     * @return string
     */
    public function description(array $identifiers)
    {
        $search = $replace = [];
        foreach ($identifiers as $key => $identifier) {
            $search[] = "{{identifier|$key}}";
            $replace[] = $identifier;
        }

        return str_replace($search, $replace, $this->description);
    }

    /**
     * Get generator
     *
     * @param string $loadBy
     * @param int[] $entityIds
     * @param array $additional
     * @param int $websiteId
     * @return Queue[]
     * @throws LocalizedException
     */
    public function generateQueues($loadBy, $entityIds, array $additional, $websiteId, $relatedUnitCode)
    {
        $generator = $this->findGenerator($loadBy);
        if ($generator instanceof CreateInterface) {
            $queues = $generator->process($entityIds, $additional, [$this, 'createQueue'], $websiteId);

            $queues = array_filter($queues);

            if (!empty($queues)) {
                $queues = $this->correctBaseEntityId($queues, $relatedUnitCode);

                $queues = $this->mergeQueue($queues);
            }
            return $queues;
        }

        if ($this->ignoreFindGeneratorException) {
            return [];
        }

        throw new LocalizedException(__(
            'Unknown %3 generating method for %1 entity. Queue code %2',
            $this->entityType,
            $this->code,
            $loadBy
        ));
    }

    /**
     * @param $queues
     * @param $createBy
     */
    public function correctBaseEntityId($queues, $relatedUnitCode)
    {
        foreach ($queues as $queue) {
            $queue->setData(
                '_base_entity_id',
                [$relatedUnitCode => $queue->getData('_base_entity_id')]
            );
        }
        return $queues;
    }

    /**
     * @param $queues
     * @return array
     */
    public function mergeQueue(&$queues)
    {
        foreach ($queues as $i => $queue1) {
            foreach ($queues as $j => $queue2) {
                if ($j <= $i) {
                    continue;
                }

                if ($queue1->getData('identify') == $queue2->getData('identify')) {
                    $queue1 = $this->mergeQueueObjects($queue1, $queue2);
                    unset($queues[$j]);
                }
            }
        }

        return $queues;
    }

    /**
     * avoid items duplication
     * @param $unique
     * @param $queues
     * @return mixed
     */
    public function baseByUnique($unique, &$queues)
    {
        foreach ($unique as $i => $queue1) {
            foreach ($queues as $j => $queue2) {

                if ($queue1->getData('identify') == $queue2->getData('identify') && $queue1->getId() != $queue2->getId()) {
                    $queues[$j] = $this->mergeQueueObjects($queue1, $queue2);
                }
            }
        }

        return $queues;
    }

    /**
     * @param $queue1
     * @param $queue2
     * @return mixed
     */
    public function mergeQueueObjects($queue1, $queue2)
    {

        $_base_entity_ids = array_merge_recursive(
            $queue1->getData('_base_entity_id'),
            $queue2->getData('_base_entity_id')
        );

        foreach ($_base_entity_ids as $k => $_base_entity_id) {
            $_base_entity_ids[$k] = array_unique($_base_entity_id);
        }

        $queue1->setData('_base_entity_id', $_base_entity_ids);
        return $queue1;
    }

    /**
     * Find Generate
     *
     * @param string $type
     * @return CreateInterface|null
     */
    public function findGenerator($type)
    {
        foreach ($this->generators as $generator) {
            if (strcasecmp($generator->createBy(), $type) !== 0) {
                continue;
            }

            return $generator;
        }

        return null;
    }

    /**
     * @return \TNW\Salesforce\Model\Queue[]
     */
    public function getQueues(): array
    {
        return $this->queues;
    }

    /**
     * @param \TNW\Salesforce\Model\Queue[] $queues
     */
    public function setQueues(array $queues): void
    {
        $this->queues = $queues;
    }
    /**
     * @param \TNW\Salesforce\Model\Queue[] $queues
     */
    public function addQueues(array $queues): void
    {
        foreach ($queues as $queue) {
            $this->queues[$queue->getIdentify()] = $queue;
        }
    }

}

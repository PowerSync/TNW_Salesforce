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
     * Queue constructor.
     * @param string $code
     * @param string $description
     * @param string $entityType
     * @param string $objectType
     * @param array $generators
     * @param \TNW\Salesforce\Model\QueueFactory $queueFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
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
     */
    public function createQueue($loadBy, $entityId, $baseEntityId, array $identifiers, array $additionalLoad = [])
    {
        return $this->queueFactory->create(['data' => [
            'code' => $this->code,
            'description' => $this->description($identifiers),
            'entity_type' => $this->entityType,
            'entity_id' => $entityId,
            'entity_load' => $loadBy,
            'entity_load_additional' => $additionalLoad,
            'object_type' => $this->objectType,
            'status' => 'new',
            '_base_entity_id' => $baseEntityId
        ]]);
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
     * @return \TNW\Salesforce\Model\Queue[]
     * @throws LocalizedException
     */
    public function generateQueues($loadBy, $entityIds, array $additional, $websiteId)
    {
        $generator = $this->findGenerator($loadBy);
        if ($generator instanceof CreateInterface) {
            return $generator->process($entityIds, $additional, [$this, 'createQueue'], $websiteId);
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
}

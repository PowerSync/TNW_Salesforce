<?php
namespace TNW\Salesforce\Synchronize\Unit;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use TNW\Salesforce\Model\Entity\SalesforceIdStorage;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Model\ResourceModel\Objects;
use TNW\Salesforce\Synchronize;

/**
 * Load
 */
class Load extends Synchronize\Unit\UnitAbstract
{
    /**
     * @var string
     */
    private $magentoType;

    /**
     * @var Queue[]
     */
    protected $queues;

    /**
     * @var LoadLoaderInterface[]
     */
    private $loaders;

    /**
     * @var IdentificationInterface
     */
    protected $identification;

    /**
     * @var HashInterface
     */
    private $hash;

    /**
     * @var SalesforceIdStorage
     */
    private $entityObject;

    /**
     * @var EntityLoaderAbstract[]
     */
    private $entityLoaders;

    /**
     * @var array
     */
    private $entityTypeMapping;

    /**
     * @var Objects
     */
    protected $objects;

    /**
     * Load constructor.
     * @param string $name
     * @param string $magentoType
     * @param Queue[] $queues
     * @param LoadLoaderInterface[] $loaders
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param Synchronize\Unit\HashInterface $hash
     * @param Objects $objects
     * @param SalesforceIdStorage|null $entityObject
     * @param EntityLoaderAbstract[] $entityLoaders
     * @param array $entityTypeMapping
     */
    public function __construct(
        $name,
        $magentoType,
        array $queues,
        array $loaders,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        Synchronize\Unit\HashInterface $hash,
        Objects $objects,
        SalesforceIdStorage $entityObject = null,
        array $entityLoaders = [],
        array $entityTypeMapping = []
    ) {
        parent::__construct($name, $units, $group);
        $this->magentoType = $magentoType;
        $this->queues = $queues;
        $this->loaders = $loaders;
        $this->identification = $identification;
        $this->hash = $hash;
        $this->objects = $objects;
        $this->entityObject = $entityObject;
        $this->entityLoaders = $entityLoaders;
        $this->entityTypeMapping = $entityTypeMapping;
    }

    /**
     * Identification
     *
     * @return IdentificationInterface
     */
    public function identification()
    {
        return $this->identification;
    }

    /**
     * Description
     */
    public function description()
    {
        return __('Loading Magento %1 ...', $this->magentoType);
    }

    /**
     * Process
     */
    public function process()
    {
        $this->cache['entities'] = $index = [];
        foreach ($this->queues as $queue) {
            try {
                $entity = $this->loadEntity($queue);
                if (empty($entity)) {
                    $message[] = __('QueueId item %1 is not available anymore', $queue->getId());
                    continue;
                }

                $entity_id = $entity->getData($entity->getIdFieldName());
                if (!isset($entity_id) && !$entity->getData('generated')) {
                    $syncDetails = __('The related Magento record is not available');
                    $message[] = $syncDetails;
                    $queue->setData('status', Queue::STATUS_SKIPPED);
                    $queue->setData('message', $syncDetails);
                    continue;
                }

                $entity->setData('_queue', $queue);

                $this->cache[$entity]['queue'] = $queue;

                foreach ($this->entityLoaders as $entityType => $entityLoader) {
                    $subEntity = $entityLoader->get($entity);
                    if (!empty($subEntity)) {
                        if (!empty($entityLoader->getSalesforceIdStorage()) && !$subEntity->getData($subEntity->getIdFieldName())) {
                            $salesforceIds = $this->objects->loadObjectIds(
                                $queue->getEntityId(),
                                $queue->getEntityLoad(),
                                $queue->getWebsiteId()
                            );
                            if ($salesforceIds) {
                                foreach (
                                    $entityLoader->getSalesforceIdStorage()->getMappingAttribute()
                                    as $salesforceIdName => $salesforceType
                                ) {
                                    if (isset($salesforceIds[$salesforceType])) {
                                        $subEntity->addData([$salesforceIdName => $salesforceIds[$salesforceType]]);
                                    }
                                }

                            }
                        }

                        $additional = [];
                        foreach ($queue->dependenciesByEntityType($this->entityTypeMap($entityType)) as $_queue) {
                            if (
                                (!$subEntity->getGenerated() && $subEntity->getId() != $_queue->getEntityId())
                                || ($subEntity->getGenerated() && $subEntity->getGeneratedEntityId() != $_queue->getEntityId())
                            ) {
                                continue;
                            }
                            foreach ($_queue->getAdditional() as $type => $id) {
                                $additional[$type][$id] = $id;
                            }
                        }
                        $additional = array_map(function($item) {
                            return is_array($item)? implode("\n", $item): $item;
                        }, $additional);
                        $subEntity->addData($additional);

                        $this->cache[$entity]['entities'][$entityType] = $subEntity;
                    }
                }

                if (null !== $this->entityObject && null !== $entity->getId()) {
                    $this->entityObject->load($entity, $entity->getData('config_website'));
                }

                $hash = $this->hash->calculateEntity($entity);
                if (isset($index[$hash])) {
                    $this->cache['duplicates'][$index[$hash]][] = $entity;
                    $entity = $index[$hash];
                }

                $index[$hash] = $this->cache['entities'][$entity] = $entity;
                $this->cache['websiteIds'][$entity] = $this->websiteId($entity);
                $message[] = __('Entity %1 loaded', $this->identification->printEntity($entity));
            } catch (Exception $e) {
                $syncDetails = sprintf(
                    'Magento entity loading error, queueId: %s. Error: %s',
                    $queue->getId(),
                    $e->getMessage()
                );
                $message[] = $syncDetails;
                $queue->setData('status', Queue::STATUS_ERROR);
                $queue->setData('message', $syncDetails);
            }
        }

        if (!empty($message)) {
            $this->group()->messageDebug(implode("\n", $message));
        }
    }

    /**
     * Website Id
     *
     * @param AbstractModel $entity
     * @return int
     */
    public function websiteId($entity)
    {
        return $entity->getData('config_website');
    }

    /**
     * Entity Type Map
     *
     * @param string $entityType
     * @return mixed
     */
    private function entityTypeMap($entityType)
    {
        if (empty($this->entityTypeMapping[$entityType])) {
            return $entityType;
        }

        return $this->entityTypeMapping[$entityType];
    }

    /**
     * Object By Entity Type
     *
     * @param AbstractModel $entity
     * @param string $entityType
     * @return AbstractModel|null
     */
    public function entityByType($entity, $entityType)
    {
        if (empty($this->cache[$entity]['entities'][$entityType])) {
            $this->group()->messageDebug('Undefined magento entity type %s', $entityType);
            return null;
        }

        return $this->cache[$entity]['entities'][$entityType];
    }

    /**
     * Load Entity
     *
     * @param Queue $queue
     * @return AbstractModel
     * @throws LocalizedException
     */
    public function loadEntity($queue)
    {
        $entity = $this->loaderBy($queue->getEntityLoad())
            ->load($queue->getEntityId(), $queue->getEntityLoadAdditional());

        if (!empty($entity)) {
            $entity->setData('config_website', $queue->getWebsiteId());
        }

        return $entity;
    }

    /**
     * Loader By
     *
     * @param string $type
     * @return LoadLoaderInterface
     * @throws LocalizedException
     */
    private function loaderBy($type)
    {
        foreach ($this->loaders as $loader) {
            if (strcasecmp($loader->loadBy(), $type) !== 0) {
                continue;
            }

            return $loader;
        }

        throw new LocalizedException(__('Unknown loader %1. Unit name %2', $type, $this->name()));
    }

    /**
     * Entities
     *
     * @return array
     */
    public function entities()
    {
        return $this->cache->get('entities');
    }

    /**
     * Skipped
     *
     * @param AbstractModel $entity
     * @return bool
     */
    public function skipped($entity)
    {
        return empty($this->cache['entities'][$entity]);
    }
}

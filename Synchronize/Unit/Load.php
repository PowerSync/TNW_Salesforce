<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Phrase;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Model\ResourceModel\Objects;
use TNW\Salesforce\Synchronize;

/**
 * Load
 */
class Load extends Synchronize\Unit\UnitAbstract
{
    /**
     * @var Queue[]
     */
    protected $queues;

    /**
     * @var LoadLoaderInterface[]
     */
    private $loaders;

    /**
     * @var HashInterface
     */
    private $hash;

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
     * @param Queue[] $queues
     * @param LoadLoaderInterface[] $loaders
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\HashInterface $hash
     * @param Objects $objects
     * @param EntityLoaderAbstract[] $entityLoaders
     * @param array $entityTypeMapping
     */
    public function __construct(
        $name,
        array $queues,
        array $loaders,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\HashInterface $hash,
        Objects $objects,
        array $entityLoaders = [],
        array $entityTypeMapping = []
    ) {
        parent::__construct($name, $units, $group);
        $this->queues = $queues;
        $this->loaders = $loaders;
        $this->hash = $hash;
        $this->objects = $objects;
        $this->entityLoaders = $entityLoaders;
        $this->entityTypeMapping = $entityTypeMapping;
    }

    /**
     * Description
     */
    public function description(): Phrase
    {
        return __('Loading Magento %1 ...', $this->units()->get('context')->getMagentoType());
    }

    /**
     * Process
     *
     * @throws LocalizedException
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
                                $queue->getEntityType(),
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

                        foreach ($queue->dependenciesByEntityType($this->entityTypeMap($entityType)) as $_queue) {
                            $subEntity->addData($_queue->getAdditional());
                        }

                        $this->cache[$entity]['entities'][$entityType] = $subEntity;
                    }
                }

                if (null !== $this->units()->get('context')->getSalesforceIdStorage() && null !== $entity->getId()) {
                    $this->units()->get('context')->getSalesforceIdStorage()
                        ->load($entity, $entity->getData('config_website'));
                }

                $hash = $this->hash->calculateEntity($entity);
                if (isset($index[$hash])) {
                    $this->cache['duplicates'][$index[$hash]][] = $entity;
                    $entity = $index[$hash];
                }

                $index[$hash] = $this->cache['entities'][$entity] = $entity;
                $this->cache['websiteIds'][$entity] = $this->websiteId($entity);
                $message[] = __(
                    'Entity %1 loaded',
                    $this->units()->get('context')->getIdentification()->printEntity($entity)
                );
            } catch (\Exception $e) {
                $this->group()->messageError(
                    'Magento entity loading error, queueId: %s. Error: %s',
                    $queue->getId(),
                    $e->getMessage()
                );
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
     * @return int|null
     */
    public function websiteId($entity): ?int
    {
        $websiteId =  $entity->getData('config_website');

        return isset($websiteId) ? (int)$websiteId : null;
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
    public function entityByType($entity, $entityType): ?AbstractModel
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
     * @return DataObject
     * @throws LocalizedException
     */
    public function loadEntity($queue): DataObject
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
    private function loaderBy($type): LoadLoaderInterface
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
    public function entities(): array
    {
        return $this->cache->get('entities');
    }

    /**
     * Skipped
     *
     * @param AbstractModel $entity
     * @return bool
     */
    public function skipped($entity): bool
    {
        return empty($this->cache['entities'][$entity]);
    }
}

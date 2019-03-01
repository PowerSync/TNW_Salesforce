<?php
namespace TNW\Salesforce\Synchronize\Unit;

use Magento\Framework\Exception\LocalizedException;
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
     * @var \TNW\Salesforce\Model\Queue[]
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
     * @var \TNW\Salesforce\Model\Entity\SalesforceIdStorage
     */
    private $entityObject;

    /**
     * @var EntityLoaderAbstract[]
     */
    private $entityLoaders;

    /**
     * Load constructor.
     * @param string $name
     * @param string $magentoType
     * @param \TNW\Salesforce\Model\Queue[] $queues
     * @param LoadLoaderInterface[] $loaders
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param Synchronize\Unit\HashInterface $hash
     * @param \TNW\Salesforce\Model\Entity\SalesforceIdStorage|null $entityObject
     * @param EntityLoaderAbstract[] $entityLoaders
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
        \TNW\Salesforce\Model\Entity\SalesforceIdStorage $entityObject = null,
        array $entityLoaders = []
    ) {
        parent::__construct($name, $units, $group);
        $this->magentoType = $magentoType;
        $this->queues = $queues;
        $this->loaders = $loaders;
        $this->identification = $identification;
        $this->hash = $hash;
        $this->entityObject = $entityObject;
        $this->entityLoaders = $entityLoaders;
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
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process()
    {
        $this->cache['entities'] = $index = [];
        foreach ($this->queues as $queue) {
            $entity = $this->loadEntity($queue);
            $this->cache[$entity]['queue'] = $queue;

            if (null !== $this->entityObject && null !== $entity->getId()) {
                $this->entityObject->load($entity, $entity->getData('config_website'));
            }

            $hash = $this->hash->calculateEntity($entity);
            if (isset($index[$hash])) {
                $this->cache['duplicates'][$index[$hash]][] = $entity;
                $entity = $index[$hash];
            }

            $this->cache['entities'][$entity] = $entity;
            $message[] = __('Entity %1 loaded', $this->identification->printEntity($entity));
        }

        if (!empty($message)) {
            $this->group()->messageDebug(implode("\n", $message));
        }
    }

    /**
     * Object By Entity Type
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @param string $entityType
     * @return \Magento\Framework\Model\AbstractModel|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function entityByType($entity, $entityType)
    {
        if (empty($this->entityLoaders[$entityType])) {
            $this->group()->messageDebug('Undefined magento entity type %s', $entityType);
            return null;
        }

        if (empty($this->cache[$entity]['entities'][$entityType])) {
            $this->cache[$entity]['entities'][$entityType]
                = $this->entityLoaders[$entityType]->get($entity);
        }

        return $this->cache[$entity]['entities'][$entityType];
    }

    /**
     * Load Entity
     *
     * @param \TNW\Salesforce\Model\Queue $queue
     * @return \Magento\Framework\Model\AbstractModel
     * @throws LocalizedException
     */
    public function loadEntity($queue)
    {
        return $this->loaderBy($queue->getEntityLoad())
            ->load($queue->getEntityId(), $queue->getEntityLoadAdditional())
            ->setData('config_website', $queue->getWebsiteId());
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
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return bool
     */
    public function skipped($entity)
    {
        return empty($this->cache['entities'][$entity]);
    }
}

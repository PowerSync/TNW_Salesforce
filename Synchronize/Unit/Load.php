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
     * @var LoaderInterface[]
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
     * Load constructor.
     * @param string $name
     * @param string $magentoType
     * @param \TNW\Salesforce\Model\Queue[] $queues
     * @param LoaderInterface[] $loaders
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param Synchronize\Unit\HashInterface $hash
     * @param \TNW\Salesforce\Model\Entity\SalesforceIdStorage|null $entityObject
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
        \TNW\Salesforce\Model\Entity\SalesforceIdStorage $entityObject = null
    ) {
        parent::__construct($name, $units, $group);
        $this->magentoType = $magentoType;
        $this->queues = $queues;
        $this->loaders = $loaders;
        $this->identification = $identification;
        $this->hash = $hash;
        $this->entityObject = $entityObject;
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
     * Load Entity
     *
     * @param \TNW\Salesforce\Model\Queue $queue
     * @return \Magento\Framework\Model\AbstractModel
     * @throws LocalizedException
     */
    public function loadEntity($queue)
    {
        return $this->loaderBy($queue->getEntityLoad())
            ->load($queue->getEntityId(), $queue->getEntityLoadAdditional());
    }

    /**
     * Loader By
     *
     * @param string $type
     * @return LoaderInterface
     * @throws LocalizedException
     */
    public function loaderBy($type)
    {
        foreach ($this->loaders as $loader) {
            if (strcasecmp($loader->loadBy(), $type) !== 0) {
                continue;
            }

            return $loader;
        }

        throw new LocalizedException(__('Unknown loader'));
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

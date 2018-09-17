<?php
namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Synchronize;

abstract class LoadByAbstract extends Synchronize\Unit\UnitAbstract
{

    /**
     * @var Synchronize\Units
     */
    private $load;

    /**
     * @var IdentificationInterface
     */
    protected $identification;

    public function __construct(
        $name,
        $load,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        array $dependents = []
    ) {
        parent::__construct($name, $units, $group, array_merge($dependents, [$load]));
        $this->load = $load;
        $this->identification = $identification;
    }

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return __('Loading related entities ...');
    }

    /**
     * @return IdentificationInterface
     */
    public function identification()
    {
        return $this->identification;
    }

    /**
     *
     */
    public function process()
    {
        $this->cache['entities'] = $index = [];
        foreach ($this->entities() as $parentEntity) {
            $loadByEntities = $this->loadByEntities($parentEntity);
            if (empty($loadByEntities)) {
                continue;
            }

            $message[] = __("Loaded entities by %1:",
                $this->load()->identification()->printEntity($parentEntity));

            foreach ($loadByEntities as $entity) {
                $message[] = __("\tentity %1",
                    $this->identification->printEntity($entity));

                $hash = $this->hashEntity($entity);

                if (isset($index[$hash])) {
                    $this->cache['duplicates'][$index[$hash]][] = $entity;
                    $entity = $index[$hash];
                }

                $index[$hash] = $this->cache['entities'][$entity] = $entity;
                $this->cache['parents'][$entity] = $parentEntity;

                $this->linked($parentEntity, $entity);
            }
        }

        if (!empty($message)) {
            $this->group()->messageDebug(implode("\n", $message));
        } else {
            $this->group()->messageDebug('Nothing loaded');
        }
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return \Magento\Framework\Model\AbstractModel[]
     */
    abstract public function loadByEntities($entity);

    /**
     * @param $entity
     * @return string
     */
    public function hashEntity($entity)
    {
        return spl_object_hash($entity);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $parentEntity
     * @param \Magento\Framework\Model\AbstractModel $entity
     */
    public function linked($parentEntity, $entity)
    {
        return;
    }

    /**
     * @return LoadAbstract
     */
    public function load()
    {
        return $this->unit($this->load);
    }

    /**
     * @return \Magento\Framework\Model\AbstractModel[]
     */
    public function entities()
    {
        return $this->load()->get('entities');
    }

    /**
     * @param $entity
     * @return bool
     */
    public function skipped($entity)
    {
        return empty($this->cache['entities'][$entity]);
    }
}
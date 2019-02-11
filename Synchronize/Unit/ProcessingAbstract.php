<?php
namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Synchronize;

/**
 * Processing Abstract
 */
abstract class ProcessingAbstract extends Synchronize\Unit\UnitAbstract
{
    /**
     * @var string
     */
    private $load;

    /**
     * @var Synchronize\Unit\IdentificationInterface
     */
    protected $identification;

    /**
     * ProcessingAbstract constructor.
     * @param string $name
     * @param string $load
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param IdentificationInterface $identification
     * @param array $dependents
     */
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
     * @inheritdoc
     */
    public function description()
    {
        return __('Processing %1', $this->name());
    }

    /**
     * Load
     *
     * @return Load|UnitInterface
     */
    public function load()
    {
        return $this->unit($this->load);
    }

    /**
     * Entities
     *
     * @return \Magento\Framework\Model\AbstractModel[]
     * @throws \OutOfBoundsException
     */
    public function entities()
    {
        return $this->load()->get('entities');
    }

    /**
     * Process
     *
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     */
    public function process()
    {
        foreach ($this->entities() as $entity) {
            $processing = $this->analize($entity);
            switch (true) {
                case $processing instanceof \Magento\Framework\Phrase:
                    $this->cache[$entity] = false;
                    $this->cache['message'][$entity] =
                        __('Entity %1 skipped because %2', $this->identification->printEntity($entity), $processing);
                    break;

                case !$processing:
                    $this->cache[$entity] = false;
                    $this->cache['message'][$entity]
                        = __('Entity %1 skipped', $this->identification->printEntity($entity));
                    break;

                default:
                    $this->cache[$entity] = true;
                    $this->cache['message'][$entity]
                        =  __('Entity %1 processed', $this->identification->printEntity($entity));
                    break;
            }
        }

        $message = $this->cache->get('message');
        if (!empty($message)) {
            $this->group()->messageDebug(implode("\n", $message));
        } else {
            $this->group()->messageDebug('Getting skipped');
        }
    }

    /**
     * Analize
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return bool|\Magento\Framework\Phrase
     */
    abstract public function analize($entity);
}

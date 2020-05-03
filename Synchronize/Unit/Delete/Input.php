<?php

namespace TNW\Salesforce\Synchronize\Unit\Delete;

use InvalidArgumentException;
use Magento\Framework\Model\AbstractModel;
use OutOfBoundsException;
use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Synchronize\Group;
use TNW\Salesforce\Synchronize\Transport\Calls\Delete\InputInterface as DeleteInterface;
use TNW\Salesforce\Synchronize\Transport\Calls\Delete\Transport\InputFactory;
use TNW\Salesforce\Synchronize\Unit\IdentificationInterface;
use TNW\Salesforce\Synchronize\Unit\Load;
use TNW\Salesforce\Synchronize\Unit\UnitInterface;
use TNW\Salesforce\Synchronize\Units;

/**
 * Delete Input
 */
class Input extends Synchronize\Unit\UnitAbstract
{
    /**
     * @var string
     */
    protected $load;

    /** @var InputFactory */
    protected $inputFactory;

    /**
     * @var DeleteInterface
     */
    protected $process;

    /** @var IdentificationInterface */
    protected $identification;

    /**
     * Delete constructor.
     * @param $name
     * @param $load
     * @param array $dependents
     * @param Units $units
     * @param Group $group
     * @param InputFactory $inputFactory
     * @param DeleteInterface $process
     * @param IdentificationInterface $identification
     */
    public function __construct(
        $name,
        $load,
        array $dependents,
        Units $units,
        Group $group,
        InputFactory $inputFactory,
        DeleteInterface $process,
        IdentificationInterface $identification
    )
    {
        parent::__construct($name, $units, $group, array_merge($dependents, [$load]));
        $this->load = $load;
        $this->inputFactory = $inputFactory;
        $this->process = $process;
        $this->identification = $identification;
    }

    /**
     * @inheritdoc
     */
    public function description()
    {
        return __('Look for the items to delete.');
    }

    /**
     * Process
     *
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException
     */
    public function process()
    {
        $input = $this->createTransport();

        $this->processInput($input);

        if ($input->count() === 0) {
            $this->group()->messageDebug('Upsert SKIPPED, input is empty');
            return;
        }

        $this->group()->messageDebug(implode("\n", array_map(function ($entity) use ($input) {
            return __(
                "Entity %1 request data:\n%2",
                $this->identification->printEntity($entity),
                print_r($input->offsetGet($entity), true)
            );
        }, $this->entities())));
        $this->group()->messageDebug("Delete. Data:\n%s", $input);

        /**
         *
         */
        $this->process->process($input);
    }

    /**
     * Create Transport
     *
     * @return Synchronize\Transport\Calls\Delete\Transport\Input
     */
    public function createTransport()
    {
        return $this->inputFactory->create();
    }

    /**
     * Process Input
     *
     * @param Synchronize\Transport\Calls\Delete\Transport\Input $input
     * @throws OutOfBoundsException
     */
    protected function processInput(Synchronize\Transport\Calls\Delete\Transport\Input $input)
    {
        foreach ($this->entities() as $entity) {
            $input->offsetSet($entity, $this->unit('mapping')->get('%s', $entity));
        }
    }

    /**
     * Entities
     *
     * @return array
     * @throws OutOfBoundsException
     */
    protected function entities()
    {
        return array_filter($entities = $this->unit('load')->get('entities'), [$this, 'filter']);
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
     * Skipped
     *
     * @param AbstractModel $entity
     * @return bool
     */
    public function skipped($entity)
    {
        return false;
    }

    /**
     * Filter
     *
     * @param object $entity
     * @return bool
     * @throws OutOfBoundsException
     */
    protected function filter($entity)
    {
        return !$this->unit('mapping')->skipped($entity);
    }
}

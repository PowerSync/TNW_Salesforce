<?php
namespace TNW\Salesforce\Synchronize\Unit\Upsert;

use TNW\Salesforce\Synchronize;

class Output extends Synchronize\Unit\UnitAbstract implements Synchronize\Unit\CheckInterface
{
    /**
     * @var Synchronize\Transport\Calls\Upsert\OutputFactory
     */
    private $outputFactory;

    /**
     * @var Synchronize\Unit\IdentificationInterface
     */
    protected $identification;

    /**
     * @var Synchronize\Transport\Calls\Upsert\OutputInterface
     */
    private $process;

    /**
     * @var string
     */
    private $load;

    /**
     * @var string
     */
    private $salesforceType;

    /**
     * @var
     */
    private $fieldSalesforceId;

    /**
     * Upsert constructor.
     *
     * @param string $name
     * @param string $load
     * @param string $salesforceType
     * @param string $fieldSalesforceId
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param Synchronize\Transport\Calls\Upsert\OutputFactory $outputFactory
     * @param Synchronize\Transport\Calls\Upsert\OutputInterface $process
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        $salesforceType,
        $fieldSalesforceId,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        Synchronize\Transport\Calls\Upsert\OutputFactory $outputFactory,
        Synchronize\Transport\Calls\Upsert\OutputInterface $process,
        array $dependents = []
    ) {
        parent::__construct($name, $units, $group, array_merge($dependents, [$load]));

        $this->load = $load;
        $this->salesforceType = $salesforceType;
        $this->fieldSalesforceId = $fieldSalesforceId;
        $this->identification = $identification;
        $this->outputFactory = $outputFactory;
        $this->process = $process;
    }

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return __('Upserting "%1" entity', $this->salesforceType);
    }

    /**
     * @inheritdoc
     */
    public function load()
    {
        return $this->unit($this->load);
    }

    /**
     * @return string
     */
    public function salesforceType()
    {
        return $this->salesforceType;
    }

    /**
     * @return string
     */
    public function fieldSalesforceId()
    {
        return $this->fieldSalesforceId;
    }

    /**
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     */
    public function process()
    {
        $output = $this->createTransport();
        $this->process->process($output);

        $this->group()->messageDebug(implode("\n", array_map(function($entity) use($output) {
            return __(
                "Entity %1 response data:\n%2",
                $this->identification->printEntity($entity),
                print_r($output->offsetGet($entity), true)
            );
        }, $this->entities())));

        $this->processOutput($output);
    }

    /**
     * @return Synchronize\Transport\Calls\Upsert\Output
     */
    public function createTransport()
    {
        $output = $this->outputFactory->create(['type' => $this->salesforceType()]);
        foreach ($this->entities() as $entity) {
            $output->offsetSet($entity, []);
        }

        return $output;
    }

    /**
     * @return array
     * @throws \OutOfBoundsException
     */
    public function entities()
    {
        return array_filter($this->load()->get('entities'), [$this, 'filter']);
    }

    /**
     * @param $entity
     * @return bool
     */
    public function filter($entity)
    {
        return !in_array(true, array_map(function ($unit) use($entity) {
            return $this->unit($unit)->skipped($entity);
        }, $this->dependents()), true);
    }

    /**
     * @param Synchronize\Transport\Calls\Upsert\Output $output
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     */
    protected function processOutput(Synchronize\Transport\Calls\Upsert\Output $output)
    {
        foreach ($this->entities() as $entity) {
            if (empty($output[$entity]['success'])) {
                $this->group()->messageError('Upsert object "%s". Entity: %s. Message: "%s".',
                    $this->salesforceType, $this->identification->printEntity($entity), $output[$entity]['message']);
            }

            $this->cache[$entity] = $output[$entity];
            $this->prepare($entity);
        }
    }

    /**
     * @param \Magento\Framework\DataObject $entity
     */
    public function prepare($entity)
    {
        if (empty($this->cache[$entity]['success'])) {
            return;
        }

        $entity->setData($this->fieldSalesforceId, $this->cache[$entity]['salesforce']);
    }

    /**
     * @param $entity
     * @return bool
     */
    public function skipped($entity)
    {
        return empty($this->cache[$entity]['success']);
    }
}

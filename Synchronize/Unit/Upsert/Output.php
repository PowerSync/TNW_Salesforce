<?php
namespace TNW\Salesforce\Synchronize\Unit\Upsert;

use TNW\Salesforce\Synchronize;

/**
 * Upsert Output
 */
class Output extends Synchronize\Unit\UnitAbstract implements Synchronize\Unit\FieldModifierInterface
{
    /**
     * @var Synchronize\Transport\Calls\Upsert\Transport\OutputFactory
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
    private $upsertInput;

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
     * @param string $upsertInput
     * @param string $salesforceType
     * @param string $fieldSalesforceId
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param Synchronize\Transport\Calls\Upsert\Transport\OutputFactory $outputFactory
     * @param Synchronize\Transport\Calls\Upsert\OutputInterface $process
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        $upsertInput,
        $salesforceType,
        $fieldSalesforceId,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        Synchronize\Transport\Calls\Upsert\Transport\OutputFactory $outputFactory,
        Synchronize\Transport\Calls\Upsert\OutputInterface $process,
        array $dependents = []
    ) {
        parent::__construct($name, $units, $group, array_merge($dependents, [$load, $upsertInput]));

        $this->load = $load;
        $this->salesforceType = $salesforceType;
        $this->fieldSalesforceId = $fieldSalesforceId;
        $this->identification = $identification;
        $this->outputFactory = $outputFactory;
        $this->process = $process;
        $this->upsertInput = $upsertInput;
    }

    /**
     * @inheritdoc
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
     * Upsert Input
     *
     * @return Synchronize\Unit\UnitInterface
     */
    public function upsertInput()
    {
        return $this->unit($this->upsertInput);
    }

    /**
     * Salesforce Type
     *
     * @return string
     */
    public function salesforceType()
    {
        return $this->salesforceType;
    }

    /**
     * Field Salesforce Id
     *
     * @return string
     */
    public function fieldSalesforceId()
    {
        return $this->fieldSalesforceId;
    }

    /**
     * Process
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     */
    public function process()
    {
        $output = $this->createTransport();
        $this->process->process($output);

        $this->group()->messageDebug(implode("\n", array_map(function ($entity) use ($output) {
            return __(
                "Entity %1 response data:\n%2",
                $this->identification->printEntity($entity),
                print_r($output->offsetGet($entity), true)
            );
        }, $this->entities())));

        $this->processOutput($output);
    }

    /**
     * Create Transport
     *
     * @return Synchronize\Transport\Calls\Upsert\Transport\Output
     */
    public function createTransport()
    {
        $output = $this->outputFactory->create(['type' => $this->salesforceType()]);
        foreach ($this->entities() as $entity) {
            $output->offsetSet($entity, [
                'success' => false,
                'created' => false,
                'message' => __('Processing error')->render()
            ]);
        }

        return $output;
    }

    /**
     * Entities
     *
     * @return array
     * @throws \OutOfBoundsException
     */
    public function entities()
    {
        return array_filter($this->load()->get('entities'), [$this, 'filter']);
    }

    /**
     * Filter
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return bool
     */
    public function filter($entity)
    {
        return !in_array(true, array_map(function ($unit) use ($entity) {
            return $this->unit($unit)->skipped($entity);
        }, $this->dependents()), true);
    }

    /**
     * Process Output
     *
     * @param Synchronize\Transport\Calls\Upsert\Transport\Output $output
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     */
    protected function processOutput(Synchronize\Transport\Calls\Upsert\Transport\Output $output)
    {
        foreach ($this->entities() as $entity) {
            if (empty($output[$entity]['skipped']) &&
                empty($output[$entity]['waiting']) &&
                empty($output[$entity]['success'])
            ) {
                $this->group()->messageError(
                    'Upsert object "%s". Entity: %s. Message: "%s".',
                    $this->salesforceType,
                    $this->identification->printEntity($entity),
                    $output[$entity]['message']
                );
            }

            $this->cache[$entity] = $output[$entity];
            $this->prepare($entity);
        }
    }

    /**
     * Prepare
     *
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
     * Skipped
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return bool
     */
    public function skipped($entity)
    {
        return empty($this->cache[$entity]['success']);
    }
}

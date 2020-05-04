<?php

namespace TNW\Salesforce\Synchronize\Unit\Delete;

use InvalidArgumentException;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;
use OutOfBoundsException;
use RuntimeException;
use TNW\Salesforce\Synchronize;

/**
 * Delete Output
 */
class Output extends Synchronize\Unit\UnitAbstract implements Synchronize\Unit\FieldModifierInterface
{
    /**
     * @var Synchronize\Transport\Calls\Delete\Transport\OutputFactory
     */
    protected $outputFactory;

    /**
     * @var Synchronize\Unit\IdentificationInterface
     */
    protected $identification;

    /**
     * @var Synchronize\Transport\Calls\Delete\OutputInterface
     */
    protected $process;

    /**
     * @var string
     */
    protected $load;

    /**
     * @var
     */
    protected $fieldSalesforceId;

    /**
     * @var string
     */
    private $salesforceType;

    /**
     * @var string
     */
    private $deleteInput;

    /**
     * Output constructor.
     * @param string $name
     * @param string $load
     * @param string $deleteInput
     * @param string $fieldSalesforceId
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param Synchronize\Transport\Calls\Delete\Transport\OutputFactory $outputFactory
     * @param Synchronize\Transport\Calls\Delete\OutputInterface $process
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        $salesforceType,
        $deleteInput,
        $fieldSalesforceId,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        Synchronize\Transport\Calls\Delete\Transport\OutputFactory $outputFactory,
        Synchronize\Transport\Calls\Delete\OutputInterface $process,
        array $dependents = []
    )
    {
        parent::__construct($name, $units, $group, $dependents);

        $this->load = $load;
        $this->salesforceType = $salesforceType;
        $this->deleteInput = $deleteInput;
        $this->fieldSalesforceId = $fieldSalesforceId;
        $this->identification = $identification;
        $this->outputFactory = $outputFactory;
        $this->process = $process;
    }

    /**
     * @inheritdoc
     */
    public function description()
    {
        return __('Delete entity');
    }

    /**
     * Upsert Input
     *
     * @return Synchronize\Unit\UnitInterface
     */
    public function deleteInput()
    {
        return $this->unit($this->deleteInput);
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
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException
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
     * @return Synchronize\Transport\Calls\Delete\Transport\Output
     */
    public function createTransport()
    {
        $output = $this->outputFactory->create(['type' => $this->salesforceType()]);
        $output->setUnit($this);

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
     * @throws OutOfBoundsException
     */
    public function entities()
    {
        return array_filter($this->load()->get('entities'), [$this, 'filter']);
    }

    /**
     * @inheritdoc
     */
    public function load()
    {
        return $this->unit($this->load);
    }

    /**
     * Process Output
     *
     * @param Synchronize\Transport\Calls\Delete\Transport\Output $output
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException
     */
    protected function processOutput(Synchronize\Transport\Calls\Delete\Transport\Output $output)
    {
        foreach ($this->entities() as $entity) {
            if (empty($output[$entity]['skipped']) &&
                empty($output[$entity]['waiting']) &&
                empty($output[$entity]['success'])
            ) {
                $this->group()->messageError(
                    'Delete object. Entity: %s. Message: "%s".',
                    $this->identification->printEntity($entity),
                    $output[$entity]['message']
                );
            }

            $this->cache[$entity] = $output[$entity];
//            $this->prepare($entity);
        }
    }

    /**
     * Prepare
     *
     * @param DataObject $entity
     */
    public function prepare($entity)
    {
        if (empty($this->cache[$entity]['success']) || empty($this->cache->get('%s/salesforce', $entity))) {
            return;
        }

        $entity->setData($this->fieldSalesforceId, $this->cache->get('%s/salesforce', $entity));
    }

    /**
     * Filter
     *
     * @param AbstractModel $entity
     * @return bool
     */
    public function filter($entity)
    {
        return !in_array(true, array_map(function ($unit) use ($entity) {
            return $this->unit($unit)->skipped($entity);
        }, $this->dependents()), true);
    }

    /**
     * Skipped
     *
     * @param AbstractModel $entity
     * @return bool
     */
    public function skipped($entity)
    {
        return empty($this->cache[$entity]['success']);
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
}

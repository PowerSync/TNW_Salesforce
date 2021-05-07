<?php
namespace TNW\Salesforce\Synchronize\Unit\Upsert;

use InvalidArgumentException;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;
use OutOfBoundsException;
use RuntimeException;
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
     * @var Synchronize\Transport\Calls\Upsert\OutputInterface
     */
    private $process;

    /**
     * @var array
     */
    protected $additionalSalesforceId;

    /**
     * Upsert constructor.
     *
     * @param string $name
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Transport\Calls\Upsert\Transport\OutputFactory $outputFactory
     * @param Synchronize\Transport\Calls\Upsert\OutputInterface $process
     * @param array $dependents
     * @param array $additionalSalesforceId
     */
    public function __construct(
        $name,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Transport\Calls\Upsert\Transport\OutputFactory $outputFactory,
        Synchronize\Transport\Calls\Upsert\OutputInterface $process,
        array $dependents = [],
        array $additionalSalesforceId = []
    ) {
        parent::__construct($name, $units, $group, array_merge($dependents, ['load', 'upsertInput']));

        $this->additionalSalesforceId = $additionalSalesforceId;
        $this->outputFactory = $outputFactory;
        $this->process = $process;
    }

    /**
     * @inheritdoc
     */
    public function description()
    {
        return __('Upserting "%1" entity', $this->units()->get('context')->getSalesforceType());
    }

    /**
     * @inheritdoc
     */
    public function load()
    {
        return $this->unit('load');
    }

    /**
     * Upsert Input
     *
     * @return Synchronize\Unit\UnitInterface
     */
    public function upsertInput()
    {
        return $this->unit('upsertInput');
    }

    /**
     * Field Salesforce Id
     *
     * @return string
     */
    public function fieldSalesforceId()
    {
        return $this->units()->get('context')->getFieldSalesforceId();
    }

    /**
     * @inheridoc
     */
    public function additionalSalesforceId()
    {
        return $this->additionalSalesforceId;
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
                $this->units()->get('context')->getIdentification()->printEntity($entity),
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
        $output = $this->outputFactory->create(['type' => $this->units()->get('context')->getSalesforceType()]);
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
     * Process Output
     *
     * @param Synchronize\Transport\Calls\Upsert\Transport\Output $output
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException
     */
    public function processOutput(Synchronize\Transport\Calls\Upsert\Transport\Output $output)
    {
        foreach ($this->entities() as $entity) {
            if (empty($output[$entity]['skipped']) &&
                empty($output[$entity]['waiting']) &&
                empty($output[$entity]['success'])
            ) {
                $this->group()->messageError(
                    'Upsert object "%s". Entity: %s. Message: "%s".',
                    $this->units()->get('context')->getSalesforceType(),
                    $this->units()->get('context')->getIdentification()->printEntity($entity),
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
     * @param DataObject $entity
     */
    public function prepare($entity)
    {
        if (empty($this->cache[$entity]['success']) || empty($this->cache->get('%s/salesforce', $entity))) {
            return;
        }

        $entity->setData($this->fieldSalesforceId(), $this->cache->get('%s/salesforce', $entity));
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
}

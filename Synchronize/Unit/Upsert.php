<?php
namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Synchronize;

class Upsert extends Synchronize\Unit\UnitAbstract implements Synchronize\Unit\CheckInterface
{
    /**
     * @var Synchronize\Transport\Calls\Upsert\Input
     */
    protected $input;

    /**
     * @var Synchronize\Transport\Calls\Upsert\Output
     */
    protected $output;

    /**
     * @var Synchronize\Unit\IdentificationInterface
     */
    protected $identification;

    /**
     * @var Synchronize\Transport\Calls\UpsertInterface
     */
    private $process;

    /**
     * @var \TNW\Salesforce\Synchronize\Transport\Soap\ClientFactory
     */
    protected $factory;

    /**
     * @var string
     */
    private $load;

    /**
     * @var string
     */
    private $mapping;

    /**
     * @var string
     */
    private $salesforceType;

    /**
     * @var array
     */
    protected $objectDescription = [];

    /** @var   */
    protected $localeDate;

    /**
     * @var
     */
    private $fieldSalesforceId;

    /**
     * Upsert constructor.
     *
     * @param string $name
     * @param string $load
     * @param string $mapping
     * @param string $salesforceType
     * @param string $fieldSalesforceId
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param IdentificationInterface $identification
     * @param Synchronize\Transport\Calls\Upsert\InputFactory $inputFactory
     * @param Synchronize\Transport\Calls\Upsert\OutputFactory $outputFactory
     * @param Synchronize\Transport\Calls\UpsertInterface $process
     * @param Synchronize\Transport\Soap\ClientFactory $factory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     */
    public function __construct(
        $name,
        $load,
        $mapping,
        $salesforceType,
        $fieldSalesforceId,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        Synchronize\Transport\Calls\Upsert\InputFactory $inputFactory,
        Synchronize\Transport\Calls\Upsert\OutputFactory $outputFactory,
        Synchronize\Transport\Calls\UpsertInterface $process,
        \TNW\Salesforce\Synchronize\Transport\Soap\ClientFactory $factory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        parent::__construct($name, $units, $group, [$load, $mapping]);
        $this->input = $inputFactory->create(['type' => $salesforceType]);
        $this->output = $outputFactory->create(['type' => $salesforceType]);
        $this->process = $process;
        $this->load = $load;
        $this->mapping = $mapping;
        $this->salesforceType = $salesforceType;
        $this->fieldSalesforceId = $fieldSalesforceId;
        $this->identification = $identification;

        $this->factory = $factory;
        $this->localeDate = $localeDate;
    }

    /**
     * @param $websiteId
     * @return \TNW\Salesforce\Lib\Tnw\SoapClient\Client
     */
    protected function getClient($websiteId = null)
    {
        return $this->factory->client($websiteId);
    }

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return __('Upserting "%1" entity', $this->salesforceType);
    }

    /**
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     */
    public function process()
    {
        $this->processInput();
        if ($this->input->count() === 0) {
            $this->group()->messageDebug('Upsert SKIPPED, input is empty');
            return;
        }

        $this->group()->messageDebug(implode("\n", array_map(function($entity) {
            return __("Entity %1 request data:\n%2",
                $this->identification->printEntity($entity),
                print_r($this->input->offsetGet($entity), true));
        }, $this->entities())));

        $this->process->process($this->input, $this->output);

        $this->group()->messageDebug(implode("\n", array_map(function($entity) {
            return __("Entity %1 response data:\n%2",
                $this->identification->printEntity($entity),
                print_r($this->output->offsetGet($entity), true));
        }, $this->entities())));

        $this->processOutput();
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     */
    protected function processInput()
    {
        foreach ($this->entities() as $entity) {
            $this->input->offsetSet($entity, $this->prepareObject($entity, $this->unit($this->mapping)->get('%s', $entity)));
        }
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
     * @param $entity
     * @return bool
     */
    public function filter($entity)
    {
        return !$this->unit($this->mapping)->skipped($entity);
    }

    /**
     *
     */
    protected function getObjectDescription()
    {

        if (empty($this->objectDescription[$this->salesforceType])) {
            $resultObjects = $this->getClient()->describeSObjects([$this->salesforceType]);
            $this->objectDescription[$this->salesforceType] = $resultObjects[0];

        }

        return $this->objectDescription[$this->salesforceType];
    }

    /**
     * @param $entity
     * @param array $object
     * @return array
     */
    protected function prepareObject($entity, array $object)
    {
        $objectDescription = $this->getObjectDescription();
        foreach ($objectDescription->getFields() as $fieldProperty) {
            $fieldName = (string)$fieldProperty->getName();
            if (!isset($object[$fieldName]) || $fieldName == 'Id') {
                continue;
            }

            $value = $object[$fieldName];

            if (!$fieldProperty->isCreateable() && empty($object['Id'])) {
                $this->group()->messageNotice('Salesforce field "%s" is not creatable, value sync skipped.', $fieldName);
                unset($object[$fieldName]);
                continue;
            }

            if (!$fieldProperty->isUpdateable() && !empty($object['Id'])) {
                $this->group()->messageNotice('Salesforce field "%s" is not updateable, value sync skipped.', $fieldName);
                unset($object[$fieldName]);
                continue;
            }

            if (in_array($fieldProperty->getType(), ['datetime', 'date'])) {
                try {
                    if (!$object[$fieldName] instanceof \DateTime) {
                        $object[$fieldName] = new \DateTime($value);
                    }

                    if (strcasecmp($fieldProperty->getType(), 'date') === 0) {
                        $object[$fieldName]->setTimezone(new \DateTimeZone($this->localeDate->getConfigTimezone()));
                    }

                    if ($object[$fieldName]->getTimestamp() <= 0) {
                        $this->group()->messageDebug('Date field "%s" is empty', $fieldName);
                        unset($object[$fieldName]);
                    }
                } catch (\Exception $e) {
                    $this->group()->messageDebug('Field "%s" incorrect datetime format: %s', $fieldName, $value);
                    unset($object[$fieldName]);
                }
            } elseif (
                is_string($value)
                && $fieldProperty->getLength()
                && $fieldProperty->getLength() < strlen($value)
            ) {
                $this->group()->messageNotice('Salesforce field "%s" value truncated.', $fieldName);
                $limit = $fieldProperty->getLength();
                $object[$fieldName] = mb_substr($value, 0, $limit - 3) . '...';

            }

        }
        return $object;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     */
    protected function processOutput()
    {
        foreach ($this->entities() as $entity) {
            if (empty($this->output[$entity]['success'])) {
                $this->group()->messageError('Upsert object "%s". Entity: %s. Message: "%s".',
                    $this->salesforceType, $this->identification->printEntity($entity), $this->output[$entity]['message']);
            }

            $this->cache[$entity] = $this->output[$entity];
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
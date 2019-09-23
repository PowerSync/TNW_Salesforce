<?php
namespace TNW\Salesforce\Synchronize\Unit\Upsert;

use TNW\Salesforce\Synchronize;

/**
 * Upsert Input
 */
class Input extends Synchronize\Unit\UnitAbstract
{
    /**
     * @var Synchronize\Unit\IdentificationInterface
     */
    protected $identification;

    /**
     * @var Synchronize\Transport\Calls\Upsert\InputInterface
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
     * @var string
     */
    private $lookup;

    /** @var [] */
    private $compareFields;

    /**
     * @var array
     */
    protected $objectDescription = [];

    /** @var   */
    protected $localeDate;

    /**
     * @var Synchronize\Transport\Calls\Upsert\Transport\InputFactory
     */
    private $inputFactory;

    /**
     * Upsert constructor.
     *
     * @param string $name
     * @param string $load
     * @param string $mapping
     * @param string $salesforceType
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param Synchronize\Transport\Calls\Upsert\Transport\InputFactory $inputFactory
     * @param Synchronize\Transport\Calls\Upsert\InputInterface $process
     * @param Synchronize\Transport\Soap\ClientFactory $factory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     */
    public function __construct(
        $name,
        $load,
        $mapping,
        $salesforceType,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        Synchronize\Transport\Calls\Upsert\Transport\InputFactory $inputFactory,
        Synchronize\Transport\Calls\Upsert\InputInterface $process,
        \TNW\Salesforce\Synchronize\Transport\Soap\ClientFactory $factory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        $lookup = null,
        $compareFields = []
    ) {
        parent::__construct($name, $units, $group, [$load, $mapping]);
        $this->process = $process;
        $this->load = $load;
        $this->mapping = $mapping;
        $this->salesforceType = $salesforceType;
        $this->lookup = $lookup;
        $this->compareFields = $compareFields;
        $this->identification = $identification;
        $this->inputFactory = $inputFactory;

        $this->factory = $factory;
        $this->localeDate = $localeDate;
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
     * Salesforce Type
     *
     * @return string
     */
    public function salesforceType()
    {
        return $this->salesforceType;
    }

    /**
     * Process
     *
     * @throws \Magento\Framework\Exception\LocalizedException
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

        $this->process->process($input);
    }

    /**
     * Create Transport
     *
     * @return Synchronize\Transport\Calls\Upsert\Transport\Input
     */
    public function createTransport()
    {
        return $this->inputFactory->create(['type' => $this->salesforceType()]);
    }

    /**
     * Process Input
     *
     * @param Synchronize\Transport\Calls\Upsert\Transport\Input $input
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function processInput(Synchronize\Transport\Calls\Upsert\Transport\Input $input)
    {
        foreach ($this->entities() as $entity) {
            $input->offsetSet($entity, $this->prepareObject($entity, $this->unit($this->mapping)->get('%s', $entity)));
        }
    }

    /**
     * Entities
     *
     * @return array
     * @throws \OutOfBoundsException
     */
    public function entities()
    {
        $entities = array_filter($this->load()->get('entities'), [$this, 'filter']);
        $entities = array_filter($entities, [$this, 'actual']);

        return $entities;
    }

    /**
     * Filter
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return bool
     */
    public function filter($entity)
    {
        return !$this->unit($this->mapping)->skipped($entity);
    }

    /**
     * Find Field Property
     *
     * @param string $fieldName
     * @return \Tnw\SoapClient\Result\DescribeSObjectResult\Field|false
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function findFieldProperty($fieldName)
    {
        if (empty($this->objectDescription[$this->salesforceType])) {
            //TODO: Cache Field
            $resultObjects = $this->factory->client()->describeSObjects([$this->salesforceType]);
            $this->objectDescription[$this->salesforceType] = $resultObjects[0];
        }

        return $this->objectDescription[$this->salesforceType]->getField($fieldName);
    }

    /**
     * Prepare Object
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @param array $object
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareObject($entity, array $object)
    {
        foreach (array_keys($object) as $fieldName) {
            if ($fieldName === 'Id') {
                continue;
            }

            $fieldProperty = $this->findFieldProperty($fieldName);
            if (!$fieldProperty instanceof \Tnw\SoapClient\Result\DescribeSObjectResult\Field) {
                $this->group()
                    ->messageNotice('Salesforce field "%s" does not exist, value sync skipped.', $fieldName);
                unset($object[$fieldName]);
                continue;
            }

            if (empty($object['Id']) && !$fieldProperty->isCreateable()) {
                $this->group()
                    ->messageNotice('Salesforce field "%s" is not creatable, value sync skipped.', $fieldName);
                unset($object[$fieldName]);
                continue;
            }

            if (!empty($object['Id']) && !$fieldProperty->isUpdateable()) {
                $this->group()
                    ->messageNotice('Salesforce field "%s" is not updateable, value sync skipped.', $fieldName);
                unset($object[$fieldName]);
                continue;
            }

            if (in_array($fieldProperty->getType(), ['datetime', 'date'])) {
                try {
                    if (!$object[$fieldName] instanceof \DateTime) {
                        $object[$fieldName] = date_create($object[$fieldName]);
                    }

                    if (strcasecmp($fieldProperty->getType(), 'date') === 0) {
                        $object[$fieldName]->setTimezone(timezone_open($this->localeDate->getConfigTimezone()));
                    }

                    if ($object[$fieldName]->getTimestamp() <= 0) {
                        $this->group()->messageDebug('Date field "%s" is empty', $fieldName);
                        unset($object[$fieldName]);
                    }
                } catch (\Exception $e) {
                    $this->group()->messageDebug(
                        'Field "%s" incorrect datetime format: %s',
                        $fieldName,
                        $object[$fieldName]
                    );
                    unset($object[$fieldName]);
                }
            } elseif (
                is_string($object[$fieldName])
                && $fieldProperty->getLength()
                && $fieldProperty->getLength() < strlen($object[$fieldName])
            ) {
                $this->group()->messageNotice('Salesforce field "%s" value truncated.', $fieldName);
                $limit = $fieldProperty->getLength();
                $object[$fieldName] = mb_substr($object[$fieldName], 0, $limit - 3) . '...';
            }
        }

        return $object;
    }

    /**
     * actual
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return bool
     */
    public function actual($entity)
    {
        if (!$this->compareFields || empty($this->lookup)) {
            return true;
        }

        $mappedObject = $this->unit($this->mapping)->get('%s', $entity);
        $lookupObject = $this->unit($this->lookup)->get('%s/record', $entity);

        if (empty($lookupObject)) {
            return true;
        }

        foreach ($this->compareFields as $compareField) {
            if ($mappedObject[$compareField] != $lookupObject[$compareField]) {
                return true;
            }
        }

        $this->cache[$entity]['message']
            = __('Entity %1 has actual data in the Salesforce already', $this->identification->printEntity($entity));

        $this->group()->messageDebug('Entity %1 has actual data in the Salesforce already', $this->identification->printEntity($entity));

        return false;
    }

    /**
     * Skipped
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return bool
     */
    public function skipped($entity)
    {
        return false;
    }
}

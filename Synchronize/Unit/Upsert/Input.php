<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Unit\Upsert;

use DateTime;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use OutOfBoundsException;
use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Synchronize\Transport\Soap\ClientFactory;
use Tnw\SoapClient\Result\DescribeSObjectResult\Field;

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
     * @var ClientFactory
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

    /** @var */
    protected $localeDate;

    /**
     * @var Synchronize\Transport\Calls\Upsert\Transport\InputFactory
     */
    private $inputFactory;

    /**
     * Input constructor.
     * @param string $name
     * @param string $load
     * @param string $mapping
     * @param string $salesforceType
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param Synchronize\Transport\Calls\Upsert\Transport\InputFactory $inputFactory
     * @param Synchronize\Transport\Calls\Upsert\InputInterface $process
     * @param ClientFactory $factory
     * @param TimezoneInterface $localeDate
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
        ClientFactory $factory,
        TimezoneInterface $localeDate
    ) {
        parent::__construct($name, $units, $group, [$load, $mapping]);
        $this->process = $process;
        $this->load = $load;
        $this->mapping = $mapping;
        $this->salesforceType = $salesforceType;
        $this->identification = $identification;
        $this->inputFactory = $inputFactory;

        $this->factory = $factory;
        $this->localeDate = $localeDate;
    }

    /**
     * @return Synchronize\Transport\Calls\Upsert\InputInterface
     */
    public function getProcess()
    {
        return $this->process;
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
    public function salesforceType(): string
    {
        return (string)$this->salesforceType;
    }

    /**
     * Process
     *
     * @throws LocalizedException
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
     * @throws LocalizedException
     */
    public function processInput(Synchronize\Transport\Calls\Upsert\Transport\Input $input)
    {
        foreach ($this->entities() as $entity) {
            $input->offsetSet($entity, $this->prepareObject($entity, $this->unit($this->mapping)->get('%s', $entity)));
        }
    }

    /**
     * Entities
     *
     * @return array
     * @throws OutOfBoundsException
     */
    public function entities()
    {
        $entities = $this->load()->get('entities') ?? [];
        $entities = array_filter($entities, [$this, 'filter']);
        $entities = array_filter($entities, [$this, 'needUpdate']);

        return $entities;
    }

    /**
     * Filter
     *
     * @param AbstractModel $entity
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
     * @return Field|false
     * @throws LocalizedException
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
     * @param $fieldProperty
     * @param $fieldName
     * @param $object
     * @return bool
     */
    public function checkFieldProperty($fieldProperty, $fieldName, $object)
    {
        switch (true) {
            case (!$fieldProperty instanceof Field):
                $this->group()
                    ->messageDebug('Salesforce field "%s" does not exist, value sync skipped.', $fieldName);
                break;
            case (empty($object['Id']) && !$fieldProperty->isCreateable()):
                $this->group()
                    ->messageDebug('Salesforce field "%s" is not creatable, value sync skipped.', $fieldName);
                break;

            case (!empty($object['Id']) && !$fieldProperty->isUpdateable()):
                $this->group()
                    ->messageDebug('Salesforce field "%s" is not updateable, value sync skipped.', $fieldName);
                break;
            default:
                return true;
        }

        return false;
    }

    /**
     * Prepare Object
     *
     * @param AbstractModel $entity
     * @param array $object
     * @return array
     * @throws LocalizedException
     */
    public function prepareObject($entity, array $object)
    {
        foreach (array_keys($object) as $fieldName) {
            if ($fieldName === 'Id') {
                continue;
            }

            $fieldProperty = $this->findFieldProperty($fieldName);

            if (!$this->checkFieldProperty($fieldProperty, $fieldName, $object)) {
                unset($object[$fieldName]);
                continue;
            }

            if (in_array($fieldProperty->getType(), ['datetime', 'date'])) {
                try {
                    if (!$object[$fieldName] instanceof DateTime) {
                        $object[$fieldName] = date_create((string)($object[$fieldName] ?? ''));
                    }

                    if ($object[$fieldName] instanceof DateTime) {
                        if (strcasecmp($fieldProperty->getType(), 'datetime') === 0) {
                            // case: if Magento field contains DATE i.e. without hours, but the SF field is DateTime
                            if ($object[$fieldName]->format('H:i:s') == '00:00:00') {
                                $object[$fieldName] = new DateTime(
                                    $object[$fieldName]->format('Y-m-d'),
                                    timezone_open($this->localeDate->getConfigTimezone())
                                );
                            }
                        }

                        if ($object[$fieldName] instanceof DateTime && $object[$fieldName]->getTimestamp() <= 0) {
                            $this->group()->messageDebug('Date field "%s" is empty', $fieldName);
                            unset($object[$fieldName]);
                        }
                    } else {
                        unset($object[$fieldName]);
                    }
                } catch (Exception $e) {
                    $this->group()->messageDebug(
                        'Field "%s" incorrect datetime format: %s',
                        $fieldName,
                        $object[$fieldName]
                    );
                    unset($object[$fieldName]);
                }
            } elseif (isset($object[$fieldName]) &&
                (string)$fieldProperty->getSoapType() === 'xsd:double'
            ) {
                $object[$fieldName] = (float)$object[$fieldName];
            } elseif (is_string($object[$fieldName])) {
                $object[$fieldName] = trim((string)$object[$fieldName]);
                if ($object[$fieldName] === '') {
                    $object[$fieldName] = null;

                    if ($fieldName === 'OwnerId' && !$object[$fieldName]) {
                        $this->group()->messageDebug('"%s" field is empty. Removing it from request.', $fieldName);
                        unset($object[$fieldName]);
                    }

                    continue;
                }

                $value = (string)($object[$fieldName] ?? '');
                if ($fieldProperty->getLength()
                    && $fieldProperty->getLength() < strlen($value)
                ) {
                    $limit = $fieldProperty->getLength();
                    if ($fieldProperty->getType() === 'reference') {
                        $object[$fieldName] = mb_strcut($value, 0, $limit);
                    } else {
                        $this->group()->messageNotice('Salesforce field "%s" value truncated.', $fieldName);
                        $object[$fieldName] = mb_strcut($value, 0, $limit - 3) . '...';
                    }
                }
            }
        }

        return $object;
    }

    /**
     * @param $entity
     * @return bool
     * @throws LocalizedException
     */
    public function needUpdate($entity)
    {
        $lookup = $this->unit('lookup');

        if (empty($lookup) || !$this->unit('upsertOutput')) {
            return true;
        }

        $mappedObject = $this->unit($this->mapping)->get('%s', $entity);
        $mappedObject = (object)$this->prepareObject($entity, (array)$mappedObject);

        $lookupObject = $lookup->get('%s/record', $entity);

        if (empty($lookupObject)) {
            return true;
        }

        foreach ($mappedObject as $compareField => $compareValue) {
            if (in_array($compareField, $this->unit($this->mapping)->getCompareIgnoreFields())) {
                continue;
            }

            if ($compareValue instanceof \DateTime && !empty($lookupObject[$compareField]) && $lookupObject[$compareField] instanceof \DateTime) {
                $fieldProperty = $this->findFieldProperty($compareField);
                if (strcasecmp($fieldProperty->getType(), 'date') === 0) {
                    $compareValue
                        ->setTimezone($lookupObject[$compareField]->getTimezone())
                        ->setTime(0, 0);
                }
            }

            if ($this->isFieldValueChanged($entity, $compareField, $compareValue)) {
                $this->group()->messageDebug('Entity %1 has changed field: %2 = %3', $this->identification->printEntity($entity), $compareField, $compareValue);
                return true;
            }
        }

        $fieldName = $this->unit('upsertOutput')->additionalSalesforceId();
        $fieldName['Id'] = $this->unit('upsertOutput')->fieldSalesforceId();

        foreach ($fieldName as $sfkey => $mKey) {
            if (!empty($lookupObject[$sfkey])) {
                $entity->setData($mKey, $lookupObject[$sfkey]);
            }
        }

        $this->cache[$entity]['updated'] = true;
        $this->cache[$entity]['salesforce'] = $lookupObject['Id'];
        $this->cache[$entity]['message']
            = __('Synchronization of the %1 was skipped, data is Salesforce matches the data in Magento.', $this->identification->printEntity($entity));

        $this->group()->messageDebug('Synchronization of the %1 was skipped, data is Salesforce matches the data in Magento.', $this->identification->printEntity($entity));

        return false;
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
     * @param \Magento\Framework\DataObject $entity
     * @param string $compareField
     * @param mixed $compareValue
     * @return bool
     */
    protected function isFieldValueChanged($entity, $compareField, $compareValue): bool
    {
        $lookup = $this->unit('lookup');
        $lookupObject = $lookup->get('%s/record', $entity);

        return (empty($lookupObject[$compareField]) && !empty($compareValue))
            || (!empty($lookupObject[$compareField]) && $compareValue != $lookupObject[$compareField]);
    }
}

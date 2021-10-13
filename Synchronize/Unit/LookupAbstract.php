<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit;

use InvalidArgumentException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Phrase;
use OutOfBoundsException;
use TNW\Salesforce\Model\ResourceModel\Mapper\Collection;
use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Synchronize\Unit\Upsert\Input;

/**
 * Lookup Abstract
 */
abstract class LookupAbstract extends Synchronize\Unit\UnitAbstract
{
    protected $skipMappingFields = false;

    /**
     * @var Synchronize\Transport\Calls\Query\InputFactory
     */
    protected $inputFactory;

    /**
     * @var Synchronize\Transport\Calls\Query\OutputFactory
     */
    protected $outputFactory;

    /**
     * @var Synchronize\Transport\Calls\Query\Input
     */
    protected $input;

    /**
     * @var Synchronize\Transport\Calls\Query\Output
     */
    protected $output;

    /**
     * @var Synchronize\Transport\Calls\QueryInterface
     */
    protected $process;

    /**
     * LookupAbstract constructor.
     * @param string $name
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Transport\Calls\Query\InputFactory $inputFactory
     * @param Synchronize\Transport\Calls\Query\OutputFactory $outputFactory
     * @param Synchronize\Transport\Calls\QueryInterface $process
     * @param bool $skipMappingFields
     * @param array $dependents
     */
    public function __construct(
        $name,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Transport\Calls\Query\InputFactory $inputFactory,
        Synchronize\Transport\Calls\Query\OutputFactory $outputFactory,
        Synchronize\Transport\Calls\QueryInterface $process,
        array $dependents = [],
        $skipMappingFields = false
    ) {
        parent::__construct($name, $units, $group, array_merge($dependents, ['load']));

        $this->inputFactory = $inputFactory;
        $this->outputFactory = $outputFactory;
        $this->process = $process;
        $this->skipMappingFields = $skipMappingFields;
    }

    /**
     * @inheritdoc
     */
    public function description(): Phrase
    {
        return __('Trying to locate entity ...');
    }

    /**
     * Input
     *
     * @return Synchronize\Transport\Calls\Query\Input
     */
    public function input(): Synchronize\Transport\Calls\Query\Input
    {
        return $this->input;
    }

    /**
     * Output
     *
     * @return Synchronize\Transport\Calls\Query\Output
     */
    public function output(): Synchronize\Transport\Calls\Query\Output
    {
        return $this->output;
    }

    /**
     * Process
     *
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException
     */
    public function process()
    {
        $this->input = $this->inputFactory->create();
        $this->output = $this->outputFactory->create();

        $this->processInput();
        $this->addMappingFieldsToSelect();

        if ($this->input->count() === 0) {
            $this->group()->messageDebug('Lookup skipped');
            return;
        }

        $this->group()->messageDebug("Query request:\n%s", $this->input);
        $this->process->process($this->input, $this->output);
        $this->group()->messageDebug("Query response:\n%s", $this->output);

        $this->processOutput();
    }


    /**
     * Process Input
     */
    abstract public function processInput();

    /**
     * @return Synchronize\Unit\UnitInterface|null
     */
    public function getMappingUnit(): ?UnitInterface
    {
        return $this->unit('mapping');
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addMappingFieldsToSelect()
    {
        if ($this->skipMappingFields) {
            return;
        }

        /** @var Input $upsertInput */
        $upsertInput = $this->unit('upsertInput');
        $mapping = [];

        if ($this->getMappingUnit()) {

            /** emulate lookup complete to load Update/Upsert mapping */
            $this->unit('lookup')->forceStatus(self::COMPLETE);

            foreach ($this->entities() as $entity) {
                $entity->setForceUpdateOnly(true);

                /** @var Collection $mapping */
                $mapping = $this->getMappingUnit()->mappers($entity);

                $entity->setForceUpdateOnly(false);
                break;
            }

            /** stop lookup complete emulation */
            $this->unit('lookup')->restoreStatus();
        }

        $definedColumns = $this->input->columns;
        // TODO : change it to the compareIgnoreFields as defined for \TNW\Salesforce\Synchronize\Unit\Upsert\Input
        $definedColumns[] = 'tnw_mage_enterp__disableMagentoSync__c';

        $definedColumns = array_map('strtolower', $definedColumns);

        foreach ($mapping as $map) {

            /** check if field is correct, available */
            if ($upsertInput) {
                $fieldName = $map->getSalesforceAttributeName();
                $fieldProperty = $upsertInput->findFieldProperty($fieldName);
                if (!$upsertInput->checkFieldProperty($fieldProperty, $fieldName, ['Id' => true])) {
                    continue;
                }
            }

            $salesforceAttributeName = strtolower((string)$map->getSalesforceAttributeName());
            if (!in_array($salesforceAttributeName, $definedColumns)) {
                $this->input->columns[] = $map->getSalesforceAttributeName();
                $definedColumns[] = $salesforceAttributeName;
            }
        }
    }

    /**
     * Process Output
     *
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException
     */
    public function processOutput()
    {
        $searchIndex = $this->collectIndex();
        foreach ($this->entities() as $entity) {
            $recordsPriority = $this->searchPriorityOrder($searchIndex, $entity);
            ksort($recordsPriority, SORT_NUMERIC);

            array_walk_recursive($recordsPriority, function (&$record) {
                $record = $this->output[$record];
            });

            if (count($recordsPriority) === 0) {
                continue;
            }

            $this->cache[$entity]['records'] = $this->mergeLookupResult($recordsPriority);

            $record = $this->filterByPriority($recordsPriority, $entity);
            if (empty($record)) {
                continue;
            }

            $this->cache[$entity]['record'] = $this->prepareRecord($record);
            $message[] = __(
                "Found %1 entity and the following data:\n%2",
                $this->units()->get('context')->getIdentification()->printEntity($entity),
                print_r($record, true)
            );
        }

        $this->cache['allRecords'] = iterator_to_array($this->output);
        if (!empty($message)) {
            $this->group()->messageDebug(implode("\n", $message));
        }
    }

    /**
     * @param $recordsPriority
     * @return array
     */
    public function mergeLookupResult($recordsPriority): array
    {
        $result = [];
        foreach ($recordsPriority as $priorityKey => $records) {
            $records = array_map([$this, 'prepareRecord'], $records);
            foreach ($records as $record) {
                $result[$record['Id']] = $record;
            }
        }
        $result = array_values($result);

        return  $result;
    }

    /**
     * Prepare Record
     *
     * @param array $record
     * @return array
     */
    public function prepareRecord(array $record): array
    {
        return $record;
    }

    /**
     * Load
     *
     * @return Load|UnitInterface
     */
    public function load()
    {
        return $this->unit('load');
    }

    /**
     * Entities
     *
     * @return AbstractModel[]
     */
    public function entities(): array
    {
        return array_filter($this->load()->get('entities'), [$this, 'filter']);
    }

    /**
     * Filter
     *
     * @param AbstractModel $entity
     * @return bool
     * @throws OutOfBoundsException
     */
    public function filter($entity): bool
    {
        return !in_array(true, array_map(function ($unit) use ($entity) {
            return $this->unit($unit)->skipped($entity);
        }, $this->dependents()), true);
    }

    /**
     * Collect Index
     *
     * @return array
     */
    abstract public function collectIndex(): array;

    /**
     * Search PriorityOrder
     *
     * @param array $searchIndex
     * @param AbstractModel $entity
     * @return array
     */
    abstract public function searchPriorityOrder(array $searchIndex, $entity): array;

    /**
     * Filter By Priority
     *
     * @param array $recordsPriority
     * @param AbstractModel $entity
     * @return array
     */
    public function filterByPriority(array $recordsPriority, $entity): ?array
    {
        $findRecord = null;
        foreach ($recordsPriority as $records) {
            foreach ($records as $record) {
                $findRecord = $record;
                break 2;
            }

            if (!empty($findRecord)) {
                break;
            }
        }

        return $findRecord;
    }

    /**
     * Skipped
     *
     * @param object $entity
     * @return bool
     */
    public function skipped($entity): bool
    {
        return false;
    }
}

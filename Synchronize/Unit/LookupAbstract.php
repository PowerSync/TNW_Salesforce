<?php
namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Synchronize;

abstract class LookupAbstract extends Synchronize\Unit\UnitAbstract
{
    /**
     * @var string
     */
    private $load;

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
     * @var IdentificationInterface
     */
    protected $identification;

    public function __construct(
        $name,
        $load,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        Synchronize\Transport\Calls\Query\InputFactory $inputFactory,
        Synchronize\Transport\Calls\Query\OutputFactory $outputFactory,
        Synchronize\Transport\Calls\QueryInterface $process,
        array $dependents = []
    ) {
        parent::__construct($name, $units, $group, array_merge($dependents, [$load]));

        $this->load = $load;
        $this->input = $inputFactory->create();
        $this->output = $outputFactory->create();
        $this->process = $process;
        $this->identification = $identification;
    }

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return __('Trying to locate entity ...');
    }

    /**
     * @return LoadAbstract|LoadByAbstract
     */
    public function load()
    {
        return $this->unit($this->load);
    }

    /**
     * @return Synchronize\Transport\Calls\Query\Input
     */
    public function input()
    {
        return $this->input;
    }

    /**
     * @return Synchronize\Transport\Calls\Query\Output
     */
    public function output()
    {
        return $this->output;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     */
    public function process()
    {
        $this->processInput();
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
     *
     */
    abstract public function processInput();

    /**
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     */
    protected function processOutput()
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

            $this->cache[$entity]['records']
                = array_map([$this, 'prepareRecord'], array_replace(...$recordsPriority));

            $record = $this->filterByPriority($recordsPriority, $entity);
            if (empty($record)) {
                continue;
            }

            $this->cache[$entity]['record'] = $this->prepareRecord($record);
            $message[] = __("Found %1 entity and the following data:\n%2",
                $this->identification->printEntity($entity), print_r($record, true));
        }

        $this->cache['allRecords'] = iterator_to_array($this->output);
        if (!empty($message)) {
            $this->group()->messageDebug(implode("\n", $message));
        }
    }

    /**
     * @param array $record
     * @return array
     */
    protected function prepareRecord(array $record)
    {
        return $record;
    }

    /**
     * @return \Magento\Framework\Model\AbstractModel[]
     */
    public function entities()
    {
        return array_filter($this->unit($this->load)->get('entities'), [$this, 'filter']);
    }

    /**
     * @param $entity
     * @return bool
     * @throws \OutOfBoundsException
     */
    public function filter($entity)
    {
        return !in_array(true, array_map(function ($unit) use($entity) {
            return $this->unit($unit)->skipped($entity);
        }, $this->dependents()), true);
    }

    /**
     * @return array
     */
    abstract public function collectIndex();

    /**
     * @param array $searchIndex
     * @param $entity
     * @return array
     */
    abstract public function searchPriorityOrder(array $searchIndex, $entity);

    /**
     * @param array $recordsPriority
     * @param $entity
     * @return array
     */
    public function filterByPriority(array $recordsPriority, $entity)
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
     * @param object $entity
     * @return bool
     */
    public function skipped($entity)
    {
        return false;
    }
}

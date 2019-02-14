<?php
namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize;

/**
 * Unit Status
 */
class Status extends Synchronize\Unit\UnitAbstract
{
    /**
     * @var string
     */
    private $load;

    /**
     * @var string
     */
    private $upsertOutput;

    /**
     * Status constructor.
     * @param string $name
     * @param string $load
     * @param string $upsertOutput
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        $upsertOutput,
        Synchronize\Units $units,
        Synchronize\Group $group,
        array $dependents = []
    ) {
        parent::__construct($name, $units, $group, array_merge($dependents, [$load, $upsertOutput]));
        $this->load = $load;
        $this->upsertOutput = $upsertOutput;
    }

    /**
     * @inheritdoc
     */
    public function description()
    {
        return __('Status queue ...');
    }

    /**
     * Process
     */
    public function process()
    {
        $upsertOutput = $this->upsertOutput();
        foreach ($this->entities() as $entity) {
            switch (true) {
                case $upsertOutput->get('%s', $entity) === null:
                    $this->cache[$entity]['status'] = Queue::STATUS_SKIPPED;
                    continue 2;

                case $upsertOutput->get('%s/waiting', $entity) === true:
                    $this->cache[$entity]['status'] = Queue::STATUS_WAITING_UPSERT;
                    continue 2;

                case $upsertOutput->get('%s/success', $entity) === true:
                    $this->cache[$entity]['status'] = Queue::STATUS_COMPLETE;
                    continue 2;

                default:
                    $this->cache[$entity]['status'] = Queue::STATUS_ERROR;
                    $this->cache[$entity]['message'] = $upsertOutput->get('%s/message', $entity);
                    continue 2;
            }
        }

        foreach ($this->entities() as $entity) {
            $this->queue($entity)
                ->addData(iterator_to_array($this->cache[$entity]));

            foreach ((array)$this->load()->get('duplicates/%s', $entity) as $duplicate) {
                $this->queue($duplicate)->addData(iterator_to_array($this->cache[$entity]));
            }
        }
    }

    /**
     * Queue
     *
     * @param object $entity
     * @return Queue
     */
    public function queue($entity)
    {
        return $this->load()->get('%s/queue', $entity);
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
     * Upsert Output
     *
     * @return Upsert\Output|UnitInterface
     */
    public function upsertOutput()
    {
        return $this->unit($this->upsertOutput);
    }

    /**
     * Entities
     *
     * @return object[]
     * @throws \OutOfBoundsException
     */
    protected function entities()
    {
        return $this->load()->get('entities');
    }
}

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
     * @var \TNW\Salesforce\Model\Entity\SalesforceIdStorage|null
     */
    private $salesforceIdStorage;

    /**
     * Status constructor.
     * @param string $name
     * @param string $load
     * @param string $upsertOutput
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param \TNW\Salesforce\Model\Entity\SalesforceIdStorage $salesforceIdStorage
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        $upsertOutput,
        Synchronize\Units $units,
        Synchronize\Group $group,
        \TNW\Salesforce\Model\Entity\SalesforceIdStorage $salesforceIdStorage = null,
        array $dependents = []
    ) {
        parent::__construct($name, $units, $group, array_merge($dependents, [$load, $upsertOutput]));
        $this->load = $load;
        $this->upsertOutput = $upsertOutput;
        $this->salesforceIdStorage = $salesforceIdStorage;
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
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process()
    {
        $upsertOutput = $this->upsertOutput();
        foreach ($this->entities() as $entity) {
            switch (true) {
                case $upsertOutput->get('%s', $entity) === null:
                    $this->cache[$entity]['status'] = Queue::STATUS_SKIPPED;
                    continue 2;

                case $upsertOutput->get('%s/skipped', $entity) === true:
                    $this->cache[$entity]['status'] = Queue::STATUS_SKIPPED;
                    $this->cache[$entity]['message'] = $upsertOutput->upsertInput()->get('%s/message', $entity);
                    continue 2;

                case $upsertOutput->get('%s/waiting', $entity) === true:
                    $this->cache[$entity]['status'] = Queue::STATUS_WAITING_UPSERT;
                    $this->cache[$entity]['message'] = $upsertOutput->upsertInput()->get('%s/message', $entity);
                    continue 2;

                case $upsertOutput->get('%s/success', $entity) === true:
                    if (null !== $this->salesforceIdStorage) {
                        $this->salesforceIdStorage->saveStatus($entity, 1, $entity->getData('config_website'));
                    }
                    $this->cache[$entity]['status'] = Queue::STATUS_COMPLETE;
                    $this->cache[$entity]['message'] = $upsertOutput->upsertInput()->get('%s/message', $entity);
                    continue 2;

                default:
                    if (null !== $this->salesforceIdStorage) {
                        $this->salesforceIdStorage->saveStatus($entity, 0, $entity->getData('config_website'));
                    }

                    $this->cache[$entity]['status'] = Queue::STATUS_ERROR;
                    $this->cache[$entity]['message'] = $upsertOutput->get('%s/message', $entity);
                    continue 2;
            }
        }

        foreach ($this->entities() as $entity) {
            $this->load()->get('%s/queue', $entity)
                ->addData(iterator_to_array($this->cache[$entity]));

            foreach ((array)$this->load()->get('duplicates/%s', $entity) as $duplicate) {
                $this->load()->get('%s/queue', $duplicate)
                    ->addData(iterator_to_array($this->cache[$entity]));
            }
        }
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

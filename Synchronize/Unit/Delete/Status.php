<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Delete;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use OutOfBoundsException;
use TNW\Salesforce\Model\Entity\SalesforceIdStorage;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Synchronize\Unit\UnitInterface;

/**
 * Unit Status
 */
class Status extends Synchronize\Unit\UnitAbstract
{

    /**
     * @var SalesforceIdStorage|null
     */
    private $salesforceIdStorage;

    /**
     * Status constructor.
     * @param string $name
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param SalesforceIdStorage $salesforceIdStorage
     * @param array $dependents
     */
    public function __construct(
        $name,
        Synchronize\Units $units,
        Synchronize\Group $group,
        SalesforceIdStorage $salesforceIdStorage = null,
        array $dependents = []
    )
    {
        parent::__construct($name, $units, $group, array_merge($dependents, ['load', 'deleteOutput']));
        $this->salesforceIdStorage = $salesforceIdStorage;
    }

    /**
     * @inheritdoc
     */
    public function description(): Phrase
    {
        return __('Status queue ...');
    }

    /**
     * Process
     *
     * @throws LocalizedException
     */
    public function process()
    {
        $deleteOutput = $this->deleteOutput();

        foreach ($this->entities() as $entity) {
            $this->processResult($entity, $deleteOutput);
        }

        foreach ($this->entities() as $entity) {
            $this->load()->get('%s/queue', $entity)
                ->addData(iterator_to_array($this->cache[$entity]));
        }
    }

    /**
     * Delete Output
     * @return UnitInterface|null
     *
     */
    public function deleteOutput(): ?UnitInterface
    {
        return $this->unit('deleteOutput');
    }

    /**
     * Entities
     *
     * @return object[]
     * @throws OutOfBoundsException
     */
    protected function entities(): array
    {
        return $this->load()->get('entities');
    }

    /**
     * Load
     *
     * @return UnitInterface|null
     */
    public function load(): ?UnitInterface
    {
        return $this->unit('load');
    }

    /**
     * @param $entity
     * @param $deleteOutput Output
     * @throws LocalizedException
     */
    public function processResult($entity, $deleteOutput)
    {
        switch (true) {
            case !empty($this->getAllEntityError($entity)):
                $this->cache[$entity]['status'] = Queue::STATUS_ERROR;
                $this->cache[$entity]['message'] = implode("<br />\n", $this->getAllEntityError($entity));
                break;
            case $deleteOutput->get('%s', $entity) === null:
                $this->cache[$entity]['status'] = Queue::STATUS_SKIPPED;
                break;

            case $deleteOutput->get('%s/skipped', $entity) === true:
                $this->cache[$entity]['status'] = $deleteOutput->deleteInput()->get('%s/updated', $entity) ? Queue::STATUS_COMPLETE : Queue::STATUS_SKIPPED;
                $this->cache[$entity]['message'] = $deleteOutput->deleteInput()->get('%s/message', $entity);
                break;

            case $deleteOutput->get('%s/waiting', $entity) === true:
                $this->cache[$entity]['status'] = Queue::STATUS_WAITING_UPSERT;
                $this->cache[$entity]['message'] = $deleteOutput->deleteInput()->get('%s/message', $entity);
                break;

            case $deleteOutput->get('%s/success', $entity) === true:
                if (null !== $this->salesforceIdStorage) {
                    $this->salesforceIdStorage->saveStatus($entity, 1, $entity->getData('config_website'));
                }
                $this->cache[$entity]['status'] = Queue::STATUS_COMPLETE;
                $this->cache[$entity]['message'] = $deleteOutput->deleteInput()->get('%s/message', $entity);
                break;

            default:
                if (null !== $this->salesforceIdStorage) {
                    $this->salesforceIdStorage->saveStatus($entity, 0, $entity->getData('config_website'));
                }

                $this->cache[$entity]['status'] = Queue::STATUS_ERROR;
                $this->cache[$entity]['message'] = $deleteOutput->get('%s/message', $entity);
                break;
        }
    }
}

<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer;

use TNW\Salesforce\Synchronize;

class Check extends Synchronize\Unit\Check
{

    /**
     * @var string
     */
    private $load;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    public function __construct(
        $name,
        $load,
        array $upsert,
        Synchronize\Units $units,
        Synchronize\Group $group,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        array $process = [],
        array $dependents = []
    ) {
        parent::__construct($name, $upsert, $units, $group, $process, $dependents);
        $this->indexerRegistry = $indexerRegistry;
        $this->load = $load;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @throws \OutOfBoundsException
     */
    protected function postProcess()
    {
        $checkSuccess = [];
        foreach ($this->entities() as $entity) {
            if (null === $entity->getId()) {
                continue;
            }

            $entity->setData('sforce_sync_status', !empty($this->cache[$entity]['success']));
            $entity->getResource()->saveAttribute($entity, 'sforce_sync_status');

            $checkSuccess[$entity->getId()] = $entity->getData('sforce_sync_status');
        }

        $this->indexerRegistry->get(\Magento\Customer\Model\Customer::CUSTOMER_GRID_INDEXER_ID)
            ->reindexList(array_keys($checkSuccess));

        $this->group()->messageDebug("Save attribute \"sforce_sync_status\":\n%s", $checkSuccess);
    }

    /**
     * @return \Magento\Customer\Model\Customer[]
     */
    protected function entities()
    {
        return $this->unit($this->load)->get('entities');
    }
}
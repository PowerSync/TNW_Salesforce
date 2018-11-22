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
     * @var \TNW\Salesforce\Model\Entity\Object
     */
    private $entityObject;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * Check constructor.
     *
     * @param string $name
     * @param string $load
     * @param array $upsert
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param \TNW\Salesforce\Model\Entity\Object $entityObject
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param array $process
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        array $upsert,
        Synchronize\Units $units,
        Synchronize\Group $group,
        \TNW\Salesforce\Model\Entity\Object $entityObject,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        array $process = [],
        array $dependents = []
    ) {
        parent::__construct($name, $upsert, $units, $group, $process, $dependents);
        $this->indexerRegistry = $indexerRegistry;
        $this->load = $load;
        $this->entityObject = $entityObject;
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
            $this->entityObject->saveStatus($entity, !empty($this->cache[$entity]['success']));

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

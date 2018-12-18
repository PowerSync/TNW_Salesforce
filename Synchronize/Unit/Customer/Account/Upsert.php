<?php

namespace TNW\Salesforce\Synchronize\Unit\Customer\Account;

use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Synchronize\Unit\Customer;
use TNW\Salesforce\Model;

class Upsert extends Synchronize\Unit\Upsert
{
    /**
     * @var Model\Customer\Config
     */
    private $customerConfig;

    /**
     * @var string
     */
    private $salesforceType;

    /**
     * @var array
     */
    private $upsertEntities;

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
        Model\Customer\Config $customerConfig,
        \TNW\Salesforce\Synchronize\Transport\Soap\ClientFactory $factory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    )
    {

        parent::__construct(
            $name,
            $load,
            $mapping,
            $salesforceType,
            $fieldSalesforceId,
            $units,
            $group,
            $identification,
            $inputFactory,
            $outputFactory,
            $process,
            $factory,
            $localeDate
        );

        $this->customerConfig = $customerConfig;
        $this->salesforceType = $salesforceType;
    }

    /**
     *
     */
    protected function processInput()
    {
        parent::processInput();

        // deDuplicate
        $index = [];
        $duplicates = [];
        for ($this->input->rewind(); $this->input->valid(); $this->input->next()) {
            $entity = $this->input->current();

            $hash = $this->hashObject($this->input->getInfo());
            if (empty($index[$hash])) {
                $index[$hash] = $entity;
                continue;
            }

            $this->upsertEntities[spl_object_hash($entity)] = $index[$hash];
            $duplicates[] = $entity;
        }

        /**
         * remove duplicated entities
         */
        foreach ($duplicates as $duplicate) {
            $this->input->offsetUnset($duplicate);
        }
    }

    /**
     * @param array $object
     * @return string
     */
    public function hashObject($object)
    {
        return empty($object['Id'])
            ? $object['Name'] : $object['Id'];
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     */
    protected function processOutput()
    {
        // restore deDuplicate
        foreach ($this->entities() as $entity) {
            $upsertEntity = isset($this->upsertEntities[spl_object_hash($entity)])
                ? $this->upsertEntities[spl_object_hash($entity)] : $entity;

            if (empty($this->output[$upsertEntity]['success'])) {
                $this->group()->messageError('Upsert object "%s". Entity: %s. Message: "%s".',
                    $this->salesforceType, $this->identification->printEntity($entity), $this->output[$upsertEntity]['message']);
            }

            $this->cache[$entity] = $this->output[$upsertEntity];
            $this->prepare($entity);
        }
    }

    /**
     * @param \Magento\Customer\Model\Customer $entity
     * @param array $object
     * @return array
     */
    protected function prepareObject($entity, array $object)
    {
        if (!empty($object['Id']) && !$this->customerConfig->canRenameAccount()) {
            unset($object['Name']);
        }

        return parent::prepareObject($entity, $object);
    }
}

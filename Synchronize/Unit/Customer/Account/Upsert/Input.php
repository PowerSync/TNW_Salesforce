<?php

namespace TNW\Salesforce\Synchronize\Unit\Customer\Account\Upsert;

use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Model;

class Input extends Synchronize\Unit\Upsert\Input
{
    /**
     * @var Model\Customer\Config
     */
    private $customerConfig;

    public function __construct(
        $name,
        $load,
        $mapping,
        $salesforceType,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        Synchronize\Transport\Calls\Upsert\InputFactory $inputFactory,
        Synchronize\Transport\Calls\Upsert\InputInterface $process,
        Model\Customer\Config $customerConfig,
        \TNW\Salesforce\Synchronize\Transport\Soap\ClientFactory $factory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        parent::__construct(
            $name,
            $load,
            $mapping,
            $salesforceType,
            $units,
            $group,
            $identification,
            $inputFactory,
            $process,
            $factory,
            $localeDate
        );

        $this->customerConfig = $customerConfig;
    }

    /**
     *
     */
    protected function processInput(Synchronize\Transport\Calls\Upsert\Input $input)
    {
        parent::processInput($input);

        // deDuplicate
        $index = [];
        $duplicates = [];
        for ($input->rewind(); $input->valid(); $input->next()) {
            $entity = $input->current();

            $hash = $this->hashObject($input->getInfo());
            if (empty($index[$hash])) {
                $index[$hash] = $entity;
                continue;
            }

            $upsertEntities[spl_object_hash($entity)] = $index[$hash];
            $duplicates[] = $entity;
        }

        //TODO: Save
        $upsertEntities;

        /**
         * remove duplicated entities
         */
        foreach ($duplicates as $duplicate) {
            $input->offsetUnset($duplicate);
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
     * @param \Magento\Customer\Model\Customer $entity
     * @param array $object
     * @return array
     */
    public function prepareObject($entity, array $object)
    {
        if (!empty($object['Id']) && !$this->customerConfig->canRenameAccount()) {
            unset($object['Name']);
        }

        return parent::prepareObject($entity, $object);
    }
}

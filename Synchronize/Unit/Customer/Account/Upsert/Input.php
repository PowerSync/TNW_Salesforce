<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer\Account\Upsert;

use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Model;

/**
 * Upsert Input
 */
class Input extends Synchronize\Unit\Upsert\Input
{
    /**
     * @var Model\Customer\Config
     */
    private $customerConfig;

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
     * @param Model\Customer\Config $customerConfig
     * @param Synchronize\Transport\Soap\ClientFactory $factory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
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
        Model\Customer\Config $customerConfig,
        \TNW\Salesforce\Synchronize\Transport\Soap\ClientFactory $factory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        $compareIgnoreFields = []
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
            $localeDate,
            $compareIgnoreFields
        );

        $this->customerConfig = $customerConfig;
    }

    /**
     * Prepare Object
     *
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

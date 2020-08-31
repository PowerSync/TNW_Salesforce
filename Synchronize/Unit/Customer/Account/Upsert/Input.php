<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer\Account\Upsert;

use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Model;
use TNW\SForceEnterprise\SForceBusiness\Model\Config;

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
     * Prepare Object
     *
     * @param \Magento\Customer\Model\Customer $entity
     * @param array $object
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareObject($entity, array $object)
    {
        $item = $this->unit('mapping')->mappers($entity)
            ->getItemByColumnValue('magento_attribute_name', 'sf_company');

        if (isset($item)) {
            if (
                !empty($object['Id'])
                && ($item->getMagentoToSfWhen() !== Config::MAPPING_WHEN_INSERT_ONLY
                || $item->getMagentoToSfWhen() !== Config::MAPPING_WHEN_UPSERT)
            ) {
                unset($object['Name']);
            }
        }

        return parent::prepareObject($entity, $object);
    }
}

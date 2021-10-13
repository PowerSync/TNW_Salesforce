<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Customer\Account\Upsert;

use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
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
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Transport\Calls\Upsert\Transport\InputFactory $inputFactory
     * @param Synchronize\Transport\Calls\Upsert\InputInterface $process
     * @param Model\Customer\Config $customerConfig
     * @param Synchronize\Transport\Soap\ClientFactory $factory
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        $name,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Transport\Calls\Upsert\Transport\InputFactory $inputFactory,
        Synchronize\Transport\Calls\Upsert\InputInterface $process,
        Model\Customer\Config $customerConfig,
        Synchronize\Transport\Soap\ClientFactory $factory,
        TimezoneInterface $localeDate
    ) {
        parent::__construct(
            $name,
            $units,
            $group,
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
     * @param Customer $entity
     * @param array $object
     * @return array
     * @throws LocalizedException
     */
    public function prepareObject($entity, array $object): array
    {
        if (!empty($object['Id']) && !$this->customerConfig->canRenameAccount()) {
            unset($object['Name']);
        }

        return parent::prepareObject($entity, $object);
    }
}

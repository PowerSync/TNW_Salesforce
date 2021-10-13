<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Customer\Load\Loader;

/**
 * Load By Customer
 */
class Customer implements \TNW\Salesforce\Synchronize\Unit\LoadLoaderInterface
{
    const LOAD_BY = 'customer';

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    private $customerFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    private $resourceCustomer;

    /**
     * ByCustomer constructor.
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
    ) {
        $this->customerFactory = $customerFactory;
        $this->resourceCustomer = $resourceCustomer;
    }

    /**
     * Load Type
     *
     * @return string
     */
    public function loadBy(): string
    {
        return self::LOAD_BY;
    }

    /**
     * Load
     *
     * @param int $entityId
     * @param array $additional
     * @return \Magento\Customer\Model\Customer
     */
    public function load($entityId, array $additional): \Magento\Customer\Model\Customer
    {
        $customer = $this->customerFactory->create();
        $this->resourceCustomer->load($customer, $entityId);

        return $customer;
    }
}

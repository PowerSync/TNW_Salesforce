<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer\Loader;

use Magento\Customer;

/**
 * Load By Customer
 */
class ByCustomer implements \TNW\Salesforce\Synchronize\Unit\LoaderInterface
{
    const LOAD_BY = 'customer';

    /**
     * @var Customer\Model\CustomerFactory
     */
    private $customerFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    private $resourceCustomer;

    /**
     * ByCustomer constructor.
     * @param Customer\Model\CustomerFactory $customerFactory
     * @param Customer\Model\ResourceModel\Customer $resourceCustomer
     */
    public function __construct(
        Customer\Model\CustomerFactory $customerFactory,
        Customer\Model\ResourceModel\Customer $resourceCustomer
    ) {
        $this->customerFactory = $customerFactory;
        $this->resourceCustomer = $resourceCustomer;
    }

    /**
     * Load Type
     *
     * @return string
     */
    public function loadBy()
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
    public function load($entityId, array $additional)
    {
        $customer = $this->customerFactory->create();
        $this->resourceCustomer->load($customer, $entityId);

        return $customer;
    }
}

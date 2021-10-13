<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Customer;

/**
 * Mapping Entity Loader
 */
abstract class LoaderAbstract  extends \TNW\Salesforce\Synchronize\Unit\EntityLoaderAbstract
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    protected $resourceCustomer;

    /**
     * Customer constructor.
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
     * @param \TNW\SForceEnterprise\Synchronize\Entity\Customer\Generate $customerGenerate
     * @param \TNW\Salesforce\Model\Entity\SalesforceIdStorage|null $salesforceIdStorage
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer,
        \TNW\Salesforce\Model\Entity\SalesforceIdStorage $salesforceIdStorage = null
    ) {
        parent::__construct($salesforceIdStorage);
        $this->customerFactory = $customerFactory;
        $this->resourceCustomer = $resourceCustomer;
    }

    /**
     * Load
     *
     * @param \Magento\Sales\Model\Order $entity
     * @return \Magento\Framework\DataObject
     */
    public function load($entity): \Magento\Framework\DataObject
    {
        $customer = $this->customerFactory->create();
        $this->resourceCustomer->load($customer, $entity->getCustomerId());

        return $customer;
    }
}

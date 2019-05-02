<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer;

/**
 * Mapping Entity Loader
 */
abstract class LoaderAbstract  extends \TNW\Salesforce\Synchronize\Unit\EntityLoaderAbstract
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    private $customerFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    private $resourceCustomer;

    /**
     * @var \TNW\SForceBusiness\Synchronize\Entity\Customer\Generate
     */
    private $customerGenerate;

    /**
     * Customer constructor.
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
     * @param \TNW\SForceBusiness\Synchronize\Entity\Customer\Generate $customerGenerate
     * @param \TNW\Salesforce\Model\Entity\SalesforceIdStorage|null $salesforceIdStorage
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer,
        \TNW\SForceBusiness\Synchronize\Entity\Customer\Generate $customerGenerate,
        \TNW\Salesforce\Model\Entity\SalesforceIdStorage $salesforceIdStorage = null
    ) {
        parent::__construct($salesforceIdStorage);
        $this->customerFactory = $customerFactory;
        $this->resourceCustomer = $resourceCustomer;
        $this->customerGenerate = $customerGenerate;
    }

    /**
     * Load
     *
     * @param \Magento\Sales\Model\Order $entity
     * @return \Magento\Framework\Model\AbstractModel
     */
    public function load($entity)
    {
        $customer = $this->customerFactory->create();
        $this->resourceCustomer->load($customer, $entity->getCustomerId());

        if (null === $customer->getId()) {
            $customer = $this->customerGenerate->bySale($entity);
        }

        return $customer;
    }
}

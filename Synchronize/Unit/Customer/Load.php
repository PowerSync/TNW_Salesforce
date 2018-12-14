<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer;

use TNW\Salesforce\Synchronize;

class Load extends Synchronize\Unit\LoadAbstract
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
     * Load constructor.
     *
     * @param string $name
     * @param array $entities
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param \TNW\Salesforce\Model\Entity\SalesforceIdStorage $entityObject
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
     */
    public function __construct(
        $name,
        array $entities,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        \TNW\Salesforce\Model\Entity\SalesforceIdStorage $entityObject,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
    ) {
        parent::__construct($name, $entities, $units, $group, $identification, $entityObject);
        $this->customerFactory = $customerFactory;
        $this->resourceCustomer = $resourceCustomer;
    }

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return __('Loading Magento customers ...');
    }

    /**
     * @param mixed $entity
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function loadEntity($entity)
    {
        //TODO: Костыль
        if ($entity instanceof \Magento\Customer\Model\Customer) {
            $entity = $entity->getId();
        }

        if (is_numeric($entity)) {
            $customer = $this->customerFactory->create();
            $this->resourceCustomer->load($customer, $entity);
            $entity = $customer;
        }

        if (!$entity instanceof \Magento\Customer\Model\Customer || null === $entity->getId()) {
            throw new \RuntimeException('Unable to load entity');
        }

        return $entity;
    }
}

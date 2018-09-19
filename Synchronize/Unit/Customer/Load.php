<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer;

use TNW\Salesforce\Synchronize;

class Load extends Synchronize\Unit\LoadAbstract
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        $name,
        array $entities,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        \Magento\Customer\Model\CustomerFactory $customerFactory
    ) {
        parent::__construct($name, $entities, $units, $group, $identification);
        $this->customerFactory = $customerFactory;
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
            $customer->getResource()->load($customer, $entity);
            $entity = $customer;
        }

        if (!$entity instanceof \Magento\Customer\Model\Customer || null === $entity->getId()) {
            throw new \RuntimeException('Unable to load entity');
        }

        return $entity;
    }
}
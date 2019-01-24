<?php
namespace TNW\Salesforce\Synchronize\Queue\Customer;

class CreateByCustomer implements \TNW\Salesforce\Synchronize\Queue\CreateInterface
{
    const CREATE_BY = 'customer';

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    private $resourceCustomer;

    /**
     * CreateByCustomer constructor.
     * @param \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
    ) {
        $this->resourceCustomer = $resourceCustomer;
    }

    /**
     * @param int $entityId
     * @param callable $create
     * @return mixed
     */
    public function process($entityId, callable $create)
    {
        if (!$this->resourceCustomer->checkCustomerId($entityId)) {
            return [];
        }

        return [$create('customer', $entityId)];
    }

    /**
     * @return string
     */
    public function createBy()
    {
        return self::CREATE_BY;
    }
}

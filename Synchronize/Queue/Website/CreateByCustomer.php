<?php
namespace TNW\Salesforce\Synchronize\Queue\Website;

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
     * @param int $websiteId
     * @return mixed
     */
    public function process($entityId, callable $create, $websiteId)
    {
        $customerWebsiteId = $this->resourceCustomer->getWebsiteId($entityId);
        if (empty($customerWebsiteId)) {
            return [];
        }

        return [$create('website', $customerWebsiteId)];
    }

    /**
     * @return string
     */
    public function createBy()
    {
        return self::CREATE_BY;
    }
}

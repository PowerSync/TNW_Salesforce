<?php
namespace TNW\Salesforce\Synchronize\Queue\Website;

class CreateByCustomer implements \TNW\Salesforce\Synchronize\Queue\CreateInterface
{
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
        $websiteId = $this->resourceCustomer->getWebsiteId($entityId);
        if (empty($websiteId)) {
            return [];
        }

        return [$create('website', $websiteId)];
    }
}

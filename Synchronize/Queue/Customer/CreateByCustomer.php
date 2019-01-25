<?php
namespace TNW\Salesforce\Synchronize\Queue\Customer;

class CreateByCustomer implements \TNW\Salesforce\Synchronize\Queue\CreateInterface
{
    const CREATE_BY = 'customer';

    /**
     * @param int $entityId
     * @param callable $create
     * @param int $websiteId
     * @return \TNW\Salesforce\Model\Queue[]
     */
    public function process($entityId, callable $create, $websiteId)
    {
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

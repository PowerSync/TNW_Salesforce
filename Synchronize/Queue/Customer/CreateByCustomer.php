<?php
namespace TNW\Salesforce\Synchronize\Queue\Customer;

/**
 * Create By Customer
 */
class CreateByCustomer implements \TNW\Salesforce\Synchronize\Queue\CreateInterface
{
    const CREATE_BY = 'customer';

    /**
     * Process
     *
     * @param int $entityId
     * @param array $additional
     * @param callable $create
     * @param int $websiteId
     * @return \TNW\Salesforce\Model\Queue[]
     */
    public function process($entityId, array $additional, callable $create, $websiteId)
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

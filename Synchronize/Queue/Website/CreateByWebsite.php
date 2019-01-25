<?php
namespace TNW\Salesforce\Synchronize\Queue\Website;

class CreateByWebsite implements \TNW\Salesforce\Synchronize\Queue\CreateInterface
{
    const CREATE_BY = 'website';

    /**
     * @param int $entityId
     * @param callable $create
     * @param int $websiteId
     * @return mixed
     */
    public function process($entityId, callable $create, $websiteId)
    {
        return [$create('website', $entityId)];
    }

    /**
     * @return string
     */
    public function createBy()
    {
        return self::CREATE_BY;
    }
}

<?php
namespace TNW\Salesforce\Synchronize\Queue\Website;

/**
 * Create By Website
 */
class CreateByWebsite implements \TNW\Salesforce\Synchronize\Queue\CreateInterface
{
    const CREATE_BY = 'website';

    /**
     * Process
     *
     * @param int $entityId
     * @param array $additional
     * @param callable $create
     * @param int $websiteId
     * @return mixed
     */
    public function process($entityId, array $additional, callable $create, $websiteId)
    {
        return [$create('website', $entityId)];
    }

    /**
     * Create By
     *
     * @return string
     */
    public function createBy()
    {
        return self::CREATE_BY;
    }
}

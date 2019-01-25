<?php
namespace TNW\Salesforce\Synchronize\Queue;

interface CreateInterface
{
    /**
     * @return string
     */
    public function createBy();

    /**
     * @param int $entityId
     * @param callable $create
     * @param int $websiteId
     * @return \TNW\Salesforce\Model\Queue[]
     */
    public function process($entityId, callable $create, $websiteId);
}

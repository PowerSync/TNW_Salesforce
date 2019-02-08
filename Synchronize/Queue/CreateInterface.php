<?php
namespace TNW\Salesforce\Synchronize\Queue;

interface CreateInterface
{
    /**
     * Create By
     *
     * @return string
     */
    public function createBy();

    /**
     * Process
     *
     * @param int $entityId
     * @param array $additional
     * @param callable $create
     * @param int $websiteId
     * @return \TNW\Salesforce\Model\Queue[]
     */
    public function process($entityId, array $additional, callable $create, $websiteId);
}

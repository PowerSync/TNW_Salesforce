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
     * @param int[] $entityIds
     * @param array $additional
     * @param callable $create
     * @param int $websiteId
     * @return \TNW\Salesforce\Model\Queue[]
     */
    public function process(array $entityIds, array $additional, callable $create, $websiteId);
}

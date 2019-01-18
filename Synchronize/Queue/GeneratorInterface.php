<?php
namespace TNW\Salesforce\Synchronize\Queue;

interface GeneratorInterface
{
    /**
     * @param int $entityId
     * @param callable $create
     * @return \TNW\Salesforce\Model\Queue[]
     */
    public function process($entityId, callable $create);
}

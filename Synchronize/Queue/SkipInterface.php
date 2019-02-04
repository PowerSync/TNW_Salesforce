<?php
namespace TNW\Salesforce\Synchronize\Queue;

interface SkipInterface
{
    /**
     * Apply
     *
     * @param \TNW\Salesforce\Model\Queue $queue
     * @return bool
     */
    public function apply(\TNW\Salesforce\Model\Queue $queue);
}

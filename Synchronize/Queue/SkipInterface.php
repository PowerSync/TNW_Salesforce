<?php
namespace TNW\Salesforce\Synchronize\Queue;

interface SkipInterface
{
    /**
     * @param Resolve $resolve
     * @param int $websiteId
     * @return bool
     */
    public function apply(Resolve $resolve, $websiteId);
}

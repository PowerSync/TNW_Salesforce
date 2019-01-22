<?php
namespace TNW\Salesforce\Synchronize\Queue\Website;

class CreateByWebsite implements \TNW\Salesforce\Synchronize\Queue\CreateInterface
{
    /**
     * @param int $entityId
     * @param callable $create
     * @return mixed
     */
    public function process($entityId, callable $create)
    {
        $entityId = $this->entityIdBy($entityId);
        if (empty($entityId)) {
            return [];
        }

        return [$create('website', $entityId)];
    }

    /**
     * @param int $customerId
     * @return int
     */
    private function entityIdBy($customerId)
    {
        //TODO: Implement
        return 0;
    }
}

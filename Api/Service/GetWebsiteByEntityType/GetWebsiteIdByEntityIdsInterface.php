<?php

namespace TNW\Salesforce\Api\Service\GetWebsiteByEntityType;

/**
 * Interface GetWebsiteIdByEntityIds
 */
interface GetWebsiteIdByEntityIdsInterface
{
    /**
     * @param array $entityIds
     *
     * @return array
     */
    public function execute(array $entityIds): array;
}

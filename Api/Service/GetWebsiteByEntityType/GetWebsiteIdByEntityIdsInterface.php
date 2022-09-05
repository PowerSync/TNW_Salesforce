<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

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

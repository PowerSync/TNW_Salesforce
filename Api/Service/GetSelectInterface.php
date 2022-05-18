<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Api\Service;

use Magento\Framework\DB\Select;

/**
 * Interface GetSelectInterface
 */
interface GetSelectInterface
{
    /**
     * @param array $entityIds
     *
     * @return null|Select
     */
    public function execute(array $entityIds): ?Select;
}

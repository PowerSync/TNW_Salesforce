<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Queue;

use TNW\Salesforce\Model\Queue;

interface CreateInterface
{
    public const CURRENCY_CODE = 'currency_code';

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
     * @return Queue[]
     */
    public function process(array $entityIds, array $additional, callable $create, $websiteId);
}

<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities\Load;

/**
 * Interface BeforeLoadExecutorInterface
 */
interface BeforeLoadExecutorInterface
{
    /**
     * @param array $entities
     *
     * @return array
     */
    public function execute(array $entities): array;
}

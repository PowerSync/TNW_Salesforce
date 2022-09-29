<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\Synchronize\Unit\Load\PreloadEntities;

/**
 * Interface AfterLoadExecutorInterface
 */
interface AfterLoadExecutorInterface
{
    /**
     * @param array $entities
     *
     * @return void
     */
    public function execute(array $entities, array $entityAdditionalByEntityId = []): array;
}

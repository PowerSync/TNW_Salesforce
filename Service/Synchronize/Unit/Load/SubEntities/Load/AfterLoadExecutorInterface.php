<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities\Load;

interface AfterLoadExecutorInterface
{
    /**
     * @param array $resultEntities
     * @param array $requestEntities
     *
     * @return array
     */
    public function execute(array $resultEntities, array $requestEntities): array;
}

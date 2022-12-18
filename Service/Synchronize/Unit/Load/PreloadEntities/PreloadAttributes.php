<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Unit\Load\PreloadEntities;

use TNW\Salesforce\Synchronize\Unit\Load\LoaderAttributes;

class PreloadAttributes implements AfterLoadExecutorInterface
{
    /** @var LoaderAttributes  */
    private $loaderAttributes;

    public function __construct(
        LoaderAttributes $loaderAttributes,
    ) {
        $this->loaderAttributes = $loaderAttributes;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entities, array $entityAdditionalByEntityId = []): array
    {
        $ids = [];

        foreach ($entities as $entity) {
            $ids[] = $entity->getId();
        }

        $ids = array_filter($ids);

        if (!empty($ids)) {
            $this->loaderAttributes->execute($ids);
        }

        return $entities;
    }
}

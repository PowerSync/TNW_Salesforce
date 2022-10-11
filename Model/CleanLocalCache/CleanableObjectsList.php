<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Model\CleanLocalCache;

use TNW\Salesforce\Api\CleanableInstanceInterface;

/**
 *  Pool for not singleton objects
 */
class CleanableObjectsList implements CleanableInstanceInterface
{
    /** @var CleanableInstanceInterface[] */
    private $list = [];

    /**
     * @param CleanableInstanceInterface $object
     *
     * @return void
     */
    public function add(CleanableInstanceInterface $object): void
    {
        $this->list[] = $object;
    }

    /**
     * @return CleanableInstanceInterface[]
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * @inheritDoc
     */
    public function clearLocalCache(): void
    {
        $this->list = [];
    }
}

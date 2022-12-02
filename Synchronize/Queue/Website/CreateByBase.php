<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Queue\Website;

use TNW\Salesforce\Synchronize\Queue\CreateInterface;

/**
 * Create By Customer
 */
abstract class CreateByBase implements CreateInterface
{
    const CREATE_BY = 'base';

    /**
     * Create By
     *
     * @return string
     */
    public function createBy()
    {
        return self::CREATE_BY;
    }

    abstract public function entities(array $entityIds);

}

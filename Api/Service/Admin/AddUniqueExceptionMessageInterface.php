<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Api\Service\Admin;

use Throwable;

/**
 * Add unique exception message to admin messages service interface.
 */
interface AddUniqueExceptionMessageInterface
{
    /**
     * Add error message by exception.
     *
     * @param Throwable $exception
     * @param bool      $logException
     */
    public function execute(Throwable $exception, bool $logException = true): void;
}

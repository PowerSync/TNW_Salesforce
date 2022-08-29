<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Synchronize;

interface UnitInterface
{
    const PENDING  = 1;
    const PROCESS  = 2;
    const COMPLETE = 3;

    /**
     * @return string
     */
    public function name();

    /**
     * @return array
     */
    public function dependents();

    /**
     * @return string
     */
    public function description();

    /**
     * @return Synchronize\Group
     */
    public function group();

    /**
     * @return Synchronize\Units
     */
    public function units();

    /**
     *
     */
    public function process();

    /**
     * @param null $path
     * @param array ...$objects
     * @return mixed
     */
    public function get($path = null, ...$objects);

    /**
     * @param $entity
     * @return mixed
     */
    public function skipped($entity);

    /**
     * @param $status
     */
    public function status($status);

    /**
     * @return mixed
     */
    public function isComplete();
}

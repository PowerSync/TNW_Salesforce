<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit;

use Magento\Framework\Phrase;
use TNW\Salesforce\Synchronize;

interface UnitInterface
{
    const PENDING  = 1;
    const PROCESS  = 2;
    const COMPLETE = 3;

    /**
     * @return string
     */
    public function name(): string;

    /**
     * @return array
     */
    public function dependents(): array;

    /**
     * @return string|Phrase
     */
    public function description();

    /**
     * @return Synchronize\Group
     */
    public function group(): Synchronize\Group;

    /**
     * @return Synchronize\Units
     */
    public function units(): Synchronize\Units;

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

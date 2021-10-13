<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Queue\Website;

/**
 * Create By Customer
 */
abstract class CreateByBase implements \TNW\Salesforce\Synchronize\Queue\CreateInterface
{
    const CREATE_BY = 'base';

    /**
     * Create By
     *
     * @return string
     */
    public function createBy(): string
    {
        return self::CREATE_BY;
    }

    abstract public function entities(array $entityIds);

}

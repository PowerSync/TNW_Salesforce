<?php
declare(strict_types=1);

namespace TNW\Salesforce\Model;

/**
 * Test interface
 */
interface TestInterface
{
    /**
     * Action
     *
     * @return TestInterface
     */
    public function execute(): TestInterface;
}

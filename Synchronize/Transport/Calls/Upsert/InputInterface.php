<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Transport\Calls\Upsert;

/**
 * Unit interface
 */
interface InputInterface
{
    /**
     * Do Unit synchronization to Salesforce object
     *
     * @param Transport\Input $input
     */
    public function process(Transport\Input $input);
}

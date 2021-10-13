<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Transport\Calls\Delete;

/**
 * Unit interface
 */
interface OutputInterface
{
    /**
     * Do Unit synchronization to Salesforce object
     *
     * @param Transport\Output $output
     */
    public function process(Transport\Output $output);
}

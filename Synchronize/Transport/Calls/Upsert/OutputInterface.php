<?php
namespace TNW\Salesforce\Synchronize\Transport\Calls\Upsert;

/**
 * Unit interface
 */
interface OutputInterface
{
    /**
     * Do Unit syncronization to Salesforce object
     *
     * @param Transport\Output $output
     */
    public function process(Transport\Output $output);
}

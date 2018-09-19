<?php
namespace TNW\Salesforce\Synchronize\Transport\Calls;

/**
 * Unit interface
 */
interface UpsertInterface
{
    /**
     * Do Unit syncronization to Salesforce object
     * @param Upsert\Input $input
     * @param Upsert\Output $output
     */
    public function process(Upsert\Input $input, Upsert\Output $output);
}

<?php
namespace TNW\Salesforce\Synchronize\Transport\Calls;

/**
 * Unit interface
 */
interface QueryInterface
{
    /**
     * Do Unit syncronization to Salesforce object
     * @param Query\Input $input
     * @param Query\Output $output
     */
    public function process(Query\Input $input, Query\Output $output);
}

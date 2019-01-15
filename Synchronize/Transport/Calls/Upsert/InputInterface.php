<?php
namespace TNW\Salesforce\Synchronize\Transport\Calls\Upsert;

/**
 * Unit interface
 */
interface InputInterface
{
    /**
     * Do Unit syncronization to Salesforce object
     * @param Input $input
     */
    public function process(Input $input);
}

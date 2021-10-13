<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Transport\Calls;

/**
 * Unit interface
 */
interface QueryInterface
{
    /**
     * Do Unit synchronization to Salesforce object
     * @param Query\Input $input
     * @param Query\Output $output
     */
    public function process(Query\Input $input, Query\Output $output);

    /**
     * @param $data
     * @param int|null $websiteId
     *
     * @return mixed
     */
    public function exec($data, $websiteId = null);
}

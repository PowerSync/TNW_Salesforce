<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
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

    /**
     * @param $data
     * @param int|null $websiteId
     *
     * @return mixed
     */
    public function exec($data, $websiteId = null);
}

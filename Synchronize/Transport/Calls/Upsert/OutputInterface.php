<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
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

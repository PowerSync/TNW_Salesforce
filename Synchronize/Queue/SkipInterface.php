<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Queue;

interface SkipInterface
{
    /**
     * Apply
     *
     * @param \TNW\Salesforce\Model\Queue $queue
     * @return bool|string|\Magento\Framework\Phrase
     */
    public function apply(\TNW\Salesforce\Model\Queue $queue);
}

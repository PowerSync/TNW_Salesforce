<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Plugin\Framework\Data\Collection;

use Magento\Framework\Data\Collection\AbstractDb as Collection;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\ResourceModel\Objects\SelectAbstract;

class AbstractDb
{
    /**
     * @var SelectAbstract[]
     */
    private $select;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        array  $select,
        Config $config
    ) {
        $this->select = $select;
        $this->config = $config;
    }

    /**
     * @param Collection $collection
     * @param bool       $printQuery
     * @param bool       $logQuery
     *
     * @return array
     */
    public function beforeLoad(Collection $collection, $printQuery = false, $logQuery = false)
    {
        if ($collection->isLoaded() || !$this->config->getSalesforceStatus()) {
            return [$printQuery, $logQuery];
        }

        $originalSelect = $collection->getSelect();
        foreach ($this->select as $alias => $select) {
            $select->build($originalSelect, $alias);
        }

        return [$printQuery, $logQuery];
    }
}

<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Plugin\Framework\Data\Collection;

use Magento\Framework\Data\Collection\AbstractDb as Collection;

class AbstractDb
{
    /**
     * @var array
     */
    private $select;

    public function __construct(
        array $select
    ) {
        $this->select = $select;
    }

    /**
     * @param Collection $collection
     * @param bool $printQuery
     * @param bool $logQuery
     *
     * @return array
     */
    public function beforeLoad(Collection $collection, $printQuery = false, $logQuery = false)
    {
        if ($collection->isLoaded()) {
            return [$printQuery, $logQuery];
        }

        foreach ($this->select as $alias => $select) {
            $collection->getSelect()->columns([$alias => $select->build()]);
        }

        return [$printQuery, $logQuery];
    }
}

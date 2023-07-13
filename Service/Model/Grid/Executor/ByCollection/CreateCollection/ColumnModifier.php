<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Model\Grid\Executor\ByCollection\CreateCollection;

use Magento\Framework\Data\Collection;
use TNW\Salesforce\Api\Model\Grid\GetColumnsDataItems\Executor\ByCollection\CreateCollection\ModifierInterface;
use TNW\Salesforce\Model\ResourceModel\Objects\SelectAbstract;

class ColumnModifier implements ModifierInterface
{
    /** @var SelectAbstract[] */
    private $builders;

    /**
     * @param array $builders
     */
    public function __construct(
        array $builders
    ) {
        $this->builders = $builders;
    }

    public function execute(Collection $collection): void
    {
        $originalSelect = $collection->getSelect();
        foreach ($this->builders as $alias => $select) {
            $select->build($originalSelect, $alias);
        }
    }
}

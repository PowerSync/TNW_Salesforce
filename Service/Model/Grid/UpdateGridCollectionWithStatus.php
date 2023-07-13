<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Model\Grid;

use Magento\Framework\Data\Collection;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Platform\Quote;

class UpdateGridCollectionWithStatus
{
    const PATTERN = '/(?:sforce|salesforce)/i';

    /**
     * @param Collection $collection
     *
     * @return void
     */
    public function execute(Collection $collection): void
    {
        $conditionSynced = [];
        $conditionNotSynced = [];
        $aliases = [];
        $originalSelect = $collection->getSelect();
        $selectPartFrom = $originalSelect->getPart(Select::FROM);

        foreach ($selectPartFrom as $alias => $data) {
            if ($data['joinType'] !== 'left join' || !preg_match(self::PATTERN, $alias)) {
                continue;
            }

            $conditionNotSynced[] = "{$alias}.status = 0";
            $conditionSynced[] = "{$alias}.status <= 1";
            $conditionIfNull[] = "IFNULL({$alias}.status, 0)";
        }

        $caseExpression = 'CASE ' .
            'WHEN ' . implode(' AND ', $conditionNotSynced) . ' THEN 0 ' .
            'WHEN ' . implode(' AND ', $conditionSynced) . ' THEN 1 ' .
            'ELSE GREATEST(' . implode(', ', $conditionIfNull) . ') END';

        $originalSelect->columns(['sforce_sync_status' => new \Zend_Db_Expr($caseExpression)]);
    }
}

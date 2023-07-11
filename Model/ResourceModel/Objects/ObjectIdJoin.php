<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\ResourceModel\Objects;

class ObjectIdJoin extends SelectAbstract
{
    /**
     * @var string
     */
    private $magentoType;

    /**
     * @var string
     */
    private $salesforceType;

    /**
     * @var string
     */
    private $entityIdField;

    /**
     * ObjectIdJoinBuilder constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param string $magentoType
     * @param string $salesforceType
     * @param string $entityIdField
     * @param string|null $connectionName
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        $magentoType,
        $salesforceType,
        $entityIdField = 'main_table.entity_id',
        $connectionName = null
    ) {
        parent::__construct($resource, $connectionName);
        $this->magentoType = $magentoType;
        $this->salesforceType = $salesforceType;
        $this->entityIdField = $entityIdField;
    }

    /**
     * @inheritdoc
     */
    public function build(\Magento\Framework\DB\Select $originalSelect, string $alias)
    {
        return $originalSelect
            ->joinLeft(
                ["object_{$alias}" => $this->getTable('tnw_salesforce_objects')],
                "object_{$alias}.entity_id = {$this->entityIdField}"
                . " AND object_{$alias}.magento_type = '{$this->magentoType}'"
                . " AND object_{$alias}.salesforce_type = '{$this->salesforceType}'"
                . " AND object_{$alias}.store_id = 0"
                . " AND object_{$alias}.website_id IN ('sf_website_id', 0)",
                [$alias => 'object_id']
            );
    }
}

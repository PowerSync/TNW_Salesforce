<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\ResourceModel\Objects;

abstract class SelectAbstract
{
    /**
     * Prefix for resources that will be used in this resource model
     *
     * @var string
     */
    protected $connectionName = \Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * SelectBuilderAbstract constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param string|null $connectionName
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        $connectionName = null
    ) {
        $this->resource = $resource;
        if ($connectionName !== null) {
            $this->connectionName = $connectionName;
        }
    }

    /**
     * @param $tableName
     *
     * @return string
     */
    public function getTable($tableName)
    {
        return $this->resource->getTableName($tableName, $this->connectionName);
    }

    /**
     * Get connection
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface|false
     */
    public function getConnection()
    {
        return $this->resource->getConnection($this->connectionName);
    }

    /**
     * @return \Magento\Framework\DB\Select
     */
    public function select()
    {
        return $this->getConnection()->select();
    }

    /**
     * @return \Magento\Framework\DB\Select|string
     */
    abstract public function build();
}

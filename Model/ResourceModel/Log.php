<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Log extends AbstractDb
{
    /**
     * @var array
     */
    protected static $checkTable = true;
    /**
     * @var \TNW\Salesforce\Model\Config
     */
    private $salesforceConfig;

    /**
     * Log constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \TNW\Salesforce\Model\Config $salesforceConfig
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \TNW\Salesforce\Model\Config $salesforceConfig,
        $connectionName = null
    ) {
        $this->salesforceConfig = $salesforceConfig;
        parent::__construct($context, $connectionName);
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('tnw_salesforce_log', 'id');
    }

    /**
     * @inheritdoc
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        return parent::_beforeSave($object);
    }

    /**
     *
     */
    public function clear()
    {
        if (self::$checkTable) {
            $count = $this->getConnection()
                ->fetchOne("SELECT COUNT(*) FROM `{$this->getMainTable()}`");

            $dbLogLimit = $this->salesforceConfig->getDbLogLimit();
            if ($count > $dbLogLimit) {
                $limit = $count - $dbLogLimit;
                $this->getConnection()
                    ->query("DELETE FROM `{$this->getMainTable()}` ORDER BY `{$this->getIdFieldName()}` ASC LIMIT {$limit}");
            }

            self::$checkTable = false;
        }
    }
}

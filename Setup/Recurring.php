<?php

namespace TNW\Salesforce\Setup;

use Magento\Framework\Amqp\TopologyInstaller;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class Recurring
 */
class Recurring implements InstallSchemaInterface
{
    /**
     * @inheritdoc
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->queueRelationForeignKeyFix($setup);

        $setup->endSetup();
    }

    /**
     * Re-creation of QueueRelationForeignKeyFix schema patch.
     *
     * @param SchemaSetupInterface $setup
     */
    private function queueRelationForeignKeyFix(SchemaSetupInterface $setup): void
    {
        $connection = $setup->getConnection();

        $queueRelationTable = $setup->getTable('tnw_salesforce_entity_queue_relation');
        $queueTable = $setup->getTable('tnw_salesforce_entity_queue');

        foreach ($connection->getForeignKeys($queueRelationTable) as $foreignKey) {
            $connection->dropForeignKey($queueRelationTable, $foreignKey['FK_NAME']);
        }

        $this->addForeignKey(
            $connection,
            $setup->getFkName($queueRelationTable, 'queue_id', $queueTable, 'queue_id'),
            $queueRelationTable,
            'queue_id',
            $queueTable,
            'queue_id',
            AdapterInterface::FK_ACTION_CASCADE,
            AdapterInterface::FK_ACTION_CASCADE
        );

        $this->addForeignKey(
            $connection,
            $setup->getFkName($queueRelationTable, 'parent_id', $queueTable, 'queue_id'),
            $queueRelationTable,
            'parent_id',
            $queueTable,
            'queue_id',
            AdapterInterface::FK_ACTION_CASCADE,
            AdapterInterface::FK_ACTION_CASCADE
        );
    }

    /**
     * Add new Foreign Key to table
     *
     * If Foreign Key with same name is exist - it will be deleted
     *
     * @param AdapterInterface $connection
     * @param string $fkName
     * @param string $tableName
     * @param string $columnName
     * @param string $refTableName
     * @param string $refColumnName
     * @param string $onDelete
     * @param string $onUpdate
     * @param bool $purge trying remove invalid data
     * @param null $schemaName
     * @return Zend_Db_Statement_Interface
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    private function addForeignKey(
        $connection,
        $fkName,
        $tableName,
        $columnName,
        $refTableName,
        $refColumnName,
        $onDelete = AdapterInterface::FK_ACTION_CASCADE,
        $onUpdate = AdapterInterface::FK_ACTION_CASCADE,
        $purge = false,
        $schemaName = null
    ) {
        $connection->dropForeignKey($tableName, $fkName, $schemaName);

        if ($purge) {
            $connection->purgeOrphanRecords($tableName, $columnName, $refTableName, $refColumnName, $onDelete);
        }

        $query = sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s)',
            $connection->quoteIdentifier($connection->getTableName($tableName)),
            $connection->quoteIdentifier($fkName),
            $connection->quoteIdentifier($columnName),
            $connection->quoteIdentifier($connection->getTableName($refTableName)),
            $connection->quoteIdentifier($refColumnName)
        );

        if ($onDelete !== null) {
            $query .= ' ON DELETE ' . strtoupper($onDelete);
        }

        if ($onUpdate !== null) {
            $query .= ' ON UPDATE ' . strtoupper($onUpdate);
        }

        $connection->getSchemaListener()->addForeignKey(
            $fkName,
            $tableName,
            $columnName,
            $refTableName,
            $refColumnName,
            $onDelete
        );

        $result = $connection->rawQuery($query);
        $connection->resetDdlCache($tableName);

        return $result;
    }
}

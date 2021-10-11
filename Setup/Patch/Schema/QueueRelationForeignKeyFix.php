<?php
namespace TNW\Salesforce\Setup\Patch\Schema;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Zend_Db_Statement_Interface;

class QueueRelationForeignKeyFix implements SchemaPatchInterface, PatchRevertableInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /**
     * QueueRelationForeignKeyFix constructor.
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup
    ) {
        $this->schemaSetup = $schemaSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->schemaSetup->startSetup();

        $connection = $this->schemaSetup->getConnection();

        $queueRelationTable = $this->schemaSetup->getTable('tnw_salesforce_entity_queue_relation');
        $queueTable = $this->schemaSetup->getTable('tnw_salesforce_entity_queue');

        foreach ($connection->getForeignKeys($queueRelationTable) as $foreignKey) {
            $connection->dropForeignKey($queueRelationTable, $foreignKey['FK_NAME']);
        }

        $this->addForeignKey(
            $this->schemaSetup->getFkName($queueRelationTable, 'queue_id', $queueTable, 'queue_id'),
            $queueRelationTable,
            'queue_id',
            $queueTable,
            'queue_id',
            AdapterInterface::FK_ACTION_CASCADE,
            AdapterInterface::FK_ACTION_CASCADE

        );

        $this->addForeignKey(
            $this->schemaSetup->getFkName($queueRelationTable, 'parent_id', $queueTable, 'queue_id'),
            $queueRelationTable,
            'parent_id',
            $queueTable,
            'queue_id',
            AdapterInterface::FK_ACTION_CASCADE,
            AdapterInterface::FK_ACTION_CASCADE

        );

        $this->schemaSetup->endSetup();
    }

    /**
     * Add new Foreign Key to table
     *
     * If Foreign Key with same name is exist - it will be deleted
     *
     * @param string $fkName
     * @param string $tableName
     * @param string $columnName
     * @param string $refTableName
     * @param string $refColumnName
     * @param string $onDelete
     * @param bool $purge trying remove invalid data
     * @param string $schemaName
     * @param string $refSchemaName
     * @return Zend_Db_Statement_Interface
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function addForeignKey(
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
        $connection = $this->schemaSetup->getConnection();

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

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    public function revert()
    {
        // Nothing to revert here
    }
}

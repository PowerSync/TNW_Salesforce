<?php

namespace TNW\Salesforce\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\Customer;
use Magento\Setup\Model\ObjectManagerProvider;
use Magento\Setup\Module\DataSetup;

/**
 * Class Uninstall
 * @package TNW\Salesforce\Setup
 */
class Uninstall implements UninstallInterface
{
    /**
     * Invoked when remove-data flag is set during module uninstall.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var \Magento\Framework\DB\Adapter\Pdo\Mysql $connection */
        $connection = $setup->getConnection();
        $entityTypeSelect = $connection->select()
            ->from($setup->getTable('eav_entity_type'), ['entity_type_id', 'default_attribute_set_id'])
            ->where($connection->prepareSqlCondition('entity_type_code', Customer::ENTITY));

        $customerType = $connection->fetchRow($entityTypeSelect);

        $connection->delete($setup->getTable('eav_attribute_group'), [
            $connection->prepareSqlCondition('attribute_group_name', 'Salesforce'),
            $connection->prepareSqlCondition('attribute_set_id', $customerType['default_attribute_set_id']),
        ]);

        $connection->delete($setup->getTable('eav_attribute'), [
            $connection->prepareSqlCondition('entity_type_id', $customerType['entity_type_id']),
            $connection->prepareSqlCondition('attribute_code', ['in'=>[
                'sforce_sync_status',
                'sforce_account_id',
                'sforce_id',
            ]]),
        ]);

        $tablesToDrop = [
            'tnw_salesforce_mapper',
            'tnw_salesforce_log',
            'salesforce_objects',
            'tnw_salesforce_objects',
            'tnw_salesforce_entity_queue_relation',
            'tnw_salesforce_entity_queue',
            'tnw_salesforce_entity_prequeue',
        ];
        $columnsToDrop = [
            'store_website' => [
                'salesforce_id',
            ],
            'customer_grid_flat' => [
                'sforce_sync_status',
                'sforce_id',
                'sforce_account_id',
            ],
        ];
        $indexesToDrop = [];
        $constraintsToDrop = [];

        $this->dropSchema($setup, $constraintsToDrop, $indexesToDrop, $columnsToDrop, $tablesToDrop);
    }

    private function dropSchema(
        SchemaSetupInterface $setup,
        array                $constraintsToDrop,
        array                $indexesToDrop,
        array                $columnsToDrop,
        array                $tablesToDrop
    ): void {
        $this->dropForeignKey($setup, $constraintsToDrop);
        $this->dropIndexes($setup, $indexesToDrop);
        $this->dropColumns($setup, $columnsToDrop);
        $this->dropTables($setup, $tablesToDrop);
    }

    private function dropForeignKey(SchemaSetupInterface $setup, array $constraintsData): void
    {
        $filteredData = array_filter(
            $constraintsData,
            function (string $table) use ($setup) {
                return $setup->getConnection()->isTableExists($setup->getTable($table));
            },
            ARRAY_FILTER_USE_KEY
        );
        array_walk(
            $filteredData,
            function (array $constraints, string $table) use ($setup) {
                array_map(
                    function (string $constraint) use ($setup, $table) {
                        $setup->getConnection()->dropForeignKey($setup->getTable($table), $constraint);
                    },
                    $constraints
                );
            }
        );
    }

    private function dropIndexes(SchemaSetupInterface $setup, array $indexesData): void
    {
        $filteredData = array_filter(
            $indexesData,
            function (string $table) use ($setup) {
                return $setup->getConnection()->isTableExists($setup->getTable($table));
            },
            ARRAY_FILTER_USE_KEY
        );
        array_walk(
            $filteredData,
            function (array $indexes, string $table) use ($setup) {
                array_map(
                    function (string $index) use ($setup, $table) {
                        $setup->getConnection()->dropIndex($setup->getTable($table), $index);
                    },
                    $indexes
                );
            }
        );
    }

    private function dropColumns(SchemaSetupInterface $setup, array $columnsData): void
    {
        $filteredData = array_filter(
            $columnsData,
            function (string $table) use ($setup) {
                return $setup->getConnection()->isTableExists($setup->getTable($table));
            },
            ARRAY_FILTER_USE_KEY
        );
        array_walk(
            $filteredData,
            function (array $columns, string $table) use ($setup) {
                array_map(
                    function (string $column) use ($setup, $table) {
                        $setup->getConnection()->dropColumn($setup->getTable($table), $column);
                    },
                    $columns
                );
            }
        );
    }

    private function dropTables(SchemaSetupInterface $setup, array $tables): void
    {
        array_map(
            function (string $table) use ($setup) {
                $setup->getConnection()->dropTable($setup->getTable($table));
            },
            $tables
        );
    }
}

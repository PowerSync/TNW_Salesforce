<?php

declare(strict_types=1);

namespace TNW\Salesforce\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Zend_Db_Expr;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpgradeData248 implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var EavSetup
     */
    private $eavSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetup $eavSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetup                 $eavSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetup = $eavSetup;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [UpgradeData2311::class];
    }

    public static function getVersion()
    {
        return '2.4.8';
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();

        // Website
        $select = $connection->select()
            ->from($this->moduleDataSetup->getTable('store_website'), [
                'entity_id' => 'website_id',
                'object_id' => 'salesforce_id',
                'magento_type' => new Zend_Db_Expr('"Website"'),
                'salesforce_type' => new Zend_Db_Expr('"tnw_mage_basic__Magento_Website__c"'),
                'website_id' => new Zend_Db_Expr('0'),
            ]);

        $query = $connection->insertFromSelect(
            $select,
            $this->moduleDataSetup->getTable('tnw_salesforce_objects'),
            ['entity_id', 'object_id', 'magento_type', 'salesforce_type', 'website_id'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );

        $connection->query($query);

        // Account
        $select = $connection->select()
            ->from($this->eavSetup->getAttributeTable(Customer::ENTITY, 'sforce_account_id'), [
                'entity_id' => 'entity_id',
                'object_id' => 'value',
                'magento_type' => new Zend_Db_Expr('"Customer"'),
                'salesforce_type' => new Zend_Db_Expr('"Account"'),
                'website_id' => new Zend_Db_Expr('0'),
            ])
            ->where('attribute_id = ?', $this->eavSetup->getAttribute(Customer::ENTITY, 'sforce_account_id', 'attribute_id'));

        $query = $connection->insertFromSelect(
            $select,
            $this->moduleDataSetup->getTable('tnw_salesforce_objects'),
            ['entity_id', 'object_id', 'magento_type', 'salesforce_type', 'website_id'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );

        $connection->query($query);

        // Contact
        $select = $connection->select()
            ->from($this->eavSetup->getAttributeTable(Customer::ENTITY, 'sforce_id'), [
                'entity_id' => 'entity_id',
                'object_id' => 'value',
                'magento_type' => new Zend_Db_Expr('"Customer"'),
                'salesforce_type' => new Zend_Db_Expr('"Contact"'),
                'website_id' => new Zend_Db_Expr('0'),
            ])
            ->where('attribute_id = ?', $this->eavSetup->getAttribute(Customer::ENTITY, 'sforce_id', 'attribute_id'));

        $query = $connection->insertFromSelect(
            $select,
            $this->moduleDataSetup->getTable('tnw_salesforce_objects'),
            ['entity_id', 'object_id', 'magento_type', 'salesforce_type', 'website_id'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );

        $connection->query($query);
        $this->moduleDataSetup->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}

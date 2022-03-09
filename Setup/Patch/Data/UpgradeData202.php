<?php

declare(strict_types=1);

namespace TNW\Salesforce\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Indexer\Address\AttributeProvider;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\DB\Select;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpgradeData202 implements DataPatchInterface, PatchVersionInterface
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
        EavSetup                 $eavSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetup = $eavSetup;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [UpgradeData0103::class];
    }

    public static function getVersion()
    {
        return '2.0.2';
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $this->updateAttributeTypes($this->moduleDataSetup);
        $this->eavSetup->updateAttribute(
            Customer::ENTITY,
            'sync_status',
            'frontend_input',
            'select'
        );
        $this->eavSetup->updateAttribute(
            Customer::ENTITY,
            'sync_status',
            'attribute_code',
            'sforce_sync_status'
        );
        $this->moduleDataSetup->endSetup();
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    protected function updateAttributeTypes(ModuleDataSetupInterface $setup)
    {
        /** @var Select $select */
        $select = $setup->getConnection()
            ->select()
            ->from($setup->getTable('tnw_salesforce_mapper'))
            ->where("object_type in (?)", ['Contact', 'Account']);

        /** @var array $customerToContactsAndAccounts */
        $customerToContactsAndAccounts = $setup->getConnection()
            ->fetchAssoc($select);

        foreach ($customerToContactsAndAccounts as $customerToContactAndAccount) {
            switch ($customerToContactAndAccount['magento_entity_type']) {
                case 'customer':
                    $attribute = $this->eavSetup->getAttribute(
                        Customer::ENTITY,
                        $customerToContactAndAccount['magento_attribute_name']
                    );
                    break;
                case 'customer_address/billing':
                case 'customer_address/shipping':
                    $attribute = $this->eavSetup->getAttribute(
                        AttributeProvider::ENTITY,
                        $customerToContactAndAccount['magento_attribute_name']
                    );
                    break;
            }

            if (isset($attribute['backend_type'])) {
                $customerToContactAndAccount['attribute_type'] =
                    $attribute['backend_type'];
            }

            $setup->getConnection()
                ->insertOnDuplicate(
                    $setup->getTable('tnw_salesforce_mapper'),
                    $customerToContactAndAccount
                );
        }
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}

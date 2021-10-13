<?php
declare(strict_types=1);

namespace TNW\Salesforce\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateCompanyNameMapping implements DataPatchInterface
{
    /**
     * ModuleDataSetupInterface
     *
     * @var ModuleDataSetupInterface
     */
    private $_moduleDataSetup;

    /**
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $salesforceSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    )
    {
        $this->_moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     *
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [UpdateAttributeSalesForce::class];
    }

    /**
     * {@inheritdoc}
     *
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @return DataPatchInterface|void
     */
    public function apply()
    {
        $setup = $this->_moduleDataSetup;
        $setup->startSetup();

        $connection = $setup->getConnection();

        /** Account */
        $connection->update(
            $setup->getTable('tnw_salesforce_mapper'),
            [
                "magento_attribute_name" => 'sf_company',
                "is_default" => true,
                "magento_entity_type" => 'customer'
            ],
            [
                'magento_attribute_name = ?' => 'company',
                'salesforce_attribute_name = ?' => 'Name',
                'object_type = ?' => 'Account'
            ]
        );

        $setup->endSetup();
    }
}

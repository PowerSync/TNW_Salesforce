<?php

declare(strict_types=1);

namespace TNW\Salesforce\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpgradeData0102 implements DataPatchInterface, PatchVersionInterface
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
        return [UpgradeData0101::class];
    }

    public static function getVersion()
    {
        return '0.1.0.2';
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        /**  prepare default mapping for customer to contact */
        $customerToContact = [
            [
                'magento_attribute_name' => 'sforce_id',
                'salesforce_attribute_name' => 'Id',
                'attribute_type' => 'string',
                'object_type' => 'Contact',
                'magento_entity_type' => 'customer',
                'is_default' => 1
            ],
            [
                'magento_attribute_name' => 'lastname',
                'salesforce_attribute_name' => 'LastName',
                'attribute_type' => 'string',
                'object_type' => 'Contact',
                'is_default' => 1
            ],
            [
                'magento_attribute_name' => 'email',
                'salesforce_attribute_name' => 'Email',
                'attribute_type' => 'string',
                'object_type' => 'Contact',
                'is_default' => 1
            ],
            [
                'magento_attribute_name' => 'firstname',
                'salesforce_attribute_name' => 'FirstName',
                'attribute_type' => 'string',
                'object_type' => 'Contact',
            ],
            [
                'magento_attribute_name' => 'dob',
                'salesforce_attribute_name' => 'Birthdate',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ]

        ];

        foreach ($customerToContact as $magento_attr) {
            $attribute = $this->eavSetup->getAttribute(
                Customer::ENTITY,
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $magento_attr['magento_entity_type'] = 'customer';
            $this->moduleDataSetup->getConnection()->insertOnDuplicate($this->moduleDataSetup->getTable('tnw_salesforce_mapper'), $magento_attr);
        }

        /** Customer Address to Account and Contact */
        $customerShippingToContact = [
            [
                'magento_attribute_name' => 'street',
                'salesforce_attribute_name' => 'MailingStreet',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [
                'magento_attribute_name' => 'city',
                'salesforce_attribute_name' => 'MailingCity',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [
                'magento_attribute_name' => 'region',
                'salesforce_attribute_name' => 'MailingState',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [
                'magento_attribute_name' => 'postcode',
                'salesforce_attribute_name' => 'MailingPostalCode',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [
                'magento_attribute_name' => 'country_id',
                'salesforce_attribute_name' => 'MailingCountry',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [
                'magento_attribute_name' => 'telephone',
                'salesforce_attribute_name' => 'Phone',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ]
        ];

        foreach ($customerShippingToContact as $magento_attr) {
            $attribute = $this->eavSetup->getAttribute(
                'customer_address',
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['magento_entity_type'] = 'customer_address/shipping';
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $this->moduleDataSetup->getConnection()->insertOnDuplicate($this->moduleDataSetup->getTable('tnw_salesforce_mapper'), $magento_attr);
        }

        /** @var  array $customerBillingToContact */
        $customerBillingToContact = [
            [
                'magento_attribute_name' => 'street',
                'salesforce_attribute_name' => 'OtherStreet',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [
                'magento_attribute_name' => 'city',
                'salesforce_attribute_name' => 'OtherCity',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [
                'magento_attribute_name' => 'region_id',
                'salesforce_attribute_name' => 'OtherState',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [
                'magento_attribute_name' => 'postcode',
                'salesforce_attribute_name' => 'OtherPostalCode',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [
                'magento_attribute_name' => 'country_id',
                'salesforce_attribute_name' => 'OtherCountry',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            [
                'magento_attribute_name' => 'telephone',
                'salesforce_attribute_name' => 'OtherPhone',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ]
        ];

        foreach ($customerBillingToContact as $magento_attr) {
            $attribute = $this->eavSetup->getAttribute(
                'customer_address',
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $magento_attr['magento_entity_type'] = 'customer_address/billing';
            $this->moduleDataSetup->getConnection()->insertOnDuplicate($this->moduleDataSetup->getTable('tnw_salesforce_mapper'), $magento_attr);
        }

        /**  prepare default mapping for customer to account */
        $customerToAccount = [
            [
                'magento_attribute_name' => 'sforce_id',
                'salesforce_attribute_name' => 'Id',
                'attribute_type' => 'string',
                'object_type' => 'Account',
                'magento_entity_type' => 'customer',
                'is_default' => 1
            ],
            [
                'magento_attribute_name' => 'firstname',
                'salesforce_attribute_name' => 'FirstName',
                'attribute_type' => 'string',
                'object_type' => 'Account'
            ],
            [
                'magento_attribute_name' => 'lastname',
                'salesforce_attribute_name' => 'LastName',
                'attribute_type' => 'string',
                'object_type' => 'Account'
            ],
            [
                'magento_attribute_name' => 'dob',
                'salesforce_attribute_name' => 'Birthdate',
                'attribute_type' => 'string',
                'object_type' => 'Account'
            ]

        ];

        foreach ($customerToAccount as $magento_attr) {
            $attribute = $this->eavSetup->getAttribute(
                Customer::ENTITY,
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $magento_attr['magento_entity_type'] = 'customer';
            $this->moduleDataSetup->getConnection()->insertOnDuplicate($this->moduleDataSetup->getTable('tnw_salesforce_mapper'), $magento_attr);
        }

        /** Customer Address to Account */

        /** @var array $customerShippingToAccount */
        $customerShippingToAccount = [
            [
                'magento_attribute_name' => 'street',
                'salesforce_attribute_name' => 'ShippingStreet',
                'attribute_type' => 'string',
                'object_type' => 'Account'
            ],
            [
                'magento_attribute_name' => 'city',
                'salesforce_attribute_name' => 'ShippingCity',
                'attribute_type' => 'string',
                'object_type' => 'Account'
            ],
            [
                'magento_attribute_name' => 'region_id',
                'salesforce_attribute_name' => 'ShippingState',
                'attribute_type' => 'string',
                'object_type' => 'Account'
            ],
            [
                'magento_attribute_name' => 'postcode',
                'salesforce_attribute_name' => 'ShippingPostalCode',
                'attribute_type' => 'string',
                'object_type' => 'Account'
            ],
            [
                'magento_attribute_name' => 'country_id',
                'salesforce_attribute_name' => 'ShippingCountry',
                'attribute_type' => 'string',
                'object_type' => 'Account'
            ],
            [
                'magento_attribute_name' => 'telephone',
                'salesforce_attribute_name' => 'Phone',
                'attribute_type' => 'string',
                'object_type' => 'Account'
            ]
        ];

        foreach ($customerShippingToAccount as $magento_attr) {
            $attribute = $this->eavSetup->getAttribute(
                'customer_address',
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['magento_entity_type'] = 'customer_address/shipping';
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $this->moduleDataSetup->getConnection()->insertOnDuplicate($this->moduleDataSetup->getTable('tnw_salesforce_mapper'), $magento_attr);
        }

        /** @var  array $customerBillingToContact */
        $customerBillingToAccount = [
            [
                'magento_attribute_name' => 'street',
                'salesforce_attribute_name' => 'BillingStreet',
                'attribute_type' => 'string',
                'object_type' => 'Account'
            ],
            [
                'magento_attribute_name' => 'city',
                'salesforce_attribute_name' => 'BillingCity',
                'attribute_type' => 'string',
                'object_type' => 'Account'
            ],
//                [
//                    'magento_attribute_name' => 'region_id',
//                    'salesforce_attribute_name' => 'BillingState',
//                    'attribute_type' => 'string',
//                    'object_type' => 'Account'
//                ],
            [
                'magento_attribute_name' => 'postcode',
                'salesforce_attribute_name' => 'BillingPostalCode',
                'attribute_type' => 'string',
                'object_type' => 'Account'
            ],
            [
                'magento_attribute_name' => 'country_id',
                'salesforce_attribute_name' => 'BillingCountry',
                'attribute_type' => 'string',
                'object_type' => 'Account'
            ],
        ];

        foreach ($customerBillingToAccount as $magento_attr) {
            $attribute = $this->eavSetup->getAttribute(
                'customer_address',
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $magento_attr['magento_entity_type'] = 'customer_address/billing';
            $this->moduleDataSetup->getConnection()->insertOnDuplicate($this->moduleDataSetup->getTable('tnw_salesforce_mapper'), $magento_attr);
        }
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

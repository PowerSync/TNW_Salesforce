<?php

declare(strict_types=1);

namespace TNW\Salesforce\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class InstallData implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
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

    public static function getDependencies()
    {
        return [];
    }

    public static function getVersion()
    {
        return '0.0.0';
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        /** @var EavSetup $eavSetup */

        $this->eavSetup->addAttribute(
            Customer::ENTITY,
            'sforce_id',
            [
                'type' => 'varchar',
                'required' => false,
                'sort_order' => 1,
                'visible' => true,
                'system' => false,
                'group' => 'Account Information',
                'default' => null,
                'label' => 'Salesforce Contact Id'
            ]
        );

        $this->eavSetup->addAttribute(
            Customer::ENTITY,
            'sforce_account_id',
            [
                'type' => 'varchar',
                'required' => false,
                'sort_order' => 2,
                'visible' => true,
                'system' => false,
                'group' => 'Account Information',
                'default' => null,
                'label' => 'Salesforce Account Id'
            ]
        );

        $this->eavSetup->addAttribute(
            Customer::ENTITY,
            'sync_status',
            [
                'type' => 'int',
                'required' => false,
                'sort_order' => 3,
                'visible' => true,
                'system' => false,
                'group' => 'Account Information',
                'default' => 0,
                'label' => 'Sync Status'
            ]
        );


        //prepare default mapping for customer to contact
        $customerToContact = [
            ['magento_attribute_name' => 'email',
                'salesforce_attribute_name' => 'Email',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            ['magento_attribute_name' => 'firstname',
                'salesforce_attribute_name' => 'FirstName',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            ['magento_attribute_name' => 'lastname',
                'salesforce_attribute_name' => 'LastName',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            ['magento_attribute_name' => 'dob',
                'salesforce_attribute_name' => 'Birthdate',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ]

        ];

        foreach ($customerToContact as $magento_attr) {

            $attribute = $this->eavSetup->getAttribute(Customer::ENTITY,
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $magento_attr['magento_entity_type'] = 'customer';
            $this->moduleDataSetup->getConnection()->insert($this->moduleDataSetup->getTable('tnw_salesforce_mapper'), $magento_attr);
        }

        // Customer Address to Account and Contact
        $customerShippingToContact = [
            ['magento_attribute_name' => 'street',
                'salesforce_attribute_name' => 'MailingStreet',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            ['magento_attribute_name' => 'city',
                'salesforce_attribute_name' => 'MailingCity',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            ['magento_attribute_name' => 'region',
                'salesforce_attribute_name' => 'MailingState',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            ['magento_attribute_name' => 'postcode',
                'salesforce_attribute_name' => 'MailingPostalCode',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            ['magento_attribute_name' => 'country_id',
                'salesforce_attribute_name' => 'MailingCountry',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            ['magento_attribute_name' => 'telephone',
                'salesforce_attribute_name' => 'Phone',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ]
        ];

        foreach ($customerShippingToContact as $magento_attr) {

            $attribute = $this->eavSetup->getAttribute('customer_address',
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['magento_entity_type'] = 'customer_address/shipping';
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $this->moduleDataSetup->getConnection()->insert($this->moduleDataSetup->getTable('tnw_salesforce_mapper'), $magento_attr);
        }

        /** @var  $customerBillingToContact */
        $customerBillingToContact = [
            ['magento_attribute_name' => 'company',
                'salesforce_attribute_name' => 'Name',
                'attribute_type' => 'string',
                'object_type' => 'Account'
            ],
            ['magento_attribute_name' => 'street',
                'salesforce_attribute_name' => 'OtherStreet',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            ['magento_attribute_name' => 'city',
                'salesforce_attribute_name' => 'OtherCity',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            ['magento_attribute_name' => 'region',
                'salesforce_attribute_name' => 'OtherState',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            ['magento_attribute_name' => 'postcode',
                'salesforce_attribute_name' => 'OtherPostalCode',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            ['magento_attribute_name' => 'country_id',
                'salesforce_attribute_name' => 'OtherCountry',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ],
            ['magento_attribute_name' => 'telephone',
                'salesforce_attribute_name' => 'OtherPhone',
                'attribute_type' => 'string',
                'object_type' => 'Contact'
            ]
        ];

        foreach ($customerBillingToContact as $magento_attr) {

            $attribute = $this->eavSetup->getAttribute('customer_address',
                $magento_attr['magento_attribute_name']
            );
            $magento_attr['attribute_id'] = $attribute['attribute_id'];
            $magento_attr['magento_entity_type'] = 'customer_address/billing';
            $this->moduleDataSetup->getConnection()->insert($this->moduleDataSetup->getTable('tnw_salesforce_mapper'), $magento_attr);
        }

        $this->moduleDataSetup->endSetup();
    }
}

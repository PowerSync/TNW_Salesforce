<?php
namespace  TNW\Salesforce\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Mapper
 */
class Mapper extends AbstractDb
{
    /**
     * Construct
     */
    public function _construct()
    {
        $this->_init('tnw_salesforce_mapper', 'map_id');
    }

    /**
     * @inheritDoc
     */
    protected function _initUniqueFields()
    {
        $this->_uniqueFields = [[
            'field' => [
                'object_type',
                'magento_entity_type',
                'magento_attribute_name',
                'salesforce_attribute_name',
                'website_id'
            ],
            'title' => __(
                'Mapper with for such %1, %2, %3, %4, %5',
                __('Object Type'),
                __('Magento Object'),
                __('Magento Attribute'),
                __('Magento Attribute'),
                __('Website')
            )
        ]];

        return $this;
    }
}

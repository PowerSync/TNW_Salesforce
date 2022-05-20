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
            ],
            'title' => __(
                'Mapper with same %1 and %2',
                __('Magento Attribute'),
                __('Salesforce Attribute')
            )
        ]];

        return $this;
    }
}

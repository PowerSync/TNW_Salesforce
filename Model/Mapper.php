<?php

namespace TNW\Salesforce\Model;

use Magento\Framework\DataObject\IdentityInterface;

class Mapper extends \Magento\Framework\Model\AbstractModel implements IdentityInterface
{
    /**
     * CMS block cache tag
     */
    protected $cache_tag = '';
    protected $map_object = '';

    const CUSTOM_ATTRIBUTE_CODE = 'custom';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\TNW\Salesforce\Model\ResourceModel\Mapper::class);
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities()
    {
        return [$this->cache_tag . '_' .$this->getId()];
    }

    /**
     * Get Unique Id
     *
     * @return int
     */
    public function getMapId()
    {
        return $this->_getData('map_id');
    }

    /**
     * Get Magento Attribute Name
     *
     * @return String
     */
    public function getMagentoAttributeName()
    {
        return $this->_getData('magento_attribute_name');
    }

    /**
     * Get Salesforce Attribute Name
     *
     * @return String
     */
    public function getSalesforceAttributeName()
    {
        return $this->_getData('salesforce_attribute_name');
    }

    /**
     * Get Default Attribute Value
     *
     * @return String
     */
    public function getDefaultValue()
    {
        return $this->_getData('default_value');
    }

    /**
     * Get Magento Entity Type / Subtype
     *
     * @return String
     */
    public function getMagentoEntityType()
    {
        return $this->_getData('magento_entity_type');
    }

    /**
     * Get Attribute Id
     *
     * @return String
     */
    public function getAttributeId()
    {
        return $this->_getData('attribute_id');
    }

    /**
     * Get Default Attribute Type
     *
     * @return String
     */
    public function getAttributeType()
    {
        return $this->_getData('attribute_type');
    }

    /**
     * Get Object type
     *
     * @return String
     */
    public function getObjectType()
    {
        return $this->map_object;
    }
}

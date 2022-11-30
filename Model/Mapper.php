<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

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

    public const MAGENTO_ENTITY_TYPE_CUSTOMER = 'customer';
    public const MAGENTO_ENTITY_TYPE_PRODUCT = 'catalog_product';

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
     * @return string
     */
    public function getMagentoAttributeName(): string
    {
        return (string)$this->_getData('magento_attribute_name');
    }

    /**
     * Get Salesforce Attribute Name
     *
     * @return string
     */
    public function getSalesforceAttributeName(): string
    {
        return (string)$this->_getData('salesforce_attribute_name');
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
     * @return string
     */
    public function getAttributeType(): string
    {
        return (string)$this->_getData('attribute_type');
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

    /**
     * Get skip blank values flag value
     *
     * @return bool
     */
    public function getSkipBlankValues(): bool
    {
        return (bool)$this->_getData('skip_blank_values');
    }
}

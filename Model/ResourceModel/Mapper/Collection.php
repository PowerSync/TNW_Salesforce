<?php

namespace  TNW\Salesforce\Model\ResourceModel\Mapper;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('TNW\Salesforce\Model\Mapper', 'TNW\Salesforce\Model\ResourceModel\Mapper');
    }

    /**
     * @param string $objectType
     * @return $this
     */
    public function addObjectToFilter($objectType)
    {
        return $this->addFieldToFilter('object_type', ['eq' => $objectType]);
    }

    /**
     * @param string $entityType
     * @return $this
     */
    public function addEntityToFilter($entityType)
    {
        return $this->addFieldToFilter('magento_entity_type', ['eq' => $entityType]);
    }

    /**
     * @inheritdoc
     */
    public function addItem(\Magento\Framework\DataObject $item)
    {
        if (null === $this->_getItemId($item)) {
            $item->setData($this->getResource()->getIdFieldName(), sprintf('system_%d', count($this->_items)));
        }

        return parent::addItem($item);
    }

    /**
     * Id field name getter
     *
     * @return string
     */
    public function getIdFieldName()
    {
        if ($this->_idFieldName === null) {
            $this->_setIdFieldName($this->getResource()->getIdFieldName());
        }

        return $this->_idFieldName;
    }
}

<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace  TNW\Salesforce\Model\ResourceModel\Mapper;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var int
     */
    private $uniquenessWebsite;

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\TNW\Salesforce\Model\Mapper::class, \TNW\Salesforce\Model\ResourceModel\Mapper::class);
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
     * Apply Uniqueness By Website
     *
     * @param int $websiteId
     * @return $this
     */
    public function applyUniquenessByWebsite($websiteId)
    {
        $this->uniquenessWebsite = (int)$websiteId;
        return $this;
    }

    /**
     * Get all data array for collection
     *
     * @return array
     * @throws \Zend_Db_Select_Exception
     */
    public function getData()
    {
        if ($this->_data === null) {
            $this->_renderFilters()->_renderOrders()->_renderLimit();

            $select = $this->getSelect();
            if (null !== $this->uniquenessWebsite) {
                $select = $this->generateUniquenessByWebsiteSelect($select);
            }

            $this->_data = $this->_fetchAll($select);
            $this->_afterLoadData();
        }

        return $this->_data;
    }

    /**
     * @return bool|\Magento\Framework\DataObject|\Magento\Framework\Model\AbstractModel
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function fetchItem()
    {
        if (null === $this->_fetchStmt) {
            $this->_renderOrders()->_renderLimit();

            $select = $this->getSelect();
            if (null !== $this->uniquenessWebsite) {
                $select = $this->generateUniquenessByWebsiteSelect($select);
            }

            $this->_fetchStmt = $this->getConnection()->query($select);
        }

        $data = $this->_fetchStmt->fetch();
        if (!empty($data) && is_array($data)) {
            $item = $this->getNewEmptyItem();
            if ($this->getIdFieldName()) {
                $item->setIdFieldName($this->getIdFieldName());
            }
            $item->setData($data);

            return $item;
        }

        return false;
    }

    /**
     * Generate Uniqueness By Website Select
     *
     * @param \Zend_Db_Select $baseSelect
     * @return \Zend_Db_Select
     * @throws \Zend_Db_Select_Exception
     */
    public function generateUniquenessByWebsiteSelect($baseSelect)
    {
        if (0 === $this->uniquenessWebsite) {

            return (clone $baseSelect)
                ->where('website_id = ?', $this->uniquenessWebsite);
        }

        $uniqueIdSelectByWebsite = (clone $baseSelect)
            ->where('website_id IN(0, ?)', $this->uniquenessWebsite)
            ->order('website_id','ASC');

        return $uniqueIdSelectByWebsite;
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getIdFieldName()
    {
        if ($this->_idFieldName === null) {
            $this->_setIdFieldName($this->getResource()->getIdFieldName());
        }

        return $this->_idFieldName;
    }
}

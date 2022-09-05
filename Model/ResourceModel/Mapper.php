<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace  TNW\Salesforce\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use TNW\Salesforce\Model\Mapper as ModelMapper;
use TNW\Salesforce\Service\MessageQueue\RestartConsumers;

/**
 * Class Mapper
 */
class Mapper extends AbstractDb
{
    /** @var RestartConsumers */
    private $restartConsumers;

    /**
     * @param Context          $context
     * @param RestartConsumers $restartConsumers
     * @param null             $connectionName
     */
    public function __construct(
        Context $context,
        RestartConsumers $restartConsumers,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->restartConsumers = $restartConsumers;
    }

    /**
     * Construct
     */
    public function _construct()
    {
        $this->_init('tnw_salesforce_mapper', 'map_id');
    }

    /**
     * Safe loader
     *
     * @param ModelMapper $object
     * @param string      $objectType
     * @param string      $magentoEntityType
     * @param string      $magentoAttributeName
     * @param string      $salesForceAttributeName
     *
     * @return void
     * @throws LocalizedException
     */
    public function loadByUniqueFields(
        ModelMapper $object,
        string      $objectType,
        string      $magentoEntityType,
        string      $magentoAttributeName,
        string      $salesForceAttributeName
    ): void {
        $connection = $this->getConnection();
        $select = $connection->select()->from($this->getMainTable(), ['map_id']);
        $select->where('object_type = ?', $objectType);
        $select->where('magento_entity_type = ?', $magentoEntityType);
        $select->where('magento_attribute_name = ?', $magentoAttributeName);
        $select->where('salesforce_attribute_name = ?', $salesForceAttributeName);
        $mapId = $this->getConnection()->fetchOne($select);
        if ($mapId) {
            $this->load($object, $mapId, 'map_id');
        }
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

    /**
     * @inheritDoc
     */
    protected function _afterSave(AbstractModel $object)
    {
        parent::_afterSave($object);

        $this->restartConsumers->execute();

        return $this;
    }
}

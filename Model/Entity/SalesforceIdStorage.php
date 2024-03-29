<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\Entity;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\ResourceModel\Objects;
use TNW\Salesforce\Service\Model\ResourceModel\Objects\MassLoadObjectIds;

class SalesforceIdStorage
{
    const MAGENTO_TYPE_ORDER = 'Order';
    const MAGENTO_TYPE_ORDER_ITEM = 'Order Item';
    const MAGENTO_TYPE_ORDER_NOTE = 'Order Note';
    const MAGENTO_TYPE_QUOTE = 'Quote';
    const MAGENTO_TYPE_QUOTE_ITEM = 'Quote Item';
    const MAGENTO_TYPE_PRODUCT = 'Product';
    const MAGENTO_TYPE_WEBSITE = 'Website';
    const MAGENTO_TYPE_CUSTOMER = 'Customer';
    const MAGENTO_TYPE_ORDER_INVOICE = 'Order Invoice';
    const MAGENTO_TYPE_ORDER_INVOICE_NOTE = 'Order Invoice Note';
    const MAGENTO_TYPE_ORDER_INVOICE_ITEM = 'Order Invoice Item';
    const MAGENTO_TYPE_ORDER_SHIPMENT = 'Order Shipment';
    const MAGENTO_TYPE_ORDER_SHIPMENT_NOTE = 'Order Shipment Note';
    const MAGENTO_TYPE_ORDER_SHIPMENT_ITEM = 'Order Shipment Item';
    const MAGENTO_TYPE_ORDER_SHIPMENT_TRACK = 'Order Shipment Track';

    /**
     * @var MassLoadObjectIds
     */
    protected $massLoadObjectIds;

    /**
     * @var string
     */
    private $magentoType;

    /**
     * @var array
     */
    private $mappingAttribute;

    /**
     * @var Objects
     */
    private $resourceObjects;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $config;

    /** @var array */
    private $records = [];

    private $cacheForUpdateStatus = [];

    /**
     * @param string $magentoType
     * @param array $mappingAttribute
     * @param Objects $resourceObjects
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param MassLoadObjectIds $massLoadObjectIds
     */
    public function __construct(
        string $magentoType,
        array $mappingAttribute,
        Objects $resourceObjects,
        StoreManagerInterface $storeManager,
        Config $config,
        MassLoadObjectIds $massLoadObjectIds
    ) {
        $this->resourceObjects = $resourceObjects;
        $this->magentoType = $magentoType;
        $this->mappingAttribute = $mappingAttribute;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->massLoadObjectIds = $massLoadObjectIds;
    }

    /**
     * @return string
     */
    public function getMagentoType()
    {
        return $this->magentoType;
    }

    /**
     * @param string $magentoType
     */
    public function setMagentoType(string $magentoType)
    {
        $this->magentoType = $magentoType;
    }

    /**
     * @return array
     */
    public function getMappingAttribute()
    {
        return $this->mappingAttribute;
    }

    /**
     * Load
     *
     * @param AbstractModel $entity
     * @param null|bool|int|string|WebsiteInterface $website
     *
     * @throws LocalizedException
     */
    public function load($entity, $website = null)
    {
        $objectIds = $this->loadObjectIds($entity, $website);

        foreach ($this->mappingAttribute as $attributeKey => $objectName) {
            if (empty($objectIds[$objectName])) {
                continue;
            }

            $entity->setData($attributeKey, $objectIds[$objectName]);
        }
    }

    /**
     * Save
     *
     * @param AbstractModel $entity
     * @param null|bool|int|string|WebsiteInterface $website
     *
     * @throws LocalizedException
     */
    public function save($entity, $website = null)
    {
        $records = [];
        foreach ($this->mappingAttribute as $attributeKey => $objectName) {
            $records[] = [
                'magento_type' => $this->magentoType,
                'entity_id' => $entity->getId(),
                'object_id' => $this->valueByAttribute($entity, $attributeKey),
                'salesforce_type' => $objectName,
                'website_id' => $this->prepareWebsiteId($website)
            ];
        }

        $this->resourceObjects->saveRecords($records);
    }

    /**
     * Save By Attribute
     *
     * @param AbstractModel $entity
     * @param string $attributeName
     * @param null|bool|int|string|WebsiteInterface $website
     *
     * @throws LocalizedException
     */
    public function saveByAttribute($entity, $attributeName, $website = null)
    {
        $salesforceId = $this->valueByAttribute($entity, $attributeName);
        $this->saveValueByAttribute($entity, $salesforceId, $attributeName, $website);
    }

    /**
     * Save Value By Attribute
     *
     * @param AbstractModel $entity
     * @param string $value
     * @param string $attributeName
     * @param null|bool|int|string|WebsiteInterface $website
     *
     * @throws LocalizedException
     */
    public function saveValueByAttribute($entity, $value, $attributeName, $website = null)
    {
        $entity_id = $entity->getId();
        $magento_type = $this->magentoType;
        if (null === $entity->getId()) {
            if (!($queue = $entity->getData('_queue')) || (null === $queue->getEntityId())) {
                return;
            }
            $entity_id = $queue->getEntityId();
            $magento_type = $queue->getEntityLoad();
        }

        $records[] = [
            'magento_type' => $magento_type,
            'entity_id' => $entity_id,
            'object_id' => $value,
            'salesforce_type' => $this->objectByAttribute($attributeName),
            'website_id' => $this->prepareWebsiteId($website)
        ];

        $this->resourceObjects->saveRecords($records);
    }

    /**
     * Save Value By Attribute
     *
     * @param AbstractModel $entity
     * @param string $value
     * @param string $attributeName
     * @param null|bool|int|string|WebsiteInterface $website
     *
     * @throws LocalizedException
     */
    public function addRecordsToCache($entity, $value, $attributeName, $website = null)
    {
        $entity_id = $entity->getId();
        $magento_type = $this->magentoType;
        if (null === $entity->getId()) {
            if (!($queue = $entity->getData('_queue')) || (null === $queue->getEntityId())) {
                return;
            }
            $entity_id = $queue->getEntityId();
            $magento_type = $queue->getEntityLoad();
        }

        $this->records[] = [
            'magento_type' => $magento_type,
            'entity_id' => $entity_id,
            'object_id' => $value,
            'salesforce_type' => $this->objectByAttribute($attributeName),
            'website_id' => $this->prepareWebsiteId($website)
        ];
    }

    public function saveRecordsFromCache()
    {
        $records = $this->records;
        $this->records = [];
        $this->resourceObjects->saveRecords($records);
    }

    /**
     * Save Status
     *
     * @param AbstractModel $entity
     * @param bool $status
     * @param null|bool|int|string|WebsiteInterface $website
     *
     * @throws LocalizedException
     */
    public function saveStatus($entity, $status, $website = null)
    {
        if (null === $entity->getId()) {
            return;
        }

        $this->resourceObjects
            ->saveStatus(
                $entity->getId(),
                $this->magentoType,
                $status,
                $this->prepareWebsiteId($website)
            );
    }

    /**
     * Save Status
     *
     * @param AbstractModel $entity
     * @param bool $status
     * @param null|bool|int|string|WebsiteInterface $website
     *
     * @throws LocalizedException
     */
    public function saveStatusFromCache()
    {
        $this->resourceObjects
            ->saveStatusMass($this->cacheForUpdateStatus);
        $this->cacheForUpdateStatus = [];
    }

    /**
     * Save Status
     *
     * @param AbstractModel $entity
     * @param bool $status
     * @param null|bool|int|string|WebsiteInterface $website
     *
     * @throws LocalizedException
     */
    public function addStatusToCacheForMassUpdate($entity, $status, $website = null)
    {
        if (null === $entity->getId()) {
            return;
        }

        $websiteId = $this->prepareWebsiteId($website);
        $this->cacheForUpdateStatus[$websiteId][$status][$this->magentoType][] = $entity->getId();
    }

    /**
     * Value By Attribute
     *
     * @param AbstractModel $entity
     * @param string $attributeName
     *
     * @return mixed
     */
    public function valueByAttribute($entity, $attributeName)
    {
        $attributeName = (string)$attributeName;
        $method = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $attributeName)));
        if (method_exists($entity, $method)) {
            return $entity->{$method}();
        } elseif (method_exists($entity, 'getData')) {
            return $entity->getData($attributeName);
        } else {
            $dump = $entity->__toArray();
            return $dump[$attributeName];
        }
        return null;
    }

    /**
     * Object By Attribute
     *
     * @param string $attributeName
     *
     * @return false|int|string
     * @throws LocalizedException
     */
    public function objectByAttribute($attributeName)
    {
        if (empty($this->mappingAttribute[$attributeName])) {
            throw new LocalizedException(__('Unknown Salesforce object name'));
        }

        return $this->mappingAttribute[$attributeName];
    }

    /**
     * Prepare Website Id
     *
     * @param null|bool|int|string|WebsiteInterface $website
     *
     * @return int
     * @throws LocalizedException
     */
    public function prepareWebsiteId($website): int
    {
        $websiteId = $this->storeManager->getWebsite($website)->getId();
        return (int)$this->config->baseWebsiteIdLogin($websiteId);
    }

    /**
     * @return \TNW\Salesforce\Model\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param AbstractModel $entity
     * @param string $attributeName
     * @param null|bool|int|string|WebsiteInterface $website
     *
     * @return bool
     * @throws LocalizedException
     */
    public function recordExist($entity, $attributeName, $website = null)
    {
        $objectIds = $this->loadObjectIds($entity, $website);

        return array_key_exists($attributeName, $objectIds);
    }

    /**
     * @param AbstractModel $entity
     * @param null|bool|int|string|WebsiteInterface $website
     *
     * @return array
     * @throws LocalizedException
     * @throws Exception
     */
    public function loadObjectIds($entity, $website = null)
    {
        if (empty($this->magentoType)) {
            throw new Exception('magentoType was not defined!');
        }

        return $this->massLoadObjectIds->loadObjectIds(
            $entity->getId(),
            $this->magentoType,
            $this->prepareWebsiteId($website)
        );
    }

    /**
     * @param array $entities
     * @param       $website
     *
     * @return array
     * @throws LocalizedException
     */
    public function massLoadObjectIds(array $entities, $website = null)
    {
        if (empty($this->magentoType)) {
            throw new Exception('magentoType was not defined!');
        }

        $entityIds = [];
        foreach ($entities as $entity) {
            $entityId = $entity->getId();
            $entityId !== null && $entityIds[] = $entityId;
        }

        return $this->massLoadObjectIds->massLoadObjectIds(
            $entityIds,
            $this->magentoType,
            $this->prepareWebsiteId($website)
        );
    }
}

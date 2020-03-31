<?php
namespace TNW\Salesforce\Model\Entity;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\ResourceModel\Objects;

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

    /**
     * ObjectAbstract constructor.
     *
     * @param string $magentoType
     * @param array $mappingAttribute
     * @param Objects $resourceObjects
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     */
    public function __construct(
        $magentoType,
        array $mappingAttribute,
        Objects $resourceObjects,
        StoreManagerInterface $storeManager,
        Config $config
    ) {
        $this->resourceObjects = $resourceObjects;
        $this->magentoType = $magentoType;
        $this->mappingAttribute = $mappingAttribute;
        $this->storeManager = $storeManager;
        $this->config = $config;
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
        if (empty($this->magentoType)) {
            throw new Exception('magentoType was not defined!');
        }

        $objectIds = $this->resourceObjects
            ->loadObjectIds(
                $entity->getId(),
                $this->magentoType,
                $this->prepareWebsiteId($website)
            );

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
        if (null === $entity->getId()) {
            return;
        }

        $records[] = [
            'magento_type' => $this->magentoType,
            'entity_id' => $entity->getId(),
            'object_id' => $value,
            'salesforce_type' => $this->objectByAttribute($attributeName),
            'website_id' => $this->prepareWebsiteId($website)
        ];

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
     * Value By Attribute
     *
     * @param AbstractModel $entity
     * @param string $attributeName
     *
     * @return mixed
     */
    public function valueByAttribute($entity, $attributeName)
    {
        return $entity->getData($attributeName);
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
    public function prepareWebsiteId($website)
    {
        $websiteId = $this->storeManager->getWebsite($website)->getId();
        return $this->config->baseWebsiteIdLogin($websiteId);
    }
}

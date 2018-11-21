<?php
namespace TNW\Salesforce\Model\Entity;

use Magento\Framework\Exception\LocalizedException;

class Object
{
    /**
     * @var string
     */
    private $magentoType;

    /**
     * @var array
     */
    private $mappingAttribute;

    /**
     * @var \TNW\Salesforce\Model\ResourceModel\Objects
     */
    private $resourceObjects;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \TNW\Salesforce\Model\Config
     */
    private $config;

    /**
     * ObjectAbstract constructor.
     *
     * @param string $magentoType
     * @param array $mappingAttribute
     * @param \TNW\Salesforce\Model\ResourceModel\Objects $resourceObjects
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \TNW\Salesforce\Model\Config $config
     */
    public function __construct(
        $magentoType,
        array $mappingAttribute,
        \TNW\Salesforce\Model\ResourceModel\Objects $resourceObjects,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \TNW\Salesforce\Model\Config $config
    ) {
        $this->resourceObjects = $resourceObjects;
        $this->magentoType = $magentoType;
        $this->mappingAttribute = $mappingAttribute;
        $this->storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $website
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function load($entity, $website = null)
    {
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
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $website
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save($entity, $website = null)
    {
        $records = [];
        foreach ($this->mappingAttribute as $attributeKey => $objectName) {
            $records[] = [
                'magento_type' => $this->magentoType,
                'entity_id' => $entity->getId(),
                'object_id' => $entity->getData($attributeKey),
                'salesforce_type' => $objectName,
                'website_id' => $this->prepareWebsiteId($website)
            ];
        }

        $this->resourceObjects->saveRecords($records);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @param string $objectName
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $website
     *
     * @throws LocalizedException
     */
    public function saveByObject($entity, $objectName, $website = null)
    {
        $records[] = [
            'magento_type' => $this->magentoType,
            'entity_id' => $entity->getId(),
            'object_id' => $this->valueByObject($entity, $objectName),
            'salesforce_type' => $objectName,
            'website_id' => $this->prepareWebsiteId($website)
        ];

        $this->resourceObjects->saveRecords($records);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @param string $objectName
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function valueByObject($entity, $objectName)
    {
        $attribute = $this->attributeByObject($objectName);
        return $entity->getData($attribute);
    }

    /**
     * @param string $objectName
     *
     * @return false|int|string
     * @throws LocalizedException
     */
    public function attributeByObject($objectName)
    {
        $attribute = array_search($objectName, $this->mappingAttribute, true);
        if (null === $attribute) {
            throw new LocalizedException(__('Unknown Salesforce object name'));
        }

        return $attribute;
    }

    /**
     * @param $website
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function prepareWebsiteId($website)
    {
        $websiteId = $this->storeManager->getWebsite($website)->getId();
        return $this->config->uniqueWebsiteIdLogin($websiteId);
    }
}

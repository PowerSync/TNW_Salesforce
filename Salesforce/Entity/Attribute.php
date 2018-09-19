<?php
/**
 * Created by PhpStorm.
 * User: eermolaev
 * Date: 20.04.18
 * Time: 12:36
 */

namespace TNW\Salesforce\Salesforce\Entity;

class Attribute {

    const CACHE_ATTRIBUTE_LIST = 'attribute_list';

    /**
     * @var \Magento\Framework\Cache\FrontendInterface
     */
    protected $cache;

    /**
     * @var \TNW\Salesforce\Synchronize\Transport\Soap\ClientFactory
     */
    protected $factory;

    /**
     * @var array
     */
    private $objectTypeMap;

    /**
     * Soap constructor.
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \TNW\Salesforce\Synchronize\Transport\Soap\ClientFactory $factory
     */
    public function __construct(
        \Magento\Framework\Cache\FrontendInterface $cache = null,
        \TNW\Salesforce\Synchronize\Transport\Soap\ClientFactory $factory,
        array $objectTypeMap = []
    ) {
        $this->cache = $cache;
        $this->factory = $factory;
        $this->objectTypeMap = $objectTypeMap;

    }

    /**
     * @param $websiteId
     * @return \TNW\Salesforce\Lib\Tnw\SoapClient\Client
     */
    protected function getClient($websiteId = null)
    {
        return $this->factory->client($websiteId);
    }

    /**
     * @param $type
     * @return mixed
     * @throws \Exception
     */
    protected function getAttributeListByNativeName($type, $websiteId = null)
    {

        if ($result = $this->cache->load(self::CACHE_ATTRIBUTE_LIST . '_' . $type . '_' . $websiteId)) {
            return unserialize($result);
        }

        /**
         * @var \Tnw\SoapClient\Result\DescribeSObjectResult[] $resultObjects
         */
        try {
            $resultObjects = $this->getClient($websiteId)->describeSObjects([$type]);
        } catch (\Exception $e) {
            throw $e;
        }

        $resultObject = $resultObjects[0];
        $fields = $resultObject->getFields();

        foreach ($fields as $field) {
            $result[$field->getName()] = $field->getLabel();
        }

        $this->cache->save(serialize($result), self::CACHE_ATTRIBUTE_LIST . '_' . $type . '_' . $websiteId);
        return $result;
    }

    /**
     * Do Unit syncronization to Salesforce object
     *
     * @param $objectName
     * @return mixed
     * @throws \Exception
     */
    public function getAttributeList($objectName, $websiteId = null)
    {

        if (isset($this->objectTypeMap[$objectName])) {
            $objectName = $this->objectTypeMap[$objectName];
        }

        $results = $this->getAttributeListByNativeName($objectName, $websiteId);

        return $results;
    }
}
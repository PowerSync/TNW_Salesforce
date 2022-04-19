<?php

namespace TNW\Salesforce\Client;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\Cache\State;
use Magento\Framework\App\Cache\Type\Collection;
use Magento\Framework\App\State as AppState;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\Serialize\SerializerInterface;
use stdClass;
use Throwable;
use TNW\Salesforce\Model\Logger;
use TNW\Salesforce\Service\ObjectConvertor;
use Tnw\SoapClient\Client;
use Tnw\SoapClient\Result\DescribeSObjectResult;
use Tnw\SoapClient\Result\DescribeSObjectResult\Field;
use Tnw\SoapClient\Result\LoginResult;
use TNW\Salesforce\Lib\Tnw\SoapClient\ClientBuilder;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\Config\WebsiteDetector;

/**
 * Class Salesforce
 *
 * @package TNW\Salesforce\Client
 */
class Salesforce extends DataObject
{
    const CACHE_TAG = 'tnw_salesforce_client';
    const SFORCE_UPSERT_CHUNK_SIZE = 200;
    const SFORCE_URL_CACHE_IDENTIFIER = 'salesforce_url';
    const SFORCE_DESCRIBE_CACHE_IDENTIFIER = 'salesforce_describe_%s';

    /** @var Config  */
    protected $salesforceConfig;

    /** @var Collection  */
    protected $cacheCollection;

    /** @var Client[] */
    private $client = [];

    /** @var LoginResult[] $loginResult */
    private $loginResult = [];

    /** @var  State */
    protected $cacheState;

    /** @var  array */
    protected $handCache = [];

    /** @var Logger */
    protected $logger;

    /** @var WebsiteDetector  */
    protected $websiteDetector;

    /** @var SerializerInterface */
    private $serializer;

    /** @var ObjectConvertor|null */
    private $objectConvertor;

    /** @var AppState|null */
    private $appState;

    /**
     * @param Config                   $salesForceConfig
     * @param Collection               $cacheCollection
     * @param State                    $cacheState
     * @param Logger                   $logger
     * @param WebsiteDetector          $websiteDetector
     * @param ObjectManagerInterface   $objectManager
     * @param SerializerInterface|null $serializer
     * @param ObjectConvertor|null     $objectConvertor
     * @param AppState|null            $appState
     */
    public function __construct(
        Config $salesForceConfig,
        Collection $cacheCollection,
        State $cacheState,
        Logger $logger,
        WebsiteDetector $websiteDetector,
        ObjectManagerInterface $objectManager,
        SerializerInterface $serializer = null,
        ObjectConvertor $objectConvertor = null,
        AppState $appState = null
    ) {
        parent::__construct();
        $this->salesforceConfig = $salesForceConfig;
        $this->cacheCollection = $cacheCollection;
        $this->cacheState = $cacheState;
        $this->logger = $logger;
        $this->websiteDetector = $websiteDetector;
        $this->serializer = $serializer ?? $objectManager->get(Serialize::class);
        $this->objectConvertor = $objectConvertor ?? $objectManager->get(ObjectConvertor::class);
        $this->appState = $appState ?? $objectManager->get(AppState::class);
    }

    /**
     * Get Connect client, connected to Salesforce
     *
     * @param int|null $websiteId
     *
     * @return null|Client
     * @throws LocalizedException|FileSystemException|Throwable
     */
    public function getClient($websiteId = null)
    {
        $websiteId = $this->websiteDetector->detectCurrentWebsite($websiteId);
        $cacheKey = $this->salesforceConfig->baseWebsiteIdLogin($websiteId);

        if (empty($this->client[$cacheKey])) {
            try {
                $this->client[$cacheKey] = $this->buildClient(
                    $this->salesforceConfig->getSalesforceWsdl($websiteId),
                    $this->salesforceConfig->getSalesforceUsername($websiteId),
                    $this->salesforceConfig->getSalesforcePassword($websiteId),
                    $this->salesforceConfig->getSalesforceToken($websiteId)
                );
                $this->loginResult[$cacheKey] = $this->client[$cacheKey]->getLoginResult();
            } catch (Throwable $e) {
                $this->client[$cacheKey] = null;
                if ($this->appState->getAreaCode() === Area::AREA_ADMINHTML) {
                    throw $e;
                }
            }
        }

        return $this->client[$cacheKey];
    }

    /**
     * Check connection for credentials by sending test query
     *
     * @param String $wsdl
     * @param String $username
     * @param String $password
     * @param String $token
     *
     * @return bool
     * @throws Exception
     */
    public function checkConnection($wsdl, $username, $password, $token)
    {
        $client = $this->buildClient($wsdl, $username, $password, $token);
        $client->getLoginResult();
        return true;
    }

    /**
     * @param $wsdl
     * @param $username
     * @param $password
     * @param $token
     *
     * @return Client
     * @throws LocalizedException
     */
    public function buildClient($wsdl, $username, $password, $token)
    {
        if (!\file_exists($wsdl)) {
            throw new LocalizedException(__('WSDL file is missing'));
        }

        $builder = new ClientBuilder($wsdl, $username, $password, $token);
        if ($this->salesforceConfig->getLogDebug()) {
            $builder->withLog($this->logger->getLogger());
        }

        return $builder->build();
    }

    /**
     * Get Client Status
     *
     * @param  int|null $websiteId
     *
     * @return bool
     */
    public function getClientStatus($websiteId = null)
    {
        return (bool) $this->salesforceConfig->getSalesforceStatus($websiteId);
    }
    /**
     * Get Client Status
     *
     * @param  int|null $websiteId
     *
     * @return bool
     */
    public function getReverseSyncEnabled($websiteId = null)
    {
        return (bool) $this->salesforceConfig->getReverseSyncEnabled($websiteId);
    }

    /**
     * Save cache
     *
     * @param String $value
     * @param String $identifier
     * @param int|null $websiteId
     *
     * @throws LocalizedException
     */
    protected function saveCache($value, $identifier, $websiteId = null)
    {
        $websiteId = $this->websiteDetector->detectCurrentWebsite($websiteId);

        if ($this->cacheState->isEnabled(Collection::TYPE_IDENTIFIER)) {

            /** @var mixed $serialized */
            $serialized = $this->serializer->serialize($value);

            $this->cacheCollection->save(
                $serialized,
                self::CACHE_TAG . $identifier . '_' . $websiteId
            );
        } else {
            $this->handCache[$identifier . '_' . $websiteId] = $value;
        }
    }

    /**
     * @param $identifier
     *
     * @param int|null $websiteId
     *
     * @return mixed
     * @throws LocalizedException
     */
    protected function loadCache($identifier, $websiteId = null)
    {
        /** @var mixed $result */
        $result = null;

        $websiteId = $this->websiteDetector->detectCurrentWebsite($websiteId);

        if ($this->cacheState->isEnabled(Collection::TYPE_IDENTIFIER)) {
            /** @var mixed $cachedData */
            $cachedData = $this->cacheCollection->load(
                self::CACHE_TAG . $identifier . '_' . $websiteId
            );

            if ($cachedData) {
                $result = $this->serializer->unserialize($cachedData);
            }

        } else if (array_key_exists($identifier . '_' . $websiteId, $this->handCache)) {
            $result = $this->handCache[$identifier . '_' . $websiteId];
        }

        return $result;
    }

    /**
     * Upsert data to SF splitted it into chanks
     *
     * @param $key
     * @param $data
     * @param $type
     *
     * @return array
     * @throws Exception
     */
    public function upsertData($key, $data, $type)
    {
        array_walk($data, function ($object, $key) use($type) {
            $this->prepareSObject($type, $object);
        });

        $this->logger->messageDebug("Upsert type \"%s\", key \"%s\". Data:\n%s", $type, $key, $data);
        foreach (array_chunk($data, self::SFORCE_UPSERT_CHUNK_SIZE) as $chunk) {
            $chunkResult[] = $this->getClient()->upsert($key, $chunk, $type);
        }

        $result = isset($chunkResult)
            ? array_merge(...$chunkResult) : [];

        $this->logger->messageDebug("Upsert type \"%s\", result. Data:\n%s", $type, $result);
        return $result;
    }

    /**
     * Prepare SObject
     *
     * @param string $type
     * @param stdClass $object
     * @throws Exception
     */
    protected function prepareSObject($type, stdClass $object)
    {
        $describe = $this->describeSObject($type);
        foreach (get_object_vars($object) as $field => $value) {
            $describeField = $describe->getField($field);
            if (!$describeField instanceof Field) {
                $this->logger->messageDebug('Field "%s::%s" not found in SF! Skipped field.', $type, $field);
                unset($object->$field);
                continue;
            }

            if ($describeField->getType() === 'string' && $describeField->getLength() < mb_strlen($value)) {
                $this->logger->messageDebug(
                    'Truncating a long value for an "%s:%s". Limit is %d value length is %d. Initial value: %s',
                    $type,
                    $field,
                    $describeField->getLength(),
                    mb_strlen($value),
                    $object->$field
                );

                $object->$field = mb_strcut($object->$field, 0, $describeField->getLength() - 3) . '...';
            }
        }
    }

    /**
     * @param $objectName
     * @return DescribeSObjectResult
     * @throws Exception
     */
    public function describeSObject($objectName)
    {
        $cacheKey = sprintf(self::SFORCE_DESCRIBE_CACHE_IDENTIFIER, strtolower($objectName));

        /** @var string|null $url */
        $describeData = $this->loadCache($cacheKey);
        if ($describeData) {
            $describe = $this->objectConvertor->toObject($describeData);
        } else {
            $describe = $this->getClient()->describeSObjects([$objectName])[0];
            $describeData = $this->objectConvertor->toArray($describe);
            $this->saveCache($describeData, $cacheKey);
        }

        return $describe;
    }

    /**
     * Get Salesforce url that will be used to generate links to objects
     *
     * @param int|null $websiteId
     *
     * @return null|string
     * @throws LocalizedException
     */
    public function getSalesForceUrl($websiteId = null)
    {
        $websiteId = $this->websiteDetector->detectCurrentWebsite($websiteId);
        $cacheKey = $this->salesforceConfig->baseWebsiteIdLogin($websiteId);

        $active = $this->salesforceConfig->getSalesforceStatus($websiteId);
        if (!$active) {
            return null;
        }

        /** @var string|null $url */
        $url = $this->loadCache(self::SFORCE_URL_CACHE_IDENTIFIER, $websiteId);

        if (!$url) {
            if (empty($this->loginResult[$cacheKey])) {
                try {
                    $this->getClient($websiteId);
                } catch (Exception $e) {
                    $this->logger->messageError($e);
                }
            }

            if (!empty($this->loginResult[$cacheKey])) {
                $serverUrl = $this->loginResult[$cacheKey]->getServerUrl();
                $instance_url = explode('/', $serverUrl);
                $url = 'https://' . $instance_url[2];
                $this->saveCache($url, self::SFORCE_URL_CACHE_IDENTIFIER, $websiteId);
            }
        }

        return $url;
    }

    /**
     * Get Salesforce batch size
     *
     * @return string
     */
    public function salesforceBatchSize()
    {
        return self::SFORCE_UPSERT_CHUNK_SIZE;
    }
}

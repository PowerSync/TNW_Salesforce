<?php

namespace TNW\Salesforce\Lib\Tnw\SoapClient;

use Magento\Framework\App\Cache\State;
use Magento\Framework\App\Cache\Type\Collection;
use Magento\Framework\Exception\LocalizedException;
use Tnw\SoapClient\Event\RequestEvent;
use Tnw\SoapClient\Event\ResponseEvent;
use Tnw\SoapClient\Events;
use Tnw\SoapClient\Result\LoginResult;
use Tnw\SoapClient\Soap\SoapClient;
use Traversable;

/**
 * A class for extending the Salesforce SOAP API
 */
class Client extends \Tnw\SoapClient\Client
{
    const CACHE_TAG = 'AUTH_RESULT';

    const TIMEOUT_RESERVE = 180;

    /** @var Collection */
    protected $cacheCollection;

    /** @var State  */
    protected $cacheState;

    /** @var \Magento\Store\Model\StoreManagerInterface  */
    protected $storeManager;

    /**
     * Client constructors.
     * @param SoapClient $soapClient
     * @param string $username
     * @param string $password
     * @param string $token
     * @param Collection $cacheCollection
     * @param State $cacheState
     */
    public function __construct(
        SoapClient $soapClient,
        $username,
        $password,
        $token
//        , Collection $cacheCollection,
//        State $cacheState,
//        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
//        $this->cacheCollection = $cacheCollection;
//        $this->cacheState = $cacheState;
//        $this->storeManager = $storeManager;

        parent::__construct($soapClient, $username, $password, $token);
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getWebsiteId()
    {
        return $this->storeManager->getStore()->getWebsiteId();
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
    protected function saveCache($value, $identifier, $lifeTime)
    {
        $websiteId = $this->getWebsiteId();
        if ($this->cacheState->isEnabled(Collection::TYPE_IDENTIFIER)) {

            /** @var mixed $serialized */
            $serialized = serialize($value);

            $this->cacheCollection->save(
                $serialized,
                self::CACHE_TAG . $identifier . '_' . $websiteId,
                [],
                $lifeTime
            );
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
    protected function loadCache($identifier)
    {
        $websiteId = $this->getWebsiteId();
        /** @var mixed $result */
        $result = null;

        if ($this->cacheState->isEnabled(Collection::TYPE_IDENTIFIER)) {
            /** @var mixed $cachedData */
            $cachedData = $this->cacheCollection->load(
                self::CACHE_TAG . $identifier . '_' . $websiteId
            );

            if ($cachedData) {
                $result = unserialize($cachedData);
            }
        }

        return $result;
    }

    /**
     * @return LoginResult
     */
    public function getLoginResult()
    {
        return parent::getLoginResult();
    }

    /**
     * @param object $object
     * @param string $objectType
     * @return object
     */
    protected function createSObject($object, $objectType)
    {
        $sObject = parent::createSObject($object, $objectType);
        foreach (get_object_vars($sObject) as $field => $value) {
            if (!is_string($value)) {
                continue;
            }

            $sObject->$field = preg_replace('/[^\x9\xA\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value);
        }

        return $sObject;
    }

    /**
     * @return SoapClient
     */
    public function getSoapClient()
    {
        return $this->soapClient;
    }

    /**
     * @param array $ids
     * @return array|Traversable
     */
    public function callDelete(array $ids)
    {
        return $this->call(
            'delete',
            ['ids' => $ids]
        );
    }

    /**
     * Set Assignment rule to created/updated Lead
     *
     * @param AssignmentRuleHeader $header
     * @return $this
     */
    public function setAssignmentRuleHeader($header)
    {
        if ($header != null) {
            $data = [
                'AssignmentRuleHeader' => [
                    'assignmentRuleId' => $header->assignmentRuleId,
                    'useDefaultRule' => $header->useDefaultRuleFlag
                ]
            ];
            $this->setSoapHeaders($data);
        }

        return $this;
    }

    /**
     * @param $method
     * @param array $params
     * @return mixed
     * @throws LocalizedException
     */
    protected function processLoginRequest($method, array $params = []) {
//        if ($this->loadCache(self::CACHE_TAG)) {
//            return $this->loadCache(self::CACHE_TAG);
//        }

        try {
            $this->sessionHeader = null;
            $this->setSessionId(null);
            $result = $this->soapClient->$method($params);
            $timeout = (int)$this->getUserInfo()->getSessionSecondsValid();
            $timeout -= self::TIMEOUT_RESERVE;

//            $this->saveCache($result, self::CACHE_TAG, $timeout);
        } finally {
            $this->logLogin();
        }

        return $result;
    }

    /**
     *
     */
    public function logLogin()
    {
        $request = $this->soapClient->__getLastRequest();
        $response = $this->soapClient->__getLastResponse();

        $patterns = [
            "/<ns1:password>.*<\/ns1:password>/",
//                "/<sessionId>.*<\/sessionId>/"
        ];

        $replace = [
            "<ns1:password>***</ns1:password>",
//                "<sessionId>***</sessionId>"
        ];

        $request = preg_replace($patterns, $replace, $request);
        $response = preg_replace($patterns, $replace, $response);

        $this->dispatch(
            Events::REQUEST,
            new RequestEvent($request)
        );

        $this->dispatch(
            Events::RESPONSE,
            new ResponseEvent($response)
        );
    }

    /**
     * @param $method
     * @param array $params
     * @return mixed
     */

    protected function logcall($method, array $params = [])
    {
        if ($method != 'login') {
            return parent::logcall($method, $params);
        }
        return $this->processLoginRequest($method, $params);
    }
}

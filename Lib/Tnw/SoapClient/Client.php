<?php

namespace TNW\Salesforce\Lib\Tnw\SoapClient;

use Exception;
use Magento\Framework\App\Cache\State;
use Magento\Framework\App\Cache\Type\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use SoapHeader;
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

    const ERR_INVALID_SESSION = 'sf:INVALID_SESSION_ID';

    const TIMEOUT_RESERVE = 180;

    /** @var Collection */
    protected $cacheCollection;

    /** @var State  */
    protected $cacheState;

    /** @var StoreManagerInterface  */
    protected $storeManager;

    protected $expirationTime;

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
    ) {

        parent::__construct($soapClient, $username, $password, $token);
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    public function getWebsiteId()
    {
        return $this->storeManager->getStore()->getWebsiteId();
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
     *
     * @return $this
     */
    public function setAssignmentRuleHeader(AssignmentRuleHeader $header)
    {
        $data = [
            'AssignmentRuleHeader' => [
                'assignmentRuleId' => $header->assignmentRuleId,
                'useDefaultRule' => $header->useDefaultRuleFlag
            ]
        ];

        $this->setSoapHeaders($data);

        return $this;
    }

    /**
     * @return bool
     */
    public function sessionExpired()
    {
        $currentTime = time();

        /** need skip this condition to allow fill the timeout at the first time */
        return !empty($this->expirationTime) && $currentTime > $this->expirationTime;
    }

    /**
     *
     */
    public function setExpirationTime()
    {
        $currentTime = time();
        $timeout = (int)$this->getUserInfo()->getSessionSecondsValid();
        $timeout -= self::TIMEOUT_RESERVE;

        $this->expirationTime = $currentTime + $timeout;
    }

    /**
     * @param LoginResult $loginResult
     */
    protected function setLoginResult(LoginResult $loginResult)
    {
        parent::setLoginResult($loginResult);
        $this->setExpirationTime();
    }

    /**
     *
     */
    public function resetSession()
    {
        $this->sessionHeader = null;
        $this->loginResult = null;
        $this->expirationTime = null;
        $this->soapClient->__setSoapHeaders(null);
        $this->headers = [];
    }

    /**
     * @param $method
     * @param array $params
     * @return mixed
     * @throws LocalizedException
     */
    protected function processLoginRequest($method, array $params = [])
    {
        try {
            $result = $this->soapClient->$method($params);

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
            "/<sessionId>.*<\/sessionId>/"
        ];

        $replace = [
            "<ns1:password>***</ns1:password>",
            "<sessionId>***</sessionId>"
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

    /**
     * @inheritDoc
     *
     * @throws \SoapFault
     */
    protected function call($method, array $params = [], $headers = [])
    {
        try {
            return parent::call($method, $params, $headers);
        } catch (\SoapFault $e) {
            if ($e->faultcode === self::ERR_INVALID_SESSION) {
                $this->resetSession();

                return parent::call($method, $params, $headers);
            }

            throw $e;
        }
    }
}

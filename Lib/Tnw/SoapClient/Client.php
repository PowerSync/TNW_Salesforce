<?php
namespace TNW\Salesforce\Lib\Tnw\SoapClient;

use Tnw\SoapClient\Events;
use Tnw\SoapClient\Event\RequestEvent;
use Tnw\SoapClient\Event\ResponseEvent;

/**
 * A class for extending the Salesforce SOAP API
 */
class Client extends \Tnw\SoapClient\Client
{
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
     * @return \Tnw\SoapClient\Soap\SoapClient
     */
    public function getSoapClient()
    {
        return $this->soapClient;
    }

    /**
     * @param array $ids
     * @return array|\Traversable
     */
    public function callDelete(array $ids)
    {
        return $this->call(
            'delete',
            array('ids' => $ids)
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
     */
    protected function logcall($method, array $params = array())
    {
        if ($method != 'login') {
            return parent::logcall($method, $params);
        }

        try {
            $result = $this->soapClient->$method($params);
        } finally {
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

        return $result;
    }
}

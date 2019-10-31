<?php
namespace TNW\Salesforce\Lib\Tnw\SoapClient;

use Symfony\Component\EventDispatcher\Event;

/**
 * A class for extending the Salesforce SOAP API
 */
class Client extends \Tnw\SoapClient\Client
{
    /**
     * @var bool
     */
    public $canDispatch = true;
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
     * {@inheritdoc}
     */
    public function doLogin($username, $password, $token)
    {
        $this->canDispatch = false;
        $result = parent::doLogin($username, $password, $token);
        $this->canDispatch = true;
        return $result;
    }

    /**
     * Dispatch an event
     *
     * @param string $name  Name of event: see Events.php
     * @param Event  $event Event object
     *
     * @return Event |null
     */
    protected function dispatch($name, Event $event)
    {
        if ($this->canDispatch) {
            return $this->getEventDispatcher()->dispatch($name, $event);
        }
        return null;
    }
}

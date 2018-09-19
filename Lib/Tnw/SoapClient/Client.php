<?php
namespace TNW\Salesforce\Lib\Tnw\SoapClient;

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
}

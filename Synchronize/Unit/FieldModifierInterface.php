<?php
namespace TNW\Salesforce\Synchronize\Unit;

interface FieldModifierInterface extends UnitInterface
{
    /**
     * Field Salesforce Id
     *
     * @return string
     */
    public function fieldSalesforceId();

    /**
     * Additional Salesforce Ids
     *
     * @return array
     */
    public function additionalSalesforceId();
}

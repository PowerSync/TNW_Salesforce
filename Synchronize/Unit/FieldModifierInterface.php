<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit;

interface FieldModifierInterface extends UnitInterface
{
    /**
     * Field Salesforce Id
     *
     * @return string
     */
    public function fieldSalesforceId(): string;

    /**
     * Additional Salesforce Ids
     *
     * @return array
     */
    public function additionalSalesforceId(): array;
}

<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
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

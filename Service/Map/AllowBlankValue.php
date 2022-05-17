<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Map;

/**
 *  Check is allow blank value for field
 */
class AllowBlankValue
{
    /** @var GetFieldsWithDisableSkipBlankValues */
    private $getFieldsWithDisableSkipBlankValues;

    public function __construct(
        GetFieldsWithDisableSkipBlankValues $getFieldsWithDisableSkipBlankValues
    ) {
        $this->getFieldsWithDisableSkipBlankValues = $getFieldsWithDisableSkipBlankValues;
    }

    /**
     * Check is allow blank value for field
     *
     * @param string $objectType
     * @param string $salesForceAttributeName
     *
     * @return bool
     */
    public function execute(string $objectType, string $salesForceAttributeName): bool
    {
        $fieldsByObjectType = $this->getFieldsWithDisableSkipBlankValues
                ->execute([$objectType])[$objectType] ?? [];

        return isset($fieldsByObjectType[$salesForceAttributeName]);
    }
}

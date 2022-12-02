<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Mapping;

use TNW\Salesforce\Service\Synchronize\Unit\Mapping\GetAttributeFrontedValueFromCache;

class Context
{
    /** @var GetAttributeFrontedValueFromCache */
    private $attributeFrontedValueFromCache;

    /**
     * @param GetAttributeFrontedValueFromCache $attributeFrontedValueFromCache
     */
    public function __construct(
        GetAttributeFrontedValueFromCache $attributeFrontedValueFromCache
    ) {
        $this->attributeFrontedValueFromCache = $attributeFrontedValueFromCache;
    }

    /**
     * @return GetAttributeFrontedValueFromCache
     */
    public function getAttributeFrontedValueFromCache(): GetAttributeFrontedValueFromCache
    {
        return $this->attributeFrontedValueFromCache;
    }
}

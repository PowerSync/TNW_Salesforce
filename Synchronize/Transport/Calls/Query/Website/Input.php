<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Transport\Calls\Query\Website;

use TNW\Salesforce\Synchronize\Transport\Calls\Query\Input as BaseInput;

class Input extends BaseInput
{
    /**
     * @param $value
     * @return string
     */
    protected function soqlQuote($value): string
    {
        if (is_integer($value)) {
            return "$value";
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        } else {
            $value = (string)$value;
        }

        $value = addslashes($value);
        return "'$value'";
    }
}

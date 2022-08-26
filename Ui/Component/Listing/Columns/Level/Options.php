<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Ui\Component\Listing\Columns\Level;

use Magento\Framework\Data\OptionSourceInterface;
use Monolog\Logger;

class Options implements OptionSourceInterface
{

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $option = [];
        foreach (Logger::getLevels() as $name => $level) {
            $option[] = ['value' => $level, 'label' => $name];
        }

        return $option;
    }
}

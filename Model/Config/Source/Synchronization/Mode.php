<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\Config\Source\Synchronization;

use Magento\Framework\Data\OptionSourceInterface;

class Mode implements OptionSourceInterface
{
    const SYNC_MODE_DISABLED = 0;
    const SYNC_MODE_M_TO_SF = 1;
    const SYNC_MODE_BOTH = 2;

    /**
     * @var array
     */
    protected $syncMode = [];

    /**
     * @var array
     */
    protected $types = [];

    /**
     * Mode constructor.
     */
    public function __construct()
    {
        $this->syncMode[self::SYNC_MODE_DISABLED] = 'Disabled';
        $this->syncMode[self::SYNC_MODE_M_TO_SF] = 'Magento to Salesforce (only)';
    }

    /**
     * @return mixed
     */
    public function toOptionArray()
    {
        foreach ($this->syncMode as $key => $value) {
            $this->types[] = [
                'value' => $key,
                'label' => $value,
            ];
        }

        return $this->types;
    }

}

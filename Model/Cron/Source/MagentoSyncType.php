<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\Cron\Source;

use TNW\Salesforce\Api\Model\Cron\Source\MagentoSyncTypeInterface;
use TNW\SForceEnterprise\Model\Synchronization\Config;

/**
 * Magento Sync Type
 */
class MagentoSyncType implements MagentoSyncTypeInterface
{
    /**
     * Get magento object type options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            Config::DIRECT_SYNC_TYPE_REALTIME => __('Realtime')
        ];
    }

    /**
     * Get all options as array of: ['value' => <value>, 'label' => <label>]
     *
     * @return array
     */
    public function getAllOptions()
    {
        $result = [];
        foreach ($this->toOptionArray() as $value => $label) {
            $result[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $result;
    }
}

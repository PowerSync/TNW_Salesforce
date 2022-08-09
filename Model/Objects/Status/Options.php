<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\Objects\Status;

use Magento\Framework\Data\OptionSourceInterface;

class Options implements OptionSourceInterface
{
    public const STATUS_OUT_OF_SYNC = 0;
    public const STATUS_IN_SYNC = 1;
    public const STATUS_IN_SYNC_PENDING = 10;
    public const STATUS_OUT_OF_SYNC_PENDING = 11;

    public const LABEL_OUT_OF_SYNC = 'Out of Sync';
    public const LABEL_IN_SYNC = 'In Sync';
    public const LABEL_PENDING = 'Pending';
    public const LABEL_IN_SYNC_PENDING = 'Pending (Resync)';
    public const LABEL_OUT_OF_SYNC_PENDING = 'Pending (Attempt to sync)';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::STATUS_OUT_OF_SYNC,
                'label' => __(self::LABEL_OUT_OF_SYNC)
            ],
            [
                'value' => self::STATUS_IN_SYNC,
                'label' => __(self::LABEL_IN_SYNC)
            ],
            [
                'value' => self::STATUS_IN_SYNC_PENDING,
                'label' => __(self::LABEL_IN_SYNC_PENDING)
            ],
            [
                'value' => self::STATUS_OUT_OF_SYNC_PENDING,
                'label' => __(self::LABEL_OUT_OF_SYNC_PENDING)
            ],
        ];
    }
}

<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Observer;

use Magento\Framework\App\Cache\Type\Collection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use TNW\Salesforce\Client\Salesforce;
use Zend_Cache;

/**
 * Clean last error connection observer.
 */
class CleanLastErrorConnection implements ObserverInterface
{
    private const APPLICABLE_GROUP_ID = 'salesforce';

    /** @var Collection */
    private $cacheCollection;

    /**
     * @param Collection $cacheCollection
     */
    public function __construct(Collection $cacheCollection)
    {
        $this->cacheCollection = $cacheCollection;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $changedPaths = $observer->getData('changed_paths');
        if (!$changedPaths) {
            return;
        }

        foreach ($changedPaths as $path) {
            $pathParts = explode('/', (string)$path);
            $groupId = $pathParts[1] ?? null;
            if ($groupId === self::APPLICABLE_GROUP_ID) {
                $this->cleanCache();

                return;
            }
        }
    }

    /**
     * Clean cache for last error connection timestamp.
     */
    private function cleanCache(): void
    {
        $this->cacheCollection->clean(
            Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
            [Salesforce::LAST_ERROR_CONNECTION_TAG]
        );
    }
}

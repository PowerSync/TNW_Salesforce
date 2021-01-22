<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Plugin\Synchronize\Queue;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Model\ResourceModel\Objects;
use TNW\Salesforce\Synchronize\Queue\Add as Subject;

class Add
{
    /**
     * @var Objects
     */
    protected $resourceObjects;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param Objects $resourceObjects
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     */
    public function __construct(
        Objects $resourceObjects,
        StoreManagerInterface $storeManager,
        RequestInterface $request
    ) {
        $this->resourceObjects = $resourceObjects;
        $this->storeManager = $storeManager;
        $this->request = $request;
    }

    /**
     * @param Subject $subject
     * @param $entityIds
     * @return array
     * @throws LocalizedException
     */
    public function beforeAddToQueue(Subject $subject, $entityIds)
    {
        $storeId = (int) $this->request->getParam('store', 0);
        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();
        $entityType = reset($subject->resolves)->entityType();

        foreach ($entityIds as $entityId) {
            if (count($this->resourceObjects->loadObjectIds($entityId, $entityType, $websiteId))) {
                $this->resourceObjects->setPendingStatus($entityId, $entityType, $websiteId);
            }
        }

        return [$entityIds];
    }
}

<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Plugin\Synchronize\Queue;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Model\ResourceModel\Objects;
use TNW\Salesforce\Service\Model\ResourceModel\Objects\MassLoadObjectIds;
use TNW\Salesforce\Synchronize\Queue\Add as Subject;

class Add
{
    /**
     * @var MassLoadObjectIds
     */
    protected $massLoadObjectIds;

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
        RequestInterface $request,
        MassLoadObjectIds $massLoadObjectIds
    ) {
        $this->resourceObjects = $resourceObjects;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->massLoadObjectIds = $massLoadObjectIds;
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

        $objectIds = $this->massLoadObjectIds->massLoadObjectIds($entityIds, (string)$entityType, (int)$websiteId);
        $entityIdsForUpdateStatus = [];
        foreach ($entityIds as $entityId) {
            $data = $objectIds[$entityId] ?? [];
            if (count($data)) {
                $entityIdsForUpdateStatus[] = $entityId;
            }
        }

        if($entityIdsForUpdateStatus) {
            $this->resourceObjects->setPendingStatus($entityIdsForUpdateStatus, $entityType, $websiteId);
        }

        return [$entityIds];
    }
}

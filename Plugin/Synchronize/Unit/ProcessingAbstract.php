<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Plugin\Synchronize\Unit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Model\ResourceModel\Objects;
use TNW\Salesforce\Service\Model\ResourceModel\Objects\MassLoadObjectIds;
use TNW\Salesforce\Synchronize\Unit\ProcessingAbstract as Subject;

class ProcessingAbstract
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
     * @param MassLoadObjectIds $massLoadObjectIds
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
     * @return array
     * @throws LocalizedException
     */
    public function beforeProcess(Subject $subject)
    {
        $storeId = (int) $this->request->getParam('store', 0);
        $store = $this->storeManager->getStore($storeId);
        $websiteId = (int)$store->getWebsiteId();

        $entityIdsByType = [];
        foreach ($subject->entities() as $entity) {
            $processing = $subject->analize($entity);
            switch (true) {
                case $processing instanceof \Magento\Framework\Phrase:
                case !$processing:
                    $entityId = $entity->getId();
                    $entityType = $entity->getData('_queue')->getEntityType();
                    $entityIdsByType[$entityType][] = $entityId;
                    break;
                default:
                    break;
            }
        }

        foreach ($entityIdsByType as $entityType => $entityIds) {
            $objectIds = $this->massLoadObjectIds->massLoadObjectIds($entityIds, $entityType, $websiteId);
            $entityIdsToUpdateStatus = [];
            foreach ($entityIds as $entityId) {
                $data = $objectIds[$entityId] ?? [];
                if (count($data)) {
                    $entityIdsToUpdateStatus[] = $entityId;
                }
            }

            $entityIdsToUpdateStatus && $this->resourceObjects->unsetPendingStatus($entityIdsToUpdateStatus, $entityType, $websiteId);
        }

        return [];
    }
}

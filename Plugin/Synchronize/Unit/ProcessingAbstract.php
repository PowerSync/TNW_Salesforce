<?php
declare(strict_types=1);
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Plugin\Synchronize\Unit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Model\ResourceModel\Objects;
use TNW\Salesforce\Synchronize\Unit\ProcessingAbstract as Subject;

class ProcessingAbstract
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
     * @return array
     * @throws LocalizedException
     */
    public function beforeProcess(Subject $subject): array
    {
        $storeId = (int) $this->request->getParam('store', 0);
        $store = $this->storeManager->getStore($storeId);
        $websiteId = $store->getWebsiteId();

        foreach ($subject->entities() as $entity) {
            $processing = $subject->analize($entity);
            switch (true) {
                case $processing instanceof \Magento\Framework\Phrase:
                case !$processing:
                    $entityId = $entity->getId();
                    $entityType = $entity->getData('_queue')->getEntityType();

                    if (count($this->resourceObjects->loadObjectIds($entityId, $entityType, $websiteId))) {
                        $this->resourceObjects->unsetPendingStatus($entityId, $entityType, $websiteId);
                    }
                    break;
                default:
                    break;
            }
        }

        return [];
    }
}

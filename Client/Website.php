<?php

namespace TNW\Salesforce\Client;

use Magento\Framework\App\Cache\Type\Collection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Message\ManagerInterface;
use TNW\Salesforce\Api\WebsiteInterface;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\ResourceModel\Website as ResourceSfWebsite;
use Tnw\SoapClient\Result\UpsertResult;
use Magento\Framework\App\Cache\State;

/**
 * Class Website
 *
 * @package TNW\Salesforce\Client
 */
class Website extends Salesforce implements WebsiteInterface
{
    const SFORCE_WEBSITE_OBJECT = 'tnw_mage_basic__Magento_Website__c';

    const SFORCE_FIELDNAME_WEBSITE_ID = 'tnw_mage_basic__Website_ID__c';

    const SFORCE_FIELDNAME_WEBSITE_CODE = 'tnw_mage_basic__Code__c';

    const SFORCE_FIELDNAME_WEBSITE_SORT_ORDER = 'tnw_mage_basic__Sort_Order__c';

    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    /**
     * TNW Salesforce resource model for Website
     *
     * @var \TNW\Salesforce\Model\ResourceModel\Website
     */
    protected $resourceSfWebsite;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    /**
     * Website constructor.
     *
     * @param Config                 $salesForceConfig
     * @param Collection             $cacheCollection
     * @param State                  $cacheState
     * @param ObjectManagerInterface $objectManager
     * @param ResourceSfWebsite      $resourceSfWebsite
     * @param ManagerInterface       $messageManager
     */
    public function __construct(
        Config $salesForceConfig,
        Collection $cacheCollection,
        State $cacheState,
        \TNW\Salesforce\Model\Logger $logger,
        ObjectManagerInterface $objectManager,
        ResourceSfWebsite $resourceSfWebsite,
        ManagerInterface $messageManager,
        \TNW\Salesforce\Model\Config\WebsiteDetector $websiteDetector
    ) {
        parent::__construct($salesForceConfig, $cacheCollection, $cacheState, $logger, $websiteDetector);
        $this->objectManager = $objectManager;
        $this->resourceSfWebsite = $resourceSfWebsite;
        $this->messageManager = $messageManager;
    }

    /**
     * Sync magento websites with Salesforce
     *
     * @param \Magento\Store\Model\Website[] $websites
     * @param bool $forceSync - Force sync even if sync queue enabled
     * @return array
     * @throws \Exception
     */
    public function syncWebsites($websites, $forceSync = false)
    {
        if (!$this->getClientStatus()) {
            return null;
        }

        if (!$forceSync && $this->addToQueue($websites)) {
            return null;
        }
        
        $transferWebsiteObjects = [];
        foreach ($websites as $website) {
            $stdObject = new \stdClass();
            $stdObject->Name = $website->getName();
            $stdObject->{self::SFORCE_FIELDNAME_WEBSITE_ID} =
                (int) $website->getWebsiteId();
            $stdObject->{self::SFORCE_FIELDNAME_WEBSITE_CODE} =
                $website->getCode();
            $stdObject->{self::SFORCE_FIELDNAME_WEBSITE_SORT_ORDER} =
                (int) $website->getSortOrder();

            $transferWebsiteObjects[] = $stdObject;
        }

        /** @var UpsertResult[] $resultWebsiteUpsert */
        $resultWebsiteUpsert = $this->upsertData(
            self::SFORCE_FIELDNAME_WEBSITE_ID,
            $transferWebsiteObjects,
            self::SFORCE_WEBSITE_OBJECT
        );
        $i = 0;
        $countTrue = 0;
        $failedToSyncWebsites = [];
        foreach ($websites as $website) {
            if ($resultWebsiteUpsert[$i]->isSuccess()) {
                $website->setData(
                    'salesforce_id',
                    $resultWebsiteUpsert[$i]->getId()
                );
                $this->resourceSfWebsite->saveSalesforceId($website);
                $countTrue++;
            } else {
                $failedToSyncWebsites[$website->getId()] =
                    $resultWebsiteUpsert[$i]->getErrors();

                $errorMessage = __(
                    'Magento website with code "%1" was not synchronized',
                    $website->getCode()
                );
                $this->messageManager->addErrorMessage($errorMessage);
            }
            $i++;
        }

        if ($countTrue) {
            $successMessage = __(
                '%1 Magento website entities were successfully synchronized',
                $countTrue
            );
            $this->messageManager->addSuccessMessage($successMessage);
        }

        return $failedToSyncWebsites;
    }

    /**
     * Add objects to sync Queue
     * @param @param \Magento\Store\Model\Website[] $websites
     * @return bool
     */
    protected function addToQueue($websites)
    {
        return false;
    }
}

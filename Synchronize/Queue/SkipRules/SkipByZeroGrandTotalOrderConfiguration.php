<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Queue\SkipRules;

use Magento\Framework\Phrase;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Service\GetFilteredIdsWithoutOrderZeroGrandTotal;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;
use TNW\SForceEnterprise\SForceBusiness\Model\Order\Config;

class SkipByZeroGrandTotalOrderConfiguration implements SkipInterface
{
    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Config */
    private $config;

    /** @var GetFilteredIdsWithoutOrderZeroGrandTotal */
    private $getFilteredIdsWithoutOrderZeroGrandTotal;

    /**
     * @param StoreManagerInterface                    $storeManager
     * @param Config                                   $config
     * @param GetFilteredIdsWithoutOrderZeroGrandTotal $getFilteredIdsWithoutOrderZeroGrandTotal
     */
    public function __construct(
        StoreManagerInterface                    $storeManager,
        Config                                   $config,
        GetFilteredIdsWithoutOrderZeroGrandTotal $getFilteredIdsWithoutOrderZeroGrandTotal
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->getFilteredIdsWithoutOrderZeroGrandTotal = $getFilteredIdsWithoutOrderZeroGrandTotal;
    }

    /**
     * @inheritDoc
     */
    public function apply(Queue $queue)
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $needSynchronize = (bool)$this->config->getZeroGrandTotal($websiteId);
        $result = false;
        if (!$needSynchronize) {
            $type = $queue->getEntityType();
            $entityId = $queue->getEntityId();
            $exist = isset($this->getFilteredIdsWithoutOrderZeroGrandTotal->execute(
                    [$entityId],
                    (string)$type
                )[$entityId]
            );
            if(!$exist) {
                $result = __('Zero total sync is disabled');
            }
        }

        return $result;
    }
}

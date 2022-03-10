<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\CustomerGroupConfiguration\NewsletterSubscribers;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Api\Service\CustomerGroupConfiguration\GetSelectInterface;
use TNW\Salesforce\Service\CustomerGroupConfiguration\GetCustomerGroupIds;
use TNW\SForceEnterprise\Model\NewsletterSubscribers\Config;

/**
 *  Newsletter subscribers ids filtered by customer group from store configuration
 */
class GetSelect implements GetSelectInterface
{
    /** @var ResourceConnection */
    private $resource;

    /** @var GetCustomerGroupIds */
    private $getCustomerGroupIds;

    /** @var Config */
    private $config;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * @param ResourceConnection    $resource
     * @param GetCustomerGroupIds   $getCustomerGroupIds
     * @param Config                $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceConnection    $resource,
        GetCustomerGroupIds   $getCustomerGroupIds,
        Config                $config,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->getCustomerGroupIds = $getCustomerGroupIds;
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entityIds): ?Select
    {
        if (!$this->config->isAutosyncEnable()) {
            return null;
        }

        $connection = $this->resource->getConnection();
        $select = $connection->select()->from(
            ['newsletter_subscriber' => $this->resource->getTableName('newsletter_subscriber')],
            [
                'subscriber_id' => 'newsletter_subscriber.subscriber_id'
            ]
        );
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $select->where('newsletter_subscriber.subscriber_id IN (?)', $entityIds);
        if ($this->config->isForRegisteredCustomersOnly($websiteId)) {
            $select->join(
                ['customer_entity' => $this->resource->getTableName('customer_entity')],
                'customer_entity.entity_id = newsletter_subscriber.customer_id',
                []
            );
            $customerSyncGroupsIds = $this->getCustomerGroupIds->execute();
            $condition = 'customer_entity.group_id IN (?)';
            $customerSyncGroupsIds !== null && $select->where($condition, $customerSyncGroupsIds);
        } else {
            $select->joinLeft(
                ['customer_entity' => $this->resource->getTableName('customer_entity')],
                'customer_entity.entity_id = newsletter_subscriber.customer_id',
                []
            );
            $customerSyncGroupsIds = $this->getCustomerGroupIds->execute();
            if ($customerSyncGroupsIds !== null) {
                $condition = implode(
                    ' OR ',
                    [
                        $connection->quoteInto(
                            '(customer_entity.group_id IN (?) AND customer_entity.entity_id IS NOT NULL)',
                            $customerSyncGroupsIds
                        ),
                        '(customer_entity.entity_id IS NULL)'
                    ]
                );
                $select->where($condition);
            }

        }


        return $select;
    }
}

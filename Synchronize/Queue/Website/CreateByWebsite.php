<?php
namespace TNW\Salesforce\Synchronize\Queue\Website;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\Store\Model\ResourceModel\Website;

/**
 * Create By Website
 */
class CreateByWebsite extends CreateByBase
{
    const CREATE_BY = 'website';

    /**
     * @var Order
     */
    private $resourceWebsite;

    /**
     * CreateByWebsite constructor.
     * @param Website $resourceWebsite
     */
    public function __construct(
        Website $resourceWebsite
    ) {
        $this->resourceWebsite = $resourceWebsite;
    }

    /**
     * Create By
     *
     * @return string
     */
    public function createBy()
    {
        return self::CREATE_BY;
    }

    /**
     * Process
     *
     * @param int[] $entityIds
     * @param array $additional
     * @param callable $create
     * @param int $websiteId
     * @return mixed
     * @throws LocalizedException
     */
    public function process(array $entityIds, array $additional, callable $create, $websiteId)
    {
        $queues = [];
        foreach ($this->entities($entityIds) as $entity) {
            $queues[] = $create(
                'website',
                $entity['website_id'],
                $entity['base_entity_id'],
                ['website' => $entity['code']]
            );
        }

        return $queues;
    }

    /**
     * Entities
     *
     * @param int[] $entityIds
     * @return array
     * @throws LocalizedException
     */
    public function entities(array $entityIds)
    {
        $connection = $this->resourceWebsite->getConnection();
        $select = $connection->select()
            ->from($this->resourceWebsite->getMainTable(), [
                'website_id',
                'code',
                'base_entity_id' => $this->resourceWebsite->getIdFieldName()
            ])
            ->where($connection->prepareSqlCondition($this->resourceWebsite->getIdFieldName(), ['in' => $entityIds]));

        return $connection->fetchAll($select);
    }
}

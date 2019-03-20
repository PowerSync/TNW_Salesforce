<?php
namespace TNW\Salesforce\Synchronize\Queue\Website;

/**
 * Create By Website
 */
class CreateByWebsite implements \TNW\Salesforce\Synchronize\Queue\CreateInterface
{
    const CREATE_BY = 'website';

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order
     */
    private $resourceWebsite;

    /**
     * CreateByWebsite constructor.
     * @param \Magento\Store\Model\ResourceModel\Website $resourceWebsite
     */
    public function __construct(
        \Magento\Store\Model\ResourceModel\Website $resourceWebsite
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
     * @param int $entityId
     * @param array $additional
     * @param callable $create
     * @param int $websiteId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process($entityId, array $additional, callable $create, $websiteId)
    {
        $queues = [];
        foreach ($this->entities($entityId) as $entity) {
            $queues[] = $create('website', $entity['website_id'], ['website' => $entity['code']]);
        }

        return $queues;
    }

    /**
     * Entities
     *
     * @param int $entityId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function entities($entityId)
    {
        $connection = $this->resourceWebsite->getConnection();
        $select = $connection->select()
            ->from($this->resourceWebsite->getMainTable(), ['website_id', 'code'])
            ->where("{$this->resourceWebsite->getIdFieldName()} = :website_id");

        return $connection->fetchAll($select, ['website_id' => $entityId]);
    }
}

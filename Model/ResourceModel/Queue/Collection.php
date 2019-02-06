<?php
namespace TNW\Salesforce\Model\ResourceModel\Queue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Queue Collection
 */
class Collection extends AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\TNW\Salesforce\Model\Queue::class, \TNW\Salesforce\Model\ResourceModel\Queue::class);
    }

    /**
     * Add Filter To Sync Type
     *
     * @param int $syncType
     * @return Collection
     */
    public function addFilterToSyncType($syncType)
    {
        return $this->addFieldToFilter('sync_type', $syncType);
    }

    /**
     * Add Filter To Code
     *
     * @param string $code
     * @return Collection
     */
    public function addFilterToCode($code)
    {
        return $this->addFieldToFilter('code', $code);
    }

    /**
     * Add Filter To WebsiteId
     *
     * @param string $code
     * @return Collection
     */
    public function addFilterToWebsiteId($code)
    {
        return $this->addFieldToFilter('website_id', $code);
    }

    /**
     * Website Ids
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function websiteIds()
    {
        $collection = clone $this;
        $collection
            ->removeAllFieldsFromSelect()
            ->removeFieldFromSelect($collection->getResource()->getIdFieldName())
            ->addFieldToSelect('website_id');

        $collection->getSelect()->group('website_id');
        return array_column($collection->getData(), 'website_id');
    }
}

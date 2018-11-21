<?php
namespace TNW\Salesforce\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Objects extends AbstractDb
{
    /**
     * @var \Magento\Framework\DB\Select
     */
    private $selectObjectId;

    /**
     * @var \Magento\Framework\DB\Select
     */
    private $selectEntityId;

    /**
     * @var \Magento\Framework\DB\Select
     */
    private $selectStatus;

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _construct()
    {
        $this->_init('salesforce_objects', 'row_id');

        $this->selectObjectId = $this->getConnection()->select()
            ->from($this->getMainTable(), ['object_id', 'salesforce_type'])
            ->where('magento_type = :magento_type')
            ->where('entity_id = :entity_id')
            ->where('website_id = :website_id');

        $this->selectEntityId = $this->getConnection()->select()
            ->from($this->getMainTable(), ['entity_id', 'magento_type'])
            ->where('salesforce_type = :salesforce_type')
            ->where('object_id = :object_id')
            ->where('website_id = :website_id');

        $this->selectStatus = $this->getConnection()->select()
            ->from($this->getMainTable(), ['status'])
            ->where('magento_type = :magento_type')
            ->where('entity_id = :entity_id')
            ->where('website_id = :website_id');
    }

    /**
     * @param int $entityId
     * @param string $magentoType
     * @param int $websiteId
     *
     * @return string
     */
    public function loadObjectId($entityId, $magentoType, $websiteId)
    {
        return $this->getConnection()->fetchOne($this->selectObjectId, [
            'magento_type' => $magentoType,
            'entity_id' => $entityId,
            'website_id' => $websiteId,
        ]);
    }

    /**
     * @param int $entityId
     * @param string $magentoType
     * @param int $websiteId
     *
     * @return array
     */
    public function loadObjectIds($entityId, $magentoType, $websiteId)
    {
        return array_flip($this->getConnection()->fetchPairs($this->selectObjectId, [
            'magento_type' => $magentoType,
            'entity_id' => $entityId,
            'website_id' => $websiteId,
        ]));
    }

    /**
     * @param int $entityId
     * @param string $magentoType
     * @param int $websiteId
     *
     * @return int
     */
    public function loadStatus($entityId, $magentoType, $websiteId)
    {
        return $this->getConnection()->fetchOne($this->selectStatus, [
            'magento_type' => $magentoType,
            'entity_id' => $entityId,
            'website_id' => $websiteId,
        ]);
    }

    /**
     * @param string $objectId
     * @param string $salesforceType
     * @param int $websiteId
     *
     * @return int
     */
    public function loadEntityId($objectId, $salesforceType, $websiteId)
    {
        return $this->getConnection()->fetchOne($this->selectEntityId, [
            'salesforce_type' => $salesforceType,
            'object_id' => $objectId,
            'website_id' => $websiteId,
        ]);
    }

    /**
     * @param string $objectId
     * @param string $salesforceType
     * @param int $websiteId
     *
     * @return array
     */
    public function loadEntityIds($objectId, $salesforceType, $websiteId)
    {
        return array_flip($this->getConnection()->fetchPairs($this->selectEntityId, [
            'salesforce_type' => $salesforceType,
            'object_id' => $objectId,
            'website_id' => $websiteId,
        ]));
    }

    /**
     * @param array $records
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveRecords(array $records)
    {
        if (empty($records)) {
            return;
        }

        $records = array_map(function (array $record) {
            return array_intersect_key($record, array_flip([
                'magento_type',
                'entity_id',
                'object_id',
                'salesforce_type',
                'status',
                'website_id',
            ]));
        }, $records);

        $this->getConnection()
            ->insertOnDuplicate($this->getMainTable(), $records);
    }

    /**
     * @param int $entityId
     * @param string $magentoType
     * @param int $status
     * @param int $websiteId
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveStatus($entityId, $magentoType, $status, $websiteId)
    {
        $this->getConnection()
            ->update(
                $this->getMainTable(),
                ['status' => (int)$status],
                "entity_id = $entityId AND magento_type = '$magentoType' AND website_id = {$websiteId}"
            );
    }
}

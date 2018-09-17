<?php
namespace  TNW\Salesforce\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Objects extends AbstractDb
{

    /**
     * @var \Magento\Framework\DB\Select
     */
    protected $selectObjectId;

    /**
     * @var \Magento\Framework\DB\Select
     */
    protected $selectEntityId;

    /**
     * @var \Magento\Framework\DB\Select
     */
    protected $selectStatus;

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _construct()
    {
        $this->_init('salesforce_objects', 'row_id');

        $this->selectObjectId = $this->getConnection()->select()
            ->from($this->getMainTable(), ['object_id', 'salesforce_type'])
            ->where('magento_type = :magento_type')
            ->where('entity_id = :entity_id');

        $this->selectEntityId = $this->getConnection()->select()
            ->from($this->getMainTable(), ['entity_id', 'magento_type'])
            ->where('salesforce_type = :salesforce_type')
            ->where('object_id = :object_id');

        $this->selectStatus = $this->getConnection()->select()
            ->from($this->getMainTable(), ['status'])
            ->where('magento_type = :magento_type')
            ->where('entity_id = :entity_id');
    }

    /**
     * @param int $entityId
     * @param string $magentoType
     * @return string
     */
    public function loadObjectId($entityId, $magentoType)
    {
        return $this->getConnection()->fetchOne($this->selectObjectId, [
            'magento_type' => $magentoType,
            'entity_id' => $entityId
        ]);
    }

    /**
     * @param int $entityId
     * @param string $magentoType
     * @return array
     */
    public function loadObjectIds($entityId, $magentoType)
    {
        return array_flip($this->getConnection()->fetchPairs($this->selectObjectId, [
            'magento_type' => $magentoType,
            'entity_id' => $entityId
        ]));
    }

    /**
     * @param int $entityId
     * @param string $magentoType
     * @return string
     */
    public function loadStatus($entityId, $magentoType)
    {
        return $this->getConnection()->fetchOne($this->selectStatus, [
            'magento_type' => $magentoType,
            'entity_id' => $entityId
        ]);
    }

    /**
     * @param string $objectId
     * @param string $salesforceType
     * @return string
     */
    public function loadEntityId($objectId, $salesforceType)
    {
        return $this->getConnection()->fetchOne($this->selectEntityId, [
            'salesforce_type' => $salesforceType,
            'object_id' => $objectId
        ]);
    }

    /**
     * @param string $objectId
     * @param string $salesforceType
     * @return array
     */
    public function loadEntityIds($objectId, $salesforceType)
    {
        return array_flip($this->getConnection()->fetchPairs($this->selectEntityId, [
            'salesforce_type' => $salesforceType,
            'object_id' => $objectId
        ]));
    }

    /**
     * @param array $records
     */
    public function saveRecords(array $records)
    {
        if (empty($records)) {
            return;
        }

        $records = array_map(function (array $record) {
            return array_intersect_key($record, array_flip(['magento_type', 'entity_id', 'object_id', 'salesforce_type', 'status']));
        }, $records);

        $this->getConnection()
            ->insertOnDuplicate($this->getMainTable(), $records);
    }

    /**
     * @param $entityId
     * @param $magentoType
     * @param $status
     */
    public function saveStatus($entityId, $magentoType, $status)
    {
        $this->getConnection()
            ->update($this->getMainTable(), ['status' => (int)$status], "entity_id = $entityId AND magento_type = '$magentoType'");
    }
}
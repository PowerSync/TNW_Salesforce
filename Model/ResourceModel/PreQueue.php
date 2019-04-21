<?php
namespace TNW\Salesforce\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Prequeue
 */
class PreQueue extends AbstractDb
{
    /**
     * Construct
     */
    public function _construct()
    {
        $this->_init('tnw_salesforce_entity_prequeue', 'prequeue_id');
    }

    /**
     * @param $ids
     * @param $entityType
     */
    public function saveEntityIds($ids, $entityType)
    {
        if (empty($ids) || empty($entityType)) {
            return;
        }

        $arrayToInsert = [];
        foreach ($ids as $id) {
            $arrayToInsert[$id] = [
                'entity_id' => $id,
                'entity_type' => $entityType
            ];
        }

        $this
            ->getConnection()
            ->insertOnDuplicate($this->getMainTable(), $arrayToInsert);
    }
}

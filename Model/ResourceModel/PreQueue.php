<?php
namespace TNW\Salesforce\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use TNW\SForceEnterprise\Model\Synchronization\Config;

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
    public function saveEntityIds($ids, $entityType, $syncType =  Config::DIRECT_SYNC_TYPE_REALTIME)
    {
        if (empty($ids) || empty($entityType)) {
            return;
        }

        foreach ($ids as $batchIds) {
            $arrayToInsert = [];
            foreach ($batchIds as $id) {
                $arrayToInsert[$id] = [
                    'entity_id' => $id,
                    'entity_type' => $entityType,
                    'sync_type' => $syncType
                ];
            }
            if (empty($arrayToInsert)) {
                continue;
            }

            $this->getConnection()
                ->insertOnDuplicate($this->getMainTable(), $arrayToInsert);
        }
    }
}

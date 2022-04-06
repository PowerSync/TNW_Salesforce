<?php

namespace TNW\Salesforce\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use RuntimeException;
use Throwable;

/**
 * Class Prequeue
 */
class Salesforceids extends AbstractDb
{
    private const CHUNK_SIZE = 500;

    /**
     * Construct
     */
    public function _construct()
    {
        $this->_init('tnw_salesforce_objects', 'id');
    }

    /**
     * Delete by object_id field
     *
     * @param array $objectIds
     *
     * @return void
     */
    public function deleteByObjectIds(array $objectIds): void
    {
        if (!$objectIds) {
            return;
        }

        $errorMessages = [];
        foreach (array_chunk($objectIds, self::CHUNK_SIZE) as $chunkedFieldValues) {
            $connection = $this->getConnection();
            try {
                $connection->beginTransaction();
                $connection->delete(
                    $this->getMainTable(),
                    $connection->quoteInto(
                        'object_id IN (?)',
                        $objectIds
                    )
                );
                $connection->commit();
            } catch (Throwable $e) {
                $connection->rollBack();
                $errorMessages[] = $e->getMessage();
            }
        }

        if ($errorMessages) {
            throw new RuntimeException(implode(' ', $errorMessages));
        }
    }
}

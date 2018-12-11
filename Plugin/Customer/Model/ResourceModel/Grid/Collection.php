<?php
namespace TNW\Salesforce\Plugin\Customer\Model\ResourceModel\Grid;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection
{
    /**
     * @param AbstractCollection $collection
     * @param bool $printQuery
     * @param bool $logQuery
     *
     * @return array
     * @throws \Zend_Db_Select_Exception
     */
    public function beforeLoad(AbstractCollection $collection, $printQuery = false, $logQuery = false)
    {
        $select = $collection->getSelect();
        $form = $select->getPart(\Zend_Db_Select::FROM);

        if (empty($form['objects_contact'])) {
            $select->joinLeft(
                ['objects_contact' => $collection->getTable('salesforce_objects')],
                implode(' AND ', [
                    'objects_contact.entity_id = main_table.entity_id',
                    'objects_contact.magento_type = "Customer"',
                    'objects_contact.salesforce_type = "Contact"',
                    'objects_contact.website_id = 0'
                ]),
                ['sforce_id' => 'object_id', 'sforce_sync_status' => 'status']
            );
        }

        if (empty($form['objects_account'])) {
            $select->joinLeft(
                ['objects_account' => $collection->getTable('salesforce_objects')],
                implode(' AND ', [
                    'objects_account.entity_id = main_table.entity_id',
                    'objects_account.magento_type = "Customer"',
                    'objects_account.salesforce_type = "Account"',
                    'objects_account.website_id = 0'
                ]),
                ['sforce_account_id' => 'object_id']
            );
        }

        return [$printQuery, $logQuery];
    }
}

<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer\Account\Lookup;

use TNW\Salesforce\Synchronize;

/**
 * @method \Magento\Customer\Model\Customer[] entities()
 */
class ByContact extends Synchronize\Unit\LookupAbstract
{
    /**
     */
    public function processInput()
    {
        //TODO Костыль
        foreach ($this->entities() as $entity) {
            $salesforce = $this->units()->get('customerContactLookup')->get('%s/record/Id', $entity);
            $entity->setData('sforce_id', $salesforce);
        }

        $this->input->columns[] = 'Id';
        $this->input->columns[] = 'Account.Id';
        $this->input->columns[] = 'Account.OwnerId';
        $this->input->columns[] = 'Account.Name';

        foreach ($this->entities() as $entity) {
            $salesforceId = $entity->getData('sforce_id');
            if (!empty($salesforceId)) {
                $this->input[$entity]['AND']['Id']['IN'][] = $salesforceId;
                $this->input[$entity]['AND']['AccountId']['!='] = '';
            }
        }

        $this->input->from = 'Contact';
    }

    /**
     * @return array
     */
    public function collectIndex()
    {
        $searchIndex = [];
        foreach ($this->output as $key => $record) {
            if (!empty($record['Id'])) {
                $searchIndex['contactId'][$key] = $record['Id'];
            }
        }

        return $searchIndex;
    }

    /**
     * @param array $searchIndex
     * @param \Magento\Customer\Model\Customer $entity
     * @return array
     */
    public function searchPriorityOrder(array $searchIndex, $entity)
    {
        $recordsIds = array();

        // Priority 1
        if (!empty($searchIndex['contactId'])) {
            $recordsIds[10] = array_keys($searchIndex['contactId'], $entity->getData('sforce_id'));
        }

        return $recordsIds;
    }

    /**
     * @param array $record
     * @return mixed
     */
    protected function prepareRecord(array $record)
    {
        return $record['Account'];
    }
}
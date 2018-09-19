<?php
namespace TNW\Salesforce\Synchronize\Unit\Website\Website;

use TNW\Salesforce\Synchronize;

/**
 * @method \Magento\Store\Model\Website[] entities()
 */
class Lookup extends Synchronize\Unit\LookupAbstract
{
    /**
     */
    public function processInput()
    {
        $codeField = 'tnw_mage_basic__Code__c';

        $this->input->columns[] = 'Id';
        $this->input->columns[] = $codeField;

        foreach ($this->entities() as $entity) {
            $this->input[$entity]['OR'][$codeField]['IN'][] = $entity->getCode();
        }

        $this->input->from = 'tnw_mage_basic__Magento_Website__c';
    }

    /**
     * @return array
     */
    public function collectIndex()
    {
        $codeField = 'tnw_mage_basic__Code__c';

        $searchIndex = [];
        foreach ($this->output as $key => $record) {
            if (!empty($record[$codeField])) {
                $searchIndex['code'][$key] = strtolower($record[$codeField]);
            }
        }

        return $searchIndex;
    }

    /**
     * @param array $searchIndex
     * @param \Magento\Store\Model\Website $entity
     * @return array
     */
    public function searchPriorityOrder(array $searchIndex, $entity)
    {
        $recordsIds = array();

        if (!empty($searchIndex['code'])) {
            // Priority 1
            $recordsIds[10] = array_keys($searchIndex['code'], strtolower($entity->getCode()));
        }

        return $recordsIds;
    }
}
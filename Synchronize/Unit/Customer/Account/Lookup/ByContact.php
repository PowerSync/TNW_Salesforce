<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Customer\Account\Lookup;

use Magento\Customer\Model\Customer;
use TNW\Salesforce\Model\ResourceModel\Mapper\Collection;
use TNW\Salesforce\Synchronize\Unit\Customer\Contact\Lookup;
use TNW\Salesforce\Synchronize\Unit\Upsert\Input;

/**
 * Lookup By Contact
 *
 * @method Customer[] entities()
 */
class ByContact extends Lookup
{

    /**
     * @inheritdoc
     */
    public function processInput(): bool
    {
        $magentoIdField = 'tnw_mage_basic__Magento_ID__c';
        $magentoWebsiteField = 'tnw_mage_basic__Magento_Website__c';

        $this->input->columns[] = 'Id';
        $this->input->columns[] = 'Email';
        $this->input->columns[] = $magentoIdField;
        $this->input->columns[] = $magentoWebsiteField;
        $this->input->columns[] = 'Account.Id';
        $this->input->columns[] = 'Account.OwnerId';
        $this->input->columns[] = 'Account.Name';

        foreach ($this->entities() as $entity) {
            $this->input[$entity]['AND']['CoM']['AND']['EaW']['AND']['Email']['=']
                = strtolower((string)$entity->getEmail());

            if ($this->customerConfigShare->isWebsiteScope()) {
                $this->input[$entity]['AND']['CoM']['AND']['EaW']['AND'][$magentoWebsiteField]['IN']
                    = ['', $this->load()->entityByType($entity, 'website')->getData('salesforce_id')];
            }

            $magentoId = $entity->getId();
            if (!empty($magentoId)) {
                $this->input[$entity]['AND']['CoM']['OR'][$magentoIdField]['='] = $magentoId;
            }

            $this->input[$entity]['AND']['AccountId']['!='] = '';
        }

        $this->input->from = 'Contact';

        return true;
    }

    /**
     * Prepare Record
     *
     * @param array $record
     * @return array
     */
    public function prepareRecord(array $record): array
    {
        return $record['Account'];
    }


    /**
     *
     */
    public function addMappingFieldsToSelect()
    {
        /** emulate lookup complete to load Update/Upsert mapping */
        $this->unit('lookup')->forceStatus(self::COMPLETE);
        $mapping = [];

        /** @var Input $upsertInput */
        $upsertInput = $this->unit('upsertInput');

        foreach ($this->entities() as $entity) {
            $entity->setForceUpdateOnly(true);

            if ($this->getMappingUnit()) {
                /** @var Collection $mapping */
                $mapping = $this->getMappingUnit()->mappers($entity);
            }
            $entity->setForceUpdateOnly(false);
            break;
        }

        /** stop lookup complete emulation */
        $this->unit('lookup')->restoreStatus();

        $definedColumns = $this->input->columns;
        // TODO : change it to the compareIgnoreFields as defined for \TNW\Salesforce\Synchronize\Unit\Upsert\Input
        $definedColumns[] = 'tnw_mage_enterp__disableMagentoSync__c';

        $definedColumns = array_map('strtolower', $definedColumns);

        foreach ($mapping as $map) {
            /** check if field is correct, available */
            if ($upsertInput) {
                $fieldName = $map->getSalesforceAttributeName();
                $fieldProperty = $upsertInput->findFieldProperty($fieldName);
                if (!$upsertInput->checkFieldProperty($fieldProperty, $fieldName, ['Id' => true])) {
                    continue;
                }
            }

            if (!in_array(strtolower('Account.' . $map->getSalesforceAttributeName()), $definedColumns)) {
                $this->input->columns[] = 'Account.' . $map->getSalesforceAttributeName();
                $definedColumns[] = strtolower('Account.' . $map->getSalesforceAttributeName());
            }
        }
    }
}

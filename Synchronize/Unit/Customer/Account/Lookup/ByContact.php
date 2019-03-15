<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer\Account\Lookup;

use TNW\Salesforce\Synchronize;

/**
 * Lookup By Contact
 *
 * @method \Magento\Customer\Model\Customer[] entities()
 */
class ByContact extends Synchronize\Unit\LookupAbstract
{
    /**
     * @var \Magento\Customer\Model\Config\Share
     */
    private $customerConfigShare;

    /**
     * ByContact constructor.
     * @param string $name
     * @param string $load
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param Synchronize\Transport\Calls\Query\InputFactory $inputFactory
     * @param Synchronize\Transport\Calls\Query\OutputFactory $outputFactory
     * @param Synchronize\Transport\Calls\QueryInterface $process
     * @param \Magento\Customer\Model\Config\Share $customerConfigShare
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        Synchronize\Transport\Calls\Query\InputFactory $inputFactory,
        Synchronize\Transport\Calls\Query\OutputFactory $outputFactory,
        Synchronize\Transport\Calls\QueryInterface $process,
        \Magento\Customer\Model\Config\Share $customerConfigShare,
        array $dependents = []
    ) {
        parent::__construct(
            $name,
            $load,
            $units,
            $group,
            $identification,
            $inputFactory,
            $outputFactory,
            $process,
            $dependents
        );

        $this->customerConfigShare = $customerConfigShare;
    }

    /**
     * ProcessInput
     */
    public function processInput()
    {
        $magentoIdField = 'tnw_mage_basic__Magento_ID__c';
        $magentoWebsiteField = 'tnw_mage_basic__Magento_Website__c';

        $this->input->columns[] = 'Id';
        $this->input->columns[] = $magentoIdField;
        $this->input->columns[] = $magentoWebsiteField;
        $this->input->columns[] = 'Account.Id';
        $this->input->columns[] = 'Account.OwnerId';
        $this->input->columns[] = 'Account.Name';

        foreach ($this->entities() as $entity) {
            $this->input[$entity]['AND']['CoM']['AND']['EaW']['AND']['Email']['=']
                = strtolower($entity->getEmail());

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
    }

    /**
     * Collect Index
     *
     * @return array
     */
    public function collectIndex()
    {
        $magentoIdField = 'tnw_mage_basic__Magento_ID__c';
        $magentoWebsiteField = 'tnw_mage_basic__Magento_Website__c';

        $searchIndex = [];
        foreach ($this->output as $key => $record) {
            $websiteId = '';
            if (!empty($record[$magentoWebsiteField]) && $this->customerConfigShare->isWebsiteScope()) {
                $websiteId = $record[$magentoWebsiteField];
            }

            if (!empty($record['Email'])) {
                $searchIndex['eaw'][$key] = strtolower("{$record['Email']}:{$websiteId}");
            }

            if (!empty($record[$magentoIdField])) {
                $searchIndex['magentoId'][$key] = strtolower($record[$magentoIdField]);
            }
        }

        return $searchIndex;
    }

    /**
     * Search Priority Order
     *
     * @param array $searchIndex
     * @param \Magento\Customer\Model\Customer $entity
     * @return array
     */
    public function searchPriorityOrder(array $searchIndex, $entity)
    {
        $recordsIds = [];
        if (!empty($searchIndex['magentoId'])) {
            $recordsIds[10] = array_keys($searchIndex['magentoId'], strtolower($entity->getId()));
        }

        if (!empty($searchIndex['eaw'])) {
            if ($this->customerConfigShare->isWebsiteScope()) {
                $websiteId = $this->load()->entityByType($entity, 'website')->getData('salesforce_id');
                $recordsIds[20] = array_keys($searchIndex['eaw'], strtolower("{$entity->getEmail()}:{$websiteId}"));
            }

            $recordsIds[25] = array_keys($searchIndex['eaw'], strtolower("{$entity->getEmail()}:"));
        }

        return $recordsIds;
    }

    /**
     * Prepare Record
     *
     * @param array $record
     * @return mixed
     */
    protected function prepareRecord(array $record)
    {
        return $record['Account'];
    }
}

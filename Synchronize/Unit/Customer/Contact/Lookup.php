<?php

namespace TNW\Salesforce\Synchronize\Unit\Customer\Contact;

use TNW\Salesforce\Synchronize;

/**
 * @method \Magento\Customer\Model\Customer[] entities()
 */
class Lookup extends Synchronize\Unit\LookupAbstract
{

    /**
     * @var \Magento\Customer\Model\Config\Share
     */
    private $customerConfigShare;

    /**
     * @var \Magento\Store\Api\WebsiteRepositoryInterface
     */
    private $websiteRepository;

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
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        array $dependents = []
    ) {
        parent::__construct($name, $load, $units, $group, $identification, $inputFactory, $outputFactory, $process, $dependents);
        $this->customerConfigShare = $customerConfigShare;
        $this->websiteRepository = $websiteRepository;
    }

    /**
     * @return bool
     */
    public function processInput()
    {
        $magentoIdField = 'tnw_mage_basic__Magento_ID__c';
        $magentoWebsiteField = 'tnw_mage_basic__Magento_Website__c';

        $this->input->columns[] = 'ID';
        $this->input->columns[] = 'AccountId';
        $this->input->columns[] = 'FirstName';
        $this->input->columns[] = 'LastName';
        $this->input->columns[] = 'Email';
        $this->input->columns[] = 'OwnerId';
        $this->input->columns[] = $magentoIdField;
        $this->input->columns[] = $magentoWebsiteField;

        foreach ($this->entities() as $entity) {
            $this->input[$entity]['AND']['EaW']['AND']['Email']['='] = strtolower($entity->getEmail());
            if ($this->customerConfigShare->isWebsiteScope()) {
                $this->input[$entity]['AND']['EaW']['AND'][$magentoWebsiteField]['IN']
                    = ['', $this->websiteRepository->getById($entity->getWebsiteId())->getSalesforceId()];
            }

            $magentoId = $entity->getId();
            if (!empty($magentoId)) {
                $this->input[$entity]['OR'][$magentoIdField]['='] = $magentoId;
            }
        }

        $this->input->from = 'Contact';
        return true;
    }

    /**
     * @return array
     */
    public function collectIndex()
    {
        $magentoIdField = 'tnw_mage_basic__Magento_ID__c';
        $magentoWebsiteField = 'tnw_mage_basic__Magento_Website__c';

        $searchIndex = [];
        foreach ($this->output as $key => $record) {

            $websiteId = '';
            if ($this->customerConfigShare->isWebsiteScope()) {
                if (!empty($record[$magentoWebsiteField])) {
                    $websiteId = $record[$magentoWebsiteField];
                }
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
     * @param array $searchIndex
     * @param \Magento\Customer\Model\Customer $entity
     * @return array
     */
    public function searchPriorityOrder(array $searchIndex, $entity)
    {
        $recordsIds = array();

        if (!empty($searchIndex['magentoId'])) {
            $recordsIds[10] = array_keys($searchIndex['magentoId'], strtolower($entity->getId()));
        }

        if (!empty($searchIndex['eaw'])) {

            if ($this->customerConfigShare->isWebsiteScope()) {
                try {
                    $websiteId = $this->websiteRepository->getById($entity->getWebsiteId())->getSalesforceId();
                } catch (\Exception $e) {
                    $websiteId = '';
                }
                $recordsIds[20] = array_keys($searchIndex['eaw'], strtolower("{$entity->getEmail()}:{$websiteId}"));
            }

            $recordsIds[25] = array_keys($searchIndex['eaw'], strtolower("{$entity->getEmail()}:"));
        }

        return $recordsIds;
    }
}
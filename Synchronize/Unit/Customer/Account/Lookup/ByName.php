<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit\Customer\Account\Lookup;

use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Model;

/**
 * Account Lookup By Name
 *
 * @method \Magento\Customer\Model\Customer[] entities()
 */
class ByName extends Synchronize\Unit\LookupAbstract
{
    /**
     * @var Model\ResourceModel\Mapper\CollectionFactory
     */
    private $mapperCollectionFactory;

    /**
     * ByName constructor.
     * @param string $name
     * @param string $load
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param Synchronize\Transport\Calls\Query\InputFactory $inputFactory
     * @param Synchronize\Transport\Calls\Query\OutputFactory $outputFactory
     * @param Synchronize\Transport\Calls\QueryInterface $process
     * @param Model\ResourceModel\Mapper\CollectionFactory $mapperCollectionFactory
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
        Model\ResourceModel\Mapper\CollectionFactory $mapperCollectionFactory,
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

        $this->mapperCollectionFactory = $mapperCollectionFactory;
    }

    /**
     * Mapper Name
     *
     * @param int $websiteId
     * @return bool|\Magento\Framework\DataObject|\Magento\Framework\Model\AbstractModel|Model\Mapper
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function mapperName($websiteId)
    {
        return $this->mapperCollectionFactory->create()
                ->addObjectToFilter('Account')
                ->addFieldToFilter('salesforce_attribute_name', 'Name')
                ->applyUniquenessByWebsite($websiteId)
                ->fetchItem();
    }

    /**
     * Process Input
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function processInput()
    {
        $this->input->columns['id'] = 'Id';
        $this->input->columns['owner'] = 'OwnerId';
        $this->input->columns['name'] = 'Name';

        foreach ($this->entities() as $entity) {
            $this->input[$entity]['AND']['Name']['IN'][] = $this->valueCompany($entity);
        }

        $this->input->from = 'Account';
    }

    /**
     * Value Company
     *
     * @param \Magento\Customer\Model\Customer $entity
     * @return mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    private function valueCompany($entity)
    {
        $mapperName = $this->mapperName($this->load()->get('websiteIds/%s', $entity));
        return $mapperName instanceof Model\Mapper && null !== $mapperName->getId()
            ? $this->unit('mapping')->value($entity, $mapperName)
            : Synchronize\Unit\Customer\Account\Mapping::companyByCustomer($entity);
    }

    /**
     * Collect Index
     *
     * @return array
     */
    public function collectIndex()
    {
        $searchIndex = [];
        foreach ($this->output as $key => $record) {
            if (!empty($record['Name'])) {
                $searchIndex['name'][$key] = strtolower($record['Name']);
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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function searchPriorityOrder(array $searchIndex, $entity)
    {
        $recordsIds = [];
        if (!empty($searchIndex['name'])) {
            $recordsIds[10] = array_keys($searchIndex['name'], strtolower($this->valueCompany($entity)));
        }

        return $recordsIds;
    }
}

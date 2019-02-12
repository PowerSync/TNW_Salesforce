<?php
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
     * @var Model\Mapper|bool
     */
    private $mapperName;

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

        $this->mapperName = $mapperCollectionFactory->create()
            ->addObjectToFilter('Account')
            ->addFieldToFilter('salesforce_attribute_name', 'Name')
            ->fetchItem();
    }

    /**
     * Process Input
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
     * @param $entity
     * @return mixed|null
     */
    private function valueCompany($entity)
    {
        return $this->mapperName instanceof Model\Mapper && null !== $this->mapperName->getId()
            ? $this->unit('customerAccountMapping')->value($entity, $this->mapperName)
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

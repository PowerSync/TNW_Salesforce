<?php
namespace TNW\Salesforce\Plugin\Synchronize\Unit\Mapping\Customer;

use Magento\Framework\Model\AbstractModel;
use OutOfBoundsException;
use TNW\Salesforce\Model\ResourceModel\Mapper\Collection;

/**
 * Unit Mapping
 */
class Account
{
    /**
     * Around Mappers
     *
     * @param \TNW\Salesforce\Synchronize\Unit\Mapping $subject
     * @param callable $proceed
     * @param AbstractModel $entity
     * @return Collection
     * @throws OutOfBoundsException
     */
    public function aroundMappers(\TNW\Salesforce\Synchronize\Unit\Mapping $subject, $proceed, $entity)
    {
        /** @var Collection $collection */
        $mappers = $proceed($entity);
        if ($this->needUpdateCompany($subject, $entity)) {
            $mappers->addItem($mappers->getNewEmptyItem()->setData([
                'magento_attribute_name' => 'sf_company',
                'salesforce_attribute_name' => 'Name',
                'magento_entity_type' => 'customer',
                'default_value' => false,
            ]));
        }
        return $mappers;
    }

    /**
     * @param $subject
     * @param $entity
     * @return bool
     */
    public function needUpdateCompany($subject, $entity)
    {
        $result = false;
        $lookup = $subject->unit('lookup');
        $lookupObject = $lookup->get('%s/record', $entity);
        if (!isset($lookupObject)) {
            $result = false;
        }
        $company = \TNW\Salesforce\Synchronize\Unit\Customer\Account\Mapping::generateCompanyByCustomer($entity);
        if (key_exists('Name', (array) $lookupObject) && $lookupObject['Name'] == $company) {
            $result = true;
        }
        return $result;
    }
}

<?php
namespace TNW\Salesforce\Plugin\Synchronize\Unit\Mapping\Customer;


use TNW\Salesforce\Model\ResourceModel\Mapper\Collection;

/**
 * Unit Mapping
 */
class Account
{
    /**
     * Around Mappers
     *
     * @param \TNW\Salesforce\Synchronize\Unit\Customer\Account\Mapping $subject
     * @param $mappers
     * @return Collection
     */
    public function afterMappers(\TNW\Salesforce\Synchronize\Unit\Mapping $subject, $mappers)
    {
        if ($subject instanceof \TNW\Salesforce\Synchronize\Unit\Customer\Account\Mapping) {
            return $mappers->addFieldToFilter('salesforce_attribute_name', ['eq' => ['Name']]);
        }
        return $mappers;
    }
}

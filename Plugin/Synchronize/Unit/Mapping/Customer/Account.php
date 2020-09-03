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
            $mappers->getSelect()
                ->orWhere('object_type =?', 'Account')
                ->where('salesforce_attribute_name =?', 'Name');
            return $mappers;
        }
        return $mappers;
    }
}

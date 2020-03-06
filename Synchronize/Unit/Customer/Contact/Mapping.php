<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer\Contact;

use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use TNW\Salesforce\Model;
use TNW\Salesforce\Model\Config\Source\Customer\ContactAssignee;
use TNW\Salesforce\Model\Customer\Config;
use TNW\Salesforce\Synchronize;

/**
 * Customer Contact Mapping
 */
class Mapping extends Synchronize\Unit\Mapping
{
    /**
     * @var Config
     */
    private $customerConfig;

    /**
     * Mapping constructor.
     *
     * @param string $name
     * @param string $load
     * @param string $lookup
     * @param string $objectType
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param Model\ResourceModel\Mapper\CollectionFactory $mapperCollectionFactory
     * @param Config $customerConfig
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        $lookup,
        $objectType,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        Model\ResourceModel\Mapper\CollectionFactory $mapperCollectionFactory,
        Config $customerConfig,
        array $dependents = []
    ) {
        parent::__construct(
            $name,
            $load,
            $lookup,
            $objectType,
            $units,
            $group,
            $identification,
            $mapperCollectionFactory,
            $dependents
        );

        $this->customerConfig = $customerConfig;
    }

    /**
     * Object By Entity Type
     *
     * @param Customer $entity
     * @param string $magentoEntityType
     * @return mixed
     * @throws LocalizedException
     */
    public function objectByEntityType($entity, $magentoEntityType)
    {
        switch ($magentoEntityType) {
            case 'customer':
                return $entity;

            case 'customer_address/shipping':
                return $entity->getDefaultShippingAddress();

            case 'customer_address/billing':
                return $entity->getDefaultBillingAddress();

            default:
                return parent::objectByEntityType($entity, $magentoEntityType);
        }
    }

    /**
     * Prepare Value
     *
     * @param AbstractModel $entity
     * @param string $attributeCode
     * @return mixed|string
     * @throws LocalizedException
     */
    public function prepareValue($entity, $attributeCode)
    {
        if ($entity instanceof Customer && strcasecmp($attributeCode, 'sforce_id') === 0) {
            return $this->lookup()->get('%s/record/Id', $entity);
        }

        if ($entity instanceof Customer && strcasecmp($attributeCode, 'website_id') === 0) {
            return $this->objectByEntityType($entity, 'website')->getData('salesforce_id');
        }

        return parent::prepareValue($entity, $attributeCode);
    }

    /**
     * Default Value
     *
     * @param AbstractModel $entity
     * @param Model\Mapper $mapper
     * @return mixed
     */
    protected function defaultValue($entity, $mapper)
    {
        if ($entity instanceof Customer &&
            strcasecmp($mapper->getSalesforceAttributeName(), 'OwnerId') === 0
        ) {
            if ($this->customerConfig->contactAssignee($entity->getData('config_website')) === ContactAssignee::DEFAULT_OWNER ||
                !$this->unit('lookup')->get('%s/record/OwnerId', $entity)
            ) {
                return $this->customerConfig->defaultOwner($entity->getData('config_website'));
            }

            return $this->unit('lookup')->get('%s/record/OwnerId', $entity);
        }

        return parent::defaultValue($entity, $mapper);
    }
}

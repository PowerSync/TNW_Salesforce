<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer\Contact;

use TNW\Salesforce\Model\Config\Source\Customer\ContactAssignee;
use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Model;

/**
 * Customer Contact Mapping
 */
class Mapping extends Synchronize\Unit\Mapping
{
    /**
     * @var \TNW\SForceBusiness\Model\Customer\Config
     */
    private $customerConfig;

    /**
     * Mapping constructor.
     *
     * @param string $name
     * @param string $load
     * @param string $lookup
     * @param string $objectType
     * @param array $entityLoaders
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param Model\ResourceModel\Mapper\CollectionFactory $mapperCollectionFactory
     * @param Model\Customer\Config $customerConfig
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        $lookup,
        $objectType,
        array $entityLoaders,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        Model\ResourceModel\Mapper\CollectionFactory $mapperCollectionFactory,
        Model\Customer\Config $customerConfig,
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
            $dependents,
            $entityLoaders
        );

        $this->customerConfig = $customerConfig;
    }

    /**
     * Object By Entity Type
     *
     * @param \Magento\Customer\Model\Customer $entity
     * @param string $magentoEntityType
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function objectByEntityType($entity, $magentoEntityType)
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
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @param string $attributeCode
     * @return mixed|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareValue($entity, $attributeCode)
    {
        if ($entity instanceof \Magento\Customer\Model\Customer && strcasecmp($attributeCode, 'sforce_id') === 0) {
            return $this->lookup()->get('%s/record/Id', $entity);
        }

        if ($entity instanceof \Magento\Customer\Model\Customer && strcasecmp($attributeCode, 'website_id') === 0) {
            return $this->objectByEntityType($entity, 'website')->getData('salesforce_id');
        }

        return parent::prepareValue($entity, $attributeCode);
    }

    /**
     * Default Value
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @param Model\Mapper $mapper
     * @return mixed
     */
    protected function defaultValue($entity, $mapper)
    {
        if ($entity instanceof \Magento\Customer\Model\Customer &&
            strcasecmp($mapper->getSalesforceAttributeName(), 'OwnerId') === 0
        ) {
            if ($this->customerConfig->contactAssignee($entity->getData('config_website')) === ContactAssignee::DEFAULT_OWNER ||
                !$this->unit('customerBusinessAccountLookup')->get('%s/record/OwnerId', $entity)
            ) {
                return $this->customerConfig->defaultOwner($entity->getData('config_website'));
            }

            return $this->unit('customerBusinessAccountLookup')->get('%s/record/OwnerId', $entity);
        }

        return parent::defaultValue($entity, $mapper);
    }
}

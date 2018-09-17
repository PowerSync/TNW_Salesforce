<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer\Contact;

use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Model;

class Mapping extends Synchronize\Unit\MappingAbstract
{

    /**
     * @var \TNW\SForceBusiness\Model\Customer\Config
     */
    private $customerConfig;

    /**
     * @var \Magento\Store\Api\WebsiteRepositoryInterface
     */
    private $websiteRepository;

    public function __construct(
        $name,
        $load,
        $lookup,
        $objectType,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        Model\ResourceModel\Mapper\CollectionFactory $mapperCollectionFactory,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        \TNW\Salesforce\Model\Customer\Config $customerConfig,
        array $dependents = []
    ) {
        parent::__construct($name, $load, $lookup, $objectType, $units, $group,
            $identification, $mapperCollectionFactory, $dependents);

        $this->websiteRepository = $websiteRepository;
        $this->customerConfig = $customerConfig;

    }

    /**
     * @param \Magento\Customer\Model\Customer $entity
     * @param string $magentoEntityType
     * @return mixed
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
                return null;
        }
    }

    public function prepareValue($entity, $attributeCode)
    {
        if ($entity instanceof \Magento\Customer\Model\Customer && strcasecmp($attributeCode, 'sforce_id') === 0) {
            return $this->units()->get('customerContactLookup')->get('%s/record/Id', $entity);
        }

        if ($entity instanceof \Magento\Customer\Model\Customer && strcasecmp($attributeCode, 'sforce_account_id') === 0) {
            return $this->units()->get('customerAccountUpsert')->get('%s/salesforce', $entity);
        }

        if ($entity instanceof \Magento\Customer\Model\Customer && strcasecmp($attributeCode, 'website_id') === 0) {
            return $this->websiteRepository->getById($entity->getWebsiteId())->getSalesforceId();
        }

        return parent::prepareValue($entity, $attributeCode);
    }

    /**
     * @param $entity
     * @param Model\Mapper $mapper
     * @return mixed
     */
    protected function defaultValue($entity, $mapper)
    {
        if (
            strcasecmp($mapper->getSalesforceAttributeName(), 'OwnerId') === 0
        ) {

            if (
                $this->customerConfig->contactAssignee($entity->getWebsiteId()) == Model\Config\Source\Customer\ContactAssignee::DEFAULT_OWNER ||
                !$this->units()->get('customerAccountLookup')->get('%s/record/OwnerId', $entity)
            ) {
                $ownerId = $this->customerConfig->defaultOwner($entity->getWebsiteId());
            } else {
                $ownerId = $this->units()->get('customerAccountLookup')->get('%s/record/OwnerId', $entity);
            }

            return $ownerId;
        }

        return parent::defaultValue($entity, $mapper);
    }


}
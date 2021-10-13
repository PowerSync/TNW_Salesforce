<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Customer\Contact;

use Magento\Customer\Model\Customer;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\DataObject;
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
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Model\ResourceModel\Mapper\CollectionFactory $mapperCollectionFactory
     * @param Config $customerConfig
     * @param array $dependents
     */
    public function __construct(
        $name,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Model\ResourceModel\Mapper\CollectionFactory $mapperCollectionFactory,
        Config $customerConfig,
        array $dependents = []
    ) {
        parent::__construct(
            $name,
            $units,
            $group,
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
     * @return DataObject|ExtensibleDataInterface|null
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

        if (strcasecmp($attributeCode, 'country_id') === 0) {
            return $entity->getData($attributeCode);
        }

        if ($entity instanceof Customer && strcasecmp($attributeCode, 'sf_company') === 0) {
            switch (true) {
                case (!empty($entity->getCompany())):
                    $company = $entity->getCompany();
                    break;
                case (!empty($entity->getDefaultBillingAddress()) && !empty($entity->getDefaultBillingAddress()->getCompany())):
                    $company = $entity->getDefaultBillingAddress()->getCompany();
                    break;
                case (!empty($entity->getDefaultShippingAddress()) && !empty($entity->getDefaultShippingAddress()->getCompany())):
                    $company = $entity->getDefaultShippingAddress()->getCompany();
                    break;
                default:
                    $company = Synchronize\Unit\Customer\Account\Mapping::generateCompanyByCustomer($entity);
                    break;
            }
            return $company;
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
            strcasecmp((string)$mapper->getSalesforceAttributeName(), 'OwnerId') === 0
        ) {
            if ($this->customerConfig->contactAssignee($entity->getData('config_website')) === ContactAssignee::DEFAULT_OWNER) {
                return $this->customerConfig->defaultOwner($entity->getData('config_website'));
            }

            $owner = $this->objectByEntityType($entity, 'customer')->getSforceAccountOwnerId();
            return $owner ?: $this->customerConfig->getDefaultOwner($entity->getData('config_website'));
        }

        return parent::defaultValue($entity, $mapper);
    }
}

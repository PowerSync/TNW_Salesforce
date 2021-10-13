<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Customer\Account;

use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;
use RuntimeException;
use TNW\Salesforce\Model;
use TNW\Salesforce\Model\Customer\Config;
use TNW\Salesforce\Model\Mapper;
use TNW\Salesforce\Synchronize;

/**
 * Customer Account Mapping
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
     * @return mixed
     * @throws RuntimeException
     */
    public function prepareValue($entity, $attributeCode)
    {
        if ($entity instanceof Customer && strcasecmp($attributeCode, 'sforce_id') === 0) {
            return $this->units()->get('lookup')->get('%s/record/Id', $entity);
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
                    $company = self::generateCompanyByCustomer($entity);
                    break;
            }
            return $company;
        }

        return parent::prepareValue($entity, $attributeCode);
    }

    /**
     * Default Value
     *
     * @param Customer $entity
     * @param Mapper $mapper
     * @return mixed
     */
    protected function defaultValue($entity, $mapper)
    {
        $default = parent::defaultValue($entity, $mapper);

        if (empty($default) && strcasecmp((string)$mapper->getSalesforceAttributeName(), 'Name') === 0) {
            return self::generateCompanyByCustomer($entity);
        }

        if (strcasecmp((string)$mapper->getSalesforceAttributeName(), 'OwnerId') === 0) {
            return $this->customerConfig->defaultOwner($entity->getData('config_website'));
        }

        return $default;
    }

    /**
     * Company By Customer
     *
     * @param Customer $entity
     * @return string
     */
    public static function companyByCustomer($entity): string
    {
        $company = self::getCompanyByCustomer($entity);
        if (empty($company)) {
            $company = self::generateCompanyByCustomer($entity);
        }

        return $company;
    }

    /**
     * Get Company By Customer
     *
     * @param Customer $entity
     * @return string
     */
    public static function getCompanyByCustomer($entity): string
    {
        $companyName = '';

        $address = $entity->getDefaultBillingAddress();
        if ($address instanceof Address) {
            $companyName = $address->getData('company');
        }

        return $companyName;
    }

    /**
     * Generate Company By Customer
     *
     * @param Customer $entity
     * @return string
     */
    public static function generateCompanyByCustomer($entity): string
    {
        return trim(sprintf('%s %s', trim((string)$entity->getFirstname()), trim((string)$entity->getLastname())));
    }
}

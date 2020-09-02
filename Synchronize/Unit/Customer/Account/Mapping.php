<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer\Account;

use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer;
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
     * @var Model\ResourceModel\Mapper\CollectionFactory
     */
    protected $mapperCollectionFactory;

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
        $this->mapperCollectionFactory = $mapperCollectionFactory;
    }

    /**
     * Object By Entity Type
     *
     * @param Customer $entity
     * @param string $magentoEntityType
     * @return mixed
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

        if (
            $entity instanceof Customer
            && strcasecmp($attributeCode, 'sf_company') === 0
            && ($this->needUpdateCompany($entity) || $this->allowToUpdate())
        ) {
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

    public function allowToUpdate()
    {
        $mapper = $this->mapperCollectionFactory->create();
        $mapperData = $mapper->addFieldToSelect('magento_to_sf_when')
            ->addFieldToFilter('salesforce_attribute_name', ['eq' => ['Name']])
            ->addFieldToFilter('object_type', ['eq' => 'Account'])
            ->fetchItem();

        return $mapperData->getMagentoToSfWhen() != 'InsertOnly';
    }

    public function needUpdateCompany($entity)
    {

        $lookup = $this->units()->get('lookup');
        $result = $lookup->isComplete();
        try{
            $lookupObject = $lookup->get('%s/record', $entity);
        } catch (\Exception $e) {
            return true;
        }
        if (!isset($lookupObject)) {
            $result = false;
        }
        $company = self::generateCompanyByCustomer($entity);
        if (key_exists('Name', (array) $lookupObject) && $lookupObject['Name'] == $company) {
            $result = true;
        }
        return $result;
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

        if (empty($default) && strcasecmp($mapper->getSalesforceAttributeName(), 'Name') === 0) {
            return self::generateCompanyByCustomer($entity);
        }

        if (strcasecmp($mapper->getSalesforceAttributeName(), 'OwnerId') === 0) {
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
    public static function companyByCustomer($entity)
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
    public static function getCompanyByCustomer($entity)
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
    public static function generateCompanyByCustomer($entity)
    {
        return trim(sprintf('%s %s', trim($entity->getFirstname()), trim($entity->getLastname())));
    }
}

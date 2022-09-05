<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit\Customer\Account;

use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer;
use Magento\Framework\Model\AbstractModel;
use RuntimeException;
use TNW\Salesforce\Api\Service\Company\GenerateCompanyNameInterface;
use TNW\Salesforce\Model;
use TNW\Salesforce\Model\Customer\Config;
use TNW\Salesforce\Model\Mapper;
use TNW\Salesforce\Service\Synchronize\Unit\Customer\Account\Mapping\GetDefaultOwnerId;
use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Utils\Company;

/**
 * Customer Account Mapping
 */
class Mapping extends Synchronize\Unit\Mapping
{
    /** @var GenerateCompanyNameInterface */
    private $generateCompanyName;

    /** @var GetDefaultOwnerId */
    private $getDefaultOwnerId;

    /**
     * Mapping constructor.
     *
     * @param string                                       $name
     * @param string                                       $load
     * @param string                                       $lookup
     * @param string                                       $objectType
     * @param Synchronize\Units                            $units
     * @param Synchronize\Group                            $group
     * @param Synchronize\Unit\IdentificationInterface     $identification
     * @param Model\ResourceModel\Mapper\CollectionFactory $mapperCollectionFactory
     * @param Config                                       $customerConfig
     * @param GenerateCompanyNameInterface                 $generateCompanyName
     * @param GetDefaultOwnerId                            $getDefaultOwnerId
     * @param array                                        $dependents
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
        GenerateCompanyNameInterface $generateCompanyName,
        GetDefaultOwnerId $getDefaultOwnerId,
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

        $this->generateCompanyName = $generateCompanyName;
        $this->getDefaultOwnerId = $getDefaultOwnerId;
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
        $attributeCode = (string)$attributeCode;
        if ($entity instanceof Customer && strcasecmp($attributeCode, 'sforce_id') === 0) {
            return $this->units()->get('lookup')->get('%s/record/Id', $entity);
        }

        if ($entity instanceof Customer && strcasecmp($attributeCode, 'sf_company') === 0) {
            return $this->generateCompanyName->execute($entity);
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

        if (empty($default) && strcasecmp($mapper->getSalesforceAttributeName(), 'Name') === 0) {
            return Company::generateCompanyByCustomer($entity);
        }

        if (strcasecmp($mapper->getSalesforceAttributeName(), 'OwnerId') === 0) {
            return $this->getDefaultOwnerId->execute($this, $entity);
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
            $company = Company::generateCompanyByCustomer($entity);
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
}

<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Unit\Customer\Contact;

use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use TNW\Salesforce\Api\Service\Company\GenerateCompanyNameInterface;
use TNW\Salesforce\Model;
use TNW\Salesforce\Model\Config\Source\Customer\ContactAssignee;
use TNW\Salesforce\Model\Customer\Config;
use TNW\Salesforce\Model\ResourceModel\Mapper\CollectionFactory;
use TNW\Salesforce\Service\Synchronize\Unit\Load\GetCustomerAddressByType;
use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Synchronize\Group;
use TNW\Salesforce\Synchronize\Unit\IdentificationInterface;
use TNW\Salesforce\Synchronize\Unit\Mapping\Context;
use TNW\Salesforce\Synchronize\Units;

/**
 * Customer Contact Mapping
 */
class Mapping extends Synchronize\Unit\Mapping
{
    /**
     * @var Config
     */
    private $customerConfig;

    /** @var GenerateCompanyNameInterface */
    private $generateCompanyName;

    /** @var GetCustomerAddressByType */
    private $getCustomerAddressByType;

    /**
     * Mapping constructor.
     *
     * @param string                       $name
     * @param string                       $load
     * @param string                       $lookup
     * @param string                       $objectType
     * @param Units                        $units
     * @param Group                        $group
     * @param IdentificationInterface      $identification
     * @param CollectionFactory            $mapperCollectionFactory
     * @param Config                       $customerConfig
     * @param GenerateCompanyNameInterface $generateCompanyName
     * @param Context                      $context
     * @param array                        $dependents
     */
    public function __construct(
        string                                       $name,
        string                                       $load,
        string                                       $lookup,
        string                                       $objectType,
        Synchronize\Units                            $units,
        Synchronize\Group                            $group,
        Synchronize\Unit\IdentificationInterface     $identification,
        Model\ResourceModel\Mapper\CollectionFactory $mapperCollectionFactory,
        Config                                       $customerConfig,
        GenerateCompanyNameInterface                 $generateCompanyName,
        Context                                      $context,
        GetCustomerAddressByType                     $getCustomerAddressByType,
        array                                        $dependents = []
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
            $context,
            $dependents
        );

        $this->customerConfig = $customerConfig;
        $this->generateCompanyName = $generateCompanyName;
        $this->getCustomerAddressByType = $getCustomerAddressByType;
    }

    /**
     * Object By Entity Type
     *
     * @param Customer $entity
     * @param string   $magentoEntityType
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function objectByEntityType($entity, $magentoEntityType)
    {
        switch ($magentoEntityType) {
            case 'customer':
                return $entity;

            case 'customer_address/shipping':
                return $this->getCustomerAddressByType->getDefaultShippingAddress($entity);

            case 'customer_address/billing':
                return $this->getCustomerAddressByType->getDefaultBillingAddress($entity);

            default:
                return parent::objectByEntityType($entity, $magentoEntityType);
        }
    }

    /**
     * Prepare Value
     *
     * @param AbstractModel $entity
     * @param string        $attributeCode
     *
     * @return mixed|string
     * @throws LocalizedException
     */
    public function prepareValue($entity, $attributeCode)
    {
        $attributeCode = (string)$attributeCode;
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
            return $this->generateCompanyName->execute($entity);
        }

        return parent::prepareValue($entity, $attributeCode);
    }

    /**
     * Default Value
     *
     * @param AbstractModel $entity
     * @param Model\Mapper  $mapper
     *
     * @return mixed
     */
    protected function defaultValue($entity, $mapper)
    {
        if ($entity instanceof Customer &&
            strcasecmp($mapper->getSalesforceAttributeName(), 'OwnerId') === 0
        ) {
            if ($this->customerConfig->contactAssignee($entity->getData('config_website')) === ContactAssignee::DEFAULT_OWNER) {
                return $this->customerConfig->defaultOwner($entity->getData('config_website'));
            }

            /** @var \Magento\Customer\Model\Backend\Customer $customer */
            $customer = $this->objectByEntityType($entity, 'customer');
            $owner = $customer->getSforceAccountOwnerId();
            $companyObject = $customer->getCompanyObject();
            if ($companyObject && $companyObject->getSforceAccountOwnerId()) {
                $owner = $companyObject->getSforceAccountOwnerId();
            }

            return $owner ?: $this->customerConfig->defaultOwner($entity->getData('config_website'));
        }

        return parent::defaultValue($entity, $mapper);
    }
}

<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Plugin\Synchronize\Unit\Mapping;

use Exception;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Model\AbstractModel;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Model\Config\Source\Customer\Owner;
use TNW\Salesforce\Model\Customer\Config;
use TNW\Salesforce\Model\Logger;
use TNW\Salesforce\Model\Mapper;
use TNW\Salesforce\Model\ResourceModel\Mapper\Collection;
use TNW\Salesforce\Synchronize\Unit\Mapping;

class CheckOwnerField implements CleanableInstanceInterface
{
    private const OWNER_ID_FIELD = 'OwnerId';
    private const MIN_LEN_SF_ID = 15;

    /** @var Logger */
    protected $logger;

    /** @var Owner */
    protected $salesforceOwners;

    /**
     * @var Config
     */
    protected $customerConfig;

    /** @var array */
    private $actualOwners;

    /**
     * Group constructor.
     *
     * @param Logger $logger
     * @param Owner  $salesforceOwners
     * @param Config $customerConfig
     */
    public function __construct(
        Logger                   $logger,
        Owner                    $salesforceOwners,
        Config                   $customerConfig
    ) {
        $this->logger = $logger;
        $this->salesforceOwners = $salesforceOwners;
        $this->customerConfig = $customerConfig;
    }

    /**
     * @param $entity
     * @param $value
     *
     * @return string
     */
    public function checkOwner($value, $entity)
    {
        $value = $this->correctSalesforceId($value);
        $actualOwners = $this->getActualOwners();

        if (!isset($actualOwners[$value])) {
            $this->logger->messageDebug('The owner %s is not valid anymore, the default owner %s used instead', $value, $this->customerConfig->defaultOwner($entity->getData('config_website')));
            $value = $this->customerConfig->defaultOwner($entity->getData('config_website'));
        }

        return $value;
    }

    /**
     * @param $actualOwners
     *
     * @return array
     */
    public function correctSalesforceIdKey($actualOwners)
    {
        $result = [];
        foreach ($actualOwners as $salesforceId => $value) {
            $salesforceId = $this->correctSalesforceId($salesforceId);
            $result[$salesforceId] = $value;
        }

        return $result;
    }

    /**
     * @param Mapping       $subject
     * @param callable      $proceed
     * @param AbstractModel $entity
     * @param Mapper        $mapper
     *
     * @return mixed|null
     */
    public function aroundValue(
        Mapping  $subject,
        callable $proceed,
                 $entity,
                 $mapper
    ) {
        $value = $proceed($entity, $mapper);

        if ($mapper->getSalesforceAttributeName() == self::OWNER_ID_FIELD) {
            $value = $this->checkOwner($value, $entity);
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function clearLocalCache(): void
    {
        $this->actualOwners = null;
    }

    /**
     * @param $id
     * @return bool|string
     */
    public function  correctSalesforceId($id)
    {
        return is_string($id) ? substr($id, 0, self::MIN_LEN_SF_ID) : $id;
    }

    /**
     * @return array
     */
    private function getActualOwners(): array
    {
        if ($this->actualOwners === null) {
            $actualOwners = $this->salesforceOwners->toOptionArray();
            $this->actualOwners = $this->correctSalesforceIdKey($actualOwners);
        }

        return $this->actualOwners;
    }
}

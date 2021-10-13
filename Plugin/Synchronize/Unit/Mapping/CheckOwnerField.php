<?php
declare(strict_types=1);

namespace TNW\Salesforce\Plugin\Synchronize\Unit\Mapping;

use Exception;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Model\AbstractModel;
use TNW\Salesforce\Model\Config\Source\Customer\Owner;
use TNW\Salesforce\Model\Customer\Config;
use TNW\Salesforce\Model\Logger;
use TNW\Salesforce\Model\Mapper;
use TNW\Salesforce\Model\ResourceModel\Mapper\Collection;
use TNW\Salesforce\Synchronize\Unit\Mapping;

class CheckOwnerField extends Mapping
{
    const OWNER_ID_FIELD = 'OwnerId';

    /** @var Logger */
    protected $logger;

    /** @var Owner */
    protected $salesforceOwners;

    /**
     * @var Config
     */
    protected $customerConfig;

    /**
     * Group constructor.
     * @param GroupRepositoryInterface $groupRepository
     * @param Logger $logger
     */
    public function __construct(
        GroupRepositoryInterface $groupRepository,
        Logger $logger,
        Owner $salesforceOwners,
        Config $customerConfig
    ) {
        $this->groupRepository = $groupRepository;
        $this->logger = $logger;
        $this->salesforceOwners = $salesforceOwners;
        $this->customerConfig = $customerConfig;
    }

    /**
     * @param $entity
     * @param $value
     * @return string|null
     */
    public function checkOwner($value, $entity): ?string
    {
        $value = $this->correctSalesforceId($value);
        $actualOwners = $this->salesforceOwners->toOptionArray();
        $actualOwners = $this->correctSalesforceIdKey($actualOwners);

        if (!isset($actualOwners[$value])) {
            $this->logger->messageDebug('The owner %s is not valid anymore, the default owner %s used instead', $value, $this->customerConfig->defaultOwner($entity->getData('config_website')));
            $value = $this->customerConfig->defaultOwner($entity->getData('config_website'));
        }

        return $value;
    }

    /**
     * @param $actualOwners
     * @return array
     */
    public function correctSalesforceIdKey($actualOwners): array
    {
        $result= [];
        foreach ($actualOwners as $salesforceId => $value) {
            $salesforceId = $this->correctSalesforceId($salesforceId);
            $result[$salesforceId] = $value;
        }

        return $result;
    }

    /**
     * @param Mapping $subject
     * @param callable $proceed
     * @param AbstractModel $entity
     * @return Collection|null
     * @throws Exception
     */
    public function aroundMappers(
        Mapping $subject,
        callable $proceed,
        $entity
    ): ?Collection
    {
        /** @var Collection $mappers */
        $mappers = $proceed($entity);

        $ownerId = $subject->lookup()->get('%s/record/OwnerId', $entity);

        if ($ownerId && $ownerId != $this->checkOwner($ownerId, $entity)) {
            if (!$this->ownerMappingDefined($mappers)) {
                $mappers->addItem($mappers->getNewEmptyItem()->setData([
                    'magento_attribute_name' => 'sf_owner_id',
                    'salesforce_attribute_name' => 'OwnerId',
                    'magento_entity_type' => '',
                    'default_value' => false,
                ]));
            }
        }

        return $mappers;
    }

    /**
     * @param $mappers Collection
     * @return bool
     */
    protected function ownerMappingDefined($mappers): bool
    {
        foreach ($mappers as $mapper) {
            if ($mapper->getSalesforceAttributeName() == self::OWNER_ID_FIELD) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Mapping $subject
     * @param callable $proceed
     * @param AbstractModel $entity
     * @param Mapper $mapper
     * @return mixed|null
     */
    public function aroundValue(
        Mapping $subject,
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
}

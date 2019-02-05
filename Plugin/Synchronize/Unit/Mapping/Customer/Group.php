<?php

namespace TNW\Salesforce\Plugin\Synchronize\Unit\Mapping\Customer;

class Group
{
    /**
     * Customer group repository
     *
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    protected $groupRepository;

    public function __construct(\Magento\Customer\Api\GroupRepositoryInterface $groupRepository) {
        $this->groupRepository = $groupRepository;
    }

    /**
     * @param \TNW\Salesforce\Synchronize\Unit\MappingAbstract $subject
     * @param callable $proceed
     * @param $entity
     * @param $attributeCode
     * @return mixed
     */
    public function aroundPrepareValue(
        \TNW\Salesforce\Synchronize\Unit\MappingAbstract $subject,
        callable $proceed,
        $entity,
        $attributeCode) {

        if ($entity instanceof \Magento\Customer\Model\Customer && strcasecmp($attributeCode, 'group_label') === 0) {
            return $this->groupRepository->getById($entity->getGroupId())->getCode();
        }

        return $proceed($entity, $attributeCode);
    }
}
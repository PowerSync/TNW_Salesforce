<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Plugin\Synchronize\Unit\Mapping\Customer;

class Group
{
    /**
     * Customer group repository
     *
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    protected $groupRepository;

    /** @var \TNW\Salesforce\Model\Logger  */
    protected $logger;

    /**
     * Group constructor.
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \TNW\Salesforce\Model\Logger $logger
     */
    public function __construct(
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \TNW\Salesforce\Model\Logger $logger
    ) {
        $this->groupRepository = $groupRepository;
        $this->logger = $logger;
    }

    /**
     * @param \TNW\Salesforce\Synchronize\Unit\Mapping $subject
     * @param callable $proceed
     * @param $entity
     * @param $attributeCode
     * @return mixed
     */
    public function aroundPrepareValue(
        \TNW\Salesforce\Synchronize\Unit\Mapping $subject,
        callable $proceed,
        $entity,
        $attributeCode) {

        if ($entity instanceof \Magento\Customer\Model\Customer && strcasecmp((string)$attributeCode, 'group_label') === 0) {
            $groupCode = '';
            try {
                $groupCode = $this->groupRepository->getById($entity->getGroupId())->getCode();
            } catch (\Exception $e) {
                $this->logger->messageError('Order customer group load error: %s', $e->getMessage());
            }

            return $groupCode;
        }

        return $proceed($entity, $attributeCode);
    }
}

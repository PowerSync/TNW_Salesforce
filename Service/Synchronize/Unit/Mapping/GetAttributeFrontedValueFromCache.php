<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Unit\Mapping;

use DateInterval;
use DateTime;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Model\AbstractModel;
use TNW\Salesforce\Api\CleanableInstanceInterface;

class GetAttributeFrontedValueFromCache implements CleanableInstanceInterface
{
    private const DATE_BACKEND_TYPES = [
        self::ATTRIBUTE_TYPE_DATETIME,
        self::ATTRIBUTE_TYPE_DATE
    ];
    private const ATTRIBUTE_TYPE_DATETIME = 'datetime';
    private const ATTRIBUTE_TYPE_DATE = 'date';

    /** @var array */
    private $cache = [];

    /** @var array */
    private $processed = [];

    /**
     * @param AbstractModel     $entity
     * @param AbstractAttribute $attribute
     *
     * @return mixed|null
     * @throws \Exception
     */
    public function execute(AbstractModel $entity, AbstractAttribute $attribute)
    {
        $attributeCode = $attribute->getAttributeCode();
        $attributeOptionCode = $entity->getData($attributeCode);

        if ($attributeOptionCode === null) {
            return null;
        }

        $entityType = $entity->getEntityType()->getEntityTypeCode();
        if (!isset($this->processed[$entityType][$attributeCode][$attributeOptionCode])) {
            $this->processed[$entityType][$attributeCode][$attributeOptionCode] = 1;

            if (in_array(
                    $attribute->getBackendType(),
                    self::DATE_BACKEND_TYPES,
                    true
                )
            ) {
                $attributeOptionValue = $entity->getData($attributeCode);
                if ($attribute->getFrontendInput() === self::ATTRIBUTE_TYPE_DATE) {
                    $dateTime = new DateTime($attributeOptionValue);
                    $attributeOptionValue = $dateTime->format('Y-m-d');
                    $dateTime = new DateTime($attributeOptionValue);
                    $dateTime->add(new DateInterval('PT12H'));
                    $attributeOptionValue = $dateTime->format('Y-m-d H:i:s');
                }
            } else {
                $attributeOptionValue = $attribute->getFrontend()->getValue($entity);
                if (!empty($attributeOptionValue) && $attribute->getFrontendInput() === 'multiselect') {
                    $attributeOptionValue = is_array($attributeOptionValue) ? implode(',', $attributeOptionValue) : (string)$attributeOptionValue;
                    $attributeOptionValue = explode(',', $attributeOptionValue);
                    $attributeOptionValue = implode(';', $attributeOptionValue);
                }
            }

            $this->cache[$entityType][$attributeCode][$attributeOptionCode] = $attributeOptionValue;
        }

        return $this->cache[$entityType][$attributeCode][$attributeOptionCode] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function clearLocalCache(): void
    {
        $this->cache = [];
        $this->processed = [];
    }
}

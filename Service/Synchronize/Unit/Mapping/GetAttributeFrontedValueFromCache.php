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
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
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

    protected $localeDate;

    /**
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        TimezoneInterface $localeDate
    ) {
        $this->localeDate = $localeDate;
    }


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

        $entityType = $attribute->getEntityTypeId();
        if (!isset($this->processed[$entityType][$attributeCode][$attributeOptionCode])) {
            $this->processed[$entityType][$attributeCode][$attributeOptionCode] = 1;

            if (in_array(
                    $attribute->getBackendType(),
                    self::DATE_BACKEND_TYPES,
                    true
                )
              ) {
                $attributeOptionValue = $entity->getData($attributeCode);
                $dateTime = new DateTime($attributeOptionValue);
                $attributeOptionValue = $dateTime->format('Y-m-d H:i:s');

            } elseif ($attribute->getBackendType() === \Magento\Eav\Model\Entity\Attribute\AbstractAttribute::TYPE_STATIC && $attribute->getFrontendInput() === self::ATTRIBUTE_TYPE_DATE) {
                // workaround for attributes like created_at, updated_at (example: Product)
                $attributeOptionValue = $entity->getData($attributeCode);
                $dateTime = new DateTime($attributeOptionValue);
                $attributeOptionValue = $dateTime->format('Y-m-d H:i:s');
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

<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Unit;

use DateInterval;
use DateTime;
use Exception;
use InvalidArgumentException;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Entity\AbstractEntity;
use Magento\Eav\Model\Entity\Attribute\Frontend\Datetime as FrontendDatetime;
use Magento\Framework\Data\Collection;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use OutOfBoundsException;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Model;
use TNW\Salesforce\Model\Mapper;
use TNW\Salesforce\Model\ResourceModel\Mapper\CollectionFactory;
use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Synchronize\Group;
use TNW\Salesforce\Synchronize\Unit\Mapping\Context;
use TNW\Salesforce\Synchronize\Units;

/**
 * Mapping Abstract
 */
class Mapping extends Synchronize\Unit\UnitAbstract implements CleanableInstanceInterface
{
    const PARENT_ENTITY = '__parent_entity';

    private const DATE_BACKEND_TYPES = [
        self::ATTRIBUTE_TYPE_DATETIME,
        self::ATTRIBUTE_TYPE_DATE
    ];
    private const ATTRIBUTE_TYPE_DATETIME = 'datetime';
    private const ATTRIBUTE_TYPE_DATE = 'date';

    /**
     * @deprecated
     * @var string
     */
    private $load;

    /**
     * @var string
     */
    private $lookup;

    /**
     * @var string
     */
    private $objectType;

    /**
     * @var CollectionFactory
     */
    private $mapperCollectionFactory;

    /**
     * @var IdentificationInterface
     */
    protected $identification;

    /**
     * ['insert/update']['entity_type']['website_id'] => Model\ResourceModel\Mapper\Collection
     * @var []
     */
    protected $collectionCache;

    /** @var array */
    private $values = [];

    /** @var array */
    private $valuesProcessed = [];

    /** @var Mapping\Context */
    private $context;

    /**
     * Mapping constructor.
     *
     * @param string                  $name
     * @param string                  $load
     * @param string                  $lookup
     * @param string                  $objectType
     * @param Units                   $units
     * @param Group                   $group
     * @param IdentificationInterface $identification
     * @param CollectionFactory       $mapperCollectionFactory
     * @param Context                 $context
     * @param array                   $dependents
     */
    public function __construct(
        string                                   $name,
        string                                   $load,
        string                                   $lookup,
        string                                   $objectType,
        Synchronize\Units                        $units,
        Synchronize\Group                        $group,
        Synchronize\Unit\IdentificationInterface $identification,
        CollectionFactory                        $mapperCollectionFactory,
        Context                                  $context,
        array                                    $dependents = []
    ) {
        parent::__construct($name, $units, $group, array_merge($dependents, [$load, $lookup]));
        $this->load = $load;
        $this->objectType = $objectType;
        $this->identification = $identification;
        $this->mapperCollectionFactory = $mapperCollectionFactory;
        $this->lookup = $lookup;
        $this->context = $context;
    }

    /**
     * @inheritdoc
     */
    public function description()
    {
        if (empty($this->objectType)) {
            return __('System mapping');
        }

        return __('Mapping for %1', $this->objectType);
    }

    /**
     * Lookup
     *
     * @return LookupAbstract|UnitInterface
     */
    public function lookup()
    {
        return $this->unit($this->lookup);
    }

    /**
     * Load
     *
     * @return Load|UnitInterface
     */
    public function load()
    {
        return $this->unit($this->load);
    }

    /**
     * Object Type
     *
     * @return string
     */
    public function objectType(): string
    {
        return (string)$this->objectType;
    }

    /**
     * Process
     *
     * @throws OutOfBoundsException
     * @throws InvalidArgumentException
     * @throws LocalizedException
     */
    public function process()
    {
        $message = [];
        foreach ($this->entities() as $hash => $entity) {
            $entity->setData('tnw_unique_hash', $hash);
            $mappers = $this->mappers($entity);
            $count = 0;
            $message[] = __(
                "Entity %1 mapping:\n%2",
                $this->identification->printEntity($entity),
                implode("\n", $mappers->walk(function (Model\Mapper $mapper) use (&$count) {
                    $count++;

                    $message = "{$count}) ";
                    $message .= strcasecmp($mapper->getAttributeType(), 'custom') !== 0
                        ? "{$mapper->getMagentoEntityType()}::" : 'custom::';
                    $message .= "{$mapper->getMagentoAttributeName()} -> {$mapper->getSalesforceAttributeName()}";

                    if (!is_numeric($mapper->getId())) {
                        $message .= ' (is system mapping)';
                    }

                    return $message;
                }))
            );

            $this->cache[$entity] = $this->generateObject($entity, $mappers, $hash);
            $message[] = __(
                "Entity %1 mapping result:\n%2",
                $this->identification->printEntity($entity),
                print_r($this->cache[$entity], true)
            );
        }

        if ($this->cache->count() === 0) {
            $this->group()->messageDebug('Mapping SKIPPED ...');
        }

        if (!empty($message)) {
            $this->group()->messageDebug(implode("\n\n", $message));
        }
    }

    /**
     * Generate Object
     *
     * @param AbstractModel                         $entity
     * @param Model\ResourceModel\Mapper\Collection $mappers
     *
     * @return array
     * @throws OutOfBoundsException
     * @throws LocalizedException
     */
    public function generateObject($entity, Model\ResourceModel\Mapper\Collection $mappers)
    {
        $object = [];

        /** @var Model\Mapper $mapper */
        foreach ($mappers as $mapper) {
            $salesforceAttributeName = $mapper->getSalesforceAttributeName();
            try {
                $value = $this->getCachedValue($entity, $mapper);
                if (null === $value && $mapper->getSkipBlankValues()) {
                    continue;
                }

                $object[$salesforceAttributeName] = $value;
            } catch (\Throwable $e) {
                $group = $this->group();
                $group->messageError('The "%s" field mapping error: %s', $salesforceAttributeName, $e->getMessage());
                $group->messageThrowable($e);

            }
        }

        $salesforce = $this->findSalesforce($entity);
        if (!empty($salesforce)) {
            $object['Id'] = $salesforce;
        } else {
            unset($object['Id']);
        }

        return $object;
    }

    /**
     * Value
     *
     * @param DataObject   $entity
     * @param Model\Mapper $mapper
     *
     * @return mixed|null
     */
    public function value($entity, $mapper)
    {
        $value = null;
        switch ($mapper->getAttributeType()) {
            case 'custom':
                $value = $mapper->getDefaultValue();
                break;

            default:
                $subEntity = $this->objectByEntityType($entity, $mapper->getMagentoEntityType());
                if (!$subEntity instanceof DataObject) {
                    $this->group()->messageDebug(
                        'Object type "%s" not found. Entity: %s.',
                        $mapper->getMagentoEntityType(),
                        $this->identification->printEntity($entity)
                    );

                    break;
                }

                $subEntity->setData(self::PARENT_ENTITY, $entity);
                $value = $this->prepareValue($subEntity, $mapper->getMagentoAttributeName());
                break;
        }

        if (null === $value || (is_string($value) && '' === trim((string)$value))) {
            $value = $this->defaultValue($entity, $mapper);
        } elseif (!empty($mapper->getFormatValue())) {
            $value = sprintf($mapper->getFormatValue(), $value);
        }

        return $value;
    }

    /**
     * Find Salesforce
     *
     * @param AbstractModel $entity
     *
     * @return mixed
     * @throws OutOfBoundsException
     */
    public function findSalesforce($entity)
    {
        return $this->lookup()->get('%s/record/Id', $entity);
    }

    /**
     * @param $entity
     *
     * @return string
     */
    public function getUpdateInsertFlag($entity)
    {
        return Model\Config::MAPPING_WHEN_INSERT_ONLY;
    }

    /**
     * Mappers
     *
     * @param AbstractModel $entity
     *
     * @return Model\ResourceModel\Mapper\Collection
     */
    public function mappers($entity)
    {
        $upsertInsertFlag = $this->getUpdateInsertFlag($entity);
        $websiteId = (int)$this->load()->get('websiteIds/%s', $entity);
        if (empty($this->collectionCache[$upsertInsertFlag][$this->objectType][$websiteId])) {

            $this->collectionCache[$upsertInsertFlag][$this->objectType][$websiteId] = $this->mapperCollectionFactory->create()
                ->addObjectToFilter($this->objectType)
                ->applyUniquenessByWebsite($websiteId)
                ->setOrder('is_default', Collection::SORT_ORDER_DESC);
        }

        return $this->collectionCache[$upsertInsertFlag][$this->objectType][$websiteId];
    }

    /**
     * Entities
     *
     * @return AbstractModel[]
     * @throws OutOfBoundsException
     */
    protected function entities()
    {
        $entities = $this->load()->get('entities') ?? [];

        return array_filter($entities, [$this, 'filter']);
    }

    /**
     * Filter
     *
     * @param AbstractModel $entity
     *
     * @return bool
     * @throws OutOfBoundsException
     */
    protected function filter($entity)
    {
        return !in_array(true, array_map(function ($unit) use ($entity) {
            return $this->unit($unit)->skipped($entity);
        }, $this->dependents()), true);
    }

    /**
     * Object By Entity Type
     *
     * @param AbstractModel $entity
     * @param string        $magentoEntityType
     *
     * @return AbstractModel
     */
    public function objectByEntityType($entity, $magentoEntityType)
    {
        return $this->load()->entityByType($entity, $magentoEntityType);
    }

    /**
     * Prepare Value
     *
     * @param AbstractModel $entity
     * @param string        $attributeCode
     *
     * @return mixed
     */
    public function prepareValue($entity, $attributeCode)
    {
        $attributeCode = (string)$attributeCode;
        $resource = $entity->getResource();
        if (
            $resource instanceof AbstractEntity &&
            $resource->getAttribute($attributeCode) &&
            $resource->getAttribute($attributeCode)->getFrontend()->getConfigField('input') != 'boolean'
        ) {
            $attribute = $resource->getAttribute($attributeCode);

            $value = $this->context->getAttributeFrontedValueFromCache()->execute($entity, $attribute);

            if (!empty($value)) {
                return (string)$value;
            }
        }

        $value = $entity->getData($attributeCode);
        if (null === $value) {
            $method = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $attributeCode)));
            $value = $entity->{$method}();
        }

        switch (true) {
            case is_scalar($value) || $value === null:
                return $value;

            case is_array($value):
                foreach ($value as $v) {
                    if (is_scalar($v) || $v === null) {
                        continue;
                    }

                    break 2;
                }

                return implode('\n', $value);
        }

        $this->group()->messageError(
            'Incorrect value for mapping: scalar values supported only. Attribute code: "%s". Mapping class: "%s"',
            $attributeCode,
            get_class($this)
        );

        return null;
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
        return $mapper->getDefaultValue();
    }

    /**
     * @return array
     */
    public function getCompareIgnoreFields()
    {
        return [
            'tnw_mage_enterp__disableMagentoSync__c',
            'tnw_mage_basic__Sort_Order__c'
        ];
    }

    /**
     * @param $id
     *
     * @return string
     */
    public static function getPrepareId($id)
    {
        return $id;
    }

    /**
     * @inheritDoc
     */
    public function clearLocalCache(): void
    {
        $this->valuesProcessed = [];
        $this->values = [];
    }

    /**
     * @param DataObject $entity
     * @param Mapper     $mapper
     *
     * @return mixed
     */
    private function getCachedValue(DataObject $entity, Model\Mapper $mapper)
    {
        $entityId = $entity->getId();
        $objectType = $this->objectType;
        $magentoAttributeName = $mapper->getMagentoAttributeName();
        $salesforceAttributeName = $mapper->getSalesforceAttributeName();
        $entityUniqueHash = $entity->getData('tnw_unique_hash');
        $key = hash('sha256', (sprintf(
            '%s/%s/%s/%s',
            $entityId,
            $magentoAttributeName,
            $salesforceAttributeName,
            $entityUniqueHash
        )));
        if (isset($this->valuesProcessed[$objectType][$key])) {
            $value = $this->values[$objectType][$key];
        } else {
            $value = $this->value($entity, $mapper);
            $this->valuesProcessed[$objectType][$key] = 1;
            $this->values[$objectType][$key] = $value;
        }

        return $value;
    }
}

<?php
namespace TNW\Salesforce\Synchronize\Unit;

use InvalidArgumentException;
use Magento\Eav\Model\Entity\AbstractEntity;
use Magento\Framework\Data\Collection;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use OutOfBoundsException;
use TNW\Salesforce\Model;
use TNW\Salesforce\Model\ResourceModel\Mapper\CollectionFactory;
use TNW\Salesforce\Synchronize;

/**
 * Mapping Abstract
 */
class Mapping extends Synchronize\Unit\UnitAbstract
{
    const PARENT_ENTITY = '__parent_entity';

    /**
     * @var CollectionFactory
     */
    private $mapperCollectionFactory;

    /**
     * ['insert/update']['entity_type']['website_id'] => Model\ResourceModel\Mapper\Collection
     * @var []
     */
    protected $collectionCache;

    /**
     * Mapping constructor.
     *
     * @param string $name
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param CollectionFactory $mapperCollectionFactory
     * @param array $dependents
     */
    public function __construct(
        $name,
        Synchronize\Units $units,
        Synchronize\Group $group,
        CollectionFactory $mapperCollectionFactory,
        array $dependents = []
    ) {
        parent::__construct($name, $units, $group, array_merge($dependents, ['load', 'lookup']));
        $this->mapperCollectionFactory = $mapperCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function description()
    {
        if (empty($this->units()->get('context')->getObjectType())) {
            return __('System mapping');
        }

        return __('Mapping for %1', $this->units()->get('context')->getObjectType());
    }

    /**
     * Lookup
     *
     * @return LookupAbstract|UnitInterface
     */
    public function lookup()
    {
        return $this->unit('lookup');
    }

    /**
     * Load
     *
     * @return Load|UnitInterface
     */
    public function load()
    {
        return $this->unit('load');
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
        foreach ($this->entities() as $entity) {
            $mappers = $this->mappers($entity);
            $count = 0;
            $message[] = __(
                "Entity %1 mapping:\n%2",
                $this->units()->get('context')->getIdentification()->printEntity($entity),
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

            $this->cache[$entity] = $this->generateObject($entity, $mappers);
            $message[] = __(
                "Entity %1 mapping result:\n%2",
                $this->units()->get('context')->getIdentification()->printEntity($entity),
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
     * @param AbstractModel $entity
     * @param Model\ResourceModel\Mapper\Collection $mappers
     * @return array
     * @throws OutOfBoundsException
     * @throws LocalizedException
     */
    public function generateObject($entity, Model\ResourceModel\Mapper\Collection $mappers)
    {
        $object = [];

        foreach ($mappers as $mapper) {
            try {
                $value = $this->value($entity, $mapper);
                if (null === $value) {
                    continue;
                }

                $object[$mapper->getSalesforceAttributeName()] = $value;
            } catch (\Exception $e) {
                $this->group()->messageError('The "%s" field mapping error: %s', $mapper->getSalesforceAttributeName(), $e->getMessage());
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
     * @param AbstractModel $entity
     * @param Model\Mapper $mapper
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
                        $this->units()->get('context')->getIdentification()->printEntity($entity)
                    );

                    break;
                }

                $subEntity->setData(self::PARENT_ENTITY, $entity);
                $value = $this->prepareValue($subEntity, $mapper->getMagentoAttributeName());
                break;
        }

        if (null === $value || (is_string($value) && '' === trim($value))) {
            $value = $this->defaultValue($entity, $mapper);
        }

        return $value;
    }

    /**
     * Find Salesforce
     *
     * @param AbstractModel $entity
     * @return mixed
     * @throws OutOfBoundsException
     */
    public function findSalesforce($entity)
    {
        return $this->lookup()->get('%s/record/Id', $entity);
    }

    /**
     * @param $entity
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
     * @return Model\ResourceModel\Mapper\Collection
     */
    public function mappers($entity)
    {
        $objectType = $this->units()->get('context')->getObjectType();
        $upsertInsertFlag = $this->getUpdateInsertFlag($entity);
        $websiteId = (int)$this->load()->get('websiteIds/%s', $entity);
        if (empty($this->collectionCache[$upsertInsertFlag][$objectType][$websiteId])) {

            $this->collectionCache[$upsertInsertFlag][$objectType][$websiteId] = $this->mapperCollectionFactory->create()
                ->addObjectToFilter($objectType)
                ->applyUniquenessByWebsite($websiteId)
                ->setOrder('is_default', Collection::SORT_ORDER_DESC);
        }

        return $this->collectionCache[$upsertInsertFlag][$objectType][$websiteId];
    }

    /**
     * Entities
     *
     * @return AbstractModel[]
     * @throws OutOfBoundsException
     */
    protected function entities()
    {
        return array_filter($this->load()->get('entities'), [$this, 'filter']);
    }

    /**
     * Filter
     *
     * @param AbstractModel $entity
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
     * @param string $magentoEntityType
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
     * @param string $attributeCode
     * @return mixed
     */
    public function prepareValue($entity, $attributeCode)
    {
        if (
            $entity->getResource() instanceof AbstractEntity &&
            $entity->getResource()->getAttribute($attributeCode) &&
            $entity->getResource()->getAttribute($attributeCode)->getFrontend()->getConfigField('input') != 'boolean'
        ) {
            $value = (string)$entity->getResource()->getAttribute($attributeCode)->getFrontend()->getValue($entity);

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
     * @param Model\Mapper $mapper
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
     * @return string
     */
    public static function getPrepareId($id)
    {
        return $id;
    }
}

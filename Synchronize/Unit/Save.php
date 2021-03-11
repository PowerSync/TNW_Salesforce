<?php

namespace TNW\Salesforce\Synchronize\Unit;

use Exception;
use function implode;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use OutOfBoundsException;
use TNW\Salesforce\Model\Entity\SalesforceIdStorage;
use TNW\Salesforce\Synchronize;

/**
 * Unit Save
 */
class Save extends Synchronize\Unit\UnitAbstract
{

    /**
     * @var string|null
     */
    protected $fieldModifier = null;

    /**
     * @var IdentificationInterface
     */
    protected $identification;

    /**
     * @var SalesforceIdStorage
     */
    protected $entityObject;

    /**
     * Save constructor.
     *
     * @param string $name
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param IdentificationInterface $identification
     * @param SalesforceIdStorage $entityObject
     * @param string $fieldModifier
     * @param array $dependents
     */
    public function __construct(
        $name,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        SalesforceIdStorage $entityObject,
        $fieldModifier = null,
        array $dependents = []
    ) {
        $this->fieldModifier = $fieldModifier ?: 'upsertOutput';
        parent::__construct($name, $units, $group, array_merge($dependents, ['load', $fieldModifier]));
        $this->identification = $identification;
        $this->entityObject = $entityObject;
    }

    /**
     * @inheritdoc
     */
    public function description()
    {
        return __('Updating Magento entity ...');
    }

    /**
     * Process
     *
     * @throws LocalizedException
     */
    public function process()
    {
        $this->processEntities($this->entities());
        $this->processEntities($this->skippedEntities());
    }

    /**
     * Process skipped
     *
     * @throws LocalizedException
     */
    public function processEntities($entities)
    {
        $message = [];

        $attributeNames = $this->fieldModifier()->additionalSalesforceId();
        $attributeNames['Id'] = $this->fieldModifier()->fieldSalesforceId();

        foreach ($attributeNames as $attributeName) {
            foreach ($entities as $entity) {
                try {
                    $salesforceId = $this->entityObject->valueByAttribute($entity, $attributeName);

                    if (!$salesforceId) {
                        continue;
                    }

                    // Save Salesforce Id
                    $this->entityObject->saveByAttribute($entity, $attributeName, $entity->getData('config_website'));
                    $this->load()->get('%s/queue', $entity)->setAdditionalByCode($attributeName, $salesforceId);

                    $message[] = __(
                        "Updating %1 attribute:\n\t\"%2\": %3",
                        $this->identification->printEntity($entity),
                        $attributeName,
                        $salesforceId
                    );

                    // Save Salesforce Id from duplicates
                    foreach ((array)$this->load()->get('duplicates/%s', $entity) as $duplicate) {
                        $this->entityObject->saveValueByAttribute(
                            $duplicate,
                            $salesforceId,
                            $attributeName,
                            $entity->getData('config_website')
                        );

                        $this->load()->get('%s/queue', $duplicate)->setAdditionalByCode($attributeName, $salesforceId);

                        $message[] = __(
                            "Updating %1 attribute:\n\t\"%2\": %3",
                            $this->identification->printEntity($duplicate),
                            $attributeName,
                            $salesforceId
                        );
                    }
                } catch (Exception $e) {
                    $this->group()->messageError($e->getMessage(), $entity->getId());
                    $this->cache[$entity]['message'] = $e->getMessage();
                }
            }
        }

        if (empty($message)) {
            $message[] = __('Nothing to save');
        }

        $this->group()->messageDebug(implode("\n", $message));
    }

    /**
     * Unit Load
     *
     * @return Load|UnitInterface
     */
    public function load()
    {
        return $this->unit('load');
    }

    /**
     * Unit Upsert
     *
     * @return FieldModifierInterface|UnitInterface
     */
    public function fieldModifier()
    {
        return $this->unit($this->fieldModifier);
    }

    /**
     * Entities
     *
     * @return Product[]
     * @throws OutOfBoundsException
     */
    public function entities()
    {
        return array_filter($this->load()->get('entities'), [$this, 'filter']);
    }

    /**
     * Filter
     *
     * @param AbstractModel $entity
     * @return bool
     */
    public function filter($entity)
    {
        $attributeName = $this->fieldModifier()->fieldSalesforceId();
        if (!is_array($attributeName)) {
            $attributeName = ['Id' => $attributeName];
        }

        $result = false;
        foreach ($attributeName as $attribute) {
            $result = $result || $this->entityObject->valueByAttribute($entity, $attribute);
        }

        return $this->fieldModifier()->get('%s/success', $entity) && $result;
    }

    /**
     * Entities
     *
     * @return Product[]
     * @throws OutOfBoundsException
     */
    public function skippedEntities()
    {
        return array_filter($this->load()->get('entities'), [$this, 'skipped']);
    }

    /**
     * Filter
     *
     * @param AbstractModel $entity
     * @return bool
     */
    public function skipped($entity)
    {
        $attributeName = $this->fieldModifier()->fieldSalesforceId();
        if (!is_array($attributeName)) {
            $attributeName = ['Id' => $attributeName];
        }

        $result = false;
        foreach ($attributeName as $attribute) {
            $result = $result || $this->entityObject->valueByAttribute($entity, $attribute);
        }

        return $this->fieldModifier()->get('%s/skipped', $entity) && $result;
    }
}

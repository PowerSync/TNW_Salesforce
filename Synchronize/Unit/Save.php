<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

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
     * @var string
     */
    protected $load;

    /**
     * @var string
     */
    protected $fieldModifier;

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
     * @param string $load
     * @param string $fieldModifier
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param IdentificationInterface $identification
     * @param SalesforceIdStorage $entityObject
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        $fieldModifier,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        SalesforceIdStorage $entityObject,
        array $dependents = []
    ) {
        parent::__construct($name, $units, $group, array_merge($dependents, [$load, $fieldModifier]));
        $this->load = $load;
        $this->fieldModifier = $fieldModifier;
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

        foreach ($attributeNames as $key => $attributeName) {
            foreach ($entities as $entity) {
                try {
                    switch (true) {
                        case (!empty($this->unit('mapping')) && !empty($this->unit('mapping')->get('%s/' . $key, $entity))):
                            $salesforceId = $this->unit('mapping')->get('%s/' . $key, $entity);
                            break;
                        case (!empty($this->unit('lookup')) && !empty($this->unit('lookup')->get('%s/record/' . $key, $entity))):
                            $salesforceId = $this->unit('lookup')->get('%s/record/' . $key, $entity);
                            break;
                        case !(empty($this->entityObject->valueByAttribute($entity, $attributeName))):
                            $salesforceId = $this->entityObject->valueByAttribute($entity, $attributeName);
                            break;
                        default:
                            $salesforceId = null;
                    }

                    $website = $entity->getData('config_website');

                    // Save Salesforce Id
                    if ($salesforceId || $this->entityObject->recordExist($entity, $attributeName, $website)) {
                        $this->entityObject->addRecordsToCache($entity, $salesforceId, $attributeName, $website);
                    }

                    $this->load()->get('%s/queue', $entity)->setAdditionalByCode($attributeName, $salesforceId);

                    $message[] = __(
                        "Updating %1 attribute:\n\t\"%2\": %3",
                        $this->identification->printEntity($entity),
                        $attributeName,
                        $salesforceId
                    );

                    // Save Salesforce Id from duplicates
                    foreach ((array)$this->load()->get('duplicates/%s', $entity) as $duplicate) {
                        $this->entityObject->addRecordsToCache($duplicate, $salesforceId, $attributeName, $website);

                        $this->load()->get('%s/queue', $duplicate)->setAdditionalByCode($attributeName, $salesforceId);

                        $message[] = __(
                            "Updating %1 attribute:\n\t\"%2\": %3",
                            $this->identification->printEntity($duplicate),
                            $attributeName,
                            $salesforceId
                        );
                    }
                } catch (\Throwable $e) {
                    $group = $this->group();
                    $group->messageError($e->getMessage(), $entity->getId());
                    $group->messageThrowable($e);
                    $this->cache[$entity]['message'] = $e->getMessage();
                }
            }
        }

        $this->entityObject->saveRecordsFromCache();

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
        return $this->unit($this->load);
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
        $entities = $this->load()->get('entities') ?? [];
        return array_filter($entities, [$this, 'filter']);
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

        $status = $this->fieldModifier()->get('%s/success', $entity)
            || $this->fieldModifier()->get('%s/waiting', $entity);
        
        return $status && $result;
    }

    /**
     * Entities
     *
     * @return Product[]
     * @throws OutOfBoundsException
     */
    public function skippedEntities()
    {
        $entities = $this->load()->get('entities') ?? [];
        return array_filter($entities, [$this, 'skipped']);
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

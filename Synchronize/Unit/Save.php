<?php
namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Synchronize;

/**
 * Unit Save
 */
class Save extends Synchronize\Unit\UnitAbstract
{
    /**
     * @var string
     */
    private $load;

    /**
     * @var string
     */
    private $fieldModifier;

    /**
     * @var IdentificationInterface
     */
    private $identification;

    /**
     * @var \TNW\Salesforce\Model\Entity\SalesforceIdStorage
     */
    private $entityObject;

    /**
     * Save constructor.
     *
     * @param string $name
     * @param string $load
     * @param string $fieldModifier
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param IdentificationInterface $identification
     * @param \TNW\Salesforce\Model\Entity\SalesforceIdStorage $entityObject
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        $fieldModifier,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        \TNW\Salesforce\Model\Entity\SalesforceIdStorage $entityObject,
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process()
    {
        $this->processEntities($this->entities(), $this->fieldModifier()->fieldSalesforceId());
        $this->processEntities($this->skippedEntities());
    }

    /**
     * Process skipped
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processEntities($entities, $attributeName = 'salesforce_id')
    {
        $message = [];

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
            } catch (\Exception $e) {
                $this->group()->messageError($e->getMessage(), $entity->getId());
                $this->cache[$entity]['message'] = $e->getMessage();
            }
        }

        if (empty($message)) {
            $message[] = __('Nothing to save');
        }

        $this->group()->messageDebug(\implode("\n", $message));
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
     * @return \Magento\Catalog\Model\Product[]
     * @throws \OutOfBoundsException
     */
    protected function entities()
    {
        return array_filter($this->load()->get('entities'), [$this, 'filter']);
    }

    /**
     * Filter
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return bool
     */
    public function filter($entity)
    {
        return $this->fieldModifier()->get('%s/success', $entity);
    }

    /**
     * Entities
     *
     * @return \Magento\Catalog\Model\Product[]
     * @throws \OutOfBoundsException
     */
    protected function skippedEntities()
    {
        return array_filter($this->load()->get('entities'), [$this, 'skipped']);
    }

    /**
     * Filter
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return bool
     */
    public function skipped($entity)
    {
        return $this->fieldModifier()->get('%s/skipped', $entity);
    }
}

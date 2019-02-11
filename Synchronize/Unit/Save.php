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
    private $upsertOutput;

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
     * @param string $upsertOutput
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param IdentificationInterface $identification
     * @param \TNW\Salesforce\Model\Entity\SalesforceIdStorage $entityObject
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        $upsertOutput,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        \TNW\Salesforce\Model\Entity\SalesforceIdStorage $entityObject,
        array $dependents = []
    ) {
        parent::__construct($name, $units, $group, array_merge($dependents, [$load, $upsertOutput]));
        $this->load = $load;
        $this->upsertOutput = $upsertOutput;
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
        $attributeName = $this->upsertOutput()->fieldSalesforceId();
        $message = [];

        foreach ($this->entities() as $entity) {
            if (null === $entity->getId()) {
                continue;
            }

            $salesforceId = $this->entityObject->valueByAttribute($entity, $attributeName);

            // Save Salesforce Id
            $this->entityObject->saveByAttribute($entity, $attributeName, $entity->getData('config_website'));

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

                $message[] = __(
                    "Updating %1 attribute:\n\t\"%2\": %3",
                    $this->identification->printEntity($duplicate),
                    $attributeName,
                    $salesforceId
                );
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
     * @return Upsert\Output|UnitInterface
     */
    public function upsertOutput()
    {
        return $this->unit($this->upsertOutput);
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
        return $this->upsertOutput()->get('%s/success', $entity);
    }
}

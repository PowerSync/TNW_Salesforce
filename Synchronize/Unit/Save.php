<?php
namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Synchronize;

class Save extends Synchronize\Unit\UnitAbstract
{
    /**
     * @var string
     */
    private $load;

    /**
     * @var string
     */
    private $upsert;

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
     * @param string $upsert
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param IdentificationInterface $identification
     * @param \TNW\Salesforce\Model\Entity\SalesforceIdStorage $entityObject
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        $upsert,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        \TNW\Salesforce\Model\Entity\SalesforceIdStorage $entityObject,
        array $dependents = []
    ) {
        parent::__construct($name, $units, $group, array_merge($dependents, [$load, $upsert]));
        $this->load = $load;
        $this->upsert = $upsert;
        $this->identification = $identification;
        $this->entityObject = $entityObject;
    }

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return __('Updating Magento entity ...');
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process()
    {
        $attributeName = $this->upsert()->fieldSalesforceId();
        $message = [];

        foreach ($this->entities() as $entity) {
            if (null === $entity->getId()) {
                continue;
            }

            $salesforceId = $this->entityObject->valueByAttribute($entity, $attributeName);

            // Save Salesforce Id
            $this->entityObject->saveByAttribute($entity, $attributeName);

            $message[] = __(
                "Updating %1 attribute:\n\t\"%2\": %3",
                $this->identification->printEntity($entity),
                $attributeName,
                $salesforceId
            );

            // Save Salesforce Id from duplicates
            foreach ((array)$this->load()->get('duplicates/%s', $entity) as $duplicate) {
                $this->entityObject->saveValueByAttribute($duplicate, $salesforceId, $attributeName);

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
     * @return LoadAbstract|LoadByAbstract
     */
    public function load()
    {
        return $this->unit($this->load);
    }

    /**
     * @return Upsert
     */
    public function upsert()
    {
        return $this->unit($this->upsert);
    }

    /**
     * @return \Magento\Catalog\Model\Product[]
     * @throws \OutOfBoundsException
     */
    protected function entities()
    {
        return array_filter($this->load()->get('entities'), [$this, 'filter']);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return bool
     */
    public function filter($entity)
    {
        return $this->upsert()->get('%s/success', $entity);
    }
}

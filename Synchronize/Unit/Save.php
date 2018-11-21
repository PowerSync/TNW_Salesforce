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
     * @var \TNW\Salesforce\Model\Entity\Object
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
     * @param \TNW\Salesforce\Model\Entity\Object $entityObject
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        $upsert,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        \TNW\Salesforce\Model\Entity\Object $entityObject,
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
        $salesforceType = $this->upsert()->salesforceType();
        $message = [];

        foreach ($this->entities() as $entity) {
            if (null === $entity->getId()) {
                continue;
            }

            $this->entityObject->saveByObject($entity, $salesforceType);
            $message[] = __(
                "Updating %1 attribute:\n\t\"%2\": %3",
                $this->identification->printEntity($entity),
                $this->entityObject->attributeByObject($salesforceType),
                $this->entityObject->valueByObject($entity, $salesforceType)
            );
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

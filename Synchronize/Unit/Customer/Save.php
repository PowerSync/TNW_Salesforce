<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer;

use TNW\Salesforce\Synchronize;

class Save extends Synchronize\Unit\SaveAbstract
{
    /**
     * @var string
     */
    private $attribute;

    public function __construct(
        $name,
        $load,
        $attribute,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        array $dependents = []
    ) {
        parent::__construct($name, $load, $units, $group, $identification, $dependents);
        $this->attribute = $attribute;
    }

    /**
     *
     */
    public function process()
    {
        foreach ($this->entities() as $entity) {
            if (null === $entity->getId()) {
                continue;
            }

            $entity->getResource()->saveAttribute($entity, $this->attribute);
            $message[] = __("Updating %1 attribute:\n\t\"%2\": %3",
                $this->identification->printEntity($entity), $this->attribute, $entity->getData($this->attribute));
        }

        if (!empty($message)) {
            $this->group()->messageDebug(implode("\n\n", $message));
        }
    }
}
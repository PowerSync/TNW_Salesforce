<?php
namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Synchronize;

class Entity extends Synchronize\Unit\UnitAbstract
{
    /**
     * @var array
     */
    private $entities;

    public function __construct(
        $name,
        array $entities,
        Synchronize\Units $units,
        Synchronize\Group $group,
        array $dependents = []
    ) {
        parent::__construct($name, $units, $group, $dependents);
        $this->entities = $entities;
    }

    /**
     *
     */
    public function process()
    {
        foreach ($this->entities as $entity) {

        }
    }
}

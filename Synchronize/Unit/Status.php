<?php
namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Synchronize;

/**
 * Unit Status
 */
class Status extends Synchronize\Unit\UnitAbstract
{
    /**
     * @var string
     */
    private $load;

    /**
     * Status constructor.
     * @param string $name
     * @param string $load
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        Synchronize\Units $units,
        Synchronize\Group $group,
        array $dependents = []
    ) {
        parent::__construct($name, $units, $group, $dependents);
        $this->load = $load;
    }

    /**
     * @inheritdoc
     */
    public function description()
    {
        return __('Status queue ...');
    }

    /**
     * Process
     */
    public function process()
    {
        foreach ($this->units() as $unit) {
            foreach ($this->entities() as $entity) {

            }
        }
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
     * Entities
     *
     * @return object[]
     * @throws \OutOfBoundsException
     */
    protected function entities()
    {
        return $this->load()->get('entities');
    }
}

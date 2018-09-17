<?php
namespace TNW\Salesforce\Synchronize;

class Entity
{
    /**
     * @var \TNW\Salesforce\Synchronize\Group
     */
    protected $synchronizeGroup;

    /**
     * Entity constructor.
     * @param \TNW\Salesforce\Synchronize\Group $synchronizeGroup
     */
    public function __construct(
        \TNW\Salesforce\Synchronize\Group $synchronizeGroup
    ) {
        $this->synchronizeGroup = $synchronizeGroup;
    }

    /**
     * @return Group
     */
    public function group()
    {
        return $this->synchronizeGroup;
    }

    /**
     * @param array $entities
     */
    public function synchronize(array $entities)
    {
        $this->synchronizeGroup->messageDebug('Start entity "%s" synchronize', $this->synchronizeGroup->code());

        try {
            $this->synchronizeGroup->synchronize($entities);
        } catch (\Exception $e) {
            $this->synchronizeGroup->messageError($e);
        }

        $this->synchronizeGroup->messageDebug('Stop entity "%s" synchronize', $this->synchronizeGroup->code());
    }
}
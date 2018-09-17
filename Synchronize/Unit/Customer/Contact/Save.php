<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer\Contact;

use TNW\Salesforce\Synchronize;

/**
 * @deprecated
 */
class Save extends Synchronize\Unit\Customer\Save
{
    public function __construct(
        $name,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        array $dependents = []
    ) {
        parent::__construct($name, 'customerLoad', 'sforce_id',
            $units, $group, $identification, $dependents);
    }
}
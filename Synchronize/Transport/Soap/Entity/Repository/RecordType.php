<?php

namespace TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository;

use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Synchronize\Transport;

/**
 * Class Base
 * @package TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository
 */
class RecordType extends Base
{

    protected $defaultConditionsData = [
        'from' => 'RecordType',
        'columns' => [
            'Id',
            'Name'
        ]
    ];

}
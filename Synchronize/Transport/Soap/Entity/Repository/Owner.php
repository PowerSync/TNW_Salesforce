<?php

namespace TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository;

use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Synchronize\Transport;

/**
 * Class Base
 * @package TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository
 */
class Owner extends Base
{

    protected $defaultConditionsData = [
        'from' => 'User',
        'columns' => [
            'Id',
            'Name'
        ],
        'where' => [
            'AND' => [
                'IsActive' => ['=' => true],
                'UserType' => ['!=' => 'CsnOnly']
            ]
        ]
    ];

}
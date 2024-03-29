<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\Customer;

/**
 * Class Mapper
 *
 * @deprecated
 * TODO: Remove
 */
class Mapper extends \TNW\Salesforce\Model\Mapper
{

    protected $cache_tag = 'tnw_salesforce_customer_mapper';

    protected $map_object = 'Contact';

    const OBJECT_TYPE_ACCOUNT = 'Account';

    const OBJECT_TYPE_CONTACT = 'Contact';

    public function initAccount()
    {
        $this->map_object =  self::OBJECT_TYPE_ACCOUNT;
    }

    public function initContact()
    {
        $this->map_object =  self::OBJECT_TYPE_CONTACT;
    }

}

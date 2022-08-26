<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\Config\Source\Customer;

use TNW\Salesforce\Api\Service\Admin\AddUniqueExceptionMessageInterface;
use TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository\Owner as OwnerRepository;
use TNW\Salesforce\Model\Config\Source\Salesforce\Base;
/**
 * Class Owner
 * @package TNW\Salesforce\Model\Config\Source\Customer
 */
class Owner extends Base
{
    /**
     * Owner constructor.
     *
     * @param AddUniqueExceptionMessageInterface $addUniqueExceptionMessage
     * @param OwnerRepository                    $salesforceEntityRepository
     * @param array                              $data
     */
    public function __construct(
        AddUniqueExceptionMessageInterface $addUniqueExceptionMessage,
        OwnerRepository $salesforceEntityRepository,
        array $data = []
    ) {
        parent::__construct($addUniqueExceptionMessage, $salesforceEntityRepository, $data);
    }
}

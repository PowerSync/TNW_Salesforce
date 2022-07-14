<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Unit\Customer\Account\Mapping;

use Magento\Customer\Model\Customer;
use TNW\Salesforce\Model\Customer\Config;
use TNW\Salesforce\Synchronize\Unit\Mapping;

/**
 *  Return default OwnerId for Account and Person Account
 */
class GetDefaultOwnerId
{
    /** @var Config */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Get default owner id
     *
     * @param Mapping  $mappingUnit
     * @param Customer $entity
     *
     * @return string
     */
    public function execute(Mapping $mappingUnit, Customer $entity): string
    {
        $leadLookup = $mappingUnit->unit('leadLookup');
        $ownerId = $this->config->defaultOwner($entity->getData('config_website'));
        if ($leadLookup
            && $leadLookup->get('%s/record/Id', $entity)
            && $leadLookup->get('%s/record/OwnerId', $entity)) {
            $ownerId = $leadLookup->get('%s/record/OwnerId', $entity);
        }

        return (string)$ownerId;
    }
}

<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Model\Grid;

use TNW\Salesforce\Api\Model\Grid\UpdateByDataInterface;

class GetGridUpdatersByEntityTypes
{
    /** @var UpdateByDataInterface[][] */
    private $gridUpdaters;

    /**
     * @param array $gridUpdaters
     */
    public function __construct(
        array $gridUpdaters = []
    ) {
        $this->gridUpdaters = $gridUpdaters;
    }

    public function execute(): array
    {
        return $this->gridUpdaters;
    }
}

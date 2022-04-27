<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Unit;

/**
 *  Get active unit singleton
 */
class CurrentUnit
{
    /** @var UnitInterface */
    private $unit;

    /**
     * Get unit from pool
     *
     * @return ?UnitInterface
     */
    public function getUnit(): ?UnitInterface
    {
        return $this->unit;
    }

    /**
     * Set unit to pool
     *
     * @param UnitInterface $unit
     */
    public function setUnit(UnitInterface $unit): void
    {
        $this->unit = $unit;
    }

    /**
     * Clear unit scope
     *
     * @return void
     */
    public function clear():void
    {
        $this->unit = null;
    }
}

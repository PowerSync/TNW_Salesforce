<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository;

use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Synchronize\Transport;

/**
 * Class Base
 * @package TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository
 */
abstract class Base
{
    /**
     * @deprecated
     * @see defaultConditionsData()
     * @var array
     */
    protected $defaultConditionsData = [];

    /**
     * @var Synchronize\Transport\Calls\QueryInterface
     */
    protected $query;

    /**
     * Base constructor.
     * @param Synchronize\Transport\Calls\QueryInterface $query
     */
    public function __construct(
        Synchronize\Transport\Calls\QueryInterface $query
    ) {
        $this->query = $query;
    }

    /**
     * @return array
     */
    public function defaultConditionsData(): array
    {
        return $this->defaultConditionsData;
    }

    /**
     * @param $conditionsData
     * @param int|null $websiteId
     *
     * @return mixed
     */
    public function search($conditionsData = null, $websiteId = null)
    {
        if ($conditionsData === null) {
            $conditionsData = $this->defaultConditionsData();
        }

        return $this->query->exec($conditionsData, $websiteId);
    }
}

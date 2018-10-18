<?php

namespace TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository;

use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Synchronize\Transport;

/**
 * Class Base
 * @package TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository
 */
abstract class Base
{
    /** @var array  */
    protected $defaultConditionsData = [];

    /**
     * @var Synchronize\Transport\Calls\QueryInterface
     */
    protected $query;

    /**
     * @var Synchronize\Transport\Calls\Query\InputFactory
     */
    protected $inputFactory;

    /**
     * @var Synchronize\Transport\Calls\Query\OutputFactory
     */
    protected $outputFactory;

    /**
     * Base constructor.
     * @param Synchronize\Transport\Calls\Query\InputFactory $inputFactory
     * @param Synchronize\Transport\Calls\Query\OutputFactory $outputFactory
     * @param Synchronize\Transport\Calls\QueryInterface $query
     */
    public function __construct(
        Transport\Calls\Query\InputFactory $inputFactory,
        Transport\Calls\Query\OutputFactory $outputFactory,
        Synchronize\Transport\Calls\QueryInterface $query
    )
    {
        $this->inputFactory = $inputFactory;
        $this->outputFactory = $outputFactory;

        $this->query = $query;
    }

    /**
     * @param $conditionsData
     * @return Transport\Calls\Query\Output
     */
    public function search($conditionsData = null)
    {
        if (is_null($conditionsData)) {
            $conditionsData = $this->defaultConditionsData;
        }

        $output = $this->query->exec($conditionsData);

        return $output;
    }
}
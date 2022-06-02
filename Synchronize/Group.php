<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize;

use BadMethodCallException;
use Iterator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SplObjectStorage;
use TNW\Salesforce\Synchronize\Unit\CurrentUnit;

/**
 * Group
 *
 * @method messageError($format, $args = null, $_ = null)
 * @method messageSuccess($format, $args = null, $_ = null)
 * @method messageWarning($format, $args = null, $_ = null)
 * @method messageNotice($format, $args = null, $_ = null)
 * @method messageDebug($format, $args = null, $_ = null)
 */
class Group
{
    /**
     * @var string[]
     */
    protected $units;

    /**
     * @var LoggerInterface
     */
    protected $systemLogger;

    /**
     * @var string
     */
    protected $groupCode;

    /**
     * @var array
     */
    protected $errorMessages = [];

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var UnitsFactory
     */
    protected $unitsFactory;

    /** @var CurrentUnit */
    private $currentUnit;

    /**
     * Entity constructor.
     * @param string $groupCode
     * @param string[] $units
     * @param UnitsFactory $unitsFactory
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface $systemLogger
     */
    public function __construct(
        $groupCode,
        array $units,
        UnitsFactory $unitsFactory,
        ObjectManagerInterface $objectManager,
        LoggerInterface $systemLogger,
        CurrentUnit $currentUnit
    ) {
        $this->groupCode = $groupCode;
        $this->units = array_filter($units);
        $this->objectManager = $objectManager;
        $this->systemLogger = $systemLogger;
        $this->unitsFactory = $unitsFactory;
        $this->currentUnit = $currentUnit;
    }

    /**
     * Code
     *
     * @return string
     */
    public function code()
    {
        return $this->groupCode;
    }

    /**
     * Synchronize
     *
     * @param \TNW\Salesforce\Model\Queue[] $queues
     * @return Units
     * @throws LocalizedException
     */
    public function synchronize(array $queues)
    {
        $units = $this->createUnits($queues)->sort();
        /** @var Unit\UnitInterface $unit */
        foreach ($units as $unit) {
            $this->currentUnit->setUnit($unit);
            foreach ($unit->dependents() as $dependent) {
                if ($units->get($dependent)->isComplete()) {
                    continue;
                }

                throw new LocalizedException(__('Unit (%1) process not complete', $units->get($dependent)->name()));
            }

            $this->messageDebug('----------------------------------------------------');
            $this->messageDebug('%s. Unit name %s', $unit->description(), $unit->name());
            $unit->status($unit::PROCESS);
            $unit->process();
            $unit->status($unit::COMPLETE);
        }
        $this->currentUnit->clear();

        return $units;
    }

    /**
     * Get object manager
     *
     * @return ObjectManagerInterface
     */
    public function getObjectManager(): ObjectManagerInterface
    {
        return $this->objectManager;
    }

    /**
     * Create Units
     *
     * @param \TNW\Salesforce\Model\Queue[] $queues
     * @return Units
     */
    protected function createUnits(array $queues)
    {
        /** @var Units $units */
        $units = $this->unitsFactory->create();
        foreach ($this->units as $instanceName) {
            $units->add($this->objectManager->create($instanceName, [
                'group' => $this,
                'queues' => $queues,
                'units' => $units
            ]));
        }

//        $this->buildUnitGraph($units);
        return $units;
    }

    /**
     *
     */
    public function buildUnitGraph($units)
    {
        $graph = [
//            '@startdot',
            sprintf('digraph %s {', $this->code()),
            "node [shape=box];",
            'labelloc = "t";'
        ];

//        [shape=box]
        $parent = null;
        foreach ($units as $unit) {
            if (!$parent) {
                $graph[] = sprintf('%s [label="%s"];', $unit->name(), $unit->name());
            } else {
                $graph[] = sprintf('%s -> %s ;', $parent, $unit->name());
            }
            $parent = $unit->name();
        }

        $graph[] = '}';
//        $graph[] = '@enddot';

        file_put_contents($this->code() . '.dot', implode("\n", $graph));
    }

    /**
     * Call
     *
     * @param string $name
     * @param array $arguments
     * @throws BadMethodCallException
     * @throws RuntimeException
     */
    public function __call($name, $arguments)
    {
        if (stripos($name, 'message') !== 0) {
            throw new BadMethodCallException('Unknown method');
        }

        if (count($arguments) === 0) {
            throw new BadMethodCallException('Missed argument "$format"');
        }

        //Prepare arguments
        $arguments = array_map([$this, 'prepareArgument'], $arguments);

        //FIX: Too few argument
        if (substr_count($arguments[0], '%') > (count($arguments) - 1)) {
            $arguments[0] = str_replace('%', '%%', $arguments[0]);
        }
        $arguments[0] = preg_replace('/%\d/', '%s', $arguments[0]);

        /** @var string $message */
        $message = sprintf(...$arguments);

        /** switch level */
        switch (strtolower((string)substr($name, 7))) {
            case 'error':
                $this->errorMessages[] = $message;
                $this->systemLogger->error($message);
                break;

            case 'success':
                $this->systemLogger->info($message);
                break;

            case 'warning':
                $this->systemLogger->warning($message);
                break;

            case 'notice':
                $this->systemLogger->notice($message);
                break;

            case 'debug':
                $this->systemLogger->debug($message);
                break;

            default:
                break;
        }
    }

    /**
     * Prepare Argument
     *
     * @param mixed $argument
     * @return string
     */
    public function prepareArgument($argument)
    {
        if ($argument instanceof Phrase) {
            $argument = $argument->render();
        }

        if ($argument instanceof \Exception) {
            $argument = $argument->getMessage();
        }

        if ($argument instanceof Transport\Calls\Query\Input) {
            $argument = $argument->query();
        }

        if ($argument instanceof SplObjectStorage) {
            $argument = array_map(function ($entity) use ($argument) {
                return $argument[$entity];
            }, iterator_to_array($argument));
        }

        if ($argument instanceof Iterator) {
            $argument = iterator_to_array($argument);
        }

        if (is_bool($argument)) {
            return $argument ? 'true' : 'false';
        }

        if (is_scalar($argument)) {
            $argument = (string)$argument;

            return str_replace("\"", "", $argument);
        }

        return print_r($argument, true);
    }

    /**
     * Is Error
     *
     * @return bool
     */
    public function isError()
    {
        return count($this->errorMessages) > 0;
    }

    /**
     * Error
     *
     * @return string
     */
    public function error()
    {
        return implode("\n", $this->errorMessages);
    }
}

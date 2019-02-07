<?php
namespace TNW\Salesforce\Synchronize;

use Magento\Framework\Exception\LocalizedException;

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
     * @var \Psr\Log\LoggerInterface
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
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var UnitsFactory
     */
    protected $unitsFactory;

    /**
     * Entity constructor.
     * @param string $groupCode
     * @param string[] $units
     * @param UnitsFactory $unitsFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Psr\Log\LoggerInterface $systemLogger
     */
    public function __construct(
        $groupCode,
        array $units,
        UnitsFactory $unitsFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Psr\Log\LoggerInterface $systemLogger
    ) {
        $this->groupCode = $groupCode;
        $this->units = array_filter($units);
        $this->objectManager = $objectManager;
        $this->systemLogger = $systemLogger;
        $this->unitsFactory = $unitsFactory;
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
        return $units;
        /** @var Unit\UnitInterface $unit */
        foreach ($units as $unit) {
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

        return $units;
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

        return $units;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @throws \BadMethodCallException
     * @throws \RuntimeException
     */
    public function __call($name, $arguments)
    {
        if (stripos($name, 'message') !== 0) {
            throw new \BadMethodCallException('Unknown method');
        }

        if (count($arguments) === 0) {
            throw new \BadMethodCallException('Missed argument "$format"');
        }

        //Prepare arguments
        $arguments = array_map(function ($argument) {
            if ($argument instanceof \Magento\Framework\Phrase) {
                $argument = $argument->render();
            }

            if ($argument instanceof \Exception) {
                $argument = $argument->getMessage();
            }

            if ($argument instanceof Transport\Calls\Query\Input) {
                $argument = $argument->query();
            }

            if ($argument instanceof \SplObjectStorage) {
                $argument = array_map(function ($entity) use ($argument) {
                    return $argument[$entity];
                }, iterator_to_array($argument));
            }

            if ($argument instanceof \Iterator) {
                $argument = iterator_to_array($argument);
            }

            if (is_bool($argument)) {
                return $argument ? 'true' : 'false';
            }

            if (is_scalar($argument)) {
                return (string) $argument;
            }

            return print_r($argument, true);
        }, $arguments);

        //FIX: Too few argument
        if (substr_count($arguments[0], '%') > (count($arguments) - 1)) {
            $arguments[0] = str_replace('%', '%%', $arguments[0]);
        }

        /** @var string $message */
        $message = sprintf(...$arguments);

        /** switch level */
        switch (strtolower(substr($name, 7))) {
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
     * @return bool
     */
    public function isError()
    {
        return count($this->errorMessages) > 0;
    }

    /**
     * @return string
     */
    public function error()
    {
        return implode("\n", $this->errorMessages);
    }
}
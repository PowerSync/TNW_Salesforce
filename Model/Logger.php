<?php
declare(strict_types=1);

namespace TNW\Salesforce\Model;

/**
 * Class Logger
 * @deprecated, code refactoring needed
 * @package TNW\Salesforce\Model
 *
 * @method messageError($format, $args = null, $_ = null)
 * @method messageSuccess($format, $args = null, $_ = null)
 * @method messageWarning($format, $args = null, $_ = null)
 * @method messageNotice($format, $args = null, $_ = null)
 * @method messageDebug($format, $args = null, $_ = null)
 */
class Logger
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $systemLogger;

    /**
     * Logger constructor.
     * @param \Psr\Log\LoggerInterface $systemLogger
     */
    public function __construct(
        \Psr\Log\LoggerInterface $systemLogger
    ) {
        $this->systemLogger = $systemLogger;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger(): \Psr\Log\LoggerInterface
    {
        return $this->systemLogger;
    }

    /**
     * Add error message to session
     * @param array|string $messages
     * @param string $group
     * @return $this
     * @deprecated
     * @see messageError method
     */
    public function addSessionErrorMessages($messages, $group = null): Logger
    {
        if (!is_array($messages)) {
            $messages = [(string)$messages];
        }
        foreach ($messages as $message) {
            $this->messageError('%s', $message);
        }
        return $this;
    }

    /**
     * Add success message to session
     * @param array|string $messages
     * @param string $group
     * @return $this
     * @deprecated
     * @see messageSuccess method
     */
    public function addSessionSuccessMessages($messages, $group = null): Logger
    {
        if (!is_array($messages)) {
            $messages = [(string)$messages];
        }
        foreach ($messages as $message) {
            $this->messageSuccess('%s', $message);
        }
        return $this;
    }

    /**
     * Add success message to session
     * @param array|string $messages
     * @param string $group
     * @return $this
     * @deprecated
     * @see messageWarning method
     */
    public function addSessionWarningMessages($messages, $group = null): Logger
    {
        if (!is_array($messages)) {
            $messages = [(string)$messages];
        }
        foreach ($messages as $message) {
            $this->messageWarning('%s', $message);
        }
        return $this;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @throws \BadMethodCallException
     */
    public function __call($name, $arguments)
    {
        if (stripos($name, 'message') !== 0){
            throw new \BadMethodCallException('Unknown method');
        }

        if (count($arguments) === 0) {
            throw new \BadMethodCallException('Missed argument "$format"');
        }

        //Prepare arguments
        $arguments = array_map(function ($argument) {
            if (is_scalar($argument)) {
                return (string) $argument;
            }

            if ($argument instanceof \Magento\Framework\Phrase) {
                $argument = $argument->render();
            }

            if ($argument instanceof \Exception) {
                $argument = $argument->getMessage();
            }

            if ($argument instanceof \Tnw\SoapClient\Result\RecordIterator) {
                $argument = $argument->getQueryResult()->getRecords();
            }

            if (is_array($argument)) {
                $argument = array_map(function ($argument) {
                    if ($argument instanceof \Tnw\SoapClient\Result\Error) {
                        return sprintf('%s [%s]', $argument->getMessage(), implode(',', (array)$argument->getFields()));
                    }

                    return $argument;
                }, $argument);
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
}

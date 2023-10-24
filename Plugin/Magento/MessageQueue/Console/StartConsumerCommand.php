<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Plugin\Magento\MessageQueue\Console;

use Closure;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TNW\Salesforce\Model\Config;

/**
 * Class CustomerGridAddAction
 * @package TNW\Salesforce\Plugin
 */
class StartConsumerCommand
{

    protected $config;
    protected $consumers = [];

    public function __construct(
        Config $config,
               $consumers = []
    )
    {
        $this->config = $config;
        $this->consumers = $consumers;
    }

    public function getAllSFConsumers()
    {
        return $this->consumers;
    }

    public function filterConsumerByNotMQMode($consumerName)
    {
        $mqMode = $this->config->getMQMode() ?: 'db';
        $lastCharactersCount = strlen($mqMode);
        return stripos($consumerName, $mqMode, -$lastCharactersCount) == false;
    }

    public function getDisabledConsumers()
    {
        return array_filter($this->getAllSFConsumers(), [$this, 'filterConsumerByNotMQMode']);
    }

    protected function consumerIsDisabled($consumerName)
    {
        return (!$this->config->getSalesforceStatus() && in_array($consumerName, $this->getAllSFConsumers()))
            || in_array($consumerName, $this->getDisabledConsumers());
    }

    public function aroundRun(
        \Magento\MessageQueue\Console\StartConsumerCommand $subject,
        Closure                                            $proceed,
        InputInterface                                     $input,
        OutputInterface                                    $output
    )
    {
        $subject->mergeApplicationDefinition();
        $input->bind($subject->getDefinition());
        $consumerName = $input->getArgument(\Magento\MessageQueue\Console\StartConsumerCommand::ARGUMENT_CONSUMER);

        if ($this->consumerIsDisabled($consumerName)) {
            $output->writeln('<info>Consumer could not be started it is disabled</info>,  Consumer name: ' . $consumerName . ' MQ mode: ' . $this->config->getMQMode());
            return Cli::RETURN_SUCCESS;
        }

        $result = $proceed($input, $output);

        return $result;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: eermolaev
 * Date: 25.12.17
 * Time: 15:50
 */

namespace TNW\Salesforce\Cron;

use \TNW\Salesforce\Model\Logger;
use \TNW\Salesforce\Model\Config;
use \TNW\Salesforce\Console\Command\CleanSystemLogsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
/**
 * Class CurrencyRatesUpdate
 *
 * @package TNW\SForceEnterprise\Cron
 */
class ClearSystemLog
{
   
   
    /** @var Logger */
    private $logger;

    /** @var Config */
    private $config;

    /**
     * UpdateCurrencyRates constructor.
     *
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(
        CleanSystemLogsCommand $cleanSystemLogsCommand,
        Logger $logger,
        Config $config
    )
    {
        $this->cleanSystemLogsCommand = $cleanSystemLogsCommand;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * exec
     */
    public function execute()
    {
        try {

            $this->cleanSystemLogsCommand->execute(InputInterface $input, OutputInterface $output);

        } catch (\Exception $e) {
            $this->getLogger()->messageError($e->getMessage());
        }
        
    }

}

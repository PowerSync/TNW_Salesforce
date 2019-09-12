<?php
/**
 * Created by PhpStorm.
 * User: eermolaev
 * Date: 25.12.17
 * Time: 15:50
 */

namespace TNW\Salesforce\Cron;

use Exception;
use FilesystemIterator;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use TNW\Salesforce\Console\Command\CleanSystemLogsCommand;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\Logger;

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
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * UpdateCurrencyRates constructor.
     *
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(
        //CleanSystemLogsCommand $cleanSystemLogsCommand,
        LoggerInterface $logger,
        Config $salesforceConfig,
        File $file,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        TimezoneInterface $timezone,
        Config $config,
        Filesystem $filesystem
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->dir = $dir;
        $this->timezone = $timezone;
        $this->config = $config;
        $this->salesforceConfig = $salesforceConfig;
        $this->_filesystem = $filesystem;
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
            if (!$this->salesforceConfig->getClearSystemLogs()) {
                $this->logger->info($this->getDateTime() . ': ' . ' Clear System logs not configured');
                return;
            }

            $path = $this->_filesystem->getDirectoryRead(DirectoryList::LOG)->getAbsolutePath() . 'sforce';

            $result = [];

            $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS;

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, $flags),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            $currentDate        =  strtotime($this->getDate());
            $logClearTershold = $this->salesforceConfig->getDebugLogClearDays();

            /** @var FilesystemIterator $file */
            foreach ($iterator as $file) {
                if (substr($file->getFilename(), -4) !== '.log') {
                    continue;
                }

                $lastModifiedDate   =  strtotime(date('Y-m-d', $file->getMTime()));

                $differenceInDays   = round(($currentDate - $lastModifiedDate) / 86400);

                if ($differenceInDays > $logClearTershold) {
                    $filePath = $file->getPathname();
                    $result[] = $file->getFilename();

                    if ($this->file->isExists($filePath)) {
                        $this->file->deleteFile($filePath);
                    }
                }
            }

            $this->logger->info($this->getDateTime() . ': ' . 'Cleared log files older than ==>' . $logClearTershold . ' days', $result);

            return true;
        } catch (Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * @return string
     */
    public function getDateTime()
    {
        return $this->timezone->date()->format('m/d/y H:i:s');
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->timezone->date()->format('m/d/y');
    }
}

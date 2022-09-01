<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\DB;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Profiler as BaseProfiler;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;

/**
 *  Usage:
 * Start profile:
 *
 * \TNW\Salesforce\DB\Profiler::start();
 * ... Code here ...
 * \TNW\Salesforce\DB\Profiler::stop(__METHOD__);
 *
 * Partial profile after "Start profile":
 *
 * $code = __METHOD__ . ':' . __LINE__;
 * \TNW\Salesforce\DB\Profiler::startPartial($code);
 *  ... Code here ...
 * \TNW\Salesforce\DB\Profiler::stopPartial($code);
 */
class Profiler extends BaseProfiler
{
    private const PARTIAL_COUNT_CALLS_KEY = 'count_calls';
    private const PARTIAL_START_TIME_KEY = 'start_time';
    private const PARTIAL_TIME_KEY = 'time';
    private const PARTIAL_MEMORY_USAGE_START_KEY = 'memory_usage_start';
    private const PARTIAL_MEMORY_USAGE_KEY = 'memory_usage';
    private const PARTIAL_SPECIFIC_QUERIES_KEY = 'specific_queries';
    private const PARTIAL_ENABLED_KEY = 'enabled';
    private const PARTIAL_REAL_CODE_KEY = 'real_code';

    /** @var bool */
    protected $_enabled = true;

    /** @var bool */
    private static $enabled = false;

    /** @var array */
    private $stacktraces = [];

    /** @var int */
    private static $startTime = 0;

    /** @var int */
    private static $endTime = 0;

    /** @var int */
    private static $startMemoryUsage = 0;

    /** @var int */
    private static $endMemoryUsage = 0;

    /** @var array */
    private $specificQueries = [];

    /** @var array */
    private static $queryConditions = [];

    /** @var string */
    private $basePath;

    /** @var WriteInterface */
    private $directoryWrite;

    /** @var array */
    private static $partialProfileInfo = [];

    /**
     * Start profile
     *
     * @param array $queryConditions
     *
     * @return void
     */
    public static function start(array $queryConditions = []): void
    {
        self::$enabled = true;
        self::$queryConditions = $queryConditions;
        self::$startMemoryUsage = memory_get_usage(true);
        self::$startTime = hrtime(true);
    }

    /**
     * Stop profile
     *
     * @param string $methodName
     *
     * @return void
     * @throws FileSystemException
     */
    public static function stop(string $methodName): void
    {
        self::$endMemoryUsage = memory_get_usage(true);
        self::$endTime = hrtime(true);
        self::$enabled = false;
        $res = ObjectManager::getInstance()->get(ResourceConnection::class);
        /** @var Profiler $profiler */
        $profiler = $res->getConnection('read')->getProfiler();
        if ($profiler && $profiler instanceof self) {
            $fileName = str_replace("\\", '_', $methodName);
            $profiler->writeResultToHtmlFiles($fileName);
        }
    }

    /**
     * @param string $realCode
     *
     * @return void
     */
    public static function startPartial(string $realCode): void
    {
        if (!self::$enabled) {
            return;
        }
        $code = str_replace("\\", '_', $realCode);

        self::$partialProfileInfo[$code][self::PARTIAL_START_TIME_KEY] = hrtime(true);
        self::$partialProfileInfo[$code][self::PARTIAL_MEMORY_USAGE_START_KEY] = memory_get_usage(true);
        self::$partialProfileInfo[$code][self::PARTIAL_ENABLED_KEY] = true;
        self::$partialProfileInfo[$code][self::PARTIAL_REAL_CODE_KEY] = $realCode;
    }

    /**
     * @param string $code
     *
     * @return void
     */
    public static function stopPartial(string $code): void
    {
        if (!self::$enabled) {
            return;
        }
        $code = str_replace("\\", '_', $code);

        $data = self::$partialProfileInfo[$code] ?? [];

        $memoryUsage = (int)($data[self::PARTIAL_MEMORY_USAGE_KEY] ?? 0);
        $memoryUsageStart = (int)($data[self::PARTIAL_MEMORY_USAGE_START_KEY] ?? 0);
        if ($memoryUsageStart) {
            $nowMemoryUsage = memory_get_usage(true) - $memoryUsageStart;
            $memoryUsage += $nowMemoryUsage;
        }
        $data[self::PARTIAL_MEMORY_USAGE_KEY] = $memoryUsage;

        $timeUsage = (int)($data[self::PARTIAL_TIME_KEY] ?? 0);
        $timeUsageStart = (int)($data[self::PARTIAL_START_TIME_KEY] ?? 0);
        if ($timeUsageStart) {
            $nowTimeUsage = hrtime(true) - $timeUsageStart;
            $timeUsage += $nowTimeUsage;
        }
        $data[self::PARTIAL_TIME_KEY] = $timeUsage;

        $countCalls = (int)($data[self::PARTIAL_COUNT_CALLS_KEY] ?? 0);
        $data[self::PARTIAL_COUNT_CALLS_KEY] = $countCalls + 1;

        $data[self::PARTIAL_ENABLED_KEY] = false;

        self::$partialProfileInfo[$code] = $data;
    }

    /**
     * @inheritDoc
     */
    public function queryStart($queryText, $queryType = null)
    {
        if (!self::$enabled) {
            return null;
        }

        return parent::queryStart($queryText, $queryType);
    }

    /**
     * @inheritDoc
     */
    public function queryEnd($queryId)
    {
        if (!self::$enabled) {
            return self::IGNORED;
        }

        $result = parent::queryEnd($queryId);

        $profileObject = $this->_queryProfiles[$queryId];
        if ($profileObject) {
            $this->stacktraces[$queryId] = (new \Exception())->getTraceAsString();
            $queryText = $profileObject->getQuery();
            foreach (self::$queryConditions as $specificQueryCondition) {
                if (preg_match($specificQueryCondition, $queryText)) {
                    $this->specificQueries[$specificQueryCondition][$queryId] = $profileObject;
                    foreach (self::$partialProfileInfo as $code => $data) {
                        $enabled = $data[self::PARTIAL_ENABLED_KEY] ?? false;
                        if ($enabled === true) {
                            self::$partialProfileInfo[$code][self::PARTIAL_SPECIFIC_QUERIES_KEY][$specificQueryCondition][$queryId] = $profileObject;
                        }
                    }
                }
            }

            foreach (self::$partialProfileInfo as $code => $data) {
                $enabled = $data[self::PARTIAL_ENABLED_KEY] ?? false;
                if ($enabled === true) {
                    self::$partialProfileInfo[$code]['queries'][$queryId] = $profileObject;
                }
            }
        }

        return $result;
    }

    /**
     * @throws FileSystemException
     */
    public function writeResultToHtmlFiles(string $pathName): void
    {
        $this->initBasePath($pathName);
        $this->buildMainTable();
        $this->buildStacktraceTable();
        $this->buildStatsTable();
        $this->buildSpecificTables($this->specificQueries);
        $this->buildPartialTables();
    }

    /**
     * @param array $queries
     *
     * @return float
     */
    public function getTotalElapsedSecsByQueries(array $queries): float
    {
        $elapsedSecs = 0;
        foreach ($queries as $key => $qp) {
            if (($qp->hasEnded())) {
                $elapsedSecs += $qp->getElapsedSecs();
            }
        }

        return (float)$elapsedSecs;
    }

    /**
     * @return void
     * @throws FileSystemException
     */
    private function buildMainTable(): void
    {
        $directoryWrite = $this->getDirectoryWrite();
        $basePath = $this->basePath;
        $queryProfiles = $this->getQueryProfiles();
        $tableData = "<table cellpadding='0' cellspacing='0' border='1'>";
        $tableData .= "<tr>";
        $tableData .= "<th>Query id</th>";
        $tableData .= "<th>Time <br/>[Total Time: " . $this->getTotalElapsedSecs() . " secs]</th>";
        $tableData .= "<th>SQL [Total: " . $this->getTotalNumQueries() . " queries]</th>";
        $tableData .= "<th>Query Params</th>";
        $tableData .= "</tr>";
        foreach ($queryProfiles as $queryId => $query) {
            /** @var \Zend_Db_Profiler_Query $query */
            $tableData .= '<tr>';
            $tableData .= '<td>' . 'QueryId: ' . $queryId . '</td>';
            $tableData .= '<td>' . number_format(1000 * $query->getElapsedSecs(), 2) . 'ms' . '</td>';
            $tableData .= '<td>' . $query->getQuery() . '</td>';
            $tableData .= '<td>' . json_encode($query->getQueryParams()) . '</td>';
            $tableData .= '</tr>';
        }
        $tableData .= "</table>";
        $directoryWrite->writeFile($basePath . "queries.html", $tableData);
    }

    /**
     * @return void
     * @throws FileSystemException
     */
    private function buildStacktraceTable(): void
    {
        $directoryWrite = $this->getDirectoryWrite();
        $basePath = $this->basePath;
        $queryProfiles = $this->getQueryProfiles();
        $tableData = "<table cellpadding='0' cellspacing='0' border='1'>";
        $tableData .= "<tr>";
        $tableData .= "<th>Query id</th>";
        $tableData .= "<th>Stack Trace</th>";
        $tableData .= "</tr>";
        foreach ($queryProfiles as $queryId => $query) {
            $tableData .= '<tr>';
            $tableData .= '<td>' . 'QueryId: ' . $queryId . '</td>';
            $tableData .= '<td>' . (string)($this->stacktraces[$queryId] ?? '') . '</td>';
            $tableData .= '</tr>';
        }
        $tableData .= "</table>";
        $directoryWrite->writeFile($basePath . "queries_stacktrace.html", $tableData);
    }

    /**
     * @return void
     * @throws FileSystemException
     */
    private function buildStatsTable(): void
    {
        $directoryWrite = $this->getDirectoryWrite();
        $basePath = $this->basePath;
        $tableData = "<table cellpadding='0' cellspacing='0' border='1'>";
        $tableData .= "<tr>";
        $tableData .= "<th>Start point:</th>";
        $tableData .= "<th>Memory usage:</th>";
        $tableData .= "<th>Time usage:</th>";
        $tableData .= "<th>Total queries:</th>";
        $tableData .= "<th>Count calls:</th>";
        $tableData .= "</tr>";
        $tableData .= '<tr>';
        $tableData .= '<td>Base path</td>';
        $tableData .= '<td>' . (string)(((self::$endMemoryUsage - self::$startMemoryUsage) / 1024) / 1024) . 'Mb' . '</td>';
        $tableData .= '<td>' . (string)((self::$endTime - self::$startTime) / 1e9) . ' s.' . '</td>';
        $tableData .= '<td>' . $this->getTotalNumQueries() . '</td>';
        $tableData .= '<td>1</td>';
        $tableData .= '</tr>';
        foreach (self::$partialProfileInfo as $info) {
            $queries = $info['queries'] ?? [];
            $tableData .= '<tr>';
            $memoryUsage = (int)($info[self::PARTIAL_MEMORY_USAGE_KEY] ?? 0);
            $tableData .= '<td>' . (string)($info[self::PARTIAL_REAL_CODE_KEY] ?? '') . '</td>';
            $tableData .= '<td>' . (string)((($memoryUsage) / 1024) / 1024) . 'Mb' . '</td>';
            $timeUsage = (int)($info[self::PARTIAL_TIME_KEY] ?? 0);
            $tableData .= '<td>' . (string)(($timeUsage) / 1e9) . ' s.' . '</td>';
            $tableData .= '<td>' . count($queries) . '</td>';
            $countCalls = (int)($info[self::PARTIAL_COUNT_CALLS_KEY] ?? 0);
            $tableData .= '<td>' . $countCalls . '</td>';
            $tableData .= '</tr>';
        }
        $tableData .= "</table>";
        $directoryWrite->writeFile($basePath . "stats.html", $tableData);
    }

    /**
     * @return void
     * @throws FileSystemException
     */
    private function buildSpecificTables(array $specificQueries, string $basePath = null): void
    {
        $directoryWrite = $this->getDirectoryWrite();
        $basePath = $basePath ?? $this->basePath;
        foreach ($specificQueries as $condition => $queries) {
            $conditionBasePath = $basePath . $condition . '/';
            $tableData = "<table cellpadding='0' cellspacing='0' border='1'>";
            $tableData .= "<tr>";
            $tableData .= "<th>Query id</th>";
            $tableData .= "<th>Time <br/>[Total Time: " . $this->getTotalElapsedSecsByQueries($queries) . " secs]</th>";
            $tableData .= "<th>SQL [Total: " . count($queries) . " queries]</th>";
            $tableData .= "<th>Query Params</th>";
            $tableData .= "</tr>";
            foreach ($queries as $queryId => $query) {
                /** @var \Zend_Db_Profiler_Query $query */
                $tableData .= '<tr>';
                $tableData .= '<td>' . 'QueryId: ' . $queryId . '</td>';
                $tableData .= '<td>' . number_format(1000 * $query->getElapsedSecs(), 2) . 'ms' . '</td>';
                $tableData .= '<td>' . $query->getQuery() . '</td>';
                $tableData .= '<td>' . json_encode($query->getQueryParams()) . '</td>';
                $tableData .= '</tr>';
            }
            $tableData .= "</table>";
            $directoryWrite->writeFile($conditionBasePath . "queries.html", $tableData);
        }
    }

    /**
     * @return void
     * @throws FileSystemException
     */
    private function buildPartialTables(): void
    {
        $directoryWrite = $this->getDirectoryWrite();
        $basePath = $this->basePath;
        foreach (self::$partialProfileInfo as $code => $info) {
            $conditionBasePath = $basePath . $code . '/';
            $queries = $info['queries'] ?? [];
            if ($queries) {
                $tableData = "<table cellpadding='0' cellspacing='0' border='1'>";
                $tableData .= "<tr>";
                $tableData .= "<th>Query id</th>";
                $tableData .= "<th>Time <br/>[Total Time: " . $this->getTotalElapsedSecsByQueries($queries) . " secs]</th>";
                $tableData .= "<th>SQL [Total: " . count($queries) . " queries]</th>";
                $tableData .= "<th>Query Params</th>";
                $tableData .= "</tr>";
                foreach ($queries as $queryId => $query) {
                    /** @var \Zend_Db_Profiler_Query $query */
                    $tableData .= '<tr>';
                    $tableData .= '<td>' . 'QueryId: ' . $queryId . '</td>';
                    $tableData .= '<td>' . number_format(1000 * $query->getElapsedSecs(), 2) . 'ms' . '</td>';
                    $tableData .= '<td>' . $query->getQuery() . '</td>';
                    $tableData .= '<td>' . json_encode($query->getQueryParams()) . '</td>';
                    $tableData .= '</tr>';
                }
                $tableData .= "</table>";
                $directoryWrite->writeFile($conditionBasePath . "queries.html", $tableData);
            }
            $specificQueries = $info[self::PARTIAL_SPECIFIC_QUERIES_KEY] ?? [];
            $specificQueries && $this->buildSpecificTables($specificQueries, $conditionBasePath);
        }
    }

    /**
     * @param string $pathName
     *
     * @return void
     */
    private function initBasePath(string $pathName): void
    {
        if ($this->basePath === null) {
            $time = (string)time();
            $this->basePath = "profile/db/{$pathName}/{$time}/";
        }

    }

    /**
     * @return WriteInterface
     * @throws FileSystemException
     */
    private function getDirectoryWrite(): WriteInterface
    {
        if ($this->directoryWrite === null) {
            $objectManager = ObjectManager::getInstance();
            $filesystem = $objectManager->get(Filesystem::class);
            $directoryList = $objectManager->get(DirectoryList::class);
            $this->directoryWrite = $filesystem->getDirectoryWrite($directoryList::VAR_DIR);
        }

        return $this->directoryWrite;
    }

}

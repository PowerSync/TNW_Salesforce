<?php
namespace TNW\Salesforce\Test\Unit\Model;

/**
 * Class LoggerTest
 * @package TNW\Salesforce\Test\Unit\Model
 */
class LoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Psr\Log\LoggerInterface mock
     */
    protected $systemLoggerMock;

    /**
     * @var \Magento\Framework\Message\ManagerInterface mock
     */
    protected $messageManagerMock;

    /**
     * @var \TNW\Salesforce\Model\Config mock
     */
    protected $salesforceConfigMock;

    /**
     * @var \TNW\Salesforce\Model\Logger
     */
    protected $model;

    /**
     * Test setup
     */
    public function setUp()
    {
        $this->systemLoggerMock = $this->getMock(
            'Psr\Log\LoggerInterface',
            [],
            [],
            '',
            false
        );

        $this->messageManagerMock = $this->getMock(
            '\Magento\Framework\Message\ManagerInterface',
            [],
            [],
            '',
            false
        );

        $this->salesforceConfigMock = $this->getMock(
            'TNW\Salesforce\Model\Config',
            [],
            [],
            '',
            false
        );

        $this->model = new \TNW\Salesforce\Model\Logger(
            $this->systemLoggerMock,
            $this->messageManagerMock,
            $this->salesforceConfigMock
        );
    }

    /**
     * Test for \TNW\Salesforce\Model\Logger::getLogger
     */
    public function testGetLogger()
    {
        $actualResult = $this->model->getLogger();
        $this->assertInstanceOf('Psr\Log\LoggerInterface', $actualResult);
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $this->systemLoggerMock     = null;
        $this->messageManagerMock   = null;
        $this->salesforceConfigMock = null;
        $this->model                = null;
    }
}

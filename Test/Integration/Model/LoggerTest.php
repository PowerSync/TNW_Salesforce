<?php
namespace TNW\Salesforce\Test\Integration\Model;

/**
 * Class LoggerTest
 * @package TNW\Salesforce\Test\Integration\Model
 */
class LoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Model\Logger
     */
    protected $logger;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Test setup
     */
    public function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->logger = $this->objectManager->create('TNW\Salesforce\Model\Logger');
    }

    /**
     * Test for \TNW\Salesforce\Model\Logger::addSessionErrorMessages
     */
    public function testAddSessionErrorMessages()
    {
        $messageText = 'Test error message';
        $this->logger->addSessionErrorMessages([$messageText]);

        $errorMessagesArray = $this->objectManager
            ->create('Magento\Framework\Message\ManagerInterface')
            ->getMessages()
            ->getErrors();
        foreach ($errorMessagesArray as $message) {
            $reflection = new \ReflectionClass($message);
            $property = $reflection->getProperty('text');
            $property->setAccessible(true);

            $this->assertEquals($messageText, $property->getValue($message));
        }
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $this->logger        = null;
        $this->objectManager = null;
    }
}

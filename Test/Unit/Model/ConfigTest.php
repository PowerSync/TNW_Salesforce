<?php
namespace TNW\Salesforce\Test\Unit\Model;

use \Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class ConfigTest
 * @package TNW\Salesforce\Test\Unit\Model
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Model\Config
     */
    protected $model;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface mock
     */
    protected $configMock;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface mock
     */
    protected $encryptorMock;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList mock
     */
    protected $directoryListMock;

    /**
     * Test setup
     */
    public function setUp()
    {
        $this->configMock = $this->getMock('Magento\Framework\App\Config\ScopeConfigInterface');

        $this->directoryListMock = $this->getMock('Magento\Framework\App\Filesystem\DirectoryList',
            [],
            [],
            '',
            false
        );

        $this->encryptorMock = $this->getMockForAbstractClass('Magento\Framework\Encryption\EncryptorInterface',
            [],
            '',
            false
        );

        /** @var $storeManagerMock \Magento\Store\Model\StoreManagerInterface */
        $storeManagerMock = $this->getMock('Magento\Store\Model\StoreManagerInterface',
            [],
            [],
            '',
            false
        );

        $this->model = new \TNW\Salesforce\Model\Config(
            $this->configMock,
            $this->directoryListMock,
            $this->encryptorMock,
            $storeManagerMock
        );
    }

    /**
     * Test for \TNW\Salesforce\Model\Config::getLogDir
     * @param $expectedResult
     *
     * @dataProvider testGetLogDirDataProvider
     */
    public function testGetLogDir($expectedResult)
    {
        $this->directoryListMock->expects($this->once())->method('getPath')->will($this->returnValue(DirectoryList::LOG));
        $this->assertSame($expectedResult, $this->model->getLogDir());
    }

    /**
     * Test for \TNW\Salesforce\Model\Config::getSalesforceWsdl
     * @param $configValue
     * @param $expectedResult
     *
     * @dataProvider testGetSalesforceWsdlDataProvider
     */
    public function testGetSalesforceWsdl($configValue, $expectedResult)
    {
        $this->configMock->expects($this->once())->method('getValue')->will($this->returnValue($configValue));
        $this->directoryListMock->expects($this->once())->method('getPath')->will($this->returnValue(DirectoryList::ROOT));
        $this->assertSame($expectedResult, $this->model->getSalesforceWsdl());
    }

    /**
     * Test for \TNW\Salesforce\Model\Config::getSalesforceStatus
     * @param $configValue
     * @param $expectedResult
     *
     * @dataProvider testIntDataProvider
     */
    public function testGetSalesforceStatus($configValue, $expectedResult)
    {
        $this->configMock->expects($this->once())->method('getValue')->will($this->returnValue($configValue));
        $this->assertSame($expectedResult, $this->model->getSalesforceStatus());
    }

    /**
     * Test for \TNW\Salesforce\Model\Config::getSalesforceUsername
     * @param $configValue
     * @param $expectedResult
     *
     * @dataProvider testGetSalesforceUsernameDataProvider
     */
    public function testGetSalesforceUsername($configValue, $expectedResult)
    {
        $this->configMock->expects($this->once())->method('getValue')->will($this->returnValue($configValue));
        $this->assertSame($expectedResult, $this->model->getSalesforceUsername());
    }

    /**
     * Test for \TNW\Salesforce\Model\Config::getSalesforcePassword
     * @param $configValue
     * @param $expectedResult
     *
     * @dataProvider testIntDataProvider
     */
    public function testGetSalesforcePassword($configValue, $expectedResult)
    {
        $this->configMock->expects(
            $this->once()
        )->method(
            'getValue'
        )->will(
            $this->returnValue($configValue)
        );
        $this->encryptorMock->expects(
            $this->any()
        )->method(
            'decrypt'
        )->with(
            $configValue
        )->will(
            $this->returnValue($configValue)
        );
        $this->assertSame($expectedResult, $this->model->getSalesforcePassword());
    }

    /**
     * Test for \TNW\Salesforce\Model\Config::getSalesforceToken
     * @param $configValue
     * @param $expectedResult
     *
     * @dataProvider testIntDataProvider
     */
    public function testGetSalesforceToken($configValue, $expectedResult)
    {
        $this->configMock->expects(
            $this->once()
        )->method(
            'getValue'
        )->will(
            $this->returnValue($configValue)
        );
        $this->encryptorMock->expects(
            $this->any()
        )->method(
            'decrypt'
        )->with(
            $configValue
        )->will(
            $this->returnValue($configValue)
        );
        $this->assertSame($expectedResult, $this->model->getSalesforceToken());
    }

    /**
     * Test for \TNW\Salesforce\Model\Config::getLogStatus
     * @param $configValue
     * @param $expectedResult
     *
     * @dataProvider testIntDataProvider
     */
    public function testGetLogStatus($configValue, $expectedResult)
    {
        $this->configMock->expects($this->once())->method('getValue')->will($this->returnValue($configValue));
        $this->assertSame($expectedResult, $this->model->getLogStatus());
    }

    /**
     * Test for \TNW\Salesforce\Model\Config::getCustomerStatus
     * @param $configValue
     * @param $expectedResult
     *
     * @dataProvider testIntDataProvider
     */
    public function testGetCustomerStatus($configValue, $expectedResult)
    {
        $this->configMock->expects($this->once())->method('getValue')->will($this->returnValue($configValue));
        $this->assertSame($expectedResult, $this->model->getCustomerStatus());
    }

    /**
     * Test for \TNW\Salesforce\Model\Config::canRenameAccount
     * @param $configValue
     * @param $expectedResult
     *
     * @dataProvider testIntDataProvider
     */
    public function testCanRenameAccount($configValue, $expectedResult)
    {
        $this->configMock->expects($this->once())->method('getValue')->will($this->returnValue($configValue));
        $this->assertSame($expectedResult, $this->model->canRenameAccount());
    }

    /**
     * @return array
     */
    public function testIntDataProvider()
    {
        return [
            'Config set to 1'      => ['1', '1'],
            'Config set to 0'      => ['0', '0'],
            'Config value not set' => [null, null]
        ];
    }

    /**
     * @return array
     */
    public function testGetSalesforceUsernameDataProvider()
    {
        return [
            'Config set to username' => ['username', 'username'],
            'Config value not set'   => [null, null]
        ];
    }

    /**
     * @return array
     */
    public function testGetLogDirDataProvider()
    {
        return [[DirectoryList::LOG . DIRECTORY_SEPARATOR . 'sforce.log']];
    }

    /**
     * @return array
     */
    public function testGetSalesforceWsdlDataProvider()
    {
        return [['wsdl.xml', DirectoryList::ROOT . DIRECTORY_SEPARATOR . 'wsdl.xml']];
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $this->configMock        = null;
        $this->model             = null;
        $this->encryptorMock     = null;
        $this->directoryListMock = null;
    }
}

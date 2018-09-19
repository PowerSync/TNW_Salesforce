<?php
namespace TNW\Salesforce\Test\Unit\Block\Wizard\Step;

/**
 * Class TestConnectionTest
 * @package TNW\Salesforce\Test\Unit\Block\Wizard\Step
 */
class TestConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Block\Wizard\Step\TestConnection
     */
    protected $block;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface mock
     */
    protected $configMock;

    /**
     * Test connection URL
     * @var string
     */
    protected $testConnectionUrl = 'test_connection_url';

    /**
     * Test setup
     */
    protected function setUp()
    {
        $this->configMock = $this->getMock('Magento\Framework\App\Config\ScopeConfigInterface');

        $objectHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        /** @var $urlBuilderMock \Magento\Framework\UrlInterface */
        $urlBuilderMock = $this->getMockForAbstractClass('Magento\Framework\UrlInterface', [], '', false);

        $urlBuilderMock->expects(
            $this->any()
        )->method(
            'getUrl'
        )->with(
            $this->stringContains('tnw_salesforce/wizard/testconnection'),
            array()
        )->will(
            $this->returnValue($this->testConnectionUrl)
        );

        /** @var $context \Magento\Backend\Block\Template\Context */
        $context = $objectHelper->getObject(
            'Magento\Backend\Block\Template\Context',
            [
                'scopeConfig' => $this->configMock,
                'urlBuilder'  => $urlBuilderMock
            ]
        );

        $this->block = new \TNW\Salesforce\Block\Wizard\Step\TestConnection($context, []);
    }

    /**
     * Test for \TNW\Salesforce\Block\Wizard\Step\TestConnection::getTestConnectionUrl
     */
    public function testGetWebsiteSyncUrl()
    {
        $this->assertSame($this->testConnectionUrl, $this->block->getTestConnectionUrl());
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $this->block      = null;
        $this->configMock = null;
    }
}

<?php
namespace TNW\Salesforce\Test\Unit\Block\Wizard\Step;

/**
 * Class TestTest
 * @package TNW\Salesforce\Test\Unit\Block\Wizard\Step
 */
class TestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Block\Wizard\Step\Test
     */
    protected $block;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface mock
     */
    protected $configMock;

    /**
     * @var \TNW\Salesforce\Model\TestCollection mock
     */
    protected $testCollectionModelMock;

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
            $this->stringContains('tnw_salesforce/wizard/tests'),
            array()
        )->will(
            $this->returnValue('test_validation_url')
        );

        /** @var $context \Magento\Backend\Block\Template\Context */
        $context = $objectHelper->getObject(
            'Magento\Backend\Block\Template\Context',
            [
                'scopeConfig' => $this->configMock,
                'urlBuilder'  => $urlBuilderMock
            ]
        );

        $this->testCollectionModelMock = $this->getMock(
            'TNW\Salesforce\Model\TestCollection',
            [],
            [],
            '',
            false
        );

        $this->block = new \TNW\Salesforce\Block\Wizard\Step\Test($context, $this->testCollectionModelMock, []);
    }

    /**
     * Test for \TNW\Salesforce\Block\Wizard\Step\test::getTests
     * @param $expectedValue
     *
     * @dataProvider getTestsDataProvider
     */
    public function testGetTests($expectedValue)
    {
        $this->testCollectionModelMock->expects(
            $this->any()
        )->method(
            'getSalesforceDependencies'
        )->will(
            $this->returnValue($expectedValue)
        );
        $this->assertEquals($expectedValue, $this->block->getTests());
    }

    /**
     * Test for \TNW\Salesforce\Block\Wizard\Step\test::getValidateTestsUrl
     */
    public function testGetValidateTestsUrl()
    {
        $this->assertSame('test_validation_url', $this->block->getValidateTestsUrl());
    }

    /**
     * DataProvider for testGetTests
     * @return array
     */
    public function getTestsDataProvider()
    {
        return [[
            'id' => 'Connection', 'status' => 'failed', 'label' => 'label1'
        ]];
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $this->testCollectionModelMock = null;
        $this->block                   = null;
        $this->configMock              = null;
    }
}

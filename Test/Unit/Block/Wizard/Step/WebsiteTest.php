<?php
namespace TNW\Salesforce\Test\Unit\Block\Wizard\Step;

/**
 * Class WebsiteTest
 * @package TNW\Salesforce\Test\Unit\Block\Wizard\Step
 */
class WebsiteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Block\Wizard\Step\Website
     */
    protected $block;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface mock
     */
    protected $configMock;

    /**
     * Data mock for websites option array
     * @var array
     */
    protected $websiteOptionArray = array(
         array(
             'value' => 1,
             'label' => 'label1'
         ),
         array(
             'value' => 2,
             'label' => 'label2'
         )
     );

    /**
     * Data mock for sync website URL
     * @var string
     */
    protected $syncWebsiteUrl = 'website_sync_url';

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
            $this->stringContains('tnw_salesforce/wizard/websites'),
            array()
        )->will(
            $this->returnValue($this->syncWebsiteUrl)
        );

        /** @var $context \Magento\Backend\Block\Template\Context */
        $context = $objectHelper->getObject(
            'Magento\Backend\Block\Template\Context',
            [
                'scopeConfig' => $this->configMock,
                'urlBuilder'  => $urlBuilderMock
            ]
        );

        /** @var $websiteFactoryModelMock \Magento\Store\Model\WebsiteFactory */
        $websiteFactoryModelMock = $this->getMock(
            'Magento\Store\Model\WebsiteFactory',
            [],
            [],
            '',
            false
        );

        /** @var $websiteModelMock \Magento\Store\Model\Website */
        $websiteModelMock = $this->getMock(
            'Magento\Store\Model\Website',
            [],
            [],
            '',
            false
        );

        /** @var $websiteCollectionModelMock \Magento\Framework\Data\Collection */
        $websiteCollectionModelMock = $this->getMock(
            'Magento\Framework\Data\Collection',
            [],
            [],
            '',
            false
        );
        $websiteCollectionModelMock->expects(
            $this->any()
        )->method(
            'toOptionArray'
        )->will(
            $this->returnValue($this->websiteOptionArray)
        );

        $websiteModelMock->expects(
            $this->any()
        )->method(
            'getCollection'
        )->will(
            $this->returnValue($websiteCollectionModelMock)
        );

        $websiteFactoryModelMock->expects(
            $this->any()
        )->method(
            'create'
        )->will(
            $this->returnValue($websiteModelMock)
        );

        $this->block = new \TNW\Salesforce\Block\Wizard\Step\Website($context, $websiteFactoryModelMock, []);
    }

    /**
     * Test for \TNW\Salesforce\Block\Wizard\Step\Website::getWebsites
     */
    public function testGetWebsites()
    {
        $this->assertSame($this->websiteOptionArray, $this->block->getWebsites());
    }

    /**
     * Test for \TNW\Salesforce\Block\Wizard\Step\Website::getWebsiteSyncUrl
     */
    public function testGetWebsiteSyncUrl()
    {
        $this->assertSame($this->syncWebsiteUrl, $this->block->getWebsiteSyncUrl());
    }
}

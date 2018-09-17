<?php
namespace TNW\Salesforce\Test\Unit\Block\Wizard\Step;

/**
 * Class CustomerTest
 * @package TNW\Salesforce\Test\Unit\Block\Wizard\Step
 */
class CustomerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Block\Wizard\Step\Customer
     */
    protected $block;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface mock
     */
    protected $configMock;

    /**
     * Data mock for customer groups
     * @var array
     */
    protected $customerGroups = array(
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
     * Test setup
     */
    protected function setUp()
    {
        $this->configMock = $this->getMock('Magento\Framework\App\Config\ScopeConfigInterface');

        $objectHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        /** @var $context \Magento\Backend\Block\Template\Context */
        $context = $objectHelper->getObject(
            'Magento\Backend\Block\Template\Context',
            ['scopeConfig' => $this->configMock]
        );

        /** @var  $customerGroupsMock \Magento\Customer\Model\Config\Source\Group\Multiselect */
        $customerGroupsMock = $this->getMock(
            '\Magento\Customer\Model\Config\Source\Group\Multiselect',
            [],
            [],
            '',
            false
        );
        $customerGroupsMock->expects(
            $this->any()
        )->method(
            'toOptionArray'
        )->will(
            $this->returnValue($this->customerGroups)
        );

        $this->block = new \TNW\Salesforce\Block\Wizard\Step\Customer($context, $customerGroupsMock, []);
    }

    /**
     * Test for \TNW\Salesforce\Block\Wizard\Step\Customer::getCustomerGroups
     */
    public function testGetCustomerGroups()
    {
        $this->assertSame($this->customerGroups, $this->block->getCustomerGroups());
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $this->configMock = null;
        $this->block      = null;
    }
}

<?php
namespace TNW\Salesforce\Test\Unit\Block\Wizard\Step;

/**
 * Class AccountTest
 * @package TNW\Salesforce\Test\Unit\Block\Wizard\Step
 */
class AccountTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Block\Wizard\Step\Account
     */
    protected $block;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface mock
     */
    protected $configMock;

    /**
     * @var \TNW\Salesforce\Model\Config\Source\Customer\Owner mock
     */
    protected $accountModelMock;

    /**
     * Data mock for owners
     * @var array
     */
    protected $owners = array(
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

        $this->accountModelMock = $this->getMock(
            'TNW\Salesforce\Model\Config\Source\Customer\Owner',
            [],
            [],
            '',
            false
        );
        $this->accountModelMock->expects(
            $this->any()
        )->method(
            'getOwners'
        )->will(
            $this->returnValue($this->owners)
        );

        $this->block = new \TNW\Salesforce\Block\Wizard\Step\Account($context, $this->accountModelMock, []);
    }

    /**
     * Test for \TNW\Salesforce\Block\Wizard\Step\Account::getOwners
     */
    public function testGetOwners()
    {
        $this->assertSame($this->owners, $this->block->getOwners());
    }
}

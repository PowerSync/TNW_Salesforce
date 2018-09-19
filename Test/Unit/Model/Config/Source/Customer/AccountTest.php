<?php
namespace TNW\Salesforce\Test\Unit\Model\Config\Source\Customer;

/**
 * Class AccountTest
 * @package TNW\Salesforce\Test\Unit\Model\Config\Source\Customer
 */
class AccountTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Model\Config\Source\Customer\AccountName
     */
    protected $model;

    /**
     * Test setup
     */
    protected function setUp()
    {
        $this->model = new \TNW\Salesforce\Model\Config\Source\Customer\AccountName();
    }

    /**
     * Test for \TNW\Salesforce\Model\Config\Source\Customer\AccountName::toOptionArray
     * @param $expectedResult
     *
     * @dataProvider toOptionArrayDataProvider
     */
    public function testToOptionArray($expectedResult)
    {
        $expectedResult = $this->proceedParsingObjectsData($expectedResult);
        $actualResult = $this->proceedParsingObjectsData($this->model->toOptionArray());
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * DataProvider for testToOptionArray
     * @return array
     */
    public function toOptionArrayDataProvider()
    {
        return [
            'Options array' => [
                [
                    ['value' => 0, 'label' => __('Don\'t modify Account name if exists')],
                    ['value' => 1, 'label' => __('Overwrite Account name from Magento')]
                ]
            ]
        ];
    }

    /**
     * Test tear down
     */
    public function tearDown()
     {
         $this->model = null;
     }

    /**
     * Sets text from parsing object in option array
     * @param $options
     * @return array
     */
    private function proceedParsingObjectsData($options)
    {
        $result = array();
        foreach ($options as $option) {
            $result[] = ['value' => $option['value'], 'label' => $option['label']->getText()];
        }
        return $result;
    }
}

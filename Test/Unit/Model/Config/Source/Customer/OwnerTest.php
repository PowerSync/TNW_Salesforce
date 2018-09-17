<?php
namespace TNW\Salesforce\Test\Unit\Model\Config\Source\Customer;

/**
 * Class OwnerTest
 * @package TNW\Salesforce\Test\Unit\Block\Wizard\Step
 */
class OwnerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Model\Config\Source\Customer\Owner
     */
    protected $model;

    /**
     * @var \TNW\Salesforce\Client\Customer mock
     */
    protected $clientMock;

    /**
     * Test setup
     */
    protected function setUp()
    {
        $this->clientMock = $this->getMock(
            'TNW\Salesforce\Client\Customer',
            [],
            [],
            '',
            false
        );

        $this->model = new \TNW\Salesforce\Model\Config\Source\Customer\Owner($this->clientMock, []);
    }

    /**
     * Test for \TNW\Salesforce\Model\Config\Source\Customer\Owner::toOptionArray
     * @param $ownersData
     * @param $expectedResult
     *
     * @dataProvider toOptionArrayDataProvider
     */
    public function testToOptionArray($ownersData, $expectedResult)
    {
        $this->clientMock->expects(
            $this->any()
        )->method(
            'getOwners'
        )->will(
            array_key_exists('exception', $ownersData)
                ? $this->throwException(new \Exception($ownersData['exception']))
                : $this->returnValue($ownersData['owners'])
        );
        $this->assertSame($expectedResult, $this->model->toOptionArray());
    }

    /**
     * Test for \TNW\Salesforce\Model\Config\Source\Customer\Owner::getOwners
     * @param $ownersData
     * @param $expectedResult
     *
     * @dataProvider getOwnersDataProvider
     */
    public function testGetOwners($ownersData, $expectedResult)
    {
        $this->clientMock->expects(
            $this->any()
        )->method(
            'getOwners'
        )->will(
            array_key_exists('exception', $ownersData)
                ? $this->throwException(new \Exception($ownersData['exception']))
                : $this->returnValue($ownersData['owners'])
        );
        $this->assertSame($expectedResult, $this->model->getOwners());
    }

    /**
     * DataProvider for testToOptionArray
     * @return array
     */
    public function toOptionArrayDataProvider()
    {
        return [
            'Case when no exception' => [
                ['owners' => ['ownerId' => 'ownerLabel']],
                ['ownerId' => 'ownerLabel']
            ],
            'Case with exception'   => [
                ['exception' => 'exception message'],
                ['exception message']
            ],
        ];
    }

    /**
     * DataProvider for testGetOwners
     * @return array
     */
    public function getOwnersDataProvider()
    {
        return [
            'Case when no exception' => [
                ['owners' => ['ownerId' => 'ownerLabel']],
                [['value' => 'ownerId', 'label' => 'ownerLabel']]
            ],
            'Case with exception'   => [
                ['exception' => 'exception message'],
                [['value' => null, 'label' => 'exception message']]
            ],
        ];
    }

    /**
     * Test tear down
     */
    public function tearDown()
     {
         $this->model      = null;
         $this->clientMock = null;
     }
}

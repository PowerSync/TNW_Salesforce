<?php
namespace TNW\Salesforce\Test\Unit\Model;

/**
 * Class TestCollection
 * @package TNW\Salesforce\Model
 */
class TestCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface mock
     */
    protected $objectManagerMock;

    /**
     * @var \TNW\Salesforce\Model\TestCollection
     */
    protected $model;

    /**
     * Test setup
     */
    protected function setUp()
    {
        $this->objectManagerMock = $this->getMockForAbstractClass(
            'Magento\Framework\ObjectManagerInterface',
            [],
            '',
            false
        );
        $this->model = new \TNW\Salesforce\Model\TestCollection($this->objectManagerMock);
    }

    /**
     * Test for \TNW\Salesforce\Model\TestCollection::getSalesforceDependencies
     * @param $testIds
     * @param $expectedValue
     *
     * @dataProvider getSalesforceDependenciesDataProvider
     */
    public function testGetSalesforceDependencies($testIds, $expectedValue)
    {
        $i = 0;
        foreach ($expectedValue as $testData) {
            $mockedTest = $this->getMock('TNW\Salesforce\Model\TestInterface', ['getStatus', 'getLabel', 'execute']);
            $mockedTest->expects(
                $this->any()
            )->method(
                'execute'
            );
            $mockedTest->expects(
                $this->any()
            )->method(
                'getStatus'
            )->will(
                $this->returnValue($testData['status'])
            );
            $mockedTest->expects(
                $this->any()
            )->method(
                'getLabel'
            )->will(
                $this->returnValue($testData['label'])
            );
            $this->objectManagerMock->expects($this->at($i))
                ->method('get')
                ->with('TNW\\Salesforce\\Model\\Test\\' . $testData['id'])
                ->will($this->returnValue($mockedTest));
            $i++;
        }
        $this->assertSame($expectedValue, $this->model->getSalesforceDependencies($testIds));
    }

    /**
     * Data provider for testGetSalesforceDependencies
     * @return array
     */
    public function getSalesforceDependenciesDataProvider()
    {
        $testData = array(
            array('id' => 'Connection', 'status' => 'failed', 'label' => 'label1'),
            array('id' => 'License', 'status' => 'passed', 'label' => 'label2')
        );

        return [
            'Case with all tests' => [array(), $testData],
            'Only specific test'  => [array($testData[0]['id']), array($testData[0])]
        ];
    }
}

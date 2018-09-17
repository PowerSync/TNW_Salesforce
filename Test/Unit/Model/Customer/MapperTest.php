<?php
namespace TNW\Salesforce\Test\Unit\Model\Customer;

/**
 * Class MapperTest
 * @package TNW\Salesforce\Test\Unit\Model\Customer
 */
class MapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Model\Customer\Mapper
     */
    protected $model;

    /**
     * Test setup
     */
    public function setUp()
    {
        $objectHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->model = $objectHelper->getObject(
            'TNW\Salesforce\Model\Customer\Mapper'
        );
    }

    /**
     * Test for TNW\Salesforce\Model\Customer\Mapper::initAccount
     */
    public function testInitAccount()
    {
        $this->model->initAccount();
        $this->assertEquals('Account', $this->readAttribute($this->model, 'map_object'));
    }

    /**
     * Test for TNW\Salesforce\Model\Customer\Mapper::initContact
     */
    public function testInitContact()
    {
        $this->model->initContact();
        $this->assertEquals('Contact', $this->readAttribute($this->model, 'map_object'));
    }

    /**
     * Test tear down
     */
    public function tearDown()
    {
        $this->model = null;
    }
}

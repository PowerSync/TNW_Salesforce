<?php
namespace TNW\Salesforce\Test\Unit\Model;

/**
 * Class MapperTest
 * @package TNW\Salesforce\Test\Unit\Model
 */
class MapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \TNW\Salesforce\Model\Mapper
     */
    protected $model;

    /**
     * Test setup
     */
    public function setUp()
    {
        $objectHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->model = $objectHelper->getObject(
            'TNW\Salesforce\Model\Mapper'
        );
    }

    /**
     * Test for \TNW\Salesforce\Model\Mapper::beforeSave
     */
    public function testBeforeSave()
    {
        $this->model->beforeSave();
        $this->assertEquals($this->readAttribute($this->model, 'map_object'), $this->model->getData('object_type'));
    }

    /**
     * Test for \TNW\Salesforce\Model\Mapper::getIdentities
     */
    public function testGetIdentities()
    {
        $expectedValue = [$this->readAttribute($this->model, 'cache_tag') . '_' . $this->model->getId()];
        $actualValue = $this->model->getIdentities();
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * Test for \TNW\Salesforce\Model\Mapper::getMapId
     */
    public function testGetMapId()
    {
        $expectedValue = 'mapId';
        $this->model->setData('map_id', $expectedValue);
        $actualValue = $this->model->getMapId();

        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * Test for \TNW\Salesforce\Model\Mapper::getMagentoAttributeName
     */
    public function testGetMagentoAttributeName()
    {
        $expectedValue = 'someMagentoAttributeName';
        $this->model->setData('magento_attribute_name', $expectedValue);
        $actualValue = $this->model->getMagentoAttributeName();

        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * Test for \TNW\Salesforce\Model\Mapper::getSalesforceAttributeName
     */
    public function testGetSalesforceAttributeName()
    {
        $expectedValue = 'someSalesforceAttributeName';
        $this->model->setData('salesforce_attribute_name', $expectedValue);
        $actualValue = $this->model->getSalesforceAttributeName();

        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * Test for \TNW\Salesforce\Model\Mapper::getDefaultValue
     */
    public function testGetDefaultValue()
    {
        $expectedValue = 'defaultValue';
        $this->model->setData('default_value', $expectedValue);
        $actualValue = $this->model->getDefaultValue();

        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * Test for \TNW\Salesforce\Model\Mapper::getMagentoEntityType
     */
    public function testGetMagentoEntityType()
    {
        $expectedValue = 'entityType';
        $this->model->setData('magento_entity_type', $expectedValue);
        $actualValue = $this->model->getMagentoEntityType();

        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * Test for \TNW\Salesforce\Model\Mapper::getAttributeId
     */
    public function testGetAttributeId()
    {
        $expectedValue = 'attrId';
        $this->model->setData('attribute_id', $expectedValue);
        $actualValue = $this->model->getAttributeId();

        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * Test for \TNW\Salesforce\Model\Mapper::getAttributeType
     */
    public function getAttributeType()
    {
        $expectedValue = 'attrType';
        $this->model->setData('attribute_type', $expectedValue);
        $actualValue = $this->model->getAttributeType();

        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * Test for \TNW\Salesforce\Model\Mapper::getObjectType
     */
    public function getObjectType()
    {
        $this->assertEquals($this->readAttribute($this->model, 'map_object'), $this->model->getObjectType());
    }

    /**
     * Test Tear Down
     */
    public function tearDown()
    {
        $this->model = null;
    }
}

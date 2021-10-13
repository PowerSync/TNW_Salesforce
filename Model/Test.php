<?php
declare(strict_types=1);

namespace TNW\Salesforce\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Phrase;

/**
 * Class Test
 * @package TNW\Salesforce\Model
 */
abstract class Test extends DataObject implements TestInterface
{
    /**
     * Test Label to display
     * @var string
     */
    protected $testLabel = 'Some Text Label';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * success test execution status
     */
    const STATUS_PASSED = 'passed';

    /**
     * failure test execution status
     */
    const STATUS_FAILED = 'failed';

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): TestInterface
    {
        return $this;
    }

    /**
     * Get test label
     *
     * @return Phrase
     */
    public function getLabel(): Phrase
    {
        return __($this->testLabel);
    }
}

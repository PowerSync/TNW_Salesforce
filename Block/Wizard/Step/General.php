<?php
namespace TNW\Salesforce\Block\Wizard\Step;

/**
 * Class General
 * @package TNW\Salesforce\Block\Wizard\Step
 */
class General extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Backend\Model\Auth\Session|null
     */
    protected $authSession = null;

    /**
     * General constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->authSession = $authSession;
    }

    /**
     * @return mixed|string
     */
    public function getAdminEmail()
    {
        return $this->authSession->getUser()->getEmail();
    }
}

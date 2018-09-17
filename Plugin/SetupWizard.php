<?php
namespace TNW\Salesforce\Plugin;

/**
 * Class SetupWizard
 * @package TNW\Salesforce\Plugin
 */
class SetupWizard
{
    /**
     * Module name for setup wizard check and install purposes
     * @var string
     */
    private $moduleNameForSetupWizard = 'tnw_salesforce';

    /**
     * Collects module name for need to install through setup wizard check
     * @param \TNW\Wizard\Block\WizardInterface $subject
     * @param array $result
     * @return array
     */
    public function beforeIsSetupRequired( \TNW\Wizard\Block\WizardInterface $subject, array $result)
    {
        $result['modules'][$this->moduleNameForSetupWizard] = array(
            'final_step_data' => array(
                'tnwsforce_general' => array(
                    'salesforce' => array(
                        'fields' => array(
                            'active' => 1
                        )
                    )
                ),
                'tnwsforce_customer' => array(
                    'general' => array(
                        'fields' => array(
                            'active' => 1
                        )
                    )
                )
            ),
            'redirect' => $subject->getUrl('adminhtml/system_config/edit', array('section' => 'tnwsforce_general'))
        );
        return $result;
    }
}

<?php
declare(strict_types=1);

namespace TNW\Salesforce\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ActionInterface;

/**
 * Class ValidatorSkip
 * @package TNW\Salesforce\Plugin
 */
class ValidatorSkip
{
    /**
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     */
    public function aroundValidate(
        \Magento\Backend\App\Request\BackendValidator $subject,
        \Closure $proceed,
        RequestInterface $request,
        ActionInterface $action
    ) {
        $current_module = $request->getModuleName();
        if($current_module == "tnw_salesforce"){
            return false;
        }
        $result = $proceed($request, $action);
        return $result;
    }
}

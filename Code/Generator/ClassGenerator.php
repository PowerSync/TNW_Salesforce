<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Code\Generator;

class ClassGenerator extends \Magento\Framework\Code\Generator\ClassGenerator
{
    /**
     * @var array
     */
    protected $_parameterOptions = [
        'name' => 'setName',
        'type' => 'setType',
        'defaultValue' => 'setDefaultValue',
        'passedByReference' => 'setPassedByReference',
        'variadic' => 'setVariadic',
    ];

    /**
     * @param array $methods
     * @return $this
     */
    public function addMethods(array $methods)
    {
        foreach ($methods as $methodOptions) {
            $methodObject = $this->createMethodGenerator();
            $this->_setDataToObject($methodObject, $methodOptions, $this->_methodOptions);

            if (isset(
                    $methodOptions['parameters']
                ) && is_array(
                    $methodOptions['parameters']
                ) && count(
                    $methodOptions['parameters']
                ) > 0
            ) {
                $parametersArray = [];
                foreach ($methodOptions['parameters'] as $parameterOptions) {
                    $parameterObject = new \TNW\Salesforce\Code\Generator\ParameterGenerator();
                    $this->_setDataToObject($parameterObject, $parameterOptions, $this->_parameterOptions);
                    $parametersArray[] = $parameterObject;
                }

                $methodObject->setParameters($parametersArray);
            }

            if (isset($methodOptions['docblock']) && is_array($methodOptions['docblock'])) {
                $docBlockObject = new \Laminas\Code\Generator\DocBlockGenerator();
                $docBlockObject->setWordWrap(false);
                $this->_setDataToObject($docBlockObject, $methodOptions['docblock'], $this->_docBlockOptions);

                $methodObject->setDocBlock($docBlockObject);
            }

            if (!empty($methodOptions['returnType']) && \method_exists($methodObject, 'setReturnType')) {
                $methodObject->setReturnType($methodOptions['returnType']);
            }

            $this->addMethodFromGenerator($methodObject);
        }
        return $this;
    }
}

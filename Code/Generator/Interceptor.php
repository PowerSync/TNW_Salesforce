<?php
declare(strict_types=1);

namespace TNW\Salesforce\Code\Generator;

class Interceptor extends \Magento\Framework\Interception\Code\Generator\Interceptor
{
    public function __construct(
        $sourceClassName = null,
        $resultClassName = null,
        \Magento\Framework\Code\Generator\Io $ioObject = null,
        \Magento\Framework\Code\Generator\CodeGeneratorInterface $classGenerator = null,
        \Magento\Framework\Code\Generator\DefinedClasses $definedClasses = null
    ) {
        if (null == $classGenerator) {
            $classGenerator = new ClassGenerator();
        }

        parent::__construct($sourceClassName, $resultClassName,
            $ioObject, $classGenerator, $definedClasses);
    }

    /**
     * @param array $parameters
     * @return string
     */
    protected function _getParameterList(array $parameters): string
    {
        return implode(
            ', ',
            array_map(
                function ($item) {
                    $output = '';
                    if ($item['variadic']) {
                        $output .= '... ';
                    }

                    $output .="\${$item['name']}";
                    return $output;
                },
                $parameters
            )
        );
    }

    /**
     * @param \ReflectionParameter $parameter
     * @return array
     */
    protected function _getMethodParameterInfo(\ReflectionParameter $parameter): array
    {
        $parameterInfo = parent::_getMethodParameterInfo($parameter);
        $parameterInfo['variadic'] = $parameter->isVariadic();
        return $parameterInfo;
    }
}

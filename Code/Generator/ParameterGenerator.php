<?php
declare(strict_types=1);

namespace TNW\Salesforce\Code\Generator;

class ParameterGenerator extends \Zend\Code\Generator\ParameterGenerator
{
    /**
     * @var bool
     */
    private $variadic = false;

    /**
     * @param $variadic
     * @return $this
     */
    public function setVariadic($variadic): ParameterGenerator
    {
        $this->variadic = (bool) $variadic;
        return $this;
    }

    /**
     * @return bool
     */
    public function getVariadic(): bool
    {
        return $this->variadic;
    }

    /**
     * @return string
     */
    public function generate(): string
    {
        $output = parent::generate();
        if (false === strpos($output, "...") && $this->variadic) {
            $output = str_replace('$', '... $', $output);
        }

        return $output;
    }
}

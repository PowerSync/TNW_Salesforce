<?php
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
    public function setVariadic($variadic)
    {
        $this->variadic = (bool) $variadic;
        return $this;
    }

    /**
     * @return bool
     */
    public function getVariadic()
    {
        return $this->variadic;
    }

    /**
     * @return string
     */
    public function generate()
    {
        $output = parent::generate();
        if (false === strpos($output, "...") && $this->variadic) {
            $output = str_replace('$', '... $', $output);
        }

        return $output;
    }
}
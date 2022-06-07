<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

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
        if ($this->variadic && false === strpos($output, "...")) {
            $output = str_replace('$', '... $', (string)$output);
        }

        return $output;
    }
}

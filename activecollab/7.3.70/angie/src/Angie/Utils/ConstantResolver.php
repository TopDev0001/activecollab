<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Utils;

use RuntimeException;

class ConstantResolver implements ConstantResolverInterface
{
    private array $constants = [];

    /**
     * @param      $constanst_names
     * @param bool $throw_exception_on_missing_const
     */
    public function __construct($constanst_names, $throw_exception_on_missing_const = true)
    {
        foreach ((array) $constanst_names as $constant_name) {
            if (defined($constant_name)) {
                $this->constants[$constant_name] = constant($constant_name);
            } else {
                if ($throw_exception_on_missing_const) {
                    throw new RuntimeException("Constant '{$constant_name}' is not defined.");
                } else {
                    $this->constants[$constant_name] = null;
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->constants;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getValueForConstant($name)
    {
        if (!array_key_exists($name, $this->constants)) {
            throw new RuntimeException("Constant '{$name}' is not defined.");
        }

        return $this->constants[$name];
    }
}

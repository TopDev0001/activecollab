<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Utils;

interface ConstantResolverInterface
{
    /**
     * @return array
     */
    public function getValues();

    /**
     * @param $name
     * @return mixed
     */
    public function getValueForConstant($name);
}

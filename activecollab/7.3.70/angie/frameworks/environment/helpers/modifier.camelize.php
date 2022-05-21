<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Return camelized string.
 *
 * @param  string $string
 * @return string
 */
function smarty_modifier_camelize($string)
{
    return Angie\Inflector::camelize($string);
}

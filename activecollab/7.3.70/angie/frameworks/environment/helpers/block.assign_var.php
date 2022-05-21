<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * assign_var helper implementation.
 *
 * @package angie.frameworks.environment
 * @subpackage helpers
 */

/**
 * Assign generated value to template.
 *
 * Params:
 *
 * - name - Variable name
 *
 * @param  array             $params
 * @param  string            $content
 * @param  Smarty            $smarty
 * @param  bool              $repeat
 * @throws InvalidParamError
 */
function smarty_block_assign_var($params, $content, &$smarty, &$repeat)
{
    if ($repeat) {
        return;
    }

    $name = trim(array_var($params, 'name'));
    if ($name == '') {
        throw new InvalidParamError('name', $name, 'name value is missing', true);
    }

    $smarty->assign($name, $content);
}

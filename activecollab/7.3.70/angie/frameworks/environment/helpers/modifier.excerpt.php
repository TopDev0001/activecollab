<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Excerpt modifier definition.
 */

/**
 * Return excerpt from string.
 *
 * @param  string $string
 * @param  int    $length
 * @param  string $etc
 * @param  bool   $flat
 * @return string
 */
function smarty_modifier_excerpt($string, $length = 100, $etc = '...', $flat = false)
{
    return str_excerpt($string, $length, $etc, $flat);
}

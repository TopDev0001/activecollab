<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Globalization;

/**
 * lang helper implementation.
 *
 * @package angie.frameworks.globalization
 * @subpackage helpers
 */

/**
 * Return lang for a given code text and parameters.
 *
 * Paramteres:
 *
 * - clean_params - boolean - Clean params before they are inserted in string,
 *   true by default
 * - language - Language - Force translation it his language
 *
 * @param  array  $params
 * @param  string $content
 * @param  Smarty $smarty
 * @param  bool   $repeat
 * @return string
 */
function smarty_block_lang($params, $content, &$smarty, &$repeat)
{
    if ($repeat) {
        return false;
    }

    $clean_params = isset($params['clean_params']) ? (bool) $params['clean_params'] : true; // true by default

    $language = null;
    if (isset($params['language'])) {
        $language = $params['language'];
        unset($params['language']);
    }

    return Globalization::lang($content, $params, $clean_params, $language);
}

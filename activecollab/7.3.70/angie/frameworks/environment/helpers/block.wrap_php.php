<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Assign generated value to template.
 *
 * @param  array  $params
 * @param  string $content
 * @param  Smarty $smarty
 * @param  bool   $repeat
 * @return string
 */
function smarty_block_wrap_php($params, $content, &$smarty, &$repeat)
{
    if ($repeat) {
        return null;
    }

    return "<?php\n$content";
}

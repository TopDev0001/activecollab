<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * time modifier implementation.
 *
 * @package angie.library.smarty
 */

/**
 * Return formated time.
 *
 * @param  string               $content
 * @param  int                  $offset
 * @throws InvalidInstanceError
 * @return string
 */
function smarty_modifier_time($content, $offset = null)
{
    if ($content && is_string($content)) {
        $content = DateTimeValue::makeFromString($content); // first try making object from string
    }

    if ($content instanceof DateTimeValue) {
        return $content->formatTimeForUser(AngieApplication::authentication()->getLoggedUser(), $offset);
    } else {
        throw new InvalidInstanceError('content', $content, 'DateTimeValue');
    }
}

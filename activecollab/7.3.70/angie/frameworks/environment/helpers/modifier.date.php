<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Return formated date.
 *
 * @param  string|DateTimeValue|DateValue $content
 * @param  int                            $offset
 * @param  IUser                          $user
 * @param  Language                       $language
 * @return string
 */
function smarty_modifier_date($content, $offset = 0, $user = null, $language = null): string
{
    if ($content && is_string($content)) {
        $content = DateTimeValue::makeFromString($content); // first try making object from string
    }

    if (!$user instanceof IUser) {
        $user = AngieApplication::authentication()->getAuthenticatedUser();
    }

    if ($content instanceof DateTimeValue) {
        return $content->formatDateForUser($user, $offset, $language);
    } elseif ($content instanceof DateValue) {
        return $content->formatForUser($user, $offset, $language);
    } else {
        throw new InvalidInstanceError('content', $content, DateValue::class);
    }
}

<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

interface TaskLabelInterface extends LabelInterface
{
    const LABEL_NEW = 'NEW';
    const LABEL_CONFIRMED = 'CONFIRMED';
    const LABEL_WORKS_FOR_ME = 'WORKS FOR ME';
    const LABEL_DUPLICATE = 'DUPLICATE';
    const LABEL_WONT_FIX = 'WONT FIX';
    const LABEL_ASSIGNED = 'ASSIGNED';
    const LABEL_BLOCKED = 'BLOCKED';
    const LABEL_IN_PROGRESS = 'IN PROGRESS';
    const LABEL_FIXED = 'FIXED';
    const LABEL_REOPENED = 'REOPENED';
    const LABEL_VERIFIED = 'VERIFIED';

    const BUILT_IN_LABELS = [
        self::LABEL_NEW,
        self::LABEL_CONFIRMED,
        self::LABEL_WORKS_FOR_ME,
        self::LABEL_DUPLICATE,
        self::LABEL_WONT_FIX,
        self::LABEL_ASSIGNED,
        self::LABEL_BLOCKED,
        self::LABEL_IN_PROGRESS,
        self::LABEL_FIXED,
        self::LABEL_REOPENED,
        self::LABEL_VERIFIED,
    ];
}

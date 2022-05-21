<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

interface ProjectLabelInterface extends LabelInterface
{
    const LABEL_NEW = 'NEW';
    const LABEL_IN_PROGRESS = 'INPROGRESS';
    const LABEL_CANCELED = 'CANCELED';
    const LABEL_PAUSED = 'PAUSED';

    const BUILT_IN_LABELS = [
        self::LABEL_NEW,
        self::LABEL_IN_PROGRESS,
        self::LABEL_CANCELED,
        self::LABEL_PAUSED,
    ];
}

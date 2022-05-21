<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use Angie\Modules\AngieFramework;

class HistoryFramework extends AngieFramework
{
    const NAME = 'history';

    /**
     * Framework name.
     */
    protected string $name = 'history';

    public function defineClasses()
    {
        AngieApplication::setForAutoload(
            [
                FwModificationLog::class => __DIR__ . '/models/modification_logs/FwModificationLog.class.php',
                FwModificationLogs::class => __DIR__ . '/models/modification_logs/FwModificationLogs.class.php',

                IHistory::class => __DIR__ . '/models/IHistory.class.php',
                IHistoryImplementation::class => __DIR__ . '/models/IHistoryImplementation.class.php',
            ]
        );
    }
}

<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\NamedList;

/**
 * on_reports event handler.
 *
 * @package ActiveCollab.modules.system
 * @subpackage handlers
 */

/**
 * @param  array             $reports
 * @throws InvalidParamError
 */
function system_handle_on_reports(array &$reports)
{
    if (isset($reports['assignments']) && $reports['assignments']['reports'] instanceof NamedList) {
        $reports['assignments']['reports']->add('assignments', new AssignmentFilter());
    } else {
        throw new InvalidParamError('reports', $reports, 'We expect to find assignments section');
    }
}

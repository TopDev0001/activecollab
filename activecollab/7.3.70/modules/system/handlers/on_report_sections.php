<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use Angie\NamedList;

function system_handle_on_report_sections(NamedList &$sections)
{
    $sections->add('assignments', lang('Assignments'));
    $sections->add('finances', lang('Finances'));
    $sections->add('time_and_expenses', lang('Time and Expense Reports'));
}

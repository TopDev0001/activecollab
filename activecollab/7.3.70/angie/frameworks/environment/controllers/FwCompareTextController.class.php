<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;

AngieApplication::useController('auth_not_required', EnvironmentFramework::INJECT_INTO);

/**
 * Framework level text compare controller implementation.
 *
 * @package ActiveCollab.modules.system
 * @subpackage controllers
 */
class FwCompareTextController extends AuthNotRequiredController
{
    /**
     * Compare two string.
     */
    public function compare(Request $request)
    {
        $before = Angie\HTML::toPlainText((string) $request->post('before'));
        $after = Angie\HTML::toPlainText((string) $request->post('after'));

        return [
            'before' => $before,
            'after' => $after,
            'diff' => nl2br((new \cogpowered\FineDiff\Diff(new \cogpowered\FineDiff\Granularity\Character()))->render($before, $after), false),
        ];
    }
}

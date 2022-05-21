<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

use Angie\Http\Request;
use Angie\Http\Response;

/**
 * Framework level dropbox controller.
 *
 * @package angie.frameworks.environment
 * @subpackage controllers
 */
abstract class FwDropboxController extends AuthRequiredController
{
    /**
     * Respond on batch add request.
     *
     * @return int|array
     */
    public function batch_add(Request $request)
    {
        $files = $request->post('files');

        if (is_array($files)) {
            return Integrations::findFirstByType('DropboxIntegration')->onBatchAdd($files);
        }

        return Response::BAD_REQUEST;
    }
}

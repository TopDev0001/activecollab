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
 * Framework level google drive controller.
 *
 * @package angie.frameworks.environment
 * @subpackage controllers
 */
abstract class FwGoogleDriveController extends AuthRequiredController
{
    /**
     * Respond on batch add request.
     *
     * @return int|array
     */
    public function batch_add(Request $request)
    {
        $docs = $request->post('docs');

        if (is_array($docs)) {
            return Integrations::findFirstByType('GoogleDriveIntegration')->onBatchAdd($docs);
        }

        return Response::BAD_REQUEST;
    }
}

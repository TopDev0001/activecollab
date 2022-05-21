<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;

AngieApplication::useController('admin', EnvironmentFramework::INJECT_INTO);

/**
 * Maintenance mode controller implementation.
 *
 * @package activeCollab.modules.system
 * @subpackage controllers
 */
class MaintenanceModeController extends AdminController
{
    /**
     * Display and process maintenance mode form.
     *
     * @return array
     */
    public function show_settings()
    {
        return ConfigOptions::getValue([
            'maintenance_enabled',
            'maintenance_message',
        ]);
    }

    /**
     * Save maintenance mode settings.
     *
     * @return array
     */
    public function save_settings(Request $request)
    {
        $put = $request->put();

        ConfigOptions::setValue([
            'maintenance_enabled' => (bool) $put['maintenance_enabled'],
            'maintenance_message' => trim($put['maintenance_message']),
        ]);

        return ConfigOptions::getValue([
            'maintenance_enabled',
            'maintenance_message',
        ]);
    }
}

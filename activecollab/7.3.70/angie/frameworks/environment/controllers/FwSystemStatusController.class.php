<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

use Angie\Events;
use Angie\Http\Request;
use Angie\Http\Response;

/**
 * Framework level system status controller.
 *
 * @package angie.frameworks.environment
 * @subpackage controllers
 */
abstract class FwSystemStatusController extends AuthRequiredController
{
    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if (!$user->isOwner()) {
            return Response::NOT_FOUND;
        }

        if (AngieApplication::isOnDemand()) {
            return [];
        }

        return null;
    }

    /**
     * @return array
     */
    public function index()
    {
        $status = [];
        Events::trigger('on_system_status', [&$status]);

        return $status;
    }

    /**
     * Check for updates.
     *
     * @return array
     */
    public function check_for_updates()
    {
        AngieApplication::autoUpgrade()->checkForUpdates();

        $status = [];
        Events::trigger('on_system_status', [&$status]);

        return $status;
    }

    /**
     * Start a release download.
     *
     * @return int|array
     */
    public function start_download(Request $request)
    {
        $version_to_download = $request->post('version_to_download');

        if ($version_to_download && AngieApplication::isValidVersionNumber($version_to_download)) {
            if (is_dir(ROOT . "/$version_to_download")) {
                return Response::CONFLICT;
            }

            try {
                AngieApplication::autoUpgrade()->downloadRelease($version_to_download, WORK_PATH . "/{$version_to_download}.phar");
                AngieApplication::autoUpgrade()->unpackPhar(WORK_PATH . "/{$version_to_download}.phar", $version_to_download);

                return [
                    'ok' => true,
                    'download_release_progress' => AngieApplication::autoUpgrade()->getDownloadProgress(),
                ];
            } catch (Exception $e) {
                return [
                    'ok' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return Response::BAD_REQUEST;
    }

    /**
     * Check download progress.
     *
     * @return array
     */
    public function get_download_progress()
    {
        return ['download_release_progress' => AngieApplication::autoUpgrade()->getDownloadProgress()];
    }

    /**
     * Check environment prior to upgrade.
     *
     * @return array
     */
    public function check_environment()
    {
        $log = [];

        AngieApplication::autoUpgrade()->includeLatestUpgradeClasses();

        $all_ok = AngieApplication::autoUpgrade()->checkEnvironment(function ($message) use (&$log) {
            $log[] = ['ok' => true, 'message' => $message];
        }, function ($message) use (&$log) {
            $log[] = ['ok' => false, 'message' => $message];
        });

        return ['ok' => $all_ok, 'log' => $log];
    }
}

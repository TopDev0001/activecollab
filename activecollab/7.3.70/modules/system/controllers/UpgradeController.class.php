<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

use Angie\Http\Request;
use Angie\Http\Response;

class UpgradeController extends AuthRequiredController
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

    public function index()
    {
        return [
            'downloaded_version' => AngieApplication::autoUpgrade()->getLatestDownloadedVersion(),
        ];
    }

    public function finish(Request $request)
    {
        $version = $request->put('version');
        $release_notes = $request->post('release_notes') ?: $request->put('release_notes');

        if (empty($version)) {
            return Response::BAD_REQUEST;
        }

        $all_ok = true;
        $log = [];

        $can_migrate = AngieApplication::autoUpgrade()->canMigrate(
            $version,
            function (AngieModelMigration $migration, $reason) use (&$all_ok, &$log) {
                $all_ok = false;
                $log[] = [
                    'ok' => false,
                    'message' => sprintf(
                        "Migration '%s' can't be executed. Reason: %s",
                        get_class($migration),
                        $reason
                    ),
                ];
            }
        );

        if ($can_migrate) {
            // backup database
            AngieApplication::autoUpgrade()->backupDatabase(
                WORK_PATH,
                function ($file) use (&$log) {
                    $log[] = [
                        'ok' => true,
                        'message' => "Creating a database backup to '$file'",
                    ];
                }, function ($file) use (&$log) {
                $log[] = [
                    'ok' => true,
                    'message' => "Database backed up to '$file'",
                ];
            }
            );

            // run migrations
            AngieApplication::autoUpgrade()->runMigrations(
                $version,
                function ($message) use (&$log) {
                    $log[] = [
                        'ok' => true,
                        'message' => $message,
                    ];
                }, function () use (&$log) {
                $log[] = [
                    'ok' => true,
                    'message' => 'Migrations executed',
                ];
            }
            );

            AngieApplication::autoUpgrade()->copyAssetsToPublicDirectory(
                $version,
                null,
                null,
                function () use (&$log) {
                    $log[] = [
                        'ok' => true,
                        'message' => 'New assets copied',
                    ];
                }
            );

            AngieApplication::autoUpgrade()->updateVersionFile(
                $version,
                function () use (&$log) {
                    $log[] = [
                        'ok' => true,
                        'message' => "Updated '/config/version.php' file",
                    ];
                }
            );

            AngieApplication::autoUpgrade()->setAppliedUpgrade($version, $release_notes);

            $system_notifications = SystemNotifications::find(
                [
                    'conditions' => [
                        '`type` = ? AND `is_dismissed` = ?',
                        UpgradeAvailableSystemNotifications::getType(),
                        false,
                    ],
                ]
            );

            if ($system_notifications) {
                foreach ($system_notifications as $system_notification) {
                    $system_notification->dismiss();
                }
            }
        }

        return [
            'ok' => $all_ok,
            'log' => $log,
        ];
    }

    public function release_notes()
    {
        return AngieApplication::autoUpgrade()->getAppliedUpgrades();
    }
}

<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\ActiveCollabJobs\Jobs\Shepherd\UpdateInstanceSettings;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Framework level configuration options controller.
 *
 * @package angie.frameworks.environment
 * @subpackage controllers
 */
abstract class FwConfigOptionsController extends AuthRequiredController
{
    /**
     * List values for the given options.
     *
     * @return int|mixed
     */
    public function get(Request $request, User $user)
    {
        $names = $request->get('names');

        if ($names) {
            $names = explode(',', $names);

            foreach ($names as $k => $name) {
                if (!ConfigOptions::canAccess($name, $user)) {
                    unset($names[$k]);
                }
            }

            return ConfigOptions::getValue($names);
        }

        return Response::BAD_REQUEST;
    }

    /**
     * Set values.
     *
     * @return int|mixed
     */
    public function set(Request $request, User $user)
    {
        $put = $request->put();

        if ($put && is_foreachable($put)) {
            $to_update = [];

            foreach ($put as $option_name => $value) {
                if (ConfigOptions::canUpdate($option_name, $user)) {
                    $to_update[$option_name] = $value;
                }
            }

            if (count($to_update)) {
                ConfigOptions::setValue($to_update);

                if (AngieApplication::isOnDemand()) {
                    AngieApplication::jobs()->dispatch(
                        new UpdateInstanceSettings(
                            [
                                'instance_id' => AngieApplication::getAccountId(),
                            ]
                        )
                    );
                }
            }

            return ConfigOptions::getValue(array_keys($to_update));
        }

        return Response::BAD_REQUEST;
    }

    /**
     * Return personalized values.
     *
     * @return int|array
     */
    public function personalized_get(Request $request, User $user)
    {
        $names = $request->get('names');

        if ($names) {
            $names = explode(',', $names);

            foreach ($names as $k => $name) {
                if (!ConfigOptions::canAccess($name, $user)) {
                    unset($names[$k]);
                }
            }

            return ConfigOptions::getValueFor($names, $user, !AngieApplication::isOnDemand());
        }

        return Response::BAD_REQUEST;
    }

    /**
     * Set personalized values.
     *
     * @return int|mixed
     * @throws Exception
     */
    public function personalized_set(Request $request, User $user)
    {
        $put = $request->put();

        if ($put && is_foreachable($put)) {
            $to_update = [];

            foreach ($put as $option_name => $value) {
                if (ConfigOptions::canUpdate($option_name, $user)) {
                    $to_update[$option_name] = $value;
                }
            }

            if (count($to_update)) {
                ConfigOptions::setValueFor($to_update, $user);

                $user->touch();
            }

            return ConfigOptions::getValueFor(array_keys($to_update), $user);
        }

        return Response::BAD_REQUEST;
    }
}

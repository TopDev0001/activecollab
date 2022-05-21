<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Events;
use Angie\Globalization;

/**
 * Initial user settings collection.
 *
 * @package angie.frameworks.environment
 * @subpackage models
 */
abstract class FwInitialUserSettingsCollection extends AbstractInitialSettingsCollection
{
    /**
     * Cached collection tag.
     *
     * @var string
     */
    private $tag = false;

    /**
     * Return collection etag.
     *
     * @param  bool   $use_cache
     * @return string
     */
    public function getTag(IUser $user, $use_cache = true)
    {
        if ($this->tag === false) {
            $timestamp_hashes = [sha1($this->getWhosAsking()->getUpdatedOn()->toMySQL())];

            foreach ($this->getCollections() as $collection) {
                $timestamp_hashes[] = $collection->getTimestampHash($collection->getTimestampField());
            }

            $this->tag = $this->prepareTagFromBits($user->getEmail(), sha1(implode('-', $timestamp_hashes)));
        }

        return $this->tag;
    }

    /**
     * @return array
     */
    public function execute()
    {
        $user = $this->getWhosAsking();

        if ($user instanceof User) {
            $result = array_merge(
                [
                    'instance_id' => AngieApplication::getAccountId(),
                    'logged_user_id' => $user->getId(),
                    'authenticated_with' => null,
                    'feed_token' => $user->getFeedToken(),
                    'new_features_count' => AngieApplication::newFeatures()->countUnseen($user),
                ],
                parent::execute(),
                [
                    'favorites' => Favorites::findFavoriteObjectsList($user),
                ]
            );

            if (AngieApplication::authentication()->getAuthenticatedWith()) {
                $result['authenticated_with'] = get_class(AngieApplication::authentication()->getAuthenticatedWith());
            }

            $result['favorites'] = $result['favorites'] instanceof DBResult ? $result['favorites']->toArray() : [];
            if ($user->isOwner()) {
                $completed_projects = Projects::count(['is_trashed = ? AND completed_on IS NOT NULL', false]);
            } else {
                $completed_projects = (int) DB::executeFirstCell('SELECT count(p.id) FROM projects p LEFT JOIN project_users u ON p.id = u.project_id WHERE  p.completed_on IS NOT NULL AND  p.is_trashed = ? AND u.user_id = ?', false, $user->getId());
            }
            $result['archived_projects_total'] = $completed_projects;
        } else {
            $result = [
                'logged_user_id' => 0,
                'settings' => [
                    'theme' => ConfigOptions::getValue('theme'),
                    'format_date' => ConfigOptions::getValue('format_date'),
                    'format_time' => ConfigOptions::getValue('format_time'),
                    'login_policy' => AngieApplication::authentication()->getLoginPolicy(),
                    'password_policy' => AngieApplication::authentication()->getPasswordPolicy(),
                ],
                'languages' => Languages::prepareCollection(DataManager::ALL, $this->getWhosAsking()),
            ];

            if (AngieApplication::isOnDemand()) {
                $result['settings']['on_demand'] = AngieApplication::isOnDemand();
            }
        }

        // we need panoramix for both protected and public pages
        $result['settings']['panoramix'] = AngieApplication::getContainer()->get('panoramix');

        return $result;
    }

    /**
     * Return number of records that match conditions set by the collection.
     *
     * @return int
     */
    public function count()
    {
        if ($this->getWhosAsking() instanceof User) {
            return parent::count();
        } else {
            return count($this->getSettings());
        }
    }

    /**
     * @var array
     */
    private $settings = false;

    /**
     * @return array
     */
    protected function getSettings()
    {
        if ($this->settings === false) {
            $user = $this->getWhosAsking();

            $this->settings = [
                'login_policy' => AngieApplication::authentication()->getLoginPolicy(),
                'password_policy' => AngieApplication::authentication()->getPasswordPolicy(),
                'homepage' => $user
                    ? ConfigOptions::getValueFor('homepage', $user)
                    : ConfigOptions::getValue('homepage'),
                'theme' => $user
                    ? ConfigOptions::getValueFor('theme', $user)
                    : ConfigOptions::getValue('theme'),
                'sidebar_collapsed' => $user
                    ? ConfigOptions::getValueFor('sidebar_collapsed', $user)
                    : ConfigOptions::getValue('sidebar_collapsed'),
                'format_date' => $user ? $user->getDateFormat() : ConfigOptions::getValue('format_date'),
                'format_time' => $user ? $user->getTimeFormat() : ConfigOptions::getValue('format_time'),
                'time_first_week_day' => $user
                    ? ConfigOptions::getValueFor('time_first_week_day', $user)
                    : ConfigOptions::getValue('time_first_week_day'),
                'timezone' => $user
                    ? ConfigOptions::getValueFor('time_timezone', $user)
                    : ConfigOptions::getValue('time_timezone'),
                'timezone_autodetect' => $user
                    ? ConfigOptions::getValueFor('time_timezone_autodetect', $user)
                    : ConfigOptions::getValue('time_timezone_autodetect'),
                'default_job_type_id' => $user && ($default_job_type_id = ConfigOptions::getValueFor('default_job_type_id', $user, false))
                    ? $default_job_type_id
                    : JobTypes::getDefaultId(),
                'gmt_offset' => $user
                    ? Globalization::getUserGmtOffset($user)
                    : Globalization::getGmtOffset(),
                'should_update_policy' => !$user->isPrivacyVersionUpdated(),
                'show_theme_modal' => $user ? ConfigOptions::getValueFor('show_theme_modal', $user)
                : ConfigOptions::getValue('show_theme_modal'),
                'workload_page_visited' => $user ? ConfigOptions::getValueFor('workload_page_visited', $user)
                : ConfigOptions::getValue('workload_page_visited'),
                'stopwatch_indicator_seen' => $user ? ConfigOptions::getValueFor('stopwatch_indicator_seen', $user)
                : ConfigOptions::getValue('stopwatch_indicator_seen'),
                'black_friday_got_it' => true,
            ];

            if (empty($this->settings['theme'])) {
                $this->settings['theme'] = 'indigo';
            }

            $this->onLoadSettings($this->settings, $user);

            Events::trigger(
                'on_initial_user_settings',
                [
                    &$this->settings,
                    $user,
                ]
            );
        }

        return $this->settings;
    }

    /**
     * @var array
     */
    private $collections = false;

    /**
     * @return ModelCollection[]
     */
    protected function &getCollections()
    {
        if ($this->collections === false) {
            $user = $this->getWhosAsking();

            $this->collections = [];

            $this->onLoadCollections($this->collections, $user);

            Events::trigger('on_initial_user_collections', [&$this->collections, $user]);
        }

        return $this->collections;
    }
}

<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Resources;

use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;
use ActiveCollab\Authentication\Session\SessionInterface;
use ActiveCollab\Authentication\Token\TokenInterface;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextImplementation;
use ActiveCollab\Foundation\Urls\Router\Context\RoutingContextInterface;
use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;
use ActiveCollab\Module\System\Model\Conversation\GeneralConversation;
use ActiveCollab\Module\System\Model\FeaturePointer\FeaturePointerInterface;
use ActiveCollab\Module\System\Model\Message\MessageInterface;
use ActiveCollab\Module\System\SystemModule;
use ActiveCollabModuleModel;
use AngieApplicationModel;
use DateTimeValue;
use DB;
use DBAdditionalPropertiesColumn;
use DBBodyColumn;
use DBBoolColumn;
use DBColumn;
use DBCreatedOnByColumn;
use DBCreatedOnColumn;
use DBDateTimeColumn;
use DBFkColumn;
use DBIdColumn;
use DBIndex;
use DBIndexPrimary;
use DBIntegerColumn;
use DBNameColumn;
use DBStringColumn;
use DBTrashColumn;
use DBTypeColumn;
use DBUpdatedOnColumn;
use IBudgetThresholds;
use IBudgetThresholdsImplementation;
use IHourlyRates;
use IHourlyRatesImplementation;
use IInvoiceBasedOn;
use IInvoiceBasedOnTrackingFilterResultImplementation;
use IReactions;
use IReactionsImplementation;
use IResetInitialSettingsTimestamp;
use ITracking;
use ITrackingImplementation;
use IUpdatedOn;
use IUpdatedOnImplementation;
use IWhoCanSeeThis;
use IWhoCanSeeThisImplementation;
use Owner;

require_once APPLICATION_PATH . '/resources/ActiveCollabModuleModel.class.php';

class SystemModuleModel extends ActiveCollabModuleModel
{
    public function __construct(SystemModule $parent)
    {
        parent::__construct($parent);

        $this
            ->addModelFromFile('companies')
            ->implementMembers()
            ->implementSearch()
            ->implementHistory()
            ->implementTrash()
            ->implementArchive()
            ->implementActivityLog()
            ->addModelTrait(IHourlyRates::class, IHourlyRatesImplementation::class)
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);

        $this
            ->addModelFromFile('users')
            ->setObjectIsAbstract(true)
            ->setTypeFromField('type')
            ->setOrderBy('order_by')
            ->implementArchive()
            ->implementTrash()
            ->implementHistory()
            ->implementActivityLog()
            ->implementSearch()
            ->addModelTrait(AuthenticatedUserInterface::class)
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);

        $this
            ->addModelFromFile('api_subscriptions')
            ->addModelTrait(TokenInterface::class)
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);

        $this->addTableFromFile('security_logs');
        $this->addTableFromFile('user_addresses');
        $this->addModelFromFile('user_invitations');
        $this
            ->addModelFromFile('user_sessions')
            ->addModelTrait(SessionInterface::class);

        $this
            ->addModelFromFile('comments')
            ->setOrderBy('created_on DESC, id DESC')
            ->implementAttachments()
            ->implementHistory()
            ->implementActivityLog()
            ->implementTrash()
            ->addModelTrait(IWhoCanSeeThis::class, IWhoCanSeeThisImplementation::class)
            ->addModelTrait(IReactions::class, IReactionsImplementation::class);

        $this
            ->addModelFromFile('conversations')
            ->setTypeFromField('type')
            ->setObjectIsAbstract(true)
            ->addModelTrait(ConversationInterface::class)
            ->implementMembers(true)
            ->addModelTrait(IWhoCanSeeThis::class, IWhoCanSeeThisImplementation::class);
        $this
            ->addModelFromFile('conversation_users')
            ->addModelTrait(IWhoCanSeeThis::class, IWhoCanSeeThisImplementation::class);

        $this
            ->addModelFromFile('shortcuts')
            ->setOrderBy('position ASC');

        $this
            ->addModelFromFile('messages')
            ->setTypeFromField('type')
            ->setObjectIsAbstract(true)
            ->setOrderBy('created_on DESC, id DESC')
            ->addModelTrait(MessageInterface::class)
            ->addModelTrait(IWhoCanSeeThis::class, IWhoCanSeeThisImplementation::class);

        $this
            ->addModelFromFile('activity_logs')
            ->setTypeFromField('type')
            ->setObjectIsAbstract(true)
            ->setOrderBy('created_on DESC, id DESC')
            ->addModelTrait(IWhoCanSeeThis::class, IWhoCanSeeThisImplementation::class);

        $this->addModel(
            DB::createTable('teams')->addColumns(
                [
                    new DBIdColumn(),
                    new DBNameColumn(100, true),
                    new DBCreatedOnByColumn(),
                    new DBUpdatedOnColumn(),
                ],
            ),
        )
            ->setOrderBy('name')
            ->implementMembers(true)
            ->implementActivityLog()
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class)
            ->addModelTrait(IWhoCanSeeThis::class, IWhoCanSeeThisImplementation::class);

        $this->addTable(
            DB::createTable('team_users')->addColumns(
                [
                    DBIntegerColumn::create('team_id', DBColumn::NORMAL, 0),
                    DBIntegerColumn::create('user_id', DBColumn::NORMAL, 0),
                ],
            )->addIndices(
                [
                    new DBIndexPrimary(
                        [
                            'team_id',
                            'user_id',
                        ],
                    ),
                    DBIndex::create('user_id'),
                ],
            ),
        );

        $this
            ->addModelFromFile('categories')
            ->setTypeFromField('type')
            ->setOrderBy('name')
            ->setObjectIsAbstract(true)
            ->addModelTrait(null, IResetInitialSettingsTimestamp::class);

        $this
            ->addModelFromFile('projects')
            ->setOrderBy('ISNULL(completed_on) DESC, name')
            ->implementComplete()
            ->implementMembers(true)
            ->implementCategory()
            ->implementCategoriesContext()
            ->implementLabel()
            ->implementTrash()
            ->implementFavorite()
            ->implementHistory()
            ->implementActivityLog()
            ->implementSearch()
            ->implementAccessLog()
            ->addModelTrait(ITracking::class, ITrackingImplementation::class)
            ->addModelTrait(IBudgetThresholds::class, IBudgetThresholdsImplementation::class)
            ->addModelTrait(
                IInvoiceBasedOn::class,
                IInvoiceBasedOnTrackingFilterResultImplementation::class,
            )
            ->addModelTrait(IHourlyRates::class, IHourlyRatesImplementation::class);

        $this->addTable(
            DB::createTable('project_users')->addColumns(
                [
                    DBIntegerColumn::create('user_id', DBColumn::NORMAL, 0)->setUnsigned(true),
                    DBIntegerColumn::create('project_id', DBColumn::NORMAL, 0)->setUnsigned(true),
                ],
            )->addIndices(
                [
                    new DBIndexPrimary(
                        [
                            'user_id',
                            'project_id',
                        ],
                    ),
                ],
            ),
        );

        $this->addModel(
            DB::createTable('project_templates')->addColumns(
                [
                    new DBIdColumn(),
                    new DBNameColumn(150),
                    new DBCreatedOnByColumn(true, true),
                    new DBUpdatedOnColumn(),
                    new DBTrashColumn(),
                ],
            )->addIndices(
                [
                    DBIndex::create('name'),
                ],
            ),
        )
            ->setOrderBy('name')
            ->implementMembers(true)
            ->implementTrash();

        $this
            ->addModelFromFile('applied_project_templates')
            ->setOrderBy('created_on DESC');

        $this->addTable(
            DB::createTable('project_template_users')->addColumns(
                [
                    DBIntegerColumn::create('user_id', DBColumn::NORMAL, 0)->setUnsigned(true),
                    DBIntegerColumn::create('project_template_id', DBColumn::NORMAL, 0)->setUnsigned(true),
                ],
            )->addIndices(
                [
                    new DBIndexPrimary(['user_id', 'project_template_id']),
                ],
            ),
        );

        $this->addModel(
            DB::createTable('project_template_elements')->addColumns(
                [
                    new DBIdColumn(),
                    new DBTypeColumn('ProjectObjectTemplate'),
                    DBFkColumn::create('template_id', 0, true),
                    new DBNameColumn(),
                    new DBBodyColumn(false),
                    new DBCreatedOnColumn(),
                    new DBUpdatedOnColumn(),
                    new DBAdditionalPropertiesColumn(),
                    DBIntegerColumn::create('position', 10, 0)->setUnsigned(true),
                ],
            ),
        )
            ->setTypeFromField('type')
            ->setObjectIsAbstract(true)
            ->setOrderBy('type, position, id')
            ->implementAttachments()
            ->addModelTrait(RoutingContextInterface::class, RoutingContextImplementation::class);

        $this->addModel(
            DB::createTable('project_template_task_dependencies')->addColumns(
                [
                    new DBIdColumn(),
                    DBFkColumn::create('parent_id', 0, true),
                    DBFkColumn::create('child_id', 0, true),
                    new DBCreatedOnColumn(),
                ],
            )->addIndices(
                [
                    DBindex::create('id', DBIndex::UNIQUE, 'id'),
                    new DBIndexPrimary(['parent_id', 'child_id']),
                    DBIndex::create('child_id'),
                ],
            ),
        );

        $this->addModel(
            DB::createTable('user_workspaces')->addColumns(
                [
                    new DBIdColumn(),
                    DBIntegerColumn::create('user_id', DBColumn::NORMAL, 0)->setUnsigned(true),
                    DBIntegerColumn::create('shepherd_account_id', DBColumn::NORMAL, 0)->setUnsigned(true),
                    DBStringColumn::create('shepherd_account_type', 150),
                    DBStringColumn::create('shepherd_account_url', 150),
                    new DBNameColumn(150),
                    new DBBoolColumn('is_shown_in_launcher', true),
                    new DBBoolColumn('is_owner', true),
                    DBIntegerColumn::create('position', 10, 0)->setUnsigned(true),
                    new DBDateTimeColumn('updated_on'),
                ],
            )->addIndices(
                [
                    DBIndex::create('user_id', DBIndex::KEY),
                ],
            ),
        )
            ->setOrderBy('position, id')
            ->addModelTrait(IUpdatedOn::class, IUpdatedOnImplementation::class);

        // Modify attachments table
        $attachments_table = AngieApplicationModel::getTable('attachments');

        $attachments_table->addColumn(DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true), 'id');
        $attachments_table->addColumn(
            new DBBoolColumn('is_hidden_from_clients'),
            'project_id',
        );

        $attachments_table->addIndex(DBIndex::create('project_id'));

        $this
            ->addModelFromFile('reactions')
            ->setObjectIsAbstract(true)
            ->setTypeFromField('type')
            ->setOrderBy('created_on, id')
            ->addModelTrait(IWhoCanSeeThis::class, IWhoCanSeeThisImplementation::class);

        $this
            ->addModelFromFile('availability_types')
            ->addModelTrait(IWhoCanSeeThis::class, IWhoCanSeeThisImplementation::class);
        $this
            ->addModelFromFile('availability_records')
            ->addModelTrait(IWhoCanSeeThis::class, IWhoCanSeeThisImplementation::class);

        $this
            ->addModelFromFile('feature_pointers')
            ->addModelTrait(FeaturePointerInterface::class)
            ->setObjectIsAbstract(true)
            ->setTypeFromField('type');

        $this->addTableFromFile('feature_pointer_dismissals');
        $this->addModelFromFile('user_devices');
    }

    public function loadInitialData()
    {
        $this->addConfigOption('group_mode_people', 'first_letter');
        $this->addConfigOption('sort_mode_projects', 'activity');
        $this->addConfigOption('sort_mode_project_groups', 'asc');
        $this->addConfigOption('sort_mode_completed_projects', 'completed');
        $this->addConfigOption('filter_projects_client', []);
        $this->addConfigOption('filter_projects_label', []);
        $this->addConfigOption('filter_projects_category', []);
        $this->addConfigOption('filter_projects_leader', []);
        $this->addConfigOption('filter_completed_projects_client', []);
        $this->addConfigOption('filter_completed_projects_label', []);
        $this->addConfigOption('filter_completed_projects_category', []);
        $this->addConfigOption('filter_completed_projects_leader', []);
        $this->addConfigOption('display_mode_projects', 'grid');
        $this->addConfigOption('display_mode_completed_projects', 'grid');
        $this->addConfigOption('display_mode_project_templates', 'grid');
        $this->addConfigOption('group_mode_projects', 'star');

        // Administration options
        $this->addConfigOption('maintenance_enabled', false);
        $this->addConfigOption('authorization_integration');

        $this->addConfigOption('password_policy_min_length', 0);
        $this->addConfigOption('password_policy_require_numbers', false);
        $this->addConfigOption('password_policy_require_mixed_case', false);
        $this->addConfigOption('password_policy_require_symbols', false);

        $this->addConfigOption('firewall_enabled', false);
        $this->addConfigOption('firewall_white_list');
        $this->addConfigOption('firewall_black_list');

        $this->addConfigOption('brute_force_protection_enabled', true);
        $this->addConfigOption('brute_force_cooldown_lenght', 600);
        $this->addConfigOption('brute_force_cooldown_threshold', 5);

        // User properties
        $this->addConfigOption('my_work_tasks_grouped_by');
        $this->addConfigOption('my_work_projects_order');
        $this->addConfigOption('my_work_collapsed_projects');
        $this->addConfigOption('my_work_collapsed_task_lists');
        $this->addConfigOption('my_work_collapsed_tasks_date_groups');
        $this->addConfigOption('my_work_selected_tab', 'tasks');
        $this->addConfigOption('my_work_activity_filter', 'daily');

        // Control Tower
        $this->addConfigOption('update_download_progress', 0);

        // Project synchronization
        $this->addConfigOption('project_sync_locked', false);
        $this->addConfigOption('project_last_synced_on');
        $this->addConfigOption('project_last_sync_locked_until');

        // Subscriptions
        $this->addConfigOption('subscribe_on_mention', true);

        // Updates badge
        $this->addConfigOption('updates_hide_notifications', false);
        $this->addConfigOption('updates_play_sound', false);

        // Default hide from clients
        $this->addConfigOption('default_hide_from_clients', false);

        // Notifications
        $this->addConfigOption('notifications_user_send_morning_paper', true);
        $this->addConfigOption('notifications_user_send_email_new_project_element', true);
        $this->addConfigOption('notifications_user_send_email_assignments', true);
        $this->addConfigOption('notifications_user_send_email_subscriptions', true);
        $this->addConfigOption('notifications_user_send_email_mentions', true);
        $this->addConfigOption('notifications_user_send_email_reminders', true);
        $this->addConfigOption('browser_notifications', false);
        $this->addConfigOption('desktop_notifications', true);

        // chat
        $this->addConfigOption('chat_messages_sound_enabled', true);
        $this->addConfigOption('chat_shortcuts_enabled', true);
        $this->addConfigOption('chat_email_notifications', true);

        // Morning Paper
        $this->addConfigOption('morning_paper_include_all_projects', false);
        $this->addConfigOption('morning_paper_last_activity', 0);

        // Self-upgrade
        $this->addConfigOption('release_notes');
        $this->addConfigOption('upgrade_warnings');
        $this->addConfigOption('new_features_notification', true);
        $this->addConfigOption('new_features_timestamp', null);

        // Default task list name
        $this->addConfigOption('default_task_list_name', 'Task List');

        // Projects filter
        $this->addConfigOption('filter_client_projects', '');
        $this->addConfigOption('filter_label_projects', '');
        $this->addConfigOption('filter_category_projects', '');
        $this->addConfigOption('project_activity_filter', 'weekly');
        $this->addConfigOption('project_groups_collapsed');

        // Global activity
        $this->addConfigOption('global_activity_filter', 'daily');

        // Onboarding survey
        $this->addMemory('fill_onboarding_survey_cta_stage', 1);
        $this->addMemory('fill_onboarding_survey_cta_visible', 0);
        $this->addMemory('fill_onboarding_survey_cta_dismissed', 0);

        // Sample projects wizard step
        $this->addConfigOption('show_sample_projects_wizard_step', true);

        // Default value for theme modal for new users
        $this->addConfigOption('show_theme_modal', false);

        $this->addConfigOption('project_timeline_export', defined('IS_ON_DEMAND') && IS_ON_DEMAND);

        // Default value for user daily capacity
        $this->addConfigOption('user_daily_capacity', 8);

        $this->addConfigOption('workload_people_picker', null);
        $this->addConfigOption('timesheet_people_picker', null);
        $this->addConfigOption('workload_enabled', true);
        $this->addConfigOption('workload_enabled_lock', false);
        $this->addConfigOption('hide_from_clients_enabled', true);
        $this->addConfigOption('hide_from_clients_enabled_lock', true);
        $this->addConfigOption('timesheet_enabled', true);
        $this->addConfigOption('timesheet_enabled_lock', false);
        $this->addConfigOption('profitability_enabled', true);
        $this->addConfigOption('profitability_enabled_lock', true);
        $this->addConfigOption('workload_page_visited', false);
        $this->addConfigOption('workload_contributor', false);
        $this->addConfigOption('had_budgeting_feature_included_in_plan', false);
        $this->addConfigOption('black_friday_got_it', false);
        $this->addConfigOption('show_workload_month_picker', false);

        //Webhook integration
        $this->addConfigOption('webhooks_integration_enabled', true);
        $this->addConfigOption('webhooks_integration_enabled_lock', true);

        // Availability
        $this->addConfigOption('availability_enabled', true);
        $this->addConfigOption('availability_enabled_lock', false);

        // Recently completed tasks
        $this->addConfigOption('show_recently_completed_tasks', true);

        // Project templates
        $this->addConfigOption('project_templates_enabled', true);
        $this->addConfigOption('project_templates_enabled_lock', true);

        // Invoice config options
        $this->addConfigOption('invoices_enabled', true);
        $this->addConfigOption('invoices_enabled_lock', true);

        //Slack integration
        $this->addConfigOption('slack_integration_enabled', true);
        $this->addConfigOption('slack_integration_enabled_lock', true);

        // Estimates config options
        $this->addConfigOption('estimates_enabled', true);
        $this->addConfigOption('estimates_enabled_lock', true);

        // Search
        $this->addConfigOption('search_sort_preference', 'score');

        // Budgeting Feature
        $this->addConfigOption('budgeting_enabled', true);
        $this->addConfigOption('budgeting_enabled_lock', true);

        // Calendar Feature
        $this->addConfigOption('calendar_enabled', true);
        $this->addConfigOption('calendar_enabled_lock', true);

        // Chat Feature
        $this->addConfigOption('chat_enabled', true);
        $this->addConfigOption('chat_enabled_lock', true);

        // Main Menu
        $this->addConfigOption('main_menu_options_order', []);
        $this->addConfigOption('hidden_main_menu_options', []);
        $this->addConfigOption('main_menu_expanded_projects', false);
        $this->addConfigOption('main_menu_shortcuts_expanded', true);

        // Push Notifications
        $this->addConfigOption('push_notification_schedule', 'always');
        $this->addConfigOption('push_notification_schedule_settings', ['08:00', '17:30']);

        // ---------------------------------------------------
        //  Order users by
        // ---------------------------------------------------

        DB::execute('ALTER TABLE `users` ADD `order_by` VARCHAR(191) NULL');
        DB::execute('ALTER TABLE `users` ADD INDEX(`order_by`)');

        DB::execute('CREATE TRIGGER order_by_for_users_before_insert BEFORE INSERT ON users FOR EACH ROW SET NEW.order_by = CONCAT(NEW.first_name, NEW.last_name, NEW.email)');
        DB::execute('CREATE TRIGGER order_by_for_users_before_update BEFORE UPDATE ON users FOR EACH ROW SET NEW.order_by = CONCAT(NEW.first_name, NEW.last_name, NEW.email)');

        // ---------------------------------------------------
        //  Extractors
        // ---------------------------------------------------

        $int_extractors_to_add = [
            'priority',
            'instance_id',
            'webhook_id',
        ];

        $after_column = 'data';

        foreach ($int_extractors_to_add as $int_extractor_to_add) {
            DB::execute(
                sprintf(
                    "ALTER TABLE `jobs_queue` ADD `%s`INT UNSIGNED GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.%s'))) STORED AFTER `%s`",
                    $int_extractor_to_add,
                    $int_extractor_to_add,
                    $after_column,
                ),
            );

            DB::execute(
                sprintf(
                    'ALTER TABLE `jobs_queue` ADD INDEX %s (%s);',
                    $int_extractor_to_add,
                    $int_extractor_to_add,
                ),
            );

            $after_column = $int_extractor_to_add;
        }

        // ---------------------------------------------------
        //  Last login on
        // ---------------------------------------------------

        DB::execute('ALTER TABLE users ADD last_login_on DATETIME NULL');
        DB::execute('ALTER TABLE users ADD INDEX(last_login_on)');

        DB::execute('CREATE TRIGGER new_subscription_updates_login_timestamp AFTER INSERT ON api_subscriptions FOR EACH ROW
            BEGIN
                IF NEW.created_on IS NOT NULL THEN
                    UPDATE users SET last_login_on = NEW.created_on WHERE id = NEW.user_id;
                END IF;
            END',
        );
        DB::execute('CREATE TRIGGER new_session_updates_login_timestamp AFTER INSERT ON user_sessions FOR EACH ROW
            BEGIN
                IF NEW.created_on IS NOT NULL THEN
                    UPDATE users SET last_login_on = NEW.created_on WHERE id = NEW.user_id;
                END IF;
            END',
        );

        // ----------------------------------------------------
        //  Defaults
        // ---------------------------------------------------

        $owner_company_id = $this->addCompany(
            'Owner Company',
            [
                'is_owner' => true,
            ],
        );

        $default_language_id = DB::executeFirstCell('SELECT id FROM languages WHERE is_default = ?', true);

        $this->addUser(
            'user@activecollab.com',
            $owner_company_id,
            [
                'type' => Owner::class,
                'language_id' => $default_language_id,
            ],
        );

        // ---------------------------------------------------
        //  Extra languages
        // ---------------------------------------------------

        $localization_file = APPLICATION_PATH . '/localization/config.json';

        if (is_file($localization_file)) {
            $localization_config = json_decode(file_get_contents($localization_file), true);

            if (is_array($localization_config)) {
                foreach ($localization_config as $locale => $language_settings) {
                    if (empty($language_settings['is_stable'])) {
                        continue;
                    }

                    $is_rtl = !empty($language_settings['is_rtl']);
                    $is_community_translation = !empty($language_settings['is_community_translation']);

                    DB::execute(
                        'INSERT INTO languages (name, locale, decimal_separator, thousands_separator, is_rtl, is_community_translation) VALUES (?, ?, ?, ?, ?, ?)',
                        $language_settings['name_localized'],
                        $locale,
                        $language_settings['decimal_separator'],
                        $language_settings['thousands_separator'],
                        $is_rtl,
                        $is_community_translation,
                    );
                }
            }
        }

        // ---------------------------------------------------
        //  Default set of project labels
        // ---------------------------------------------------

        $counter = 1;

        $default_project_labels = [
            'NEW' => '#FDF196',
            'INPROGRESS' => '#C3E799',
            'CANCELED' => '#FF9C9C',
            'PAUSED' => '#BEACF9',
        ];

        foreach ($default_project_labels as $name => $color) {
            DB::execute(
                "INSERT INTO `labels` (`type`, `name`, `color`, `position`) VALUES ('ProjectLabel', ?, ?, ?)",
                $name,
                $color,
                $counter++,
            );
        }

        // ---------------------------------------------------
        //  Default set of availability types
        // ---------------------------------------------------

        $default_availability_types = [
            'Day Off',
            'Vacation',
            'Sick Leave',
        ];

        foreach ($default_availability_types as $name) {
            DB::execute(
                'INSERT INTO `availability_types` (`name`, `level`, `created_on`) VALUES (?, ?, ?)',
                $name,
                'not_available',
                DateTimeValue::now()->toMySql(),
            );
        }

        // ---------------------------------------------------
        //  Default set of chat
        // ---------------------------------------------------

        DB::execute(
            'INSERT INTO `conversations` (`type`, `created_on`, `updated_on`) VALUES (?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
            GeneralConversation::class,
        );

        parent::loadInitialData();
    }
}

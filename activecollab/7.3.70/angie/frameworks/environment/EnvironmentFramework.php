<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Modules\AngieFramework;

require_once __DIR__ . '/functions.php';

defined('ELASTIC_SEARCH_INDEX_NAME') or define('ELASTIC_SEARCH_INDEX_NAME', null);

// define path to custom ca file
defined('VERIFY_APPLICATION_VENDOR_SSL') or define('VERIFY_APPLICATION_VENDOR_SSL', true);
defined('CUSTOM_CA_FILE') or define('CUSTOM_CA_FILE', __DIR__ . '/resources/ca-bundle.crt');

// Available application object states
const STATE_DELETED = 0;
const STATE_TRASHED = 1;
const STATE_ARCHIVED = 2;
const STATE_VISIBLE = 3;

// Project object priority
const PRIORITY_LOWEST = -2;
const PRIORITY_LOW = -1;
const PRIORITY_NORMAL = 0;
const PRIORITY_HIGH = 1;
const PRIORITY_HIGHEST = 2;

// Scheduled task types
const SCHEDULED_TASK_FREQUENTLY = 'frequently';
const SCHEDULED_TASK_HOURLY = 'hourly';
const SCHEDULED_TASK_DAILY = 'daily';

// Charts
const NON_WORK_DAY_COLOR_CHART = '#F7F7F7';
const DAY_OFF_COLOR_CHART = '#FFEDED';

class EnvironmentFramework extends AngieFramework
{
    const NAME = 'environment';
    const PATH = __DIR__;
    protected string $name = 'environment';

    public function init()
    {
        parent::init();

        DataObjectPool::registerTypeLoader(
            Language::class,
            function (array $ids): ?iterable
            {
                return Languages::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            Currency::class,
            function (array $ids): ?iterable
            {
                return Currencies::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            DayOff::class,
            function (array $ids): ?iterable
            {
                return DayOffs::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            SystemNotification::class,
            function (array $ids): ?iterable
            {
                return SystemNotifications::findByIds($ids);
            }
        );

        DataObjectPool::registerTypeLoader(
            DataFilter::class,
            function (array $ids): ?iterable
            {
                return DataFilters::findByIds($ids);
            }
        );
    }

    public function defineClasses()
    {
        AngieApplication::setForAutoload(
            [
                ConfigOptions::class => __DIR__ . '/models/config_options/ConfigOptions.class.php',
                IConfigContext::class => __DIR__ . '/models/config_options/IConfigContext.class.php',
                ConfigOptionDnxError::class => __DIR__ . '/models/config_options/ConfigOptionDnxError.class.php',

                FwUploadedFile::class => __DIR__ . '/models/uploaded_files/FwUploadedFile.class.php',
                FwUploadedFiles::class => __DIR__ . '/models/uploaded_files/FwUploadedFiles.class.php',

                // Notifications
                FwInfoNotification::class => __DIR__ . '/notifications/FwInfoNotification.class.php',

                IAccessLog::class => __DIR__ . '/models/access_logs/IAccessLog.class.php',
                IAccessLogImplementation::class => __DIR__ . '/models/access_logs/IAccessLogImplementation.class.php',

                IAdditionalProperties::class => __DIR__ . '/models/additional_properties/IAdditionalProperties.class.php',
                IAdditionalPropertiesImplementation::class => __DIR__ . '/models/additional_properties/IAdditionalPropertiesImplementation.class.php',

                IChild::class => __DIR__ . '/models/child/IChild.class.php',
                IChildImplementation::class => __DIR__ . '/models/child/IChildImplementation.class.php',

                // Created by
                ICreatedBy::class => __DIR__ . '/models/created_on_by/ICreatedBy.class.php',
                ICreatedByImplementation::class => __DIR__ . '/models/created_on_by/ICreatedByImplementation.class.php',
                ICreatedOn::class => __DIR__ . '/models/created_on_by/ICreatedOn.class.php',
                ICreatedOnImplementation::class => __DIR__ . '/models/created_on_by/ICreatedOnImplementation.class.php',

                IUpdatedBy::class => __DIR__ . '/models/updated_on_by/IUpdatedBy.class.php',
                IUpdatedByImplementation::class => __DIR__ . '/models/updated_on_by/IUpdatedByImplementation.class.php',
                IUpdatedOn::class => __DIR__ . '/models/updated_on_by/IUpdatedOn.class.php',
                IUpdatedOnImplementation::class => __DIR__ . '/models/updated_on_by/IUpdatedOnImplementation.class.php',

                // Body
                IBody::class => __DIR__ . '/models/body/IBody.class.php',
                IBodyImplementation::class => __DIR__ . '/models/body/IBodyImplementation.class.php',

                IArchive::class => __DIR__ . '/models/archive/IArchive.php',
                IArchiveImplementation::class => __DIR__ . '/models/archive/IArchiveImplementation.php',

                ITrash::class => __DIR__ . '/models/trash/ITrash.php',
                ITrashImplementation::class => __DIR__ . '/models/trash/ITrashImplementation.php',

                RestoreFromTrashError::class => __DIR__ . '/errors/RestoreFromTrashError.class.php',

                IWhosAsking::class => __DIR__ . '/models/IWhosAsking.php',

                // Localisation
                CountriesInterface::class => __DIR__ . '/models/countries/CountriesInterface.php',
                Countries::class => __DIR__ . '/models/countries/Countries.php',

                FwDayOff::class => __DIR__ . '/models/day_offs/FwDayOff.class.php',
                FwDayOffs::class => __DIR__ . '/models/day_offs/FwDayOffs.class.php',

                FwThirdPartyIntegration::class => __DIR__ . '/models/integrations/FwThirdPartyIntegration.php',
                FwGoogleDriveIntegration::class => __DIR__ . '/models/integrations/FwGoogleDriveIntegration.php',
                FwDropboxIntegration::class => __DIR__ . '/models/integrations/FwDropboxIntegration.php',

                CronIntegration::class => __DIR__ . '/models/integrations/CronIntegration.php',
                SearchIntegrationInterface::class => __DIR__ . '/models/integrations/SearchIntegrationInterface.php',
                SearchIntegration::class => __DIR__ . '/models/integrations/SearchIntegration.php',

                // Assignee
                IAssignees::class => __DIR__ . '/models/assignee/IAssignees.php',
                IAssigneesImplementation::class => __DIR__ . '/models/assignee/IAssigneesImplementation.php',

                // Complete
                IComplete::class => __DIR__ . '/models/complete/IComplete.php',
                ICompleteImplementation::class => __DIR__ . '/models/complete/ICompleteImplementation.php',

                // Data filters
                FwDataFilter::class => __DIR__ . '/models/data_filters/FwDataFilter.php',
                FwDataFilters::class => __DIR__ . '/models/data_filters/FwDataFilters.php',

                DataFilterConditionsError::class => __DIR__ . '/models/data_filters/DataFilterConditionsError.php',
                DataFilterExportError::class => __DIR__ . '/models/data_filters/DataFilterExportError.php',

                // Favorites
                FwFavorites::class => __DIR__ . '/models/favorites/FwFavorites.php',
                IFavorite::class => __DIR__ . '/models/favorites/IFavorite.php',
                IFavoriteImplementation::class => __DIR__ . '/models/favorites/IFavoriteImplementation.php',

                AbstractInitialSettingsCollection::class => __DIR__ . '/models/initial_settings/AbstractInitialSettingsCollection.php',
                FwInitialSettingsCollection::class => __DIR__ . '/models/initial_settings/FwInitialSettingsCollection.php',
                FwInitialUserSettingsCollection::class => __DIR__ . '/models/initial_settings/FwInitialUserSettingsCollection.php',
                IResetInitialSettingsTimestamp::class => __DIR__ . '/models/initial_settings/IResetInitialSettingsTimestamp.php',

                DiskSpaceSystemNotification::class => __DIR__ . '/models/system_notifications/disk_space/DiskSpaceSystemNotification.class.php',
                DiskSpaceSystemNotifications::class => __DIR__ . '/models/system_notifications/disk_space/DiskSpaceSystemNotifications.class.php',

                DiskSpaceAlmostUsedSystemNotification::class => __DIR__ . '/models/system_notifications/disk_space/DiskSpaceAlmostUsedSystemNotification.class.php',
                DiskSpaceAlmostUsedSystemNotifications::class => __DIR__ . '/models/system_notifications/disk_space/DiskSpaceAlmostUsedSystemNotifications.class.php',

                FreeTrialSystemNotification::class => __DIR__ . '/models/system_notifications/free_trial/FreeTrialSystemNotification.class.php',
                FreeTrialSystemNotifications::class => __DIR__ . '/models/system_notifications/free_trial/FreeTrialSystemNotifications.class.php',

                MembersExceededSystemNotification::class => __DIR__ . '/models/system_notifications/members_exceeded/MembersExceededSystemNotification.class.php',
                MembersExceededSystemNotifications::class => __DIR__ . '/models/system_notifications/members_exceeded/MembersExceededSystemNotifications.class.php',

                SubscriptionCancelledSystemNotification::class => __DIR__ . '/models/system_notifications/subscription_cancelled/SubscriptionCancelledSystemNotification.class.php',
                SubscriptionCancelledSystemNotifications::class => __DIR__ . '/models/system_notifications/subscription_cancelled/SubscriptionCancelledSystemNotifications.class.php',

                SupportExpirationSystemNotification::class => __DIR__ . '/models/system_notifications/support_expiration/SupportExpirationSystemNotification.class.php',
                SupportExpirationSystemNotifications::class => __DIR__ . '/models/system_notifications/support_expiration/SupportExpirationSystemNotifications.class.php',

                PaymentFailedSystemNotification::class => __DIR__ . '/models/system_notifications/payment_failed/PaymentFailedSystemNotification.class.php',
                PaymentFailedSystemNotifications::class => __DIR__ . '/models/system_notifications/payment_failed/PaymentFailedSystemNotifications.class.php',

                UpgradeAvailableSystemNotification::class => __DIR__ . '/models/system_notifications/upgrade/UpgradeAvailableSystemNotification.class.php',
                UpgradeAvailableSystemNotifications::class => __DIR__ . '/models/system_notifications/upgrade/UpgradeAvailableSystemNotifications.class.php',

                // Test data objects
                FwTestDataObject::class => __DIR__ . '/models/test_data_objects/FwTestDataObject.class.php',
                FwTestDataObjects::class => __DIR__ . '/models/test_data_objects/FwTestDataObjects.class.php',

                WebhookPayloadTransformatorInterface::class => __DIR__ . '/models/webhooks/WebhookPayloadTransformatorInterface.php',
                WebhookPayloadTransformator::class => __DIR__ . '/models/webhooks/WebhookPayloadTransformator.php',

                WebhooksIntegration::class => __DIR__ . '/models/integrations/WebhooksIntegration.php',

                LocalFilesStorage::class => __DIR__ . '/models/storage/LocalFilesStorage.php',

                // who can see this
                IWhoCanSeeThis::class => __DIR__ . '/models/who_can_see_this/IWhoCanSeeThis.class.php',
                IWhoCanSeeThisImplementation::class => __DIR__ . '/models/who_can_see_this/IWhoCanSeeThisImplementation.class.php',
            ]
        );
    }

    public function defineHandlers()
    {
        $this->listen('on_available_integrations');
        $this->listen('on_daily_maintenance');
        $this->listen('on_protected_config_options');
        $this->listen('on_rawtext_to_richtext');
        $this->listen('on_resets_initial_settings_timestamp');
        $this->listen('on_search_filters');
        $this->listen('on_system_status');
        $this->listen('on_clear_cache');
    }
}

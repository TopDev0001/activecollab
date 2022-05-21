<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System;

use AbstractImporterIntegration;
use AccountInactivityWarningNotification;
use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEventInterface;
use ActiveCollab\Module\System\EventListeners\BadgeCountEvents\BadgeCountChanged;
use ActiveCollab\Module\System\EventListeners\BadgeCountEvents\BadgeCountChangedEventInterface;
use ActiveCollab\Module\System\EventListeners\ConversationUserEvents\ConversationUserDeleted;
use ActiveCollab\Module\System\EventListeners\FeatureEvents\FeatureDeactivated;
use ActiveCollab\Module\System\EventListeners\MessageEvents\MessageCreated;
use ActiveCollab\Module\System\EventListeners\MessageEvents\MessageUpdated;
use ActiveCollab\Module\System\EventListeners\ReactionEvents\ReactionCreated;
use ActiveCollab\Module\System\EventListeners\TeamEvents\TeamDeleted;
use ActiveCollab\Module\System\EventListeners\TeamEvents\TeamUpdated;
use ActiveCollab\Module\System\EventListeners\UserEvents\UserMovedToArchive;
use ActiveCollab\Module\System\EventListeners\UserEvents\UserMovedToTrash;
use ActiveCollab\Module\System\EventListeners\WebhookDispatcherInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ActivityLogEvents\ActivityLogLifeCycleEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\AvailabilityRecordEvents\AvailabilityRecordCreatedEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\AvailabilityRecordEvents\AvailabilityRecordLifeCycleEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\AvailabilityTypeEvents\AvailabilityTypeLifeCycleEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\CommentEvents\CommentLifeCycleEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationEvents\ConversationLifeCycleEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationUserEvents\ConversationUserDeletedEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationUserEvents\ConversationUserLifeCycleEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\MessageEvents\MessageCreatedEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\MessageEvents\MessageLifeCycleEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\MessageEvents\MessageUpdatedEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\NotificationRecipientEvents\NotificationRecipientLifeCycleEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectEvents\ProjectLifeCycleEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectTemplateEvents\ProjectTemplateLifeCycleEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ReactionEvents\ReactionCreatedEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ReactionEvents\ReactionLifeCycleEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ReminderEvents\ReminderLifeCycleEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\TeamEvents\TeamDeletedEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\TeamEvents\TeamLifeCycleEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\TeamEvents\TeamUpdatedEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\TrashEvents\MovedToTrashEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\UserEvents\UserLifeCycleEventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\UserEvents\UserMovedToArchiveEventInterface;
use ActiveCollab\Module\System\Events\FeatureEvents\FeatureDeactivatedEvent;
use ActiveCollab\Module\System\Events\Maintenance\DailyMaintenanceEventInterface;
use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;
use ActiveCollab\Module\System\Utils\Conversations\ChatMessagePushNotificationDispatcherInterface;
use ActiveCollab\Module\System\Utils\Conversations\ConversationResolverInterface;
use ActiveCollab\Module\System\Utils\Conversations\MessageMentionResolverInterface;
use ActiveCollab\Module\System\Utils\Conversations\ParentObjectToGroupConversationConverterInterface;
use ActiveCollab\Module\System\Utils\ExpiredFeaturePointersCleaner\ExpiredFeaturePointersCleanerInterface;
use ActiveCollab\Module\System\Utils\SubscriptionCleaner\SubscriptionCleanerInterface;
use ActiveCollab\Module\System\Utils\UsersBadgeCountThrottler\UsersBadgeCountThrottlerInterface;
use ActiveCollab\Module\Tasks\EventListeners\SubtaskEvents\SubtaskPromotedToTask;
use ActiveCollab\Module\Tasks\Events\Subtask\SubtaskPromotedToTaskEventInterface;
use ActivityLog;
use ActivityLogs;
use ActivityLogsInCollection;
use Angie\FeatureFlags\FeatureFlagsInterface;
use Angie\Notifications\NotificationsInterface;
use AngieApplication;
use AngieModule;
use AnonymousUser;
use ApiSubscription;
use ApiSubscriptionError;
use ApiSubscriptions;
use ApplauseReaction;
use AppliedProjectTemplate;
use AppliedProjectTemplates;
use ArchivedProjectFiltersCollection;
use AsanaImporterIntegration;
use AssignmentFilter;
use AuthorizationIntegration;
use AuthorizationIntegrationInterface;
use AvailabilityRecord;
use AvailabilityRecordsCollection;
use AvailabilityTypeInterface;
use BasecampImporterIntegration;
use BounceEmailNotification;
use Categories;
use Category;
use Client;
use ClientPlusIntegration;
use Comment;
use CommentCreatedActivityLog;
use Comments;
use Companies;
use CompaniesSearchBuilder;
use Company;
use CompanySearchDocument;
use Conversation;
use Conversations;
use ConversationUser;
use ConversationUsers;
use CTANotificationInterface;
use CTANotifications;
use CustomReminder;
use CustomReminderNotification;
use DailyUserActivityLogsForCollection;
use DataObjectPool;
use DesktopAppIntegration;
use DropboxAttachment;
use DropboxIntegration;
use DropboxUploadedFile;
use FailedLoginNotification;
use Favorites;
use FillOnboardingSurveyNotification;
use FillOnboardingSurveyNotificationInterface;
use FillOnboardingSurveyNotificationStageResolver;
use GoogleDriveAttachment;
use GoogleDriveIntegration;
use GoogleDriveUploadedFile;
use HeartReaction;
use HubstaffIntegration;
use IActivityLog;
use IActivityLogImplementation;
use IActivityLogsCollection;
use IBasicMembersImplementation;
use IComments;
use ICommentsImplementation;
use IdpAuthorizationIntegration;
use IHiddenFromClients;
use IHourlyRates;
use IHourlyRatesImplementation;
use IMembers;
use IMembersImplementation;
use IMembersViaConnectionTableImplementation;
use INewProjectElementNotificationOptOutConfig;
use InfoNotification;
use InitialSettingsCollection;
use InitialUserSettingsCollection;
use InstanceCreatedActivityLog;
use InstanceUpdatedActivityLog;
use Integration;
use IntegrationInterface;
use Integrations;
use InviteToSharedObjectNotification;
use IProjectBasedOn;
use IProjectElement;
use IProjectElementImplementation;
use IProjectElementsImplementation;
use IProjectTemplateTaskDependency;
use IReactions;
use IReactionsImplementation;
use IUser;
use LabelInterface;
use LastOwnerRoleChangeError;
use LocalAttachment;
use LocalAuthorizationIntegration;
use LocalToWarehouseMover;
use LocalUploadedFile;
use Member;
use Message;
use Messages;
use MorningPaper;
use MorningPaperSnapshot;
use MoveToProjectControllerAction;
use NewCalendarEventNotification;
use NewCommentNotification;
use NewProjectNotification;
use NewReactionNotification;
use NotifyEmailSenderNotification;
use NotifyOwnersNotification;
use OnboardingSurvey;
use OnboardingSurveyInterface;
use OneLoginAuthorizationIntegration;
use OneLoginIntegration;
use Owner;
use PartyReaction;
use PasswordRecoveryNotification;
use PaymentReceivedNotification;
use Project;
use ProjectAdditionalDataCollection;
use ProjectBudgetCollection;
use ProjectCategory;
use ProjectElementSearchDocument;
use ProjectElementsSearchBuilder;
use ProjectElementsSearchBuilderInterface;
use ProjectFinancialStatsCollection;
use ProjectLabel;
use ProjectLabelInterface;
use Projects;
use ProjectSearchDocument;
use ProjectsFilter;
use ProjectsInvoicingDataCollection;
use ProjectsRawCollection;
use ProjectsSearchBuilder;
use ProjectsTimelineFilter;
use ProjectTemplate;
use ProjectTemplateDiscussion;
use ProjectTemplateElement;
use ProjectTemplateElements;
use ProjectTemplateFile;
use ProjectTemplateNote;
use ProjectTemplateNoteGroup;
use ProjectTemplateRecurringTask;
use ProjectTemplates;
use ProjectTemplateSubtask;
use ProjectTemplateTask;
use ProjectTemplateTaskDependenciesCollection;
use ProjectTemplateTaskList;
use PusherIntegration;
use RangeActivityLogsInCollection;
use RangeUserActivityLogsByCollection;
use RangeUserActivityLogsForCollection;
use Reaction;
use Reactions;
use RealTimeIntegration;
use RealTimeIntegrationInterface;
use RemoteAttachment;
use RemoteUploadedFile;
use ReplacingProjectUserNotification;
use SampleProjectsIntegration;
use SetupWizard;
use SetupWizardInterface;
use ShepherdAuthorizationIntegration;
use Shortcut;
use Shortcuts;
use SinceLastVisitService;
use SinceLastVisitServiceInterface;
use SlackIntegration;
use SlackWebhook;
use SmileReaction;
use StorageOverusedNotification;
use Task;
use TaskDependencies;
use Team;
use Teams;
use TeamTimelineFilter;
use TestLodgeIntegration;
use ThinkingReaction;
use ThirdPartyIntegration;
use Thumbnails;
use ThumbsDownReaction;
use ThumbsUpReaction;
use TimeCampIntegration;
use TrelloImporterIntegration;
use UnreadMessagesNotification;
use User;
use UserActivityLogsByCollection;
use UserActivityLogsCollection;
use UserActivityLogsForCollection;
use UserCalendar;
use UserInvitation;
use UserInvitations;
use UserObjectUpdatesCollection;
use UserProfilePermissionsChecker;
use Users;
use UserSearchDocument;
use UserSession;
use UserSessions;
use UsersSearchBuilder;
use Versions;
use WarehouseAttachment;
use WarehouseIntegration;
use WarehouseIntegrationInterface;
use WarehouseUploadedFile;
use Webhook;
use Webhooks;
use WrikeImporterIntegration;
use ZapierIntegration;

class SystemModule extends AngieModule
{
    public const NAME = 'system';
    public const PATH = __DIR__;

    public const MAINTENANCE_JOBS_QUEUE_CHANNEL = 'maintenance';

    protected string $name = 'system';
    protected string $version = '5.0';

    public function init()
    {
        parent::init();

        DataObjectPool::registerTypeLoader(
            Integration::class,
            function (array $ids): ?iterable
            {
                return Integrations::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            [
                User::class,
                Member::class,
                Owner::class,
                Client::class,
            ],
            function (array $ids): ?iterable
            {
                return Users::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            ApiSubscription::class,
            function (array $ids): ?iterable
            {
                return ApiSubscriptions::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            UserSession::class,
            function (array $ids): ?iterable
            {
                return UserSessions::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            UserInvitation::class,
            function (array $ids): ?iterable
            {
                return UserInvitations::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            Category::class,
            function (array $ids): ?iterable
            {
                return Categories::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            Project::class,
            function (array $ids): ?iterable
            {
                return Projects::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            Company::class,
            function (array $ids): ?iterable
            {
                return Companies::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            Team::class,
            function (array $ids): ?iterable
            {
                return Teams::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            ProjectTemplate::class,
            function (array $ids): ?iterable
            {
                return ProjectTemplates::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            AppliedProjectTemplate::class,
            function (array $ids): ?iterable
            {
                return AppliedProjectTemplates::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            [
                ProjectTemplateElement::class,
                ProjectTemplateTaskList::class,
                ProjectTemplateRecurringTask::class,
                ProjectTemplateTask::class,
                ProjectTemplateSubtask::class,
                ProjectTemplateDiscussion::class,
                ProjectTemplateNote::class,
            ],
            function (array $ids): ?iterable
            {
                return ProjectTemplateElements::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            Webhook::class,
            function (array $ids): ?iterable
            {
                return Webhooks::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            Comment::class,
            function (array $ids): ?iterable
            {
                return Comments::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            Reaction::class,
            function (array $ids): ?iterable
            {
                return Reactions::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            ActivityLog::class,
            function (array $ids): ?iterable
            {
                return ActivityLogs::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            Conversation::class,
            function (array $ids): ?iterable
            {
                return Conversations::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            Shortcut::class,
            function (array $ids): ?iterable
            {
                return Shortcuts::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            ConversationUser::class,
            function (array $ids): ?iterable
            {
                return ConversationUsers::findByIds($ids);
            },
        );

        DataObjectPool::registerTypeLoader(
            Message::class,
            function (array $ids): ?iterable
            {
                return Messages::findByIds($ids);
            },
        );
    }

    public function defineClasses()
    {
        require_once __DIR__ . '/resources/autoload_model.php';
        require_once __DIR__ . '/models/application_objects/ApplicationObject.class.php';

        AngieApplication::setForAutoload(
            [
                // Errors
                ApiSubscriptionError::class => __DIR__ . '/errors/ApiSubscriptionError.class.php',
                LastOwnerRoleChangeError::class => __DIR__ . '/errors/LastOwnerRoleChangeError.class.php',

                IProjectBasedOn::class => __DIR__ . '/models/IProjectBasedOn.class.php',

                LabelInterface::class => __DIR__ . '/models/LabelInterface.php',
                ProjectLabelInterface::class => __DIR__ . '/models/ProjectLabelInterface.php',
                ProjectLabel::class => __DIR__ . '/models/ProjectLabel.class.php',

                ProjectsRawCollection::class => __DIR__ . '/models/ProjectsRawCollection.php',
                ArchivedProjectFiltersCollection::class => __DIR__ . '/models/ArchivedProjectFiltersCollection.php',
                ProjectCategory::class => __DIR__ . '/models/ProjectCategory.class.php',
                ProjectBudgetCollection::class => __DIR__ . '/models/ProjectBudgetCollection.php',
                ProjectAdditionalDataCollection::class => __DIR__ . '/models/ProjectAdditionalDataCollection.php',
                ProjectFinancialStatsCollection::class => __DIR__ . '/models/ProjectFinancialStatsCollection.php',
                ProjectsInvoicingDataCollection::class => __DIR__ . '/models/ProjectsInvoicingDataCollection.php',
                AvailabilityRecordsCollection::class => __DIR__ . '/models/availability_records/AvailabilityRecordsCollection.php',

                Favorites::class => __DIR__ . '/models/Favorites.class.php',

                MoveToProjectControllerAction::class => __DIR__ . '/controller_actions/MoveToProjectControllerAction.class.php',

                AnonymousUser::class => __DIR__ . '/models/AnonymousUser.class.php',
                Thumbnails::class => __DIR__ . '/models/Thumbnails.class.php',

                IProjectElement::class => __DIR__ . '/models/project_elements/IProjectElement.class.php',
                IProjectElementImplementation::class => __DIR__ . '/models/project_elements/IProjectElementImplementation.class.php',
                IProjectElementsImplementation::class => __DIR__ . '/models/project_elements/IProjectElementsImplementation.class.php',

                // Filters
                AssignmentFilter::class => __DIR__ . '/models/AssignmentFilter.php',
                ProjectsFilter::class => __DIR__ . '/models/ProjectsFilter.php',
                ProjectsTimelineFilter::class => __DIR__ . '/models/ProjectsTimelineFilter.php',
                TeamTimelineFilter::class => __DIR__ . '/models/TeamTimelineFilter.php',

                // Notifications
                NewProjectNotification::class => __DIR__ . '/notifications/NewProjectNotification.class.php',
                NewCommentNotification::class => __DIR__ . '/notifications/NewCommentNotification.class.php',
                NewReactionNotification::class => __DIR__ . '/notifications/NewReactionNotification.class.php',
                PasswordRecoveryNotification::class => __DIR__ . '/notifications/PasswordRecoveryNotification.class.php',
                ReplacingProjectUserNotification::class => __DIR__ . '/notifications/ReplacingProjectUserNotification.class.php',
                NotifyEmailSenderNotification::class => __DIR__ . '/notifications/NotifyEmailSenderNotification.class.php',
                InviteToSharedObjectNotification::class => __DIR__ . '/notifications/InviteToSharedObjectNotification.class.php',
                NewCalendarEventNotification::class => __DIR__ . '/notifications/NewCalendarEventNotification.class.php',
                InfoNotification::class => __DIR__ . '/notifications/InfoNotification.class.php',
                UnreadMessagesNotification::class => __DIR__ . '/notifications/UnreadMessagesNotification.class.php',

                PaymentReceivedNotification::class => __DIR__ . '/notifications/PaymentReceivedNotification.class.php',

                FailedLoginNotification::class => __DIR__ . '/notifications/FailedLoginNotification.class.php',

                BounceEmailNotification::class => __DIR__ . '/notifications/BounceEmailNotification.class.php',
                NotifyOwnersNotification::class => __DIR__ . '/notifications/NotifyOwnersNotification.class.php',

                // Authentication related
                IUser::class => __DIR__ . '/models/IUser.php',

                AuthorizationIntegrationInterface::class => __DIR__ . '/models/integrations/authorization/AuthorizationIntegrationInterface.php',
                AuthorizationIntegration::class => __DIR__ . '/models/integrations/authorization/AuthorizationIntegration.php',
                IdpAuthorizationIntegration::class => __DIR__ . '/models/integrations/authorization/IdpAuthorizationIntegration.php',

                LocalAuthorizationIntegration::class => __DIR__ . '/models/integrations/authorization/LocalAuthorizationIntegration.php',
                ShepherdAuthorizationIntegration::class => __DIR__ . '/models/integrations/authorization/idp/ShepherdAuthorizationIntegration.php',
                OneLoginAuthorizationIntegration::class => __DIR__ . '/models/integrations/authorization/idp/OneLoginAuthorizationIntegration.php',

                // User roles
                Owner::class => __DIR__ . '/models/user_roles/Owner.class.php',
                Member::class => __DIR__ . '/models/user_roles/Member.class.php',
                Client::class => __DIR__ . '/models/user_roles/Client.class.php',

                // Members
                IMembers::class => __DIR__ . '/models/members/IMembers.class.php',
                IBasicMembersImplementation::class => __DIR__ . '/models/members/IBasicMembersImplementation.class.php',
                IMembersImplementation::class => __DIR__ . '/models/members/IMembersImplementation.class.php',
                IMembersViaConnectionTableImplementation::class => __DIR__ . '/models/members/IMembersViaConnectionTableImplementation.class.php',

                // Calendars
                UserCalendar::class => __DIR__ . '/models/UserCalendar.class.php',

                // Morning paper
                MorningPaper::class => __DIR__ . '/models/morning_paper/MorningPaper.php',
                MorningPaperSnapshot::class => __DIR__ . '/models/morning_paper/MorningPaperSnapshot.php',

                // Hourly rates
                IHourlyRates::class => __DIR__ . '/models/hourly_rates/IHourlyRates.class.php',
                IHourlyRatesImplementation::class => __DIR__ . '/models/hourly_rates/IHourlyRatesImplementation.class.php',

                // Project template elements
                ProjectTemplateTaskList::class => __DIR__ . '/models/ProjectTemplateTaskList.php',
                ProjectTemplateTask::class => __DIR__ . '/models/ProjectTemplateTask.php',
                IProjectTemplateTaskDependency::class => __DIR__ . '/models/IProjectTemplateTaskDependency.php',
                ProjectTemplateTaskDependenciesCollection::class => __DIR__ . '/models/ProjectTemplateTaskDependenciesCollection.php',
                ProjectTemplateRecurringTask::class => __DIR__ . '/models/ProjectTemplateRecurringTask.php',
                ProjectTemplateSubtask::class => __DIR__ . '/models/ProjectTemplateSubtask.php',
                ProjectTemplateDiscussion::class => __DIR__ . '/models/ProjectTemplateDiscussion.php',
                ProjectTemplateNoteGroup::class => __DIR__ . '/models/ProjectTemplateNoteGroup.php',
                ProjectTemplateNote::class => __DIR__ . '/models/ProjectTemplateNote.php',
                ProjectTemplateFile::class => __DIR__ . '/models/ProjectTemplateFile.php',

                InitialSettingsCollection::class => __DIR__ . '/models/initial_settings/InitialSettingsCollection.php',
                InitialUserSettingsCollection::class => __DIR__ . '/models/initial_settings/InitialUserSettingsCollection.php',

                // Reminders
                CustomReminder::class => __DIR__ . '/models/CustomReminder.php',
                CustomReminderNotification::class => __DIR__ . '/notifications/CustomReminderNotification.class.php',

                UserObjectUpdatesCollection::class => __DIR__ . '/models/UserObjectUpdatesCollection.php',

                // Search
                UsersSearchBuilder::class => __DIR__ . '/models/search_builders/UsersSearchBuilder.php',
                CompaniesSearchBuilder::class => __DIR__ . '/models/search_builders/CompaniesSearchBuilder.php',
                ProjectsSearchBuilder::class => __DIR__ . '/models/search_builders/ProjectsSearchBuilder.php',
                ProjectElementsSearchBuilder::class => __DIR__ . '/models/search_builders/ProjectElementsSearchBuilder.php',

                ProjectElementsSearchBuilderInterface::class => __DIR__ . '/models/search_builders/ProjectElementsSearchBuilderInterface.php',

                CompanySearchDocument::class => __DIR__ . '/models/search_documents/CompanySearchDocument.php',
                UserSearchDocument::class => __DIR__ . '/models/search_documents/UserSearchDocument.php',
                ProjectSearchDocument::class => __DIR__ . '/models/search_documents/ProjectSearchDocument.php',
                ProjectElementSearchDocument::class => __DIR__ . '/models/search_documents/ProjectElementSearchDocument.php',

                // Integrations
                IntegrationInterface::class => __DIR__ . '/models/integrations/IntegrationInterface.php',
                DesktopAppIntegration::class => __DIR__ . '/models/integrations/DesktopAppIntegration.php',
                AbstractImporterIntegration::class => __DIR__ . '/models/integrations/AbstractImporterIntegration.class.php',
                BasecampImporterIntegration::class => __DIR__ . '/models/integrations/BasecampImporterIntegration.php',
                ClientPlusIntegration::class => __DIR__ . '/models/integrations/ClientPlusIntegration.php',
                TestLodgeIntegration::class => __DIR__ . '/models/integrations/TestLodgeIntegration.php',
                HubstaffIntegration::class => __DIR__ . '/models/integrations/HubstaffIntegration.php',
                TimeCampIntegration::class => __DIR__ . '/models/integrations/TimeCampIntegration.php',
                ThirdPartyIntegration::class => __DIR__ . '/models/integrations/ThirdPartyIntegration.php',
                TrelloImporterIntegration::class => __DIR__ . '/models/integrations/TrelloImporterIntegration.php',
                AsanaImporterIntegration::class => __DIR__ . '/models/integrations/AsanaImporterIntegration.php',
                SlackIntegration::class => __DIR__ . '/models/integrations/SlackIntegration.php',
                WarehouseIntegration::class => __DIR__ . '/models/integrations/WarehouseIntegration.php',
                WarehouseIntegrationInterface::class => __DIR__ . '/models/integrations/WarehouseIntegrationInterface.php',
                GoogleDriveIntegration::class => __DIR__ . '/models/integrations/GoogleDriveIntegration.php',
                DropboxIntegration::class => __DIR__ . '/models/integrations/DropboxIntegration.php',
                ZapierIntegration::class => __DIR__ . '/models/integrations/ZapierIntegration.php',
                OneLoginIntegration::class => __DIR__ . '/models/integrations/OneLoginIntegration.php',
                WrikeImporterIntegration::class => __DIR__ . '/models/integrations/WrikeImporterIntegration.php',
                RealTimeIntegration::class => __DIR__ . '/models/integrations/RealTimeIntegration.php',
                RealTimeIntegrationInterface::class => __DIR__ . '/models/integrations/RealTimeIntegrationInterface.php',
                PusherIntegration::class => __DIR__ . '/models/integrations/PusherIntegration.php',
                SampleProjectsIntegration::class => __DIR__ . '/models/integrations/SampleProjectsIntegration.php',

                IHiddenFromClients::class => __DIR__ . '/models/IHiddenFromClients.php',
                INewProjectElementNotificationOptOutConfig::class => __DIR__ . '/models/INewProjectElementNotificationOptOutConfig.php',

                LocalAttachment::class => __DIR__ . '/models/attachments/LocalAttachment.class.php',
                RemoteAttachment::class => __DIR__ . '/models/attachments/RemoteAttachment.class.php',
                WarehouseAttachment::class => __DIR__ . '/models/attachments/WarehouseAttachment.class.php',
                GoogleDriveAttachment::class => __DIR__ . '/models/attachments/GoogleDriveAttachment.class.php',
                DropboxAttachment::class => __DIR__ . '/models/attachments/DropboxAttachment.class.php',

                LocalUploadedFile::class => __DIR__ . '/models/uploaded_files/LocalUploadedFile.class.php',
                RemoteUploadedFile::class => __DIR__ . '/models/uploaded_files/RemoteUploadedFile.class.php',
                WarehouseUploadedFile::class => __DIR__ . '/models/uploaded_files/WarehouseUploadedFile.class.php',
                GoogleDriveUploadedFile::class => __DIR__ . '/models/uploaded_files/GoogleDriveUploadedFile.class.php',
                DropboxUploadedFile::class => __DIR__ . '/models/uploaded_files/DropboxUploadedFile.class.php',

                // Webhooks
                SlackWebhook::class => __DIR__ . '/models/webhooks/SlackWebhook.class.php',

                Versions::class => __DIR__ . '/models/Versions.php',
                LocalToWarehouseMover::class => __DIR__ . '/models/LocalToWarehouseMover.php',

                UserProfilePermissionsChecker::class => __DIR__ . '/models/UserProfilePermissionsChecker.php',
                OnboardingSurveyInterface::class => __DIR__ . '/models/OnboardingSurvey/OnboardingSurveyInterface.php',
                OnboardingSurvey::class => __DIR__ . '/models/OnboardingSurvey/OnboardingSurvey.php',
                SinceLastVisitServiceInterface::class => __DIR__ . '/models/SinceLastVisitServiceInterface.php',
                SinceLastVisitService::class => __DIR__ . '/models/SinceLastVisitService.php',

                SetupWizard::class => __DIR__ . '/models/SetupWizard/SetupWizard.php',
                SetupWizardInterface::class => __DIR__ . '/models/SetupWizard/SetupWizardInterface.php',

                // CTA Notifications
                FillOnboardingSurveyNotificationInterface::class => __DIR__ . '/models/CTANotification/FillOnboardingSurveyNotificationInterface.php',
                CTANotificationInterface::class => __DIR__ . '/models/CTANotification/CTANotificationInterface.php',
                FillOnboardingSurveyNotification::class => __DIR__ . '/models/CTANotification/FillOnboardingSurveyNotification.php',
                FillOnboardingSurveyNotificationStageResolver::class => __DIR__ . '/models/CTANotification/FillOnboardingSurveyNotificationStageResolver.php',
                CTANotifications::class => __DIR__ . '/models/CTANotification/CTANotifications.php',

                // Comments.
                IComments::class => __DIR__ . '/models/comments/IComments.php',
                ICommentsImplementation::class => __DIR__ . '/models/comments/ICommentsImplementation.php',

                CommentCreatedActivityLog::class => __DIR__ . '/models/CommentCreatedActivityLog.php',

                // Reactions.
                IReactions::class => __DIR__ . '/models/reactions/IReactions.php',
                IReactionsImplementation::class => __DIR__ . '/models/reactions/IReactionsImplementation.php',
                SmileReaction::class => __DIR__ . '/models/reactions/SmileReaction.php',
                ThinkingReaction::class => __DIR__ . '/models/reactions/ThinkingReaction.php',
                ThumbsUpReaction::class => __DIR__ . '/models/reactions/ThumbsUpReaction.php',
                ThumbsDownReaction::class => __DIR__ . '/models/reactions/ThumbsDownReaction.php',
                ApplauseReaction::class => __DIR__ . '/models/reactions/ApplauseReaction.php',
                HeartReaction::class => __DIR__ . '/models/reactions/HeartReaction.php',
                PartyReaction::class => __DIR__ . '/models/reactions/PartyReaction.php',

                // Activity logs.
                IActivityLog::class => __DIR__ . '/models/activity_logs/IActivityLog.php',
                IActivityLogImplementation::class => __DIR__ . '/models/activity_logs/IActivityLogImplementation.php',

                InstanceCreatedActivityLog::class => __DIR__ . '/models/InstanceCreatedActivityLog.php',
                InstanceUpdatedActivityLog::class => __DIR__ . '/models/InstanceUpdatedActivityLog.php',

                IActivityLogsCollection::class => __DIR__ . '/models/activity_log_collections/IActivityLogsCollection.php',
                ActivityLogsInCollection::class => __DIR__ . '/models/activity_log_collections/ActivityLogsInCollection.php',
                RangeActivityLogsInCollection::class => __DIR__ . '/models/activity_log_collections/RangeActivityLogsInCollection.php',

                UserActivityLogsCollection::class => __DIR__ . '/models/activity_log_collections/UserActivityLogsCollection.php',
                UserActivityLogsForCollection::class => __DIR__ . '/models/activity_log_collections/UserActivityLogsForCollection.php',
                UserActivityLogsByCollection::class => __DIR__ . '/models/activity_log_collections/UserActivityLogsByCollection.php',

                DailyUserActivityLogsForCollection::class => __DIR__ . '/models/activity_log_collections/DailyUserActivityLogsForCollection.php',
                RangeUserActivityLogsByCollection::class => __DIR__ . '/models/activity_log_collections/RangeUserActivityLogsByCollection.php',
                RangeUserActivityLogsForCollection::class => __DIR__ . '/models/activity_log_collections/RangeUserActivityLogsForCollection.php',

                // Notifications.
                StorageOverusedNotification::class => __DIR__ . '/notifications/StorageOverusedNotification.class.php',
                AccountInactivityWarningNotification::class => __DIR__ . '/notifications/AccountInactivityWarningNotification.class.php',

                AvailabilityTypeInterface::class => __DIR__ . '/models/AvailabilityTypeInterface.php',
            ],
        );
    }

    public function defineHandlers()
    {
        $this->listen('on_notification_inspector');

        $this->listen('on_rebuild_activity_logs');

        $this->listen('on_extra_stats');

        $this->listen('on_handle_public_subscribe');
        $this->listen('on_handle_public_unsubscribe');

        $this->listen('on_available_integrations');
        $this->listen('on_initial_settings');
        $this->listen('on_available_webhook_payload_transformators');

        $this->listen('on_visible_object_paths');
        $this->listen('on_trash_sections');
        $this->listen('on_search_filters');
        $this->listen('on_search_rebuild_index');
        $this->listen('on_user_access_search_filter');
        $this->listen('on_report_sections');
        $this->listen('on_reports');

        $this->listen('on_morning_mail');
        $this->listen('on_hourly_maintenance');

        $this->listen('on_protected_config_options');
        $this->listen('on_history_field_renderers');

        $this->listen('on_daily_maintenance');
        $this->listen('on_reset_manager_states');
        $this->listen('on_session_started');

        $this->listen('on_resets_initial_settings_timestamp');

        $this->listen('on_email_received', 'on_email_received');
    }

    public function defineListeners(): array
    {
        return [
            // Listen for all data object life cycle events, capture ones that need to send a webhook, and prepare jobs
            // that will match webhooks with the object payload.
            DataObjectLifeCycleEventInterface::class => AngieApplication::getContainer()->get(WebhookDispatcherInterface::class),
            AvailabilityRecordCreatedEventInterface::class => function ($event) {
                /** @var AvailabilityRecord $availability_record */
                $availability_record = $event->getObject();
                $user = $availability_record->getUser();

                $send_to = $availability_record->isCreatedByAnotherUser() ? $user : Users::findOwners();

                AngieApplication::notifications()
                    ->notifyAbout(
                        'system/availability_record_added',
                        $availability_record,
                        $availability_record->getCreatedBy(),
                    )
                    ->sendToUsers($send_to);
            },
            AvailabilityRecordLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch($event, $event->getWebhookEventType());
            },
            AvailabilityTypeLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch($event, $event->getWebhookEventType());
            },
            ReminderLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch($event, $event->getWebhookEventType());
            },
            ProjectLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch($event, $event->getWebhookEventType());
            },
            ProjectTemplateLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch($event, $event->getWebhookEventType());
            },
            UserLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch($event, $event->getWebhookEventType());
            },
            UserMovedToArchiveEventInterface::class => new UserMovedToArchive(
                AngieApplication::jobs(),
                AngieApplication::getAccountId(),
            ),
            MovedToTrashEventInterface::class => new UserMovedToTrash(
                AngieApplication::jobs(),
                AngieApplication::getAccountId(),
            ),
            FeatureDeactivatedEvent::class => new FeatureDeactivated(
                AngieApplication::log(),
            ),
            NotificationRecipientLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch($event, $event->getWebhookEventType());
            },
            ActivityLogLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch($event, $event->getWebhookEventType());
            },
            SubtaskPromotedToTaskEventInterface::class => new SubtaskPromotedToTask(
                AngieApplication::featureFactory(),
                function (Task $parent, Task $child, User $by) {
                    TaskDependencies::createDependency($parent, $child, $by);
                },
            ),
            MessageLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch(
                    $event,
                    $event->getWebhookEventType(),
                    false,
                    RealTimeIntegrationInterface::CHAT_JOBS_QUEUE_CHANNEL,
                );
            },
            ConversationLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch(
                    $event,
                    $event->getWebhookEventType(),
                    false,
                    RealTimeIntegrationInterface::CHAT_JOBS_QUEUE_CHANNEL,
                );
            },
            ConversationUserLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch(
                    $event,
                    $event->getWebhookEventType(),
                    false,
                    RealTimeIntegrationInterface::CHAT_JOBS_QUEUE_CHANNEL,
                );

                if ($event instanceof ConversationUserDeletedEventInterface) {
                    call_user_func(new ConversationUserDeleted(AngieApplication::log()), $event);
                }
            },
            MessageCreatedEventInterface::class => new MessageCreated(
                AngieApplication::getContainer()->get(MessageMentionResolverInterface::class),
                AngieApplication::getContainer()->get(ChatMessagePushNotificationDispatcherInterface::class),
                AngieApplication::log(),
            ),
            BadgeCountChangedEventInterface::class => new BadgeCountChanged(
                AngieApplication::getContainer()->get(ChatMessagePushNotificationDispatcherInterface::class),
                AngieApplication::getContainer()->get(UsersBadgeCountThrottlerInterface::class),
                AngieApplication::getContainer()->get(FeatureFlagsInterface::class),
            ),
            MessageUpdatedEventInterface::class => new MessageUpdated(
                AngieApplication::getContainer()->get(MessageMentionResolverInterface::class),
            ),
            TeamLifeCycleEventInterface::class => function ($event) {
                if ($event instanceof TeamDeletedEventInterface) {
                    call_user_func(
                        new TeamDeleted(
                            AngieApplication::getContainer()->get(ConversationResolverInterface::class),
                            AngieApplication::getContainer()->get(ParentObjectToGroupConversationConverterInterface::class),
                        ),
                        $event,
                    );
                }

                AngieApplication::socketsDispatcher()->dispatch($event, $event->getWebhookEventType());
            },
            TeamUpdatedEventInterface::class => new TeamUpdated(
                AngieApplication::getContainer()->get(ConversationResolverInterface::class),
                function (ConversationInterface $conversation, array $attributes) {
                    return Conversations::update($conversation, $attributes);
                },
            ),
            CommentLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch($event, $event->getWebhookEventType());
            },
            ReactionLifeCycleEventInterface::class => function ($event) {
                AngieApplication::socketsDispatcher()->dispatch($event, $event->getWebhookEventType());
            },
            ReactionCreatedEventInterface::class => new ReactionCreated(
                AngieApplication::getContainer()->get(NotificationsInterface::class),
                AngieApplication::log(),
            ),
            DailyMaintenanceEventInterface::class => function ($event) {
                call_user_func(AngieApplication::getContainer()->get(SubscriptionCleanerInterface::class), $event);
                call_user_func(AngieApplication::getContainer()->get(ExpiredFeaturePointersCleanerInterface::class), $event);
            },
        ];
    }
}

<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\ActiveCollabJwt\Factory\JwtFactoryInterface;
use ActiveCollab\ActiveCollabJwt\Factory\LcobucciJwtFactory;
use ActiveCollab\ActiveCollabJwt\Verifier\JwtVerifierInterface;
use ActiveCollab\ActiveCollabJwt\Verifier\LcobucciJwtVerifier;
use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use ActiveCollab\EventsDispatcher\EventsDispatcherInterface;
use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use ActiveCollab\Foundation\App\Mode\ApplicationModeInterface;
use ActiveCollab\Foundation\App\RootUrl\RootUrlInterface;
use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEventInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\MailboxesSet\Resolver\DefaultSenderResolverInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\MailboxesSet\Resolver\MailboxesSetResolver;
use ActiveCollab\Foundation\Mail\Incoming\Processor\MailboxesSet\Resolver\MailboxesSetResolverInterface;
use ActiveCollab\Foundation\Mail\Incoming\Processor\Processor;
use ActiveCollab\Foundation\Mail\Incoming\Processor\ProcessorInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Links\TextReplacement\Resolver\TextReplacementResolverInterface;
use ActiveCollab\Foundation\Urls\Expander\UrlExpander;
use ActiveCollab\Foundation\Urls\Expander\UrlExpanderInterface;
use ActiveCollab\Foundation\Urls\Factory\UrlFactory;
use ActiveCollab\Foundation\Urls\IgnoredDomainsResolver\IgnoredDomainsResolverInterface;
use ActiveCollab\Foundation\Urls\Router\RouterInterface;
use ActiveCollab\Foundation\Wrappers\Cache\CacheInterface;
use ActiveCollab\Foundation\Wrappers\DataObjectPool\DataObjectPoolInterface;
use ActiveCollab\Module\OnDemand\services\account\OccupyAndActivateService;
use ActiveCollab\Module\OnDemand\Utils\AccountSettingsManager\AccountSettingsManagerInterface;
use ActiveCollab\Module\OnDemand\Utils\OccupyAccountServiceResolver\OccupyAccountServiceResolver;
use ActiveCollab\Module\OnDemand\Utils\OccupyAccountServiceResolver\OccupyAccountServiceResolverInterface;
use ActiveCollab\Module\System\Services\Conversation\EmailNotifications\ConversationEmailNotificationSettingsResolver;
use ActiveCollab\Module\System\Services\Conversation\EmailNotifications\ConversationEmailNotificationSettingsResolverInterface;
use ActiveCollab\Module\System\Services\Conversation\EmailNotifications\ConversationEmailNotificationsUpdater;
use ActiveCollab\Module\System\Services\Conversation\EmailNotifications\ConversationEmailNotificationsUpdaterInterface;
use ActiveCollab\Module\System\Services\Conversation\EmailNotifications\ConversationNotificationDataFactory;
use ActiveCollab\Module\System\Services\Conversation\EmailNotifications\ConversationNotificationDataFactoryInterface;
use ActiveCollab\Module\System\Services\Conversation\EmailNotifications\NotificationDispatcher;
use ActiveCollab\Module\System\Services\Conversation\EmailNotifications\NotificationDispatcherInterface;
use ActiveCollab\Module\System\Services\Conversation\EmailNotifications\UsersToNotifyAboutUnreadMessagesResolver;
use ActiveCollab\Module\System\Services\Conversation\EmailNotifications\UsersToNotifyAboutUnreadMessagesResolverInterface;
use ActiveCollab\Module\System\Services\Conversation\GroupConversationAdminService;
use ActiveCollab\Module\System\Services\Conversation\GroupConversationAdminServiceInterface;
use ActiveCollab\Module\System\Services\Conversation\GroupConversationInviteService;
use ActiveCollab\Module\System\Services\Conversation\GroupConversationInviteServiceInterface;
use ActiveCollab\Module\System\Services\Conversation\GroupConversationLeaveService;
use ActiveCollab\Module\System\Services\Conversation\GroupConversationLeaveServiceInterface;
use ActiveCollab\Module\System\Services\Conversation\GroupConversationRemoveService;
use ActiveCollab\Module\System\Services\Conversation\GroupConversationRemoveServiceInterface;
use ActiveCollab\Module\System\Services\Conversation\GroupConversationRenameService;
use ActiveCollab\Module\System\Services\Conversation\GroupConversationRenameServiceInterface;
use ActiveCollab\Module\System\Services\Conversation\NotifyUserAboutUnreadMessagesService;
use ActiveCollab\Module\System\Services\Conversation\NotifyUserAboutUnreadMessagesServiceInterface;
use ActiveCollab\Module\System\Services\Message\UserMessage\MarkAsUnreadService;
use ActiveCollab\Module\System\Services\Message\UserMessage\MarkAsUnreadServiceInterface;
use ActiveCollab\Module\System\Services\Message\UserMessage\PushNotificationUserMessageService;
use ActiveCollab\Module\System\Services\Message\UserMessage\PushNotificationUserMessageServiceInterface;
use ActiveCollab\Module\System\SystemModule;
use ActiveCollab\Module\System\Utils\ActiveCollabCliCommandExecutor\ActiveCollabCliCommandExecutor;
use ActiveCollab\Module\System\Utils\ActiveCollabCliCommandExecutor\ActiveCollabCliCommandExecutorInterface;
use ActiveCollab\Module\System\Utils\AuthorizeFileAccessService\AuthorizeFileAccessService;
use ActiveCollab\Module\System\Utils\AuthorizeFileAccessService\AuthorizeFileAccessServiceInterface;
use ActiveCollab\Module\System\Utils\BodyProcessorResolver\BodyProcessorResolver;
use ActiveCollab\Module\System\Utils\BodyProcessorResolver\BodyProcessorResolverInterface;
use ActiveCollab\Module\System\Utils\Conversations\ChatMessagePushNotificationDispatcher;
use ActiveCollab\Module\System\Utils\Conversations\ChatMessagePushNotificationDispatcherInterface;
use ActiveCollab\Module\System\Utils\Conversations\ConversationFactory;
use ActiveCollab\Module\System\Utils\Conversations\ConversationFactoryInterface;
use ActiveCollab\Module\System\Utils\Conversations\ConversationMessageFactory;
use ActiveCollab\Module\System\Utils\Conversations\ConversationMessageFactoryInterface;
use ActiveCollab\Module\System\Utils\Conversations\ConversationMessageMentionResolver;
use ActiveCollab\Module\System\Utils\Conversations\ConversationResolver;
use ActiveCollab\Module\System\Utils\Conversations\ConversationResolverInterface;
use ActiveCollab\Module\System\Utils\Conversations\GroupConversationAdminGenerator;
use ActiveCollab\Module\System\Utils\Conversations\GroupConversationAdminGeneratorInterface;
use ActiveCollab\Module\System\Utils\Conversations\MessageMentionResolverInterface;
use ActiveCollab\Module\System\Utils\Conversations\MessageOrderIdResolver;
use ActiveCollab\Module\System\Utils\Conversations\MessageOrderIdResolverInterface;
use ActiveCollab\Module\System\Utils\Conversations\ParentObjectToGroupConversationConverter;
use ActiveCollab\Module\System\Utils\Conversations\ParentObjectToGroupConversationConverterInterface;
use ActiveCollab\Module\System\Utils\DateValidationResolver\TaskDateValidationResolver;
use ActiveCollab\Module\System\Utils\DefaultCurrencyResolver\DefaultCurrencyResolver;
use ActiveCollab\Module\System\Utils\DefaultCurrencyResolver\DefaultCurrencyResolverInterface;
use ActiveCollab\Module\System\Utils\Dependency\ProjectTemplateDependencyResolver;
use ActiveCollab\Module\System\Utils\Dependency\ProjectTemplateDependencyResolverInterface;
use ActiveCollab\Module\System\Utils\ExpiredFeaturePointersCleaner\ExpiredFeaturePointersCleaner;
use ActiveCollab\Module\System\Utils\ExpiredFeaturePointersCleaner\ExpiredFeaturePointersCleanerInterface;
use ActiveCollab\Module\System\Utils\InactiveUsersResolver\InactiveUsersResolver;
use ActiveCollab\Module\System\Utils\InactiveUsersResolver\InactiveUsersResolverInterface;
use ActiveCollab\Module\System\Utils\IncomingMail\Bouncer;
use ActiveCollab\Module\System\Utils\IncomingMail\MailToCommentMiddleware;
use ActiveCollab\Module\System\Utils\IncomingMail\MailToProjectMiddleware;
use ActiveCollab\Module\System\Utils\InlineImageDetailsResolver\InlineImageDetailsResolver;
use ActiveCollab\Module\System\Utils\InlineImageDetailsResolver\InlineImageDetailsResolverInterface;
use ActiveCollab\Module\System\Utils\JobsThrottler\JobsThrottleInterface;
use ActiveCollab\Module\System\Utils\JobsThrottler\JobsThrottler;
use ActiveCollab\Module\System\Utils\JwtTokenIssuer\FileAccessIntentIssuer;
use ActiveCollab\Module\System\Utils\JwtTokenIssuer\JwtTokenIssuerInterface;
use ActiveCollab\Module\System\Utils\MessagesTransformator\MessagesTransformator;
use ActiveCollab\Module\System\Utils\MessagesTransformator\MessagesTransformatorInterface;
use ActiveCollab\Module\System\Utils\NotificationRecipientsCleaner\NotificationRecipientsCleaner;
use ActiveCollab\Module\System\Utils\NotificationRecipientsCleaner\NotificationRecipientsCleanerInterface;
use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolver;
use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;
use ActiveCollab\Module\System\Utils\ProjectTemplateDuplicator\ProjectTemplateDuplicator;
use ActiveCollab\Module\System\Utils\ProjectTemplateDuplicator\ProjectTemplateDuplicatorInterface;
use ActiveCollab\Module\System\Utils\ProjectToTemplateConverter\ProjectToTemplateConverter;
use ActiveCollab\Module\System\Utils\ProjectToTemplateConverter\ProjectToTemplateConverterInterface;
use ActiveCollab\Module\System\Utils\PushNotification\PushNotificationPayloadTransformer;
use ActiveCollab\Module\System\Utils\PushNotification\PushNotificationScheduleMatcher;
use ActiveCollab\Module\System\Utils\PushNotification\PushNotificationScheduleMatcherInterface;
use ActiveCollab\Module\System\Utils\PushNotification\PushNotificationService;
use ActiveCollab\Module\System\Utils\PushNotification\PushNotificationServiceInterface;
use ActiveCollab\Module\System\Utils\PushNotification\PushScheduleDaysOffResolver;
use ActiveCollab\Module\System\Utils\PushNotification\UserDeviceManager;
use ActiveCollab\Module\System\Utils\PushNotification\UserDeviceManagerInterface;
use ActiveCollab\Module\System\Utils\PushNotificationJobDispatcher\PushNotificationJobDispatcher;
use ActiveCollab\Module\System\Utils\PushNotificationJobDispatcher\PushNotificationJobDispatcherInterface;
use ActiveCollab\Module\System\Utils\ReorderService\ReorderService;
use ActiveCollab\Module\System\Utils\ReorderService\ReorderServiceInterface;
use ActiveCollab\Module\System\Utils\Sockets\PusherSocket;
use ActiveCollab\Module\System\Utils\Sockets\PusherSocketInterface;
use ActiveCollab\Module\System\Utils\Storage\StorageOverusedNotifier\StorageOverusedNotifier;
use ActiveCollab\Module\System\Utils\Storage\StorageOverusedNotifier\StorageOverusedNotifierInterface;
use ActiveCollab\Module\System\Utils\SubscriptionCleaner\SubscriptionCleaner;
use ActiveCollab\Module\System\Utils\SubscriptionCleaner\SubscriptionCleanerInterface;
use ActiveCollab\Module\System\Utils\UserBadgeCountNotifier\UserBadgeCountNotifier;
use ActiveCollab\Module\System\Utils\UserBadgeCountNotifier\UserBadgeCountNotifierInterface;
use ActiveCollab\Module\System\Utils\UsersBadgeCountThrottler\UsersBadgeCountThrottler;
use ActiveCollab\Module\System\Utils\UsersBadgeCountThrottler\UsersBadgeCountThrottlerInterface;
use ActiveCollab\Module\System\Utils\UsersDisplayNameResolver\UsersDisplayNameResolver;
use ActiveCollab\Module\System\Utils\UsersDisplayNameResolver\UsersDisplayNameResolverInterface;
use ActiveCollab\Module\System\Utils\VisibleCompanyIdsResolver\VisibleCompanyIdsResolver;
use ActiveCollab\Module\System\Utils\VisibleCompanyIdsResolver\VisibleCompanyIdsResolverInterface;
use ActiveCollab\Module\System\Utils\VisibleUserIdsResolver\VisibleUserIdsResolver;
use ActiveCollab\Module\System\Utils\VisibleUserIdsResolver\VisibleUserIdsResolverInterface;
use ActiveCollab\Module\System\Utils\Webhooks\Resolver\RealTimeUsersChannelsResolver;
use ActiveCollab\Module\System\Utils\Webhooks\Resolver\RealTimeUsersChannelsResolverInterface;
use ActiveCollab\Module\System\Utils\Webhooks\Transformator\PusherSocketPayloadPartialTransformator;
use ActiveCollab\Module\System\Utils\Webhooks\Transformator\PusherSocketPayloadTransformator;
use Angie\Authentication\RequestProcessor\ShepherdRequestProcessor;
use Angie\FeatureFlags\FeatureFlagsInterface;
use Angie\Inflector;
use Angie\Mailer;
use Angie\Memories\MemoriesWrapperInterface;
use Angie\Notifications\NotificationsInterface;
use Angie\Storage\Capacity\StorageCapacityCalculatorInterface;
use Angie\Utils\OnDemandStatus\OnDemandStatusInterface;
use function DI\get;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [
    UrlExpanderInterface::class => get(UrlExpander::class),
    InlineImageDetailsResolverInterface::class => get(InlineImageDetailsResolver::class),
    ProjectTemplateDuplicatorInterface::class => get(ProjectTemplateDuplicator::class),
    ProjectTemplateDependencyResolverInterface::class => function () {
        return new ProjectTemplateDependencyResolver(
            AngieApplication::authentication()->getAuthenticatedUser(),
        );
    },
    DefaultCurrencyResolverInterface::class => get(DefaultCurrencyResolver::class),

    // @TODO: Remove dependencies that AngieApplication provides.
    BodyProcessorResolverInterface::class => function (ContainerInterface $c) {
        return new BodyProcessorResolver(
            $c->get(DataObjectPoolInterface::class),
            AngieApplication::authentication(),
            $c->get(RouterInterface::class),
            $c->get(RouterInterface::class),
            $c->get(UrlExpanderInterface::class),
            $c->get(InlineImageDetailsResolverInterface::class),
            $c->get(TextReplacementResolverInterface::class),
            $c->get(IgnoredDomainsResolverInterface::class),
            $c->get(RootUrlInterface::class),
            AngieApplication::log(),
        );
    },

    // @TODO: Remove dependencies that AngieApplication provides.
    StorageOverusedNotifierInterface::class => function (ContainerInterface $container) {
        return new StorageOverusedNotifier(
            $container->get(NotificationsInterface::class),
            $container->get(MemoriesWrapperInterface::class),
            AngieApplication::getContainer()->get(StorageCapacityCalculatorInterface::class),
            $container->get(CurrentTimestampInterface::class),
            $container->get(RootUrlInterface::class),
            AngieApplication::log(),
        );
    },

    TaskDateValidationResolver::class => function () {
        return new TaskDateValidationResolver(
            DateValue::makeFromString('2000-01-01'),
            DateValue::now()->addDays(365 * 20),
        );
    },
    ProjectToTemplateConverterInterface::class => get(ProjectToTemplateConverter::class),
    'panoramix' => function (ContainerInterface $container) {
        return [
            'is_enabled' => $container->get(OnDemandStatusInterface::class)->isOnDemand() && $container->get(FeatureFlagsInterface::class)->isEnabled('panoramix_enabled'),
            'url' => defined('PANORAMIX_URL') ? PANORAMIX_URL : null,
        ];
    },

    NotificationRecipientsCleanerInterface::class => get(NotificationRecipientsCleaner::class),
    UserDeviceManagerInterface::class => get(UserDeviceManager::class),
    PushNotificationServiceInterface::class => function (ContainerInterface $container) {
        return new PushNotificationService(
            $container->get(UserDeviceManagerInterface::class),
            new PushNotificationPayloadTransformer(AngieApplication::getAccountId()),
        );
    },
    JwtFactoryInterface::class => function () {
        return new LcobucciJwtFactory(
            ROOT_URL,
        );
    },
    JwtTokenIssuerInterface::class => function ($c) {
        return new FileAccessIntentIssuer(
            $c->get(JwtFactoryInterface::class),
            defined('FILE_ACCESS_TOKEN_KEY') ? (string) FILE_ACCESS_TOKEN_KEY : (string) AngieApplication::getLicenseKey(),
            AngieApplication::isOnDemand() ? JwtTokenIssuerInterface::WH_AUDIENCE : ROOT_URL,
            new DateTimeImmutable(),
            AngieApplication::isOnDemand() ? AngieApplication::getAccountId() : null,
        );
    },
    AuthorizeFileAccessServiceInterface::class => function (ContainerInterface $container) {
        return new AuthorizeFileAccessService(
            $container->get(JwtTokenIssuerInterface::class),
            new UrlFactory(
                $container->get(RouterInterface::class),
                $container->get(RootUrlInterface::class),
            ),
        );
    },

    JwtVerifierInterface::class => function () {
        return new LcobucciJwtVerifier(
            AngieApplication::isOnDemand() ? ShepherdRequestProcessor::INTENT_AUDIENCE_ACTIVECOLLAB : ROOT_URL,
        );
    },
    RealTimeUsersChannelsResolverInterface::class => function (ContainerInterface $container) {
        return new RealTimeUsersChannelsResolver(
            AngieApplication::authentication(),
            $container->get(FeatureFlagsInterface::class),
        );
    },
    PusherSocketInterface::class => function (ContainerInterface $container) {
        return new PusherSocket(
            AngieApplication::realTimeIntegrationResolver(),
            $container->get(RealTimeUsersChannelsResolverInterface::class),
            new PusherSocketPayloadTransformator(),
            new PusherSocketPayloadPartialTransformator(),
            AngieApplication::getAccountId(),
        );
    },
    DefaultSenderResolverInterface::class => function () {
        return new class() implements DefaultSenderResolverInterface {
            public function getDefaultSender(): string
            {
                return Mailer::getDefaultSender()->getEmail();
            }
        };
    },
    MailboxesSetResolverInterface::class => get(MailboxesSetResolver::class),
    ProcessorInterface::class => function (ContainerInterface $container) {
        $data_object_pool = $container->get(DataObjectPoolInterface::class);
        $logger = $container->get(LoggerInterface::class);

        return new Processor(
            $container->get(MailboxesSetResolverInterface::class),
            new Bouncer(
                $container->get(NotificationsInterface::class),
                $logger,
            ),
            $logger,
            new MailToProjectMiddleware(
                $data_object_pool,
                $logger,
            ),
            new MailToCommentMiddleware(
                $data_object_pool,
                $logger,
            ),
        );
    },
    OccupyAccountServiceResolverInterface::class => function (ContainerInterface $container) {
        return new OccupyAccountServiceResolver(
            $container->get(OccupyAccountServiceInterface::class),
            new OccupyAndActivateService(
                $container->get(OccupyAccountServiceInterface::class),
                AngieApplication::initialSettingsCacheInvalidator(),
                $container->get(AccountSettingsManagerInterface::class),
                AngieApplication::eventsDispatcher(),
                AngieApplication::billingDateCalculator(),
                AngieApplication::log(),
                AngieApplication::addOnFinder(),
            ),
        );
    },
    ConversationResolverInterface::class => function () {
        return new ConversationResolver();
    },
    ConversationFactoryInterface::class => function (ContainerInterface $container) {
        return new ConversationFactory(
            $container->get(ConversationResolverInterface::class),
            function (array $user_ids) {
                Users::clearCacheFor($user_ids);
            },
        );
    },
    GroupConversationAdminGeneratorInterface::class => function (ContainerInterface $container) {
        return new GroupConversationAdminGenerator($container->get(CacheInterface::class));
    },
    GroupConversationAdminServiceInterface::class => function (ContainerInterface $container) {
        return new GroupConversationAdminService(
            $container->get(EventsDispatcherInterface::class),
            $container->get(GroupConversationAdminGeneratorInterface::class),
        );
    },
    GroupConversationRenameServiceInterface::class => function (ContainerInterface $container) {
        return new GroupConversationRenameService(
            $container->get(EventsDispatcherInterface::class),
            $container->get(ConversationMessageFactoryInterface::class),
        );
    },
    GroupConversationLeaveServiceInterface::class => function (ContainerInterface $container) {
        return new GroupConversationLeaveService(
            $container->get(EventsDispatcherInterface::class),
            $container->get(GroupConversationAdminGeneratorInterface::class),
            $container->get(ConversationMessageFactoryInterface::class),
        );
    },
    GroupConversationRemoveServiceInterface::class => function (ContainerInterface $container) {
        return new GroupConversationRemoveService(
            $container->get(EventsDispatcherInterface::class),
            $container->get(GroupConversationAdminGeneratorInterface::class),
            $container->get(ConversationMessageFactoryInterface::class),
        );
    },
    GroupConversationInviteServiceInterface::class => function (ContainerInterface $container) {
        return new GroupConversationInviteService(
            $container->get(EventsDispatcherInterface::class),
            function (array $ids) {
                return Users::findByIds($ids);
            },
            AngieApplication::currentTimestamp(),
            $container->get(ConversationMessageFactoryInterface::class),
        );
    },
    ConversationMessageFactoryInterface::class => function () {
        return new ConversationMessageFactory(
            function (DataObjectLifeCycleEventInterface $event) {
                DataObjectPool::announce($event);
            },
            function (array $attributes) {
                return Messages::create($attributes);
            },
        );
    },
    ParentObjectToGroupConversationConverterInterface::class => function () {
        return new ParentObjectToGroupConversationConverter();
    },
    MessageOrderIdResolverInterface::class => function (ContainerInterface $container) {
        return new MessageOrderIdResolver(
            intval(microtime(true) * 1000),
            $container->get(ApplicationModeInterface::class)->isInTestMode(),
        );
    },
    ConversationEmailNotificationSettingsResolverInterface::class => function () {
        return new ConversationEmailNotificationSettingsResolver();
    },
    InactiveUsersResolverInterface::class => function () {
        return new InactiveUsersResolver();
    },
    UsersToNotifyAboutUnreadMessagesResolverInterface::class => function (ContainerInterface $container) {
        return new UsersToNotifyAboutUnreadMessagesResolver(
            $container->get(ConversationEmailNotificationSettingsResolverInterface::class),
            $container->get(InactiveUsersResolverInterface::class),
            function (DateTimeValue $current_time, int $unread_in_last_seconds) {
                return Conversations::getUserIdsWithUnreadMessages(
                    $current_time->advance(-1 * $unread_in_last_seconds, false),
                );
            },
        );
    },
    ConversationEmailNotificationsUpdaterInterface::class => function (ContainerInterface $container) {
        return new ConversationEmailNotificationsUpdater(
            $container->get(UsersToNotifyAboutUnreadMessagesResolverInterface::class),
            AngieApplication::jobs(),
            ENVIRONMENT_PATH,
            $container->get(AccountIdResolverInterface::class),
            AngieApplication::log(),
        );
    },
    NotificationDispatcherInterface::class => function (ContainerInterface $container) {
        return new NotificationDispatcher(
            $container->get(NotificationsInterface::class),
            $container->get(RootUrlInterface::class),
        );
    },
    ConversationNotificationDataFactoryInterface::class => function (ContainerInterface $container) {
        return new ConversationNotificationDataFactory(SystemModule::PATH);
    },
    NotifyUserAboutUnreadMessagesServiceInterface::class => function (ContainerInterface $container) {
        return new NotifyUserAboutUnreadMessagesService(
            function (IConfigContext $user) {
                return ConfigOptions::getValueFor('chat_email_notifications', $user);
            },
            $container->get(InactiveUsersResolverInterface::class),
            $container->get(NotificationDispatcherInterface::class),
            $container->get(ConversationNotificationDataFactoryInterface::class),
            AngieApplication::log(),
        );
    },
    OwnerCompanyResolverInterface::class => get(OwnerCompanyResolver::class),
    VisibleCompanyIdsResolverInterface::class => get(VisibleCompanyIdsResolver::class),
    VisibleUserIdsResolverInterface::class => get(VisibleUserIdsResolver::class),
    AccountSettingsInterface::class => function (ContainerInterface $container) {
        if ($container->get(OnDemandStatusInterface::class)->isOnDemand()) {
            return $container
                ->get(AccountSettingsManagerInterface::class)
                ->getAccountSettings();
        } else {
            return null;
        }
    },
    UsersDisplayNameResolverInterface::class => function () {
        return new UsersDisplayNameResolver();
    },
    MarkAsUnreadServiceInterface::class => function (ContainerInterface $container) {
        return new MarkAsUnreadService(
            function (ConversationUser $conversation_user, array $attributes) {
                ConversationUsers::update($conversation_user, $attributes);
            },
            $container->get(UsersBadgeCountThrottlerInterface::class),
        );
    },
    ActiveCollabCliCommandExecutorInterface::class => function ($container) {
        return new ActiveCollabCliCommandExecutor(
            AngieApplication::jobs(),
            ENVIRONMENT_PATH,
            $container->get(AccountIdResolverInterface::class),
        );
    },
    MessageMentionResolverInterface::class => function (ContainerInterface $container) {
        return new ConversationMessageMentionResolver(
            $container->get(ActiveCollabCliCommandExecutorInterface::class),
        );
    },
    ChatMessagePushNotificationDispatcherInterface::class => function (ContainerInterface $container) {
        return new ChatMessagePushNotificationDispatcher(
            $container->get(FeatureFlagsInterface::class),
            $container->get(ActiveCollabCliCommandExecutorInterface::class),
        );
    },
    PushNotificationJobDispatcherInterface::class => function (ContainerInterface $container) {
        return new PushNotificationJobDispatcher(
            $container->get(UserDeviceManagerInterface::class),
            AngieApplication::jobs(),
            $container->get(AccountIdResolverInterface::class),
        );
    },
    PushNotificationScheduleMatcherInterface::class => function () {
        return new PushNotificationScheduleMatcher(new PushScheduleDaysOffResolver());
    },
    PushNotificationUserMessageServiceInterface::class => function (ContainerInterface $container) {
        return new PushNotificationUserMessageService(
            $container->get(PushNotificationJobDispatcherInterface::class),
            $container->get(PushNotificationScheduleMatcherInterface::class),
            function (Conversation $conversation): array
            {
                return Conversations::getMutedMemberIdsFromConversation($conversation);
            },
        );
    },
    UserBadgeCountNotifierInterface::class => function (ContainerInterface $container) {
        return new UserBadgeCountNotifier(
            $container->get(UserDeviceManagerInterface::class),
            function (User $user) {
                return Messages::getUnreadMessagesCountForUser($user);
            },
            AngieApplication::jobs(),
            $container->get(AccountIdResolverInterface::class),
        );
    },
    JobsThrottleInterface::class => function () {
        return new JobsThrottler(AngieApplication::jobs());
    },
    UsersBadgeCountThrottlerInterface::class => function (ContainerInterface $container) {
        return new UsersBadgeCountThrottler(
            $container->get(UserDeviceManagerInterface::class),
            $container->get(JobsThrottleInterface::class),
            $container->get(AccountIdResolverInterface::class),
        );
    },
    MessagesTransformatorInterface::class => function () {
        return new MessagesTransformator(
            function (string $class, array $attributes): ApplicationObject
            {
                $class = Inflector::pluralize($class);

                return $class::create($attributes);
            },
        );
    },
    SubscriptionCleanerInterface::class => function () {
        return new SubscriptionCleaner(
            AngieApplication::log(),
        );
    },
    ReorderServiceInterface::class => function () {
        return new ReorderService();
    },
    ExpiredFeaturePointersCleanerInterface::class => function () {
        return new ExpiredFeaturePointersCleaner(
            AngieApplication::log(),
        );
    },
];

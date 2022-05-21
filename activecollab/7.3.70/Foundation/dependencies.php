<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation;

use ActiveCollab\Authentication\AuthenticationInterface;
use ActiveCollab\Authentication\Password\Manager\PasswordManagerInterface;
use ActiveCollab\Cookies\Cookies;
use ActiveCollab\Cookies\CookiesInterface;
use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use ActiveCollab\Encryptor\Encryptor;
use ActiveCollab\Encryptor\EncryptorInterface;
use ActiveCollab\EventsDispatcher\EventsDispatcher;
use ActiveCollab\EventsDispatcher\EventsDispatcherInterface;
use ActiveCollab\Firewall\FirewallInterface;
use ActiveCollab\Foundation\App\AccountId\AccountIdResolver;
use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use ActiveCollab\Foundation\App\Channel\OnDemandChannel;
use ActiveCollab\Foundation\App\Channel\OnDemandChannelInterface;
use ActiveCollab\Foundation\App\Mode\ApplicationMode;
use ActiveCollab\Foundation\App\Mode\ApplicationModeInterface;
use ActiveCollab\Foundation\App\Proxy\UrlBuilder\ProxyUrlBuilder;
use ActiveCollab\Foundation\App\Proxy\UrlBuilder\ProxyUrlBuilderInterface;
use ActiveCollab\Foundation\App\RootUrl\RootUrl;
use ActiveCollab\Foundation\App\RootUrl\RootUrlInterface;
use ActiveCollab\Foundation\Compile\CompiledUrlAssembler;
use ActiveCollab\Foundation\Compile\CompiledUrlMatcher;
use ActiveCollab\Foundation\Mail\MailRouter;
use ActiveCollab\Foundation\Mail\MailRouterInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Links\TextReplacement\Resolver\TextReplacementResolver;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Links\TextReplacement\Resolver\TextReplacementResolverInterface;
use ActiveCollab\Foundation\Text\HtmlToDomConverter\HtmlToDomConverter;
use ActiveCollab\Foundation\Text\HtmlToDomConverter\HtmlToDomConverterInterface;
use ActiveCollab\Foundation\Urls\ClassNameFromUrlResolver\ClassNameFromUrlResolver;
use ActiveCollab\Foundation\Urls\ClassNameFromUrlResolver\ClassNameFromUrlResolverInterface;
use ActiveCollab\Foundation\Urls\IgnoredDomainsResolver\IgnoredDomainsResolver;
use ActiveCollab\Foundation\Urls\IgnoredDomainsResolver\IgnoredDomainsResolverInterface;
use ActiveCollab\Foundation\Urls\Router\Mapper\Factory\RouteMapperFactory;
use ActiveCollab\Foundation\Urls\Router\Mapper\Factory\RouteMapperFactoryInterface;
use ActiveCollab\Foundation\Urls\Router\Mapper\RouteMapperInterface;
use ActiveCollab\Foundation\Urls\Router\Router;
use ActiveCollab\Foundation\Urls\Router\RouterInterface;
use ActiveCollab\Foundation\Urls\Router\UrlAssembler\LiveUrlAssembler;
use ActiveCollab\Foundation\Urls\Router\UrlAssembler\UrlAssemblerInterface;
use ActiveCollab\Foundation\Urls\Router\UrlMatcher\LiveUrlMatcher;
use ActiveCollab\Foundation\Urls\Router\UrlMatcher\UrlMatcherInterface;
use ActiveCollab\Foundation\Wrappers\Cache\Cache;
use ActiveCollab\Foundation\Wrappers\Cache\CacheInterface;
use ActiveCollab\Foundation\Wrappers\Cache\DefaultCacheLifetimeResolver\DefaultCacheLifetimeResolver;
use ActiveCollab\Foundation\Wrappers\Cache\DefaultCacheLifetimeResolver\DefaultCacheLifetimeResolverInterface;
use ActiveCollab\Foundation\Wrappers\Cache\DriverFactory\CacheDriverFactory;
use ActiveCollab\Foundation\Wrappers\Cache\DriverFactory\CacheDriverFactoryInterface;
use ActiveCollab\Foundation\Wrappers\Cache\PoolFactory\CachePoolFactory;
use ActiveCollab\Foundation\Wrappers\Cache\PoolFactory\CachePoolFactoryInterface;
use ActiveCollab\Foundation\Wrappers\ConfigOptions\ConfigOptions;
use ActiveCollab\Foundation\Wrappers\ConfigOptions\ConfigOptionsInterface;
use ActiveCollab\Foundation\Wrappers\DataObjectPool\DataObjectPool;
use ActiveCollab\Foundation\Wrappers\DataObjectPool\DataObjectPoolInterface;
use ActiveCollab\Module\OnDemand\Utils\Mail\MailRouter as OnDemandMailRouter;
use ActiveCollab\Module\System\EventListeners\WebhookDispatcher;
use ActiveCollab\Module\System\EventListeners\WebhookDispatcherInterface;
use Angie\Authentication\Firewall\Firewall;
use Angie\Authentication\PasswordManager\PasswordManager;
use Angie\FeatureFlags\FeatureFlags;
use Angie\FeatureFlags\FeatureFlagsInterface;
use Angie\FeatureFlags\FeatureFlagsStringResolver;
use Angie\FeatureFlags\FeatureFlagsStringResolverInterface;
use Angie\FeatureFlags\TestFeatureFlags;
use Angie\Globalization\WorkdayResolver;
use Angie\Globalization\WorkdayResolverInterface;
use Angie\Launcher\Launcher;
use Angie\Launcher\LauncherInterface;
use Angie\Memories\MemoriesWrapper;
use Angie\Memories\MemoriesWrapperInterface;
use Angie\Migrations\Migrations;
use Angie\Migrations\MigrationsInterface;
use Angie\Notifications\Notifications;
use Angie\Notifications\NotificationsInterface;
use Angie\Storage\AdapterResolver\StorageAdapterResolver;
use Angie\Storage\Capacity\StorageCapacityCalculatorInterface;
use Angie\Storage\CapacityCalculatorResolver\StorageCapacityCalculatorResolver;
use Angie\Storage\OveruseResolver\StorageOveruseResolver;
use Angie\Storage\OveruseResolver\StorageOveruseResolverInterface;
use Angie\Storage\ServicesManager\StorageServicesManagerInterface;
use Angie\Storage\ServicesManager\StorageStorageServicesManager;
use Angie\Storage\StorageAdapterInterface;
use Angie\Storage\Usage\UsedDiskSpaceCalculator;
use Angie\Storage\Usage\UsedDiskSpaceCalculatorInterface;
use Angie\Utils\ConfigReader\ConfigReader;
use Angie\Utils\ConfigReader\ConfigReaderInterface;
use Angie\Utils\ConstantResolver;
use Angie\Utils\CurrentTimestamp;
use Angie\Utils\FeatureStatusResolver\FeatureStatusResolverInterface;
use Angie\Utils\FeatureStatusResolver\OnDemandFeatureStatusResolver;
use Angie\Utils\FeatureStatusResolver\SelfHostedFeatureStatusResolver;
use Angie\Utils\OnDemandStatus\OnDemandStatus;
use Angie\Utils\OnDemandStatus\OnDemandStatusInterface;
use Angie\Utils\OnDemandStatus\Overridable\OverridableOnDemandStatus;
use Angie\Utils\SystemDateResolver\SystemDateResolver;
use Angie\Utils\SystemDateResolver\SystemDateResolverInterface;
use Angie\Utils\UserDateResolver\UserDateResolver;
use Angie\Utils\UserDateResolver\UserDateResolverInterface;
use AngieApplication;
use DB;
use DBConnection;
use function DI\get;
use Http\Client\Curl\Client;
use Integrations;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use WarehouseIntegration;
use Webhooks;

return [
    // @TODO: Switch from DBConnection to interface.
    DBConnection::class => function () {
        return DB::getConnection();
    },
    ApplicationModeInterface::class => function () {
        return new ApplicationMode(
            defined('APPLICATION_MODE')
                && in_array(APPLICATION_MODE, ApplicationModeInterface::MODES)
                ? APPLICATION_MODE
                : ApplicationModeInterface::IN_PRODUCTION,
            defined('ANGIE_IN_TEST') && ANGIE_IN_TEST
        );
    },

    OnDemandStatusInterface::class => function (ContainerInterface $container)
    {
        if ($container->get(ApplicationModeInterface::class)->isInTestMode()) {
            return new OverridableOnDemandStatus(true);
        }

        return new OnDemandStatus(defined('IS_ON_DEMAND') && IS_ON_DEMAND);
    },

    OnDemandChannelInterface::class => function (ContainerInterface $container) {
        return new OnDemandChannel(
            $container->get(ApplicationModeInterface::class),
            $container->get(OnDemandStatusInterface::class),
            new ConstantResolver('ON_DEMAND_APPLICATION_CHANNEL', false),
        );
    },
    AccountIdResolverInterface::class => get(AccountIdResolver::class),

    RootUrlInterface::class => function (ContainerInterface $container) {
        return new RootUrl(
            ROOT_URL,
            $container->get(AccountIdResolverInterface::class)
        );
    },

    // @TODO Remove global dependency on AngieApplication.
    LoggerInterface::class => function () {
        return AngieApplication::log();
    },

    FeatureFlagsStringResolverInterface::class => get(FeatureFlagsStringResolver::class),
    FeatureFlagsInterface::class => function (ContainerInterface $container) {
        return $container->get(ApplicationModeInterface::class)->isInTestMode() ?
            new TestFeatureFlags() :
            new FeatureFlags(
                $container->get(AccountIdResolverInterface::class),
                $container->get(OnDemandChannelInterface::class),
                $container->get(FeatureFlagsStringResolverInterface::class)
            );
    },

    DefaultCacheLifetimeResolverInterface::class => function () {
        return new DefaultCacheLifetimeResolver(
            new ConstantResolver(
                'CACHE_LIFETIME',
            )
        );
    },
    CacheDriverFactoryInterface::class => get(CacheDriverFactory::class),
    CachePoolFactoryInterface::class => function (ContainerInterface $container) {
        return new CachePoolFactory(
            $container->get(ApplicationModeInterface::class),
            $container->get(OnDemandStatusInterface::class),
            $container->get(AccountIdResolver::class),
            $container->get(DefaultCacheLifetimeResolverInterface::class),
            new ConstantResolver(
                [
                    'CACHE_BACKEND',
                    'CACHE_MEMCACHED_SERVERS',
                    'CACHE_PATH',
                    'REDIS_HOST',
                    'REDIS_PORT',
                ],
                false
            ),
            $container->get(CacheDriverFactoryInterface::class),
            $container->get(FeatureFlagsInterface::class),
            $container->get(LoggerInterface::class)
        );
    },
    CacheInterface::class => get(Cache::class),
    ConfigReaderInterface::class => get(ConfigReader::class),
    CurrentTimestampInterface::class => get(CurrentTimestamp::class),
    LauncherInterface::class => get(Launcher::class),
    MigrationsInterface::class => get(Migrations::class),
    NotificationsInterface::class => get(Notifications::class),
    HtmlToDomConverterInterface::class => get(HtmlToDomConverter::class),
    DataObjectPoolInterface::class => get(DataObjectPool::class),
    ConfigOptionsInterface::class => get(ConfigOptions::class),
    EventsDispatcherInterface::class => get(EventsDispatcher::class),
    WebhookDispatcherInterface::class => function (ContainerInterface $container) {
        return new WebhookDispatcher(
            function () {
                return Webhooks::findEnabled();
            },
            AngieApplication::jobs(),
            $container->get(AccountIdResolverInterface::class),
            $container->get(LoggerInterface::class),
        );
    },
    TextReplacementResolverInterface::class => get(TextReplacementResolver::class),
    IgnoredDomainsResolverInterface::class => function () {
        $ignored_domains = (string) getenv('ACTIVECOLLAB_IGNORED_DOMAINS');

        if (empty($ignored_domains)) {
            $ignored_domains = [];
        } else {
            $ignored_domains = explode(',', $ignored_domains);
        }

        return new IgnoredDomainsResolver(...$ignored_domains);
    },

    AuthenticationInterface::class => function () {
        return AngieApplication::authentication();
    },

    // @TODO Remove global dependency on AngieApplication.
    RouteMapperFactoryInterface::class => function () {
        return new RouteMapperFactory(
            ANGIE_PATH . '/frameworks',
            AngieApplication::getFrameworkNames(),
            APPLICATION_PATH . '/modules',
            AngieApplication::getModuleNames()
        );
    },
    RouteMapperInterface::class => function (ContainerInterface $c) {
        return $c->get(RouteMapperFactoryInterface::class)->createMapper();
    },
    UrlAssemblerInterface::class => function (ContainerInterface $container) {
        if ($container->get(ApplicationModeInterface::class)->isInDevelopment()) {
            return $container->get(LiveUrlAssembler::class);
        } else {
            return $container->get(CompiledUrlAssembler::class);
        }
    },
    UrlMatcherInterface::class => function (ContainerInterface $container) {
        if ($container->get(ApplicationModeInterface::class)->isInDevelopment()) {
            return $container->get(LiveUrlMatcher::class);
        } else {
            return $container->get(CompiledUrlMatcher::class);
        }
    },
    RouterInterface::class => get(Router::class),

    // @TODO Remove global dependency on DB class.
    MemoriesWrapperInterface::class => function () {
        return new MemoriesWrapper(DB::getConnection()->getLink());
    },

    MailRouterInterface::class => function (ContainerInterface $container) {
        return $container->get(OnDemandStatusInterface::class)->isOnDemand()
            ? new OnDemandMailRouter(AngieApplication::accountConfigReader())
            : new MailRouter();
    },
    SystemDateResolverInterface::class => get(SystemDateResolver::class),
    UserDateResolverInterface::class => get(UserDateResolver::class),
    WorkdayResolverInterface::class => function (ContainerInterface $container) {
        $workdays = $container->get(ConfigReaderInterface::class)->getValue('time_workdays');

        if (empty($workdays) || !is_array($workdays)) {
            $workdays = [];
        }

        $workdays = array_map('intval', $workdays);

        return new WorkdayResolver($workdays);
    },
    FeatureStatusResolverInterface::class => function (ContainerInterface $container) {
        return $container->get(OnDemandStatusInterface::class)->isOnDemand()
            ? new OnDemandFeatureStatusResolver()
            : new SelfHostedFeatureStatusResolver();
    },

    // ---------------------------------------------------
    //  Storage!
    // ---------------------------------------------------

    StorageServicesManagerInterface::class => get(StorageStorageServicesManager::class),
    UsedDiskSpaceCalculatorInterface::class => get(UsedDiskSpaceCalculator::class),
    StorageCapacityCalculatorInterface::class => function (ContainerInterface $container) {
        return (new StorageCapacityCalculatorResolver(
            $container->get(OnDemandStatusInterface::class),
            $container->get(OnDemandStatusInterface::class)->isOnDemand() ? AngieApplication::accountConfigReader() : null
        ))->getCapacityCalculator();
    },
    StorageOveruseResolverInterface::class => get(StorageOveruseResolver::class),
    StorageAdapterInterface::class => function (ContainerInterface $container) {
        return (new StorageAdapterResolver(
            $container->get(AccountIdResolverInterface::class),
            $container->get(StorageServicesManagerInterface::class),
            AngieApplication::jobs(),
            AngieApplication::log()
        ))->getByIntegration(
            Integrations::findFirstByType(WarehouseIntegration::class)
        );
    },
    ClientInterface::class => function () {
        return new Client(
            new ResponseFactory(),
            new StreamFactory(),
            [
                CURLOPT_CONNECTTIMEOUT => 3,
            ]
        );
    },
    EncryptorInterface::class => function () {
        return new Encryptor(APPLICATION_UNIQUE_KEY);
    },
    CookiesInterface::class => function (ContainerInterface $container) {
        $bits = parse_url(ROOT_URL);

        $cookies_host = empty($bits['host']) || in_array($bits['host'], ['localhost', '0.0.0.0', '127.0.0.1', 'activecollab.dev'])
            ? ''
            : $bits['host'];
        $cookies_path = empty($bits['path']) ? '/' : $bits['path'];
        $cookies_secure = isset($bits['scheme']) && $bits['scheme'] == 'https';

        return (new Cookies(
            $container->get(CurrentTimestampInterface::class),
            $container->get(EncryptorInterface::class),
        ))
            ->prefix('activecollab_')
            ->domain($cookies_host)
            ->path($cookies_path)
            ->secure($cookies_secure);
    },
    FirewallInterface::class => function (ContainerInterface $container)
    {
        /** @var ConfigOptionsInterface $configOptions */
        $configOptions = $container->get(ConfigOptionsInterface::class);

        $is_enabled = (bool) $configOptions->getValue('firewall_enabled');

        if ($container->get(OnDemandStatusInterface::class)->isOnDemand()) {
            $is_enabled = false;
        }

        $readFirewallList = function (ConfigOptionsInterface $config_options, string $config_option_name): array
        {
            $value = $config_options->getValue($config_option_name);

            if (is_string($value)) {
                $value = explode("\n", $value);
            }

            if (!is_array($value) || empty($value)) {
                $value = [];
            }

            return $value;
        };

        return new Firewall(
            $is_enabled,
            $readFirewallList($configOptions, 'firewall_white_list'),
            $readFirewallList($configOptions, 'firewall_black_list')
        );
    },
    PasswordManagerInterface::class => function () {
        return new PasswordManager(APPLICATION_UNIQUE_KEY);
    },
    ClassNameFromUrlResolverInterface::class => function () {
        return new ClassNameFromUrlResolver();
    },
    ProxyUrlBuilderInterface::class => get(ProxyUrlBuilder::class),
];

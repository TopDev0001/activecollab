<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Cookies\CookiesInterface;
use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use ActiveCollab\DatabaseConnection\Connection\MysqliConnection as DatabaseConnection;
use ActiveCollab\DateValue\DateTimeValue;
use ActiveCollab\DateValue\DateValue as ActiveCollabDateValue;
use ActiveCollab\Encryptor\Encryptor;
use ActiveCollab\Encryptor\EncryptorInterface;
use ActiveCollab\EventsDispatcher\EventsDispatcherInterface;
use ActiveCollab\Firewall\FirewallInterface;
use ActiveCollab\Foundation\App\Channel\OnDemandChannelInterface;
use ActiveCollab\Foundation\App\Mode\ApplicationModeInterface;
use ActiveCollab\Foundation\App\Proxy\UrlBuilder\ProxyUrlBuilderInterface;
use ActiveCollab\Foundation\Urls\Router\RouterInterface;
use ActiveCollab\Foundation\Wrappers\Cache\CacheInterface;
use ActiveCollab\HumanNameParser\Parser;
use ActiveCollab\JobsQueue\JobsDispatcher;
use ActiveCollab\JobsQueue\Queue\MySqlQueue as MySqlJobsQueue;
use ActiveCollab\JobsQueue\Queue\PropertyExtractors\IntPropertyExtractor;
use ActiveCollab\Logger\AppRequest\CliRequest;
use ActiveCollab\Logger\AppRequest\HttpRequest;
use ActiveCollab\Logger\AppResponse\HttpResponse;
use ActiveCollab\Logger\ErrorHandler\ErrorHandler;
use ActiveCollab\Logger\ErrorHandler\ErrorHandlerInterface;
use ActiveCollab\Logger\Factory\Factory as LoggerFactory;
use ActiveCollab\Logger\LoggerInterface;
use ActiveCollab\Module\Invoicing\Utils\InvoiceNumberSuggester\InvoiceNumberSuggesterInterface;
use ActiveCollab\Module\Invoicing\Utils\InvoicePreSendChecker\InvoicePreSendChecker as SelfHostedInvoicePreSendChecker;
use ActiveCollab\Module\Invoicing\Utils\InvoicePreSendChecker\InvoicePreSendCheckerInterface;
use ActiveCollab\Module\Invoicing\Utils\RecurringInvoicesDispatcher\RecurringInvoicesDispatcher;
use ActiveCollab\Module\Invoicing\Utils\RecurringInvoicesDispatcher\RecurringInvoicesDispatcherInterface;
use ActiveCollab\Module\Invoicing\Utils\RecurringProfilesToTriggerResolver\RecurringProfilesToTriggerResolver;
use ActiveCollab\Module\OnDemand\EventListeners\ChargableUserBalanceRecorder\ChargableUserAddBalance;
use ActiveCollab\Module\OnDemand\EventListeners\ChargableUserBalanceRecorder\ChargableUserAddBalanceInterface;
use ActiveCollab\Module\OnDemand\EventListeners\ChargableUserBalanceRecorder\ChargableUserWithdrawBalance;
use ActiveCollab\Module\OnDemand\EventListeners\ChargableUserBalanceRecorder\ChargableUserWithdrawBalanceInterface;
use ActiveCollab\Module\OnDemand\EventListeners\UserSessionsEvents;
use ActiveCollab\Module\OnDemand\Model\AddOn\AddOnInterface;
use ActiveCollab\Module\OnDemand\Models\Pricing\Discount\DiscountInterface;
use ActiveCollab\Module\OnDemand\Models\Pricing\PaidOrderResolver\PaidOrderResolver;
use ActiveCollab\Module\OnDemand\Models\Pricing\PaidOrderResolver\PaidOrderResolverInterface;
use ActiveCollab\Module\OnDemand\Models\Pricing\PerSeat2018\AddOn\AddOnFinder;
use ActiveCollab\Module\OnDemand\Models\Pricing\PricingModelInterface;
use ActiveCollab\Module\OnDemand\Models\Pricing\PricingModelResolver\PricingModelResolver;
use ActiveCollab\Module\OnDemand\Models\Pricing\PricingModelResolver\PricingModelResolverInterface;
use ActiveCollab\Module\OnDemand\Models\Pricing\SubscriptionBalanceRecorder\SubscriptionBalanceRecorderInterface;
use ActiveCollab\Module\OnDemand\OnDemandModule;
use ActiveCollab\Module\OnDemand\Utils\AccountExporter\AccountExporter;
use ActiveCollab\Module\OnDemand\Utils\AccountExporter\AccountExporterInterface;
use ActiveCollab\Module\OnDemand\Utils\AccountExporter\RecipientResolver\RecipientResolver as AccountExportRecipientResolver;
use ActiveCollab\Module\OnDemand\Utils\AccountExporter\RecipientResolver\RecipientResolverInterface as AccountExportRecipientResolverInterface;
use ActiveCollab\Module\OnDemand\Utils\AccountSettingsManager\AccountSettingsManagerInterface;
use ActiveCollab\Module\OnDemand\Utils\AddOnsManager\AddOnsManager;
use ActiveCollab\Module\OnDemand\Utils\AddOnsManager\AddOnsManagerInterface;
use ActiveCollab\Module\OnDemand\Utils\AddOnsPriceResolver\AddOnsPriceResolver;
use ActiveCollab\Module\OnDemand\Utils\AddOnsPriceResolver\AddOnsPriceResolverInterface;
use ActiveCollab\Module\OnDemand\Utils\AvailableAddOnsResolver\AvailableAddOnsResolverInterface;
use ActiveCollab\Module\OnDemand\Utils\AvailableAddOnsResolver\LifetimeAvailableAddOnsResolverInterface;
use ActiveCollab\Module\OnDemand\Utils\BillingPaymentMethodFactory\BillingPaymentMethodFactory;
use ActiveCollab\Module\OnDemand\Utils\BillingPaymentMethodFactory\BillingPaymentMethodFactoryInterface;
use ActiveCollab\Module\OnDemand\Utils\BillingPaymentMethodResolver\BillingPaymentMethodResolver;
use ActiveCollab\Module\OnDemand\Utils\ChargableUsersResolver\ChargeableUsersResolverInterface;
use ActiveCollab\Module\OnDemand\Utils\ChargeableBeforeCoronaResolver\ChargeableUsersBeforeCoronaResolverInterface;
use ActiveCollab\Module\OnDemand\Utils\FailedPaymentDaysResolver\FailedPaymentDaysResolverInterface;
use ActiveCollab\Module\OnDemand\Utils\FastSpring\TestFastSpringApiClient;
use ActiveCollab\Module\OnDemand\Utils\InvoicePreSendChecker\InvoicePreSendChecker as OnDemandInvoicePreSendChecker;
use ActiveCollab\Module\OnDemand\Utils\OrderFactory\OrderItemsFactory;
use ActiveCollab\Module\OnDemand\Utils\OrderFactory\OrderItemsFactoryInterface;
use ActiveCollab\Module\OnDemand\Utils\OrderProrationCalculator\OrderProrationCalculator;
use ActiveCollab\Module\OnDemand\Utils\OrderThankYouResolver\OrderThankYouResolver;
use ActiveCollab\Module\OnDemand\Utils\OrderThankYouResolver\OrderThankYouResolverInterface;
use ActiveCollab\Module\OnDemand\Utils\PlanComparator\PlanComparator;
use ActiveCollab\Module\OnDemand\Utils\PlanComparator\PlanComparatorInterface;
use ActiveCollab\Module\OnDemand\Utils\PlanPriceResolver\LifetimePlanPriceResolver;
use ActiveCollab\Module\OnDemand\Utils\PlanPriceResolver\PerSeatPlanPriceResolver;
use ActiveCollab\Module\OnDemand\Utils\PlanPriceResolver\PlanPriceResolver;
use ActiveCollab\Module\OnDemand\Utils\PlanPriceResolver\PlanPriceResolverInterface;
use ActiveCollab\Module\OnDemand\Utils\PlansFactory\PlansFactory;
use ActiveCollab\Module\OnDemand\Utils\PlansFactory\PlansFactoryInterface;
use ActiveCollab\Module\OnDemand\Utils\PushIntegrationConfigurator\PushIntegrationConfigurator;
use ActiveCollab\Module\OnDemand\Utils\PushIntegrationConfigurator\PushIntegrationConfiguratorInterface;
use ActiveCollab\Module\OnDemand\Utils\SubscribeToNewsletterService\SubscribeToNewsletterServiceInterface;
use ActiveCollab\Module\OnDemand\Utils\SubscriptionPricePerUserCalculator\SubscriptionPricePerUserCalculator;
use ActiveCollab\Module\OnDemand\Utils\TestVerifyPasswordResolver;
use ActiveCollab\Module\System\SystemModule;
use ActiveCollab\Module\System\Utils\InitialSettingsCacheInvalidator\InitialSettingsCacheInvalidator;
use ActiveCollab\Module\System\Utils\InitialSettingsCacheInvalidator\InitialSettingsCacheInvalidatorInterface;
use ActiveCollab\Module\System\Utils\MorningMailResolver\MorningMailResolver;
use ActiveCollab\Module\System\Utils\MorningMailResolver\MorningMailResolverInterface;
use ActiveCollab\Module\System\Utils\NewFeatures\NewFeatureAnnouncementInterface;
use ActiveCollab\Module\System\Utils\NewFeatures\NewFeatureAnnouncementsLoader\NewFeatureAnnouncementsFromFileLoader;
use ActiveCollab\Module\System\Utils\NewFeatures\NewFeaturesManager;
use ActiveCollab\Module\System\Utils\RealTimeIntegrationResolver\RealTimeIntegrationResolver;
use ActiveCollab\Module\System\Utils\RealTimeIntegrationResolver\RealTimeIntegrationResolverInterface;
use ActiveCollab\Module\System\Utils\Sockets\PusherSocketInterface;
use ActiveCollab\Module\System\Utils\Sockets\SocketsDispatcher;
use ActiveCollab\Module\System\Utils\Sockets\SocketsDispatcherInterface;
use ActiveCollab\Module\Tasks\Utils\CheckCyclicDependencyResolver\CheckCyclicDependencyResolver;
use ActiveCollab\Module\Tasks\Utils\CheckCyclicDependencyResolver\CheckCyclicDependencyResolverInterface;
use ActiveCollab\Module\Tasks\Utils\DatesRescheduleCalculator\DatesRescheduleCalculator;
use ActiveCollab\Module\Tasks\Utils\DatesRescheduleCalculator\DatesRescheduleCalculatorInterface;
use ActiveCollab\Module\Tasks\Utils\DependencyChainsManager\DependencyChainsManager;
use ActiveCollab\Module\Tasks\Utils\DependencyChainsManager\DependencyChainsManagerInterface;
use ActiveCollab\Module\Tasks\Utils\DirectAcyclicGraphFactory\DirectAcyclicGraphFactory;
use ActiveCollab\Module\Tasks\Utils\DirectAcyclicGraphFactory\DirectAcyclicGraphFactoryInterface;
use ActiveCollab\Module\Tasks\Utils\ScheduleDependenciesChainsService\ScheduleDependenciesChainsService;
use ActiveCollab\Module\Tasks\Utils\ScheduleDependenciesChainsService\ScheduleDependenciesChainsServiceInterface;
use ActiveCollab\Module\Tasks\Utils\SkipWorkingDaysResolver\SkipWorkingDaysResolver;
use ActiveCollab\Module\Tasks\Utils\TaskDateRescheduler\SkippableTaskDatesCorrector;
use ActiveCollab\Module\Tasks\Utils\TaskDateRescheduler\TaskDateRescheduler;
use ActiveCollab\Module\Tasks\Utils\TaskDateRescheduler\TaskDateReschedulerInterface;
use ActiveCollab\Module\Tasks\Utils\TaskDateRescheduler\TaskDatesManipulator;
use ActiveCollab\Module\Tasks\Utils\TaskDependenciesRescheduleSimulator;
use ActiveCollab\Module\Tasks\Utils\TaskDependenciesResolver\TaskDependenciesResolver;
use ActiveCollab\Module\Tasks\Utils\TaskDependenciesResolver\TaskDependenciesResolverInterface;
use ActiveCollab\Module\Tasks\Utils\TaskDependencyNotificationDispatcher\TaskDependencyNotificationDispatcher;
use ActiveCollab\Module\Tasks\Utils\TaskDependencyNotificationDispatcher\TaskDependencyNotificationDispatcherInterface;
use ActiveCollab\ShepherdAccountConfig\Cypher\OpenSSLAES256CBCDecryptor;
use ActiveCollab\ShepherdAccountConfig\Cypher\OpenSSLAES256CBCEncryptor;
use ActiveCollab\ShepherdAccountConfig\Utils\MySqlAdapter;
use ActiveCollab\ShepherdAccountConfig\Utils\ShepherdAccountConfig;
use ActiveCollab\ShepherdAccountConfig\Utils\ShepherdAccountConfigInterface;
use ActiveCollab\ShepherdSDK\Api\Accounts\AccountsApi;
use ActiveCollab\ShepherdSDK\Api\Accounts\AccountsApiInterface;
use ActiveCollab\ShepherdSDK\Api\Users\UsersApi;
use ActiveCollab\ShepherdSDK\Client;
use ActiveCollab\ShepherdSDK\Token;
use ActiveCollab\ShepherdSDK\Utils\UrlCreator\UrlCreator;
use Angie\Authentication;
use Angie\Authentication\AuthorizationIntegrationLocator\AuthorizationIntegrationLocator;
use Angie\Authentication\BruteForceProtector\BruteForceProtector;
use Angie\Authentication\BruteForceProtector\BruteForceProtectorInterface;
use Angie\Authentication\Repositories\UsersRepository;
use Angie\Authentication\SecurityLog\EventHandlers\AuthorizationFailedEventHander;
use Angie\Authentication\SecurityLog\EventHandlers\AuthorizedEventHander;
use Angie\Authentication\SecurityLog\EventHandlers\DeauthenticationEventHander;
use Angie\Authentication\SecurityLog\EventHandlers\UserSetEventHander;
use Angie\Authentication\SecurityLog\SecurityLog;
use Angie\Authentication\SecurityLog\SecurityLogInterface;
use Angie\AutoUpgrade;
use Angie\Error;
use Angie\Events;
use Angie\FeatureFlags\FeatureFlagsInterface;
use Angie\Features\FeatureFactory;
use Angie\Features\FeatureFactoryInterface;
use Angie\Http\RequestFactory;
use Angie\Http\RequestHandler\RequestHandler;
use Angie\Http\Response;
use Angie\Inflector;
use Angie\Launcher\LauncherInterface;
use Angie\Memories\MemoriesWrapperInterface;
use Angie\Migrations\MigrationsInterface;
use Angie\ModuleFactory\ModuleFactory;
use Angie\Modules\AngieFramework;
use Angie\Notifications\NotificationsInterface;
use Angie\Search\Adapter\Disabled;
use Angie\Search\Adapter\Queued;
use Angie\Search\AdapterFactory\SearchAdapterFactory;
use Angie\Search\HostsResolver\HostsResolver;
use Angie\Search\HostsResolver\TestHostsResolver;
use Angie\Search\SearchEngine;
use Angie\Search\SearchEngineInterface;
use Angie\Search\SearchIndexResolver\MultiTenantIndexResolver;
use Angie\Search\SearchIndexResolver\SearchIndexResolverInterface;
use Angie\Search\SearchIndexResolver\SingleTenantIndexResolver;
use Angie\Storage\StorageAdapterInterface;
use Angie\Storage\Usage\UsedDiskSpaceCalculatorInterface;
use Angie\Utils\AccountConfigReader\AccountConfigReaderInterface;
use Angie\Utils\AccountConfigReader\DatabaseConfigReader;
use Angie\Utils\AccountConfigReader\TestConfigReader;
use Angie\Utils\ConstantResolver;
use Angie\Utils\ConstantResolverInterface;
use Angie\Utils\CurrentTimestamp;
use Angie\Utils\OnDemandStatus\OnDemandStatusInterface;
use Angie\Utils\OnDemandStatus\Overridable\OverridableOnDemandStatusInterface;
use DI\ContainerBuilder;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Notifications as NotificationsManager;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

final class AngieApplication
{
    public const STABLE_CHANNEL = 0;
    public const BETA_CHANNEL = 1;
    public const EDGE_CHANNEL = 2;

    // Api token variable name
    public const API_TOKEN_HEADER_NAME = 'HTTP_X_ANGIE_AUTHAPITOKEN';

    // ---------------------------------------------------
    //  Meta information
    // ---------------------------------------------------

    /**
     * Return application name.
     *
     * @return string
     */
    public static function getName()
    {
        return 'ActiveCollab';
    }

    /**
     * Return application name.
     *
     * @return string
     */
    public static function getUrl()
    {
        return 'https://activecollab.com';
    }

    /**
     * Return application version.
     *
     * @return string
     */
    public static function getVersion()
    {
        return APPLICATION_VERSION;
    }

    /**
     * Return build number.
     *
     * @return string
     */
    public static function getBuild()
    {
        return APPLICATION_BUILD == '%APPLICATION-BUILD%' ? 'DEV' : APPLICATION_BUILD;
    }

    /**
     * Return vendor name.
     */
    public static function getVendor()
    {
        return 'A51';
    }

    /**
     * Return license key.
     *
     * @return string
     */
    public static function getLicenseKey()
    {
        return LICENSE_KEY;
    }

    /**
     * Cached account ID.
     *
     * @var int
     */
    private static $account_id = false;

    public static function getAccountId(): int
    {
        if (self::$account_id === false) {
            if (self::isOnDemand()) {
                self::$account_id = (int) ON_DEMAND_INSTANCE_ID;
            } else {
                self::$account_id = (int) explode('/', self::getLicenseKey())[1];
            }

            if (empty(self::$account_id) && self::isInTestMode()) {
                self::$account_id = 145040;
            }
        }

        return self::$account_id;
    }

    public static function setAccountId(int $account_id): void
    {
        if (!self::getContainer()->get(ApplicationModeInterface::class)->isInTestMode()) {
            throw new RuntimeException('Account ID can be set using this method only in test mode.');
        }

        self::$account_id = $account_id;
    }

    /**
     * Cached account creation date.
     *
     * @var int
     */
    private static $account_created_at = null;

    /**
     * Return account creation date.
     *
     * @return DateTimeValue
     */
    public static function getAccountCreatedAt()
    {
        if (empty(self::$account_created_at)) {
            if ($created_at = DB::executeFirstCell(
                'SELECT created_on FROM users WHERE type = ? AND is_archived = ? AND is_trashed = ? AND id = ?',
                Owner::class,
                false,
                false,
                1,
            )) {
                self::$account_created_at = new DateTimeValue($created_at);
            } elseif ($created_at = DB::executeFirstCell(
                'SELECT MIN(created_on) FROM activity_logs',
            )) {
                self::$account_created_at = new DateTimeValue($created_at);
            }
        }

        return self::$account_created_at;
    }

    /**
     * Return license agreement URL.
     *
     * @return string
     */
    public static function getLicenseAgreementUrl()
    {
        return 'https://activecollab.com/terms-selfhosted';
    }

    /**
     * Return anonymous usage stats.
     *
     * @return array|bool
     */
    public static function getStats(DateValue $date = null)
    {
        if (empty($date)) {
            $date = DateValue::now();
        }

        $stats = [];

        Events::trigger('on_extra_stats', [&$stats, $date]);

        if (self::isOnDemand()) {
            OnDemand::enrichStats($stats, $date);
        }

        return $stats;
    }

    // ---------------------------------------------------
    //  Bootstrapping
    // ---------------------------------------------------

    /**
     * Load system so it can properly handle HTTP request.
     */
    public static function bootstrapForHttpRequest()
    {
        self::initFrameworks();
        self::initModules();

        self::initEnvironment();
        self::initErrorHandler();

        if (!self::isInstalled()) {
            self::initInstaller();

            return;
        }

        self::initDatabaseConnection();
        self::initEventsManager();
    }

    /**
     * Returns true if $version is a valid angie application version number.
     *
     * @param  string $version
     * @return bool
     */
    public static function isValidVersionNumber($version)
    {
        if (strpos($version, '.') !== false) {
            $parts = explode('.', $version);

            if (count($parts) == 3) {
                foreach ($parts as $part) {
                    if (!is_numeric($part)) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Load system so it can properly handle CLI request (scheduled task etc).
     */
    public static function bootstrapForCommandLineRequest()
    {
        self::initFrameworks();
        self::initModules();

        self::initEnvironment();
        self::initErrorHandler();

        self::log()->setAppRequest(new CliRequest(self::getAccountId(), $_SERVER['argv']));

        self::initDatabaseConnection();

        self::initEventsManager();
    }

    /**
     * Bootstrap when ActiveCollab is not installed.
     */
    public static function bootstrapForInstallation()
    {
        self::initFrameworks();
        self::initModules();

        self::initEnvironment(false);
    }

    public static function bootstrapForAutoLoad(): void
    {
        self::initFrameworks();
        self::initModules();
    }

    public static function bootstrapForUnitTest(): void
    {
        self::initFrameworks();
        self::initModules();

        // Make sure that application is bootstrapped in on-demand mode.
        self::setAccountId(145040);

        // Bootstrap the application.
        self::initEnvironment();
        self::initDatabaseConnection();
        //self::initModelForTest();
        self::initEventsManager();

        self::cache()->clear();
    }

    public static function bootstrapForTest(string $pricing_model = 'plans_2013'): void
    {
        self::initFrameworks();
        self::initModules();

        // Make sure that application is bootstrapped in on-demand mode.
        self::setAccountId(145040);

        // Bootstrap the application.
        self::initEnvironment();
        self::initDatabaseConnection();
        self::initModelForTest();
        self::initEventsManager();

        self::cache()->clear();

        if ($pricing_model !== 'plans_2013') {
            throw new LogicException('Please update AngieApplication::bootstrapForTest() to support your pricing model.');
        }

        require_once dirname(ROOT) . '/tests/phpunit/fixtures/TestShepherdAccountConfig.php';

        self::setShepherdAccountConfig(
            new TestShepherdAccountConfig(),
        );
    }

    /**
     * @var ContainerInterface
     */
    private static $container;

    public static function getContainer(): ContainerInterface
    {
        if (empty(self::$container)) {
            if (empty(self::$frameworks_initialized) || empty(self::$modules_initialized)) {
                throw new RuntimeException('Container can be constructed only after frameworks and modules are initialied.');
            }

            $definition_files = [
                APPLICATION_PATH . '/Foundation/dependencies.php',
            ];

            foreach (self::$loaded_frameworks_and_modules as $framework_or_module) {
                $definition_file = $framework_or_module->getPath() . '/resources/dependencies.php';

                if (is_file($definition_file)) {
                    $definition_files[] = $definition_file;
                }
            }

            $container_builder = new ContainerBuilder();

            if (!empty($definition_files)) {
                $container_builder->addDefinitions(...$definition_files);
            }

            self::$container = $container_builder->build();
        }

        return self::$container;
    }

    public static function setContainer(?ContainerInterface $container): void
    {
        self::$container = $container;
    }

    /**
     * Initialize PHP environment.
     *
     * @param bool $register_shutdown_function
     */
    public static function initEnvironment($register_shutdown_function = true)
    {
        // CLI can start the session earlier, let's avoid warnings
        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }

        set_include_path('');
        error_reporting(E_ALL);

        ini_set('display_errors', self::isInProduction() ? 0 : 1);

        if ($register_shutdown_function) {
            register_shutdown_function(['AngieApplication', 'shutdown']);
        }
    }

    /**
     * @var ErrorHandlerInterface
     */
    private static $error_handler;

    /**
     * Init error handler.
     */
    public static function initErrorHandler()
    {
        if (empty(self::$error_handler)) {
            self::$error_handler = (new ErrorHandler(self::log()))
                ->setHowToHandleError(E_STRICT, ErrorHandlerInterface::SILENCE)
                ->initialize();
        }
    }

    /**
     * Initialize database connection.
     */
    public static function initDatabaseConnection()
    {
        try {
            DB::setConnection('default', new MySQLDBConnection(DB_HOST, DB_USER, DB_PASS, DB_NAME));
        } catch (Exception $e) {
            if (!self::isInProduction()) {
                throw $e;
            }

            trigger_error('Failed to connect to database');
        }
    }

    /**
     * @var Smarty
     */
    private static $smarty;

    /**
     * @return Smarty
     */
    public static function &getSmarty()
    {
        if (empty(self::$smarty)) {
            self::$smarty = new Smarty();

            self::$smarty->setCompileDir(COMPILE_PATH);
            self::$smarty->setCacheDir(ENVIRONMENT_PATH . '/cache');
            self::$smarty->compile_check = true;
            self::$smarty->registerFilter('variable', 'clean'); // {$foo nofilter}
        }

        return self::$smarty;
    }

    /**
     * Initialize application model for test.
     */
    public static function initModelForTest(): void
    {
        if (AngieApplicationModel::isEmpty()) {
            AngieApplicationModel::load(self::getFrameworkNames(), self::getModuleNames());
        }

        AngieApplicationModel::clear(true);
        AngieApplicationModel::init('test');
    }

    /**
     * Array of loaded frameworks and modules.
     *
     * @var AngieFramework[]|AngieModule[]
     */
    private static $loaded_frameworks_and_modules = [];

    /**
     * Flag that is set to true when frameworks are initialized.
     *
     * @var bool
     */
    private static $frameworks_initialized = false;

    /**
     * Flag that is set to true when modules are initialized.
     *
     * @var bool
     */
    private static $modules_initialized = false;

    public static function initFrameworks(): void
    {
        if (!empty(self::$frameworks_initialized)) {
            return;
        }

        foreach (self::getFrameworks() as $framework) {
            self::$loaded_frameworks_and_modules[$framework->getName()] = $framework; // Set as loaded before we call init.php

            $framework->init();

            self::getSmarty()->addPluginsDir([$framework->getPath() . '/helpers']);
        }

        self::$frameworks_initialized = true;
    }

    public static function initModules(): void
    {
        if (!empty(self::$modules_initialized)) {
            return;
        }

        foreach (self::getModules() as $module) {
            self::$loaded_frameworks_and_modules[$module->getName()] = $module; // Set as loaded before we call init.php

            $module->init();

            self::getSmarty()->addPluginsDir([$module->getPath() . '/helpers']);
        }

        self::$modules_initialized = true;
    }

    public static function getFrameworkNames(): array
    {
        return [
            'environment',
            'history',
            'email',
            'attachments',
            'notifications',
            'subscriptions',
            'labels',
            'payments',
            'reminders',
            'calendars',
        ];
    }

    public static function getModuleNames(): array
    {
        $result = [
            'system',
            'discussions',
            'files',
            'invoicing',
            'tasks',
            'notes',
            'tracking',
        ];

        if ((defined('IS_ON_DEMAND') && IS_ON_DEMAND)
            || (defined('ANGIE_IN_TEST') && ANGIE_IN_TEST)) {
            $result[] = 'source';
            $result[] = 'on_demand';
        }

        return $result;
    }

    public static function initEventsManager()
    {
        foreach (self::$frameworks as $framework) {
            $framework->defineHandlers();

            foreach ($framework->defineListeners() as $event_type => $listener) {
                self::eventsDispatcher()->listen($event_type, $listener);
            }
        }

        foreach (self::$modules as $module) {
            $module->defineHandlers();

            foreach ($module->defineListeners() as $event_type => $listener) {
                self::eventsDispatcher()->listen($event_type, $listener);
            }
        }
    }

    public static function includeCoreInstallerFiles()
    {
        require_once ANGIE_PATH . '/classes/application/installer/AngieApplicationInstaller.class.php';
        require_once ANGIE_PATH . '/classes/application/installer/AngieApplicationInstallerAdapter.class.php';
    }

    /**
     * Initialize installer.
     *
     * @param string $adapter_class
     * @param string $adapter_class_path
     */
    public static function initInstaller($adapter_class = null, $adapter_class_path = null)
    {
        self::includeCoreInstallerFiles();
        AngieApplicationInstaller::init($adapter_class, $adapter_class_path);
    }

    public static function cache(): CacheInterface
    {
        return self::getContainer()->get(CacheInterface::class);
    }

    public static function launcher(): LauncherInterface
    {
        return self::getContainer()->get(LauncherInterface::class);
    }

    public static function notifications(): NotificationsInterface
    {
        return self::getContainer()->get(NotificationsInterface::class);
    }

    public static function migration(): MigrationsInterface
    {
        return self::getContainer()->get(MigrationsInterface::class);
    }

    /**
     * @var JobsDispatcher
     */
    private static $jobs_dispatcher;

    /**
     * Connection to global jobs queue. It is closed on shutdown (AngieApplication::shutdown()).
     *
     * @var MySQLi
     */
    private static $global_job_queue_connection;

    /**
     * Interface to jobs dispatcher.
     *
     * @return JobsDispatcher
     */
    public static function &jobs()
    {
        if (empty(self::$jobs_dispatcher)) {
            if (self::isOnDemand() &&
                defined('ACTIVECOLLAB_JOB_CONSUMER_MYSQL_HOST') && defined('ACTIVECOLLAB_JOB_CONSUMER_MYSQL_USER') &&
                defined('ACTIVECOLLAB_JOB_CONSUMER_MYSQL_PASS') && defined('ACTIVECOLLAB_JOB_CONSUMER_MYSQL_NAME')) {
                self::$global_job_queue_connection = new MySQLi(
                    ACTIVECOLLAB_JOB_CONSUMER_MYSQL_HOST,
                    ACTIVECOLLAB_JOB_CONSUMER_MYSQL_USER,
                    ACTIVECOLLAB_JOB_CONSUMER_MYSQL_PASS,
                    ACTIVECOLLAB_JOB_CONSUMER_MYSQL_NAME,
                );

                if (self::$global_job_queue_connection->connect_error) {
                    throw new RuntimeException('Failed to connect to database. MySQL said: ' . self::$global_job_queue_connection->connect_error);
                }

                self::$global_job_queue_connection->query('SET NAMES utf8mb4');

                $connection = new DatabaseConnection(self::$global_job_queue_connection);
            } elseif (defined('GLOBAL_JOBS_QUEUE_HOST') && defined('GLOBAL_JOBS_QUEUE_USER') && defined('GLOBAL_JOBS_QUEUE_PASS') && defined('GLOBAL_JOBS_QUEUE_NAME')) {
                self::$global_job_queue_connection = new MySQLi(GLOBAL_JOBS_QUEUE_HOST, GLOBAL_JOBS_QUEUE_USER, GLOBAL_JOBS_QUEUE_PASS, GLOBAL_JOBS_QUEUE_NAME);

                if (self::$global_job_queue_connection->connect_error) {
                    throw new RuntimeException('Failed to connect to database. MySQL said: ' . self::$global_job_queue_connection->connect_error);
                }

                self::$global_job_queue_connection->query('SET NAMES utf8mb4');

                $connection = new DatabaseConnection(self::$global_job_queue_connection);
            } else {
                $connection = new DatabaseConnection(DB::getConnection()->getLink());
            }

            $mysql_queue = new MySqlJobsQueue(
                $connection,
                [
                    new IntPropertyExtractor('instance_id'),
                    new IntPropertyExtractor('webhook_id'),
                ],
                false,
            );

            self::$jobs_dispatcher = new JobsDispatcher($mysql_queue);
            self::$jobs_dispatcher->registerChannels(
                SystemModule::MAINTENANCE_JOBS_QUEUE_CHANNEL,
                EmailIntegration::JOBS_QUEUE_CHANNEL,
                SearchIntegration::JOBS_QUEUE_CHANNEL,
                WebhooksIntegration::JOBS_QUEUE_CHANNEL,
                RealTimeIntegrationInterface::JOBS_QUEUE_CHANNEL,
                RealTimeIntegrationInterface::CHAT_JOBS_QUEUE_CHANNEL,
                AbstractImporterIntegration::DOWNLOAD_FILE_CHANNEL,
                AbstractImporterIntegration::MIGRATION_CHANNEL,
                PushNotificationChannel::CHANNEL_NAME,
            );

            if (self::isOnDemand()) {
                self::$jobs_dispatcher->registerChannel(OnDemandModule::STATS_JOBS_QUEUE_CHANNEL);
            }
        }

        return self::$jobs_dispatcher;
    }

    /**
     * Return a connection that is connected to jobs queue.
     *
     * @return DatabaseConnection
     */
    public static function jobsConnection()
    {
        self::jobs(); // Make sure that we open a connection

        if (self::isOnDemand() &&
            defined('ACTIVECOLLAB_JOB_CONSUMER_MYSQL_HOST') && defined('ACTIVECOLLAB_JOB_CONSUMER_MYSQL_USER') &&
            defined('ACTIVECOLLAB_JOB_CONSUMER_MYSQL_PASS') && defined('ACTIVECOLLAB_JOB_CONSUMER_MYSQL_NAME')) {
            return new DatabaseConnection(self::$global_job_queue_connection);
        }

        if (defined('GLOBAL_JOBS_QUEUE_HOST') && defined('GLOBAL_JOBS_QUEUE_USER') && defined('GLOBAL_JOBS_QUEUE_PASS') && defined('GLOBAL_JOBS_QUEUE_NAME')) {
            return new DatabaseConnection(self::$global_job_queue_connection);
        }

        return new DatabaseConnection(DB::getConnection()->getLink());
    }

    private static EventsDispatcherInterface $events_dispatcher;

    public static function eventsDispatcher(): EventsDispatcherInterface
    {
        if (empty(self::$events_dispatcher)) {
            self::$events_dispatcher = self::getContainer()->get(EventsDispatcherInterface::class);
        }

        return self::$events_dispatcher;
    }

    public static function featureFactory(): FeatureFactoryInterface
    {
        return new FeatureFactory(self::eventsDispatcher());
    }

    private static $sockets_dispatcher;

    public static function socketsDispatcher(): SocketsDispatcherInterface
    {
        if (empty(self::$sockets_dispatcher)) {
            self::$sockets_dispatcher = new SocketsDispatcher(
                self::getContainer()->get(PusherSocketInterface::class),
                self::jobs(),
                self::log(),
            );
        }

        return self::$sockets_dispatcher;
    }

    public static function memories(): MemoriesWrapperInterface
    {
        return self::getContainer()->get(MemoriesWrapperInterface::class);
    }

    /**
     * @var AutoUpgrade
     */
    private static $auto_upgrade;

    /**
     * Return auto-upgrade instance.
     *
     * @return AutoUpgrade
     */
    public static function &autoUpgrade()
    {
        if (empty(self::$auto_upgrade)) {
            self::$auto_upgrade = new AutoUpgrade(
                self::memories()->getInstance(),
                '',
                (bool) ConfigOptions::getValue('help_improve_application'),
            );
        }

        return self::$auto_upgrade;
    }

    private static ?Authentication $authentication = null;

    public static function &authentication(): Authentication
    {
        if (empty(self::$authentication)) {
            $authorization_locator = new AuthorizationIntegrationLocator(
                self::isOnDemand(),
                self::isInDevelopment(),
                self::isInTestMode(),
                self::getIsLegacyDevelopment(),
                (string) ConfigOptions::getValue('authorization_integration'),
            );

            /** @var AuthorizationIntegrationInterface $authorization_integration */
            $authorization_integration = $authorization_locator->getAuthorizationIntegration();

            self::$authentication = new Authentication($authorization_integration);

            $users_repository = new UsersRepository();

            self::$authentication
                ->onUserAuthorizationFailed(
                    new AuthorizationFailedEventHander(
                        self::securityLog(),
                        $users_repository,
                    ), )
                ->onUserAuthorized(new AuthorizedEventHander(self::securityLog()))
                ->onUserDeauthenticated(
                    new DeauthenticationEventHander(
                        self::securityLog(),
                        $users_repository,
                    ),
                )
                ->onUserSet(new UserSetEventHander());
        }

        return self::$authentication;
    }

    /**
     * Return true if authentication is loaded.
     *
     * @return bool
     */
    public static function isAuthenticationLoaded()
    {
        return !empty(self::$authentication);
    }

    /**
     * Reset authentication service.
     *
     * This method is used for testing only, so we can reset auth layer between tests
     */
    public static function unsetAuthentication()
    {
        self::$authentication = null;
    }

    /**
     * @return string
     */
    public static function getSessionIdCookieName()
    {
        return 'us_for_' . sha1(ROOT_URL);
    }

    /**
     * @return string
     */
    public static function getCsrfValidatorCookieName()
    {
        return 'csrf_validator_for_' . sha1(ROOT_URL);
    }

    /**
     * @return string
     */
    public static function getLanguageCookieName()
    {
        return 'ul_for_' . sha1(ROOT_URL);
    }

    /**
     * @var SearchEngineInterface
     */
    private static $search;

    /**
     * @return SearchEngineInterface
     */
    public static function &search()
    {
        if (empty(self::$search)) {
            $tenant_id = self::getAccountId();

            /** @var SearchIntegration $search_integration */
            $search_integration = Integrations::findFirstByType(SearchIntegration::class);

            if (self::isInTestMode()) {
                $hosts_resolver = new TestHostsResolver();
            } elseif (self::isOnDemand()) {
                $hosts_resolver = new HostsResolver(
                    defined('ELASTIC_SEARCH_HOSTS_EDGE') ? ELASTIC_SEARCH_HOSTS_EDGE : '',
                );
            } else {
                $hosts_resolver = new HostsResolver((string) $search_integration->getHosts());
            }

            $hosts = $hosts_resolver->getHosts();

            $adapter_factory = new SearchAdapterFactory(
                $hosts,
                $search_integration->getShards(),
                $search_integration->getReplicas(),
                self::searchIndexResolver()->getIndexName($tenant_id),
                SearchEngineInterface::DOCUMENT_TYPE,
                $tenant_id,
                self::jobs(),
                self::log(),
            );

            if (self::isOnDemand() || !empty($hosts)) {
                $adapter_class = Queued::class;
            } else {
                $adapter_class = Disabled::class;
            }

            $adapter = $adapter_factory->produce($adapter_class);

            self::$search = new SearchEngine(
                $adapter,
                self::log(),
                self::isOnDemand(),
                self::isInDevelopment(),
            );
        }

        return self::$search;
    }

    private static $search_index_resolver = [];

    /**
     * @param  bool                         $is_on_demand
     * @return SearchIndexResolverInterface
     */
    public static function searchIndexResolver($is_on_demand = null)
    {
        if ($is_on_demand === null) {
            $is_on_demand = self::isOnDemand();
        }

        $resolver_key = $is_on_demand ? 1 : 0;

        if (empty(self::$search_index_resolver[$resolver_key])) {
            if ($is_on_demand) {
                self::$search_index_resolver[$resolver_key] = new MultiTenantIndexResolver(
                    OnDemand::getSearchIndexNames(),
                );
            } else {
                $license_key = self::getLicenseKey();

                if (empty($license_key)) {
                    $license_key = 'invalid_license_key';
                }

                self::$search_index_resolver[$resolver_key] = new SingleTenantIndexResolver(
                    'active_collab_' . strtolower(str_replace('/', '_', $license_key)),
                );
            }
        }

        return self::$search_index_resolver[$resolver_key];
    }

    /**
     * Unset search.
     */
    public static function unsetSearch()
    {
        self::$search = null;
    }

    /**
     * @return string
     */
    public static function getUserInstancesCookieName()
    {
        return 'user_instances';
    }

    /**
     * @return string
     */
    public static function getUserInstancesCookeDomain()
    {
        return '.activecollab.com';
    }

    /**
     * @var SecurityLogInterface
     */
    private static $security_logs;

    /**
     * @return SecurityLogInterface
     */
    public static function &securityLog()
    {
        if (empty(self::$security_logs)) {
            self::$security_logs = new SecurityLog();
        }

        return self::$security_logs;
    }

    /**
     * Unset security logs.
     */
    public static function unsetSecurityLog()
    {
        self::$security_logs = null;
    }

    /**
     * @var BruteForceProtectorInterface
     */
    private static $brute_force_protector;

    /**
     * @return BruteForceProtectorInterface
     */
    public static function &bruteForceProtector()
    {
        if (empty(self::$brute_force_protector)) {
            $is_enabled = ConfigOptions::getValue('brute_force_protection_enabled');

            if (self::isOnDemand()) {
                $is_enabled = false;
            }

            self::$brute_force_protector = new BruteForceProtector(
                self::securityLog(),
                $is_enabled,
                ConfigOptions::getValue('brute_force_cooldown_threshold'),
                ConfigOptions::getValue('brute_force_cooldown_lenght'),
            );
        }

        return self::$brute_force_protector;
    }

    /**
     * @var LoggerInterface
     */
    private static $logger;

    /**
     * Return logger instance.
     *
     * @return LoggerInterface
     */
    public static function &log()
    {
        if (empty(self::$logger)) {
            $factory = new LoggerFactory();
            $factory->setAdditionalEnvArguments(
                [
                    'account_id' => self::getAccountId(),
                ],
            );
            $factory->addExceptionSerializer(function ($argument_name, $exception, array &$context) {
                if ($exception instanceof Error) {
                    foreach ($exception->getParams() as $k => $v) {
                        $context["{$argument_name}_param_{$k}"] = $v;
                    }
                }
            });

            $environment = 'production';
            $logger_type = LoggerInterface::BLACKHOLE;
            $logger_arguments = [];

            if (!self::isInTestMode()) {
                if (self::isOnDemand() && !self::isInDevelopment()) {
                    if (self::getContainer()->get(OnDemandChannelInterface::class)->isEdgeChannel()) {
                        $environment = 'staging';
                    }

                    if (defined('GRAYLOG_HOST') && defined('GRAYLOG_PORT')) {
                        $logger_type = LoggerInterface::GRAYLOG;
                        $logger_arguments = [
                            GRAYLOG_HOST,
                            GRAYLOG_PORT,
                        ];
                    }
                } else {
                    if (self::isInDevelopment()) {
                        $environment = 'development';
                    }

                    if (!self::isInProduction()) {
                        $logger_type = LoggerInterface::FILE;
                        $logger_arguments = [
                            ENVIRONMENT_PATH . '/logs',
                            'log.txt',
                            0777,
                        ];
                    }
                }
            }

            $log_level = $environment === 'production' ?
                LoggerInterface::LOG_FOR_PRODUCTION :
                LoggerInterface::LOG_FOR_DEBUG;

            self::$logger = $factory->create(
                self::getName(),
                self::getVersion(),
                $environment,
                $log_level,
                $logger_type,
                ...$logger_arguments,
            );
        }

        return self::$logger;
    }

    private static ?CurrentTimestampInterface $current_timestamp = null;

    public static function currentTimestamp(): CurrentTimestampInterface
    {
        if (empty(self::$current_timestamp)) {
            self::$current_timestamp = new CurrentTimestamp();
        }

        return self::$current_timestamp;
    }

    public static function storage(): StorageAdapterInterface
    {
        return self::getContainer()->get(StorageAdapterInterface::class);
    }

    private static $shepherd_syncer;

    public static function &shepherdSyncer(): ShepherdSyncerInterface
    {
        if (empty(self::$shepherd_syncer)) {
            if (self::isInTestMode()) {
                self::$shepherd_syncer = new TestShepherdSyncer();
            } else {
                self::$shepherd_syncer = new ShepherdSyncer(
                    self::getAccountId(),
                    SHEPHERD_URL,
                    SHEPHERD_ACCESS_TOKEN,
                    self::jobs(),
                    ShepherdSyncerInterface::JOBS_CHANNEL,
                    self::shepherdUsersApi(),
                    self::log(),
                );
            }
        }

        return self::$shepherd_syncer;
    }

    /**
     * @var OnboardingSurvey
     */
    private static $onboarding_survey;

    /**
     * Return OnboardingSurvey instance.
     *
     * @param  bool                      $is_on_demand
     * @return OnboardingSurveyInterface
     */
    public static function &onboardingSurvey($is_on_demand = null)
    {
        if ($is_on_demand === null) {
            $is_on_demand = self::isOnDemand();
        }

        if (!self::$onboarding_survey) {
            self::$onboarding_survey = new OnboardingSurvey(
                self::memories()->getInstance(),
                Users::findFirstOwner()->getId(),
                $is_on_demand,
                self::currentTimestamp(),
            );
        }

        return self::$onboarding_survey;
    }

    /**
     * @var SetupWizard
     */
    private static $setup_wizard;

    /**
     * Return SetupWizard instance.
     *
     * @param  null        $is_on_demand
     * @return SetupWizard
     */
    public static function &setupWizard($is_on_demand = null)
    {
        if ($is_on_demand === null) {
            $is_on_demand = self::isOnDemand();
        }

        if (!self::$setup_wizard) {
            self::$setup_wizard = new SetupWizard(
                self::getAccountId(),
                $is_on_demand ? self::shepherdUsersApi() : null,
                $is_on_demand ? self::shepherdSyncer() : null,
                self::memories()->getInstance(),
                Users::findFirstOwner(),
                self::onboardingSurvey($is_on_demand),
                $is_on_demand,
                new Parser(),
                $is_on_demand ? AngieApplication::getContainer()->get(SubscribeToNewsletterServiceInterface::class) : null,
            );
        }

        return self::$setup_wizard;
    }

    public static function unsetSetupWizard()
    {
        self::$setup_wizard = null;
    }

    /**
     * Unset onboarding survey instance.
     */
    public static function unsetOnboardingSurvey()
    {
        self::$onboarding_survey = null;
    }

    private static $invoice_pre_send_checker;

    public static function invoicePreSendChecker(): InvoicePreSendCheckerInterface
    {
        if (empty(self::$invoice_pre_send_checker)) {
            if (self::isOnDemand()) {
                self::$invoice_pre_send_checker = new OnDemandInvoicePreSendChecker(self::log());
            } else {
                self::$invoice_pre_send_checker = new SelfHostedInvoicePreSendChecker();
            }
        }

        return self::$invoice_pre_send_checker;
    }

    private static $recurring_invoices_dispatcher;

    public static function recurringInvoicesDispatcher(): RecurringInvoicesDispatcherInterface
    {
        if (empty(self::$recurring_invoices_dispatcher)) {
            self::$recurring_invoices_dispatcher = new RecurringInvoicesDispatcher(
                new RecurringProfilesToTriggerResolver(),
                self::getContainer()->get(InvoiceNumberSuggesterInterface::class),
                self::invoicePreSendChecker(),
                self::notifications(),
            );
        }

        return self::$recurring_invoices_dispatcher;
    }

    private static $skip_working_days_resolver;

    public static function &skipWorkingDaysResolver(): callable
    {
        if (!self::$skip_working_days_resolver) {
            self::$skip_working_days_resolver = new SkipWorkingDaysResolver();
        }

        return self::$skip_working_days_resolver;
    }

    /**
     * @var TaskDateReschedulerInterface
     */
    private static $task_date_rescheduler;

    public static function taskDateRescheduler(): TaskDateReschedulerInterface
    {
        if (empty(self::$task_date_rescheduler)) {
            self::$task_date_rescheduler = new TaskDateRescheduler(
                self::taskDependenciesRescheduleSimulator(),
                self::scheduleDependenciesChainsService(),
            );
        }

        return self::$task_date_rescheduler;
    }

    public static function unsetTaskDateRescheduler()
    {
        return self::$task_date_rescheduler = null;
    }

    /**
     * @var TaskDependenciesRescheduleSimulator
     */
    private static $task_dependencies_reschedule_simulator;

    public static function taskDependenciesRescheduleSimulator(): TaskDependenciesRescheduleSimulator
    {
        if (empty(self::$task_dependencies_reschedule_simulator)) {
            self::$task_dependencies_reschedule_simulator = new TaskDependenciesRescheduleSimulator(
                self::cyclicDependencyResolver(),
                self::taskDependenciesResolver(),
                self::datesRescheduleCalculator(),
                self::directAcyclicGraphFactory(),
                function (string $object_type, array $ids) {
                    return DataObjectPool::getByIds($object_type, $ids);
                },
            );
        }

        return self::$task_dependencies_reschedule_simulator;
    }

    private static $task_dependency_notification_dispatcher;

    public static function taskDependencyNotificationDispatcher(): TaskDependencyNotificationDispatcherInterface
    {
        if (empty(self::$task_dependency_notification_dispatcher)) {
            self::$task_dependency_notification_dispatcher = new TaskDependencyNotificationDispatcher(
                function ($event, $context = null, $sender = null, $decorator = null) {
                    return self::notifications()->notifyAbout(
                        $event,
                        $context,
                        $sender,
                        $decorator,
                    );
                },
                function (ApplicationObject $parent, string $property_name, string $property_value) {
                    NotificationsManager::deleteByParentAndAdditionalProperty(
                        $parent,
                        $property_name,
                        $property_value,
                    );
                },
            );
        }

        return self::$task_dependency_notification_dispatcher;
    }

    /**
     * @var TaskDatesManipulator
     */
    private static $task_dates_manipulator;

    public static function taskDatesManipulator(): TaskDatesManipulator
    {
        if (empty(self::$task_dates_manipulator)) {
            self::$task_dates_manipulator = new TaskDatesManipulator(
                self::datesRescheduleCalculator(),
            );
        }

        return self::$task_dates_manipulator;
    }

    /**
     * @var SkippableTaskDatesCorrector
     */
    private static $skippable_task_dates_corrector;

    public static function skippableTaskDatesCorrector(): SkippableTaskDatesCorrector
    {
        if (empty(self::$skippable_task_dates_corrector)) {
            self::$skippable_task_dates_corrector = new SkippableTaskDatesCorrector(
                self::taskDatesManipulator(),
                self::skipWorkingDaysResolver(),
            );
        }

        return self::$skippable_task_dates_corrector;
    }

    /**
     * @var TaskDependenciesResolverInterface
     */
    private static $task_dependencies_resolver;

    public static function taskDependenciesResolver(?IUser $user = null)
    {
        if (empty(self::$task_dependencies_resolver)) {
            self::$task_dependencies_resolver = new TaskDependenciesResolver($user ?? Users::findFirstOwner());
        }

        return self::$task_dependencies_resolver;
    }

    private static $cyclic_dependency_resolver;

    public static function cyclicDependencyResolver(): CheckCyclicDependencyResolverInterface
    {
        if (empty(self::$cyclic_dependency_resolver)) {
            self::$cyclic_dependency_resolver = new CheckCyclicDependencyResolver();
        }

        return self::$cyclic_dependency_resolver;
    }

    private static $schedule_dependencies_chains_service;

    public static function scheduleDependenciesChainsService(): ScheduleDependenciesChainsServiceInterface
    {
        if (empty(self::$schedule_dependencies_chains_service)) {
            self::$schedule_dependencies_chains_service = new ScheduleDependenciesChainsService(
                self::dependencyChainsManager(),
                self::taskDatesManipulator(),
                function (string $object_type, int $id) {
                    return DataObjectPool::get($object_type, $id);
                },
            );
        }

        return self::$schedule_dependencies_chains_service;
    }

    public static function unsetScheduleDependenciesChainsService()
    {
        return self::$schedule_dependencies_chains_service = null;
    }

    private static $direct_acyclic_graph_factory;

    public static function directAcyclicGraphFactory(): DirectAcyclicGraphFactoryInterface
    {
        if (!self::$direct_acyclic_graph_factory) {
            self::$direct_acyclic_graph_factory = new DirectAcyclicGraphFactory();
        }

        return self::$direct_acyclic_graph_factory;
    }

    private static $dependency_chains_manager;

    public static function dependencyChainsManager(): DependencyChainsManagerInterface
    {
        if (!self::$dependency_chains_manager) {
            self::$dependency_chains_manager = new DependencyChainsManager(
                self::taskDependenciesResolver(),
                self::directAcyclicGraphFactory(),
                function (string $object_type, array $ids) {
                    DataObjectPool::getByIds($object_type, $ids);
                },
                function (string $object_type, int $id) {
                    return DataObjectPool::get($object_type, $id);
                },
            );
        }

        return self::$dependency_chains_manager;
    }

    /**
     * @var MorningMailResolverInterface
     */
    private static $morning_mail_resolver;

    public static function morningMailResolver(): MorningMailResolverInterface
    {
        if (empty(self::$morning_mail_resolver)) {
            self::$morning_mail_resolver = new MorningMailResolver(
                self::isOnDemand(),
                self::isOnDemand() ? self::accountSettings() : null,
            );
        }

        return self::$morning_mail_resolver;
    }

    public static function currentUsage(): CurrentUsageInterface
    {
        return new CurrentUsage(
            self::getContainer()->get(UsedDiskSpaceCalculatorInterface::class),
            self::getContainer()->get(ChargeableUsersResolverInterface::class),
            self::accountConfigReader()->getPlan(),
            self::accountConfigReader()->getMaxDiskSpace(),
        );
    }

    /**
     * @var PricingModelResolverInterface
     */
    private static $pricing_model_resolver;

    public static function pricingModelResolver(): PricingModelResolverInterface
    {
        if (empty(self::$pricing_model_resolver)) {
            self::$pricing_model_resolver = new PricingModelResolver(
                self::getContainer()->get(ChargeableUsersResolverInterface::class),
                self::planPriceResolver(),
                self::getContainer()->get(AvailableAddOnsResolverInterface::class),
                self::getContainer()->get(LifetimeAvailableAddOnsResolverInterface::class),
            );
        }

        return self::$pricing_model_resolver;
    }

    /**
     * @param  null                     $is_on_demand
     * @return AccountSettingsInterface
     */
    public static function accountSettings($is_on_demand = null)
    {
        return self::getContainer()->get(AccountSettingsManagerInterface::class)->getAccountSettings();
    }

    /**
     * @var BillingDateCalculatorInterface
     */
    private static $billing_date_calculator;

    public static function &billingDateCalculator()
    {
        if (!self::$billing_date_calculator) {
            self::$billing_date_calculator = new BillingDateCalculator();
        }

        return self::$billing_date_calculator;
    }

    /**
     * @var SuspendedPeriodCalculatorInterface
     */
    private static $suspended_period_calculator;

    public static function &suspendedPeriodCalculator()
    {
        if (!self::$suspended_period_calculator) {
            self::$suspended_period_calculator = new SuspendedPeriodCalculator(
                self::accountSettings()->getPricingModel()->getPlanPriceResolver(),
            );
        }

        return self::$suspended_period_calculator;
    }

    /**
     * @var SuspensionDaysResolverInterface
     */
    private static $suspension_days_resolver;

    public static function &suspensionDaysResolver()
    {
        if (!self::$suspension_days_resolver) {
            self::$suspension_days_resolver = new SuspensionDaysResolver(
                self::getContainer()->get(FailedPaymentDaysResolverInterface::class),
            );
        }

        return self::$suspension_days_resolver;
    }

    /**
     * @var MarkAsPaidServiceInterface
     */
    private static $mark_as_paid_service;

    public static function &markAsPaidService($is_on_demand = null)
    {
        if ($is_on_demand === null) {
            $is_on_demand = self::isOnDemand();
        }

        if ($is_on_demand && !self::$mark_as_paid_service) {
            self::$mark_as_paid_service = new MarkAsPaidService(
                self::getContainer()->get(ChargeableUsersResolverInterface::class),
                self::log(),
            );
        }

        return self::$mark_as_paid_service;
    }

    /**
     * @var FastSpringApiClientInterface
     */
    private static $fast_spring_api_client = null;

    public static function &fastSpringApiClient($is_on_demand = null)
    {
        if ($is_on_demand === null) {
            $is_on_demand = self::isOnDemand();
        }

        if ($is_on_demand && !self::$fast_spring_api_client) {
            if (!self::isInTestMode()) {
                $password = defined('FASTSPRING_PASSWORD') ? FASTSPRING_PASSWORD : null;
                $username = defined('FASTSPRING_USERNAME') ? FASTSPRING_USERNAME : null;
                $store_id = defined('FASTSPRING_STORE_ID') ? FASTSPRING_STORE_ID : null;

                if ($password && $username && $store_id) {
                    self::$fast_spring_api_client = new FastSpringApiClient($password, $username, $store_id);
                }
            } else {
                self::$fast_spring_api_client = new TestFastSpringApiClient();
            }
        }

        return self::$fast_spring_api_client;
    }

    private static $failed_payment_account_status_updater;

    public static function failedPaymentAccountStatusUpdater(): FailedPaymentStatusUpdater
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Managing account status is available for OnDemand accounts only');
        }

        if (!self::$failed_payment_account_status_updater) {
            self::$failed_payment_account_status_updater = new FailedPaymentStatusUpdater(
                self::accountSettings()->getAccountPlan(),
                self::getContainer()->get(OrderFactoryInterface::class),
                self::orderExecutor(),
                self::accountSettings()->getPaymentMethod(),
                self::log(),
            );
        }

        return self::$failed_payment_account_status_updater;
    }

    /**
     * @var TrialStatusUpdater
     */
    private static $trial_account_status_updater;

    public static function trialAccountStatusUpdater(): TrialStatusUpdater
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Managing account status is available for OnDemand accounts only');
        }

        if (!self::$trial_account_status_updater) {
            self::$trial_account_status_updater = new TrialStatusUpdater(
                self::getContainer()->get(SuspendAccountSubscriptionServiceInterface::class),
                self::TrialNotificationsDispatcher(),
            );
        }

        return self::$trial_account_status_updater;
    }

    /**
     * @var CancelStatusUpdater
     */
    private static $cancel_account_status_updater;

    public static function unsetCancelAccountStatusUpdater()
    {
        self::$cancel_account_status_updater = null;
    }

    public static function cancelAccountStatusUpdater(): CancelStatusUpdater
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Managing account status is available for OnDemand accounts only');
        }

        if (!self::$cancel_account_status_updater) {
            self::$cancel_account_status_updater = new CancelStatusUpdater(
                self::getContainer()->get(RetireAccountSubscriptionServiceInterface::class),
                self::accountExporter(),
                self::accountExportRecipientResolver(),
            );
        }

        return self::$cancel_account_status_updater;
    }

    /**
     * @var AccountExporterInterface
     */
    private static $account_exporter;

    public static function accountExporter(): AccountExporterInterface
    {
        if (empty(self::$account_exporter)) {
            self::$account_exporter = new AccountExporter(
                self::jobs(),
                self::getAccountId(),
                SHEPHERD_ACCESS_TOKEN,
                SHEPHERD_URL,
            );
        }

        return self::$account_exporter;
    }

    private static $account_export_recipipient_resolver;

    public static function accountExportRecipientResolver(): AccountExportRecipientResolverInterface
    {
        if (empty(self::$account_export_recipipient_resolver)) {
            self::$account_export_recipipient_resolver = new AccountExportRecipientResolver(
                function () {
                    $cancellation_request = BillingCancellationRequests::findOneBy(
                        [
                            'status' => BillingCancellationRequests::STATUS_CONFIRMED,
                        ],
                    );

                    if ($cancellation_request instanceof BillingCancellationRequest) {
                        return $cancellation_request->getCreatedBy();
                    }

                    return null;
                },
                function () {
                    return Users::findFirstOwner();
                },
            );
        }

        return self::$account_export_recipipient_resolver;
    }

    /**
     * @var OrderResolverInterface
     */
    private static $order_resolver;

    public static function &orderResolver($is_on_demand = null)
    {
        if ($is_on_demand === null) {
            $is_on_demand = self::isOnDemand();
        }

        if ($is_on_demand && !self::$order_resolver) {
            self::$order_resolver = new OrderResolver(
                self::accountSettings()->getAccountStatus(),
                self::accountSettings()->getAccountPlan(),
                self::getContainer()->get(OrderFactoryInterface::class),
                self::getContainer()->get(AccountBalanceInterface::class),
            );
        }

        return self::$order_resolver;
    }

    /**
     * @var VerifyPasswordResolverInterface
     */
    private static $verify_password_resolver;

    /**
     * @param  null                            $is_on_demand
     * @return VerifyPasswordResolverInterface
     */
    public static function &verifyPasswordResolver($is_on_demand = null)
    {
        if ($is_on_demand === null) {
            $is_on_demand = self::isOnDemand();
        }

        if ($is_on_demand && !self::$verify_password_resolver) {
            if (!self::isInTestMode()) {
                self::$verify_password_resolver = new VerifyPasswordResolver(
                    self::shepherdUsersApi(),
                    new Encryptor(PASSWORD_CRYPT_HASH),
                    self::getAccountId(),
                );
            } else {
                self::$verify_password_resolver = new TestVerifyPasswordResolver();
            }
        }

        return self::$verify_password_resolver;
    }

    /**
     * @var VerifyCancelationRequestServiceInterface
     */
    private static $verify_cancelation_request_service;

    public static function unsetVerifyCancellationRequestService()
    {
        self::$verify_cancelation_request_service = null;
    }

    /**
     * @param  null                                     $is_on_demand
     * @return VerifyCancelationRequestServiceInterface
     */
    public static function &verifyCancelationRequest($is_on_demand = null)
    {
        if ($is_on_demand === null) {
            $is_on_demand = self::isOnDemand();
        }

        if ($is_on_demand && !self::$verify_cancelation_request_service) {
            self::$verify_cancelation_request_service = new VerifyCancelationRequestService(
                self::verifyPasswordResolver(),
                self::getContainer()->get(CancelAccountSubscriptionServiceInterface::class),
            );
        }

        return self::$verify_cancelation_request_service;
    }

    private static $shepherd_account_config;

    public static function shepherdAccountConfig(): ?ShepherdAccountConfigInterface
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Shepherd is available only for OnDemand accounts');
        }

        if (!self::$shepherd_account_config && !self::isInTestMode()) {
            try {
                self::$shepherd_account_config = ShepherdAccountConfig::getInstance();
            }
            catch (Exception $e) {
                try {
                    return ShepherdAccountConfig::getInstance();
                } catch (Exception $e) {
                    $account_hash_key = SHEPHERD_ACCOUNT_HASH_KEY;

                    $encryptor = new OpenSSLAES256CBCEncryptor($account_hash_key);
                    $decryptor = new OpenSSLAES256CBCDecryptor($account_hash_key);

                    $redis_adapter = null;
                    $mysql_adapter = null;

                    $mysql_adapter = new MySqlAdapter(function () {
                        $link = new mysqli(
                            SHEPHERD_ACCOUNT_MYSQL_HOST,
                            SHEPHERD_ACCOUNT_MYSQL_USER,
                            SHEPHERD_ACCOUNT_MYSQL_PASS,
                            SHEPHERD_ACCOUNT_MYSQL_DBNAME,
                            !empty(SHEPHERD_ACCOUNT_MYSQL_PORT) ? (int) SHEPHERD_ACCOUNT_MYSQL_PORT : 3306,
                        );

                        if ($link->connect_error) {
                            $message = 'Failed to connect to multi-account database.';

                            if (self::isInDebugMode() || self::isInDevelopment()) {
                                $message .= " MySQL said: {$link->connect_error}";

                                throw new RuntimeException($message);
                            }

                            return null;
                        }

                        $link->query('SET NAMES utf8mb4');

                        return $link;
                    }, $encryptor, $decryptor, $account_hash_key);

                    return ShepherdAccountConfig::produce(
                        $redis_adapter,
                        $mysql_adapter,
                    );
                }
            }
        }

        return self::$shepherd_account_config;
    }

    public static function unsetShepherdAccountConfig()
    {
        if (!self::isInTestMode()) {
            throw new BadMethodCallException('This method is available only when system is in test mode.');
        }

        self::$shepherd_account_config = null;
    }

    public static function setShepherdAccountConfig(ShepherdAccountConfigInterface $shepherd_account_config)
    {
        if (!self::isInTestMode()) {
            throw new BadMethodCallException('This method is available only when system is in test mode.');
        }

        self::$shepherd_account_config = $shepherd_account_config;
    }

    private static $account_config_reader;

    /**
     * @deprecated Use AngieApplication::accountSettingsManger()->getAccountSettings() or AngieApplication::accountSettingsManger()->getAccountSettings()->getAccountStatus()
     */
    public static function accountConfigReader(): AccountConfigReaderInterface
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Account config reader is available only for OnDemand accounts');
        }

        $account_id = defined('ON_DEMAND_INSTANCE_ID') ? ON_DEMAND_INSTANCE_ID : 0;

        if (!self::$account_config_reader) {
            if (!self::isInTestMode()) {
                self::$account_config_reader = new DatabaseConfigReader(
                    self::shepherdAccountConfig(),
                    $account_id,
                );
            } else {
                self::$account_config_reader = new TestConfigReader(
                    defined('ON_DEMAND_PLAN_NAME') ? ON_DEMAND_PLAN_NAME : 'XL',
                    defined('ON_DEMAND_PLAN_PRICE') && !empty(ON_DEMAND_PLAN_PRICE) ? ON_DEMAND_PLAN_PRICE : 0.0,
                    defined('ON_DEMAND_PLAN_PERIOD') ? ON_DEMAND_PLAN_PERIOD : 'monthly',
                    defined('ON_DEMAND_ACCOUNT_STATUS') ? ON_DEMAND_ACCOUNT_STATUS : ClassicAccountStatusInterface::CLASSIC_STATUS_ACTIVE_FREE,
                    defined('ON_DEMAND_ACCOUNT_STATUS_EXPIRES_ON') ? ON_DEMAND_ACCOUNT_STATUS_EXPIRES_ON : null,
                    null,
                    defined('ON_DEMAND_PLAN_MAX_USERS') && !empty(ON_DEMAND_PLAN_MAX_USERS) ? ON_DEMAND_PLAN_MAX_USERS : 0,
                    defined('ON_DEMAND_PLAN_MAX_DISK_SPACE') && !empty(ON_DEMAND_PLAN_MAX_DISK_SPACE) ? ON_DEMAND_PLAN_MAX_DISK_SPACE : 0,
                );
            }
        }

        return self::$account_config_reader;
    }

    public static function setAccountConfigReader(AccountConfigReaderInterface $account_config_reader)
    {
        if (!self::isInTestMode()) {
            throw new BadMethodCallException('This method is available only when system is in test mode.');
        }

        self::$account_config_reader = $account_config_reader;
    }

    private static $initial_settings_cache_invalidator;

    public static function initialSettingsCacheInvalidator(): InitialSettingsCacheInvalidatorInterface
    {
        if (empty(self::$initial_settings_cache_invalidator)) {
            self::$initial_settings_cache_invalidator = new InitialSettingsCacheInvalidator();
        }

        return self::$initial_settings_cache_invalidator;
    }

    /**
     * @var CTANotifications
     */
    private static $cta_notifications;

    public static function &CTANotifications($is_on_demand = null)
    {
        if ($is_on_demand === null) {
            $is_on_demand = self::isOnDemand();
        }

        if (!self::$cta_notifications) {
            self::$cta_notifications = new CTANotifications($is_on_demand);
        }

        return self::$cta_notifications;
    }

    /**
     * Unset CTANotifications instance.
     */
    public static function unsetCTANotifications()
    {
        self::$cta_notifications = null;
    }

    /**
     * @var EmailImporterInterface
     */
    private static $email_importer;

    public static function emailImporter()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Email importer service is currently available only for OnDemand');
        }

        if (!self::$email_importer) {
            self::$email_importer = new OnDemandEmailImporter(self::log());
        }

        return self::$email_importer;
    }

    /**
     * @var ConstantResolverInterface
     */
    private static $constants_resolver;

    public static function constantsResolver()
    {
        if (!self::$constants_resolver) {
            self::$constants_resolver = new ConstantResolver(
                [
                    'BILLING_NEXUS_COUNTRY',
                    'BILLING_NEXUS_ZIP',
                    'BILLING_NEXUS_STATE',
                    'BILLING_NEXUS_CITY',
                    'BILLING_NEXUS_STREET',
                    'TAX_JAR_API_TOKEN',
                ],
            );
        }

        return self::$constants_resolver;
    }

    private static ?PushIntegrationConfiguratorInterface $push_integration_configurator = null;

    public static function pushIntegrationConfigurator(): ?PushIntegrationConfiguratorInterface
    {
        if (self::isOnDemand() && !self::$push_integration_configurator) {
            // PUSHER_API_HOST can be comma separated list of hosts
            $api_hosts = defined('PUSHER_API_HOST') ? (string) PUSHER_API_HOST : '';

            if (self::isInProduction() && empty($api_hosts)) {
                self::log()->error('Pusher api host environment does not exist.');
            }

            if ($api_hosts) {
                self::$push_integration_configurator = new PushIntegrationConfigurator(
                    $api_hosts,
                    defined('PUSHER_APP_ID') ? PUSHER_APP_ID : '',
                    defined('PUSHER_KEY') ? PUSHER_KEY : '',
                    defined('PUSHER_SECRET') ? PUSHER_SECRET : '',
                    defined('PUSHER_API_PORT') ? (int) PUSHER_API_PORT : 443,
                    self::getContainer()->get(OnDemandChannelInterface::class),
                    self::currentTimestamp(),
                );
            }
        }

        return self::$push_integration_configurator;
    }

    private static $real_time_integration_resolver;

    public static function realTimeIntegrationResolver(): RealTimeIntegrationResolverInterface
    {
        if (empty(self::$real_time_integration_resolver)) {
            self::$real_time_integration_resolver = new RealTimeIntegrationResolver(
                self::isOnDemand(),
                self::getAccountId(),
                true,
                self::pushIntegrationConfigurator(),
            );
        }

        return self::$real_time_integration_resolver;
    }

    public static function setRealTimeIntegrationResolver(?RealTimeIntegrationResolverInterface $resolver)
    {
        self::$real_time_integration_resolver = $resolver;
    }

    /**
     * @var TaxRateChangeCheckerInterface
     */
    private static $tax_rate_change_checker;

    public static function &taxRateChangeChecker()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Tax Rate Change Checker is available only for OnDemand');
        }

        if (!self::$tax_rate_change_checker) {
            self::$tax_rate_change_checker = new TaxRateChangeChecker(self::taxResolver(), new Countries());
        }

        return self::$tax_rate_change_checker;
    }

    /**
     * @var BalanceCalculatorInterface
     */
    private static $balance_calculator;

    public static function &balanceCalculator()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Balance Calculator is available only for OnDemand');
        }

        if (!self::$balance_calculator) {
            self::$balance_calculator = new BalanceCalculator();
        }

        return self::$balance_calculator;
    }

    /**
     * @var TaxResolverInterface
     */
    private static $tax_resolver;

    /**
     * Set tax resolver.
     */
    public static function setTaxResolver(TaxResolverInterface $tax_resolver)
    {
        if (!self::isInTestMode()) {
            throw new BadMethodCallException('This method is available only when system is in test mode.');
        }

        self::$tax_resolver = $tax_resolver;
    }

    /**
     * Unset tax resolver.
     */
    public static function unsetTaxResolver()
    {
        self::$tax_resolver = null;
    }

    public static function taxResolver()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Tax resolver is available only for OnDemand');
        }

        if (!self::$tax_resolver) {
            if (self::isInTestMode() || self::isInDevelopment()) {
                self::$tax_resolver = new TestTaxJarResolver();
            } else {
                self::$tax_resolver = new TaxJar(
                    new TaxJarApiClient(self::constantsResolver()->getValueForConstant('TAX_JAR_API_TOKEN')),
                    new Countries(),
                    self::constantsResolver(),
                );
            }
        }

        return self::$tax_resolver;
    }

    /**
     * @var PlanPriceResolverInterface
     */
    private static $plan_price_resolver;

    public static function planPriceResolver()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Plan price resolver is available only for OnDemand');
        }

        if (!self::$plan_price_resolver) {
            self::$plan_price_resolver = new PlanPriceResolver();
        }

        return self::$plan_price_resolver;
    }

    private static $order_proration_calculator;

    public static function usetOrderProrationCalculator()
    {
        self::$order_proration_calculator = null;
    }

    public static function &orderProrationCalculator()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('OrderProrationCalculator is available only for OnDemand');
        }

        if (!self::$order_proration_calculator) {
            self::$order_proration_calculator = new OrderProrationCalculator(
                self::taxResolver(),
                self::accountSettings()->getAccountStatus()->getDiscount(),
                self::getContainer()->get(AccountBalanceInterface::class),
                self::accountSettings()->getPaymentMethod(),
            );
        }

        return self::$order_proration_calculator;
    }

    public static function setAddOnsPriceResolver(AddOnsPriceResolverInterface $add_ons_price_resolver)
    {
        if (!self::isInTestMode()) {
            throw new LogicException('Set AddOns price resolver is available only for test mode');
        }

        self::$add_ons_price_resolver = $add_ons_price_resolver;
    }

    public static function setPricingModelResolver(PricingModelResolver $pricing_model_resolver)
    {
        if (!self::isInTestMode()) {
            throw new LogicException('Set AddOns price resolver is available only for test mode');
        }

        self::$pricing_model_resolver = $pricing_model_resolver;
    }

    private static $add_ons_manager;

    public static function &addOnsManager(): AddOnsManagerInterface
    {
        if (!self::isOnDemand()) {
            throw new LogicException('AddOns manager is available only for OnDemand');
        }

        if (!self::$add_ons_manager) {
            self::$add_ons_manager = new AddOnsManager(
                self::addOnFinder(),
                AddOnInterface::UNIQUE_ADD_ONS,
            );
        }

        return self::$add_ons_manager;
    }

    /**
     * @var OrderItemsFactoryInterface
     */
    private static $order_items_facotry;

    public static function &orderItemsFactory()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Order Items Factory is available only for OnDemand');
        }

        if (!self::$order_items_facotry) {
            self::$order_items_facotry = new OrderItemsFactory(
                self::getContainer()->get(AccountBalanceInterface::class),
            );
        }

        return self::$order_items_facotry;
    }

    /**
     * @var BillingPaymentMethodFactoryInterface
     */
    private static $billing_payment_method_factory;

    public static function &billingPaymentMethodFactory()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Billing Payment Method Factory is available only for OnDemand');
        }

        if (!self::$billing_payment_method_factory) {
            self::$billing_payment_method_factory = new BillingPaymentMethodFactory(
                self::fastSpringApiClient(),
                self::log(),
                self::getContainer()->get(AccountBalanceInterface::class),
                self::billingPaymentMethodResolver(),
            );
        }

        return self::$billing_payment_method_factory;
    }

    private static $billing_payment_method_resolver;

    public static function &billingPaymentMethodResolver(): callable
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Billing Payment Method Resolver is available only for OnDemand');
        }

        if (!self::$billing_payment_method_resolver) {
            self::$billing_payment_method_resolver = new BillingPaymentMethodResolver();
        }

        return self::$billing_payment_method_resolver;
    }

    /**
     * @var OrderExecutorInterface
     */
    private static $order_executor;

    public static function &orderExecutor()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Order Executor is available only for OnDemand');
        }

        if (!self::$order_executor) {
            self::$order_executor = new OrderExecutor(
                self::getContainer()->get(AccountBalanceInterface::class),
                self::log(),
                self::discountFactory(),
                self::shepherdAccountConfig(),
                self::getAccountId(),
            );
        }

        return self::$order_executor;
    }

    /**
     * @var OrderThankYouResolverInterface
     */
    private static $order_thank_you_resolver;

    public static function orderThankYouResolver()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Order Thank You Resolver is available only for OnDemand');
        }

        if (!self::$order_thank_you_resolver) {
            self::$order_thank_you_resolver = new OrderThankYouResolver(
                self::accountSettings()->getAccountStatus(),
                self::accountSettings()->getAccountPlan(),
                self::plansFactory(),
                self::planComparator(),
                self::getContainer()->get(FeatureFlagsInterface::class),
            );
        }

        return self::$order_thank_you_resolver;
    }

    /**
     * @var PlanComparatorInterface
     */
    private static $plan_comparator;

    public static function planComparator()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Plan Comparator is available only for OnDemand');
        }

        if (!self::$plan_comparator) {
            self::$plan_comparator = new PlanComparator();
        }

        return self::$plan_comparator;
    }

    /**
     * @var PlansFactoryInterface
     */
    private static $plans_factory;

    public static function plansFactory()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Plan Comparator is available only for OnDemand');
        }

        if (!self::$plans_factory) {
            self::$plans_factory = new PlansFactory();
        }

        return self::$plans_factory;
    }

    /**
     * @var SuspendedAccountAccessManagerInterface
     */
    private static $suspended_account_access_manager;

    public static function &suspendedAccountAccessManager()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Suspended account access manager is available only for OnDemand');
        }

        if (!self::$suspended_account_access_manager) {
            self::$suspended_account_access_manager = new SuspendedAccountAccessManager(
                self::memories()->getInstance(),
                self::notifications(),
                self::subscriptionSectionService()->getUrl(),
            );
        }

        return self::$suspended_account_access_manager;
    }

    public static function setSuspendedAccountAccessManager(
        SuspendedAccountAccessManagerInterface $suspended_account_access_manager
    )
    {
        if (!self::isInTestMode()) {
            throw new RuntimeException('Flag can be set using this method only in test mode.');
        }

        self::$suspended_account_access_manager = $suspended_account_access_manager;
    }

    public static function unsetSuspendedAccountAccessManager()
    {
        self::$suspended_account_access_manager = null;
    }

    /**
     * @var DiscountInterface
     */
    private static $discount_factory;

    public static function unsetDiscountFactory()
    {
        self::$discount_factory = null;
    }

    public static function &discountFactory()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Discount Factory is available only for OnDemand');
        }

        if (!self::$discount_factory) {
            self::$discount_factory = new DiscountFactory();
        }

        return self::$discount_factory;
    }

    /**
     * @var PaidOrderResolverInterface
     */
    private static $paid_order_resolver;

    public static function unsetPaidOrderResolver()
    {
        self::$paid_order_resolver = null;
    }

    public static function &paidOrderResolver()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Paid Order Resolver is available only for OnDemand');
        }

        if (!self::$paid_order_resolver) {
            self::$paid_order_resolver = new PaidOrderResolver(
                function () {
                    return BillingOrders::getLastPaidOrder();
                },
                function () {
                    return BillingOrders::getLastPaidAddOnOrder();
                },
                self::accountSettings(),
            );
        }

        return self::$paid_order_resolver;
    }

    public static function shouldRecordChargableUserBalance(): bool
    {
        return in_array(self::accountSettings()->getPricingModel()->getName(),
                [
                    PricingModelInterface::PRICING_MODEL_PER_SEAT_2018,
                    PricingModelInterface::PRICING_MODEL_LIFETIME_2021,
                ], )
            && !self::accountSettings()->getAccountStatus()->isTrial();
    }

    public static function calculateSubscriptionPricePerUser(): float
    {
        $chargeable_users_resolver = self::getContainer()->get(ChargeableUsersResolverInterface::class);
        $per_seat_plan_price_resolver = new PerSeatPlanPriceResolver(
            $chargeable_users_resolver,
        );
        $lifetime_plan_price_resolver = new LifetimePlanPriceResolver();
        $add_ons_price_resolver = new AddOnsPriceResolver(
            $chargeable_users_resolver,
            self::balanceCalculator(),
            new ActiveCollabDateValue(),
            self::accountSettings(),
            self::addOnFinder(),
        );

        return (new SubscriptionPricePerUserCalculator(
            self::accountSettings(),
            $per_seat_plan_price_resolver,
            $lifetime_plan_price_resolver,
            $add_ons_price_resolver,
        ))->calculatePricePerUser();
    }

    /**
     * @var ChargableUserAddBalanceInterface
     */
    private static $chargable_user_add_balance;

    public static function unsetChargableUserAddBalance()
    {
        self::$chargable_user_add_balance = null;
    }

    public static function chargableUserAddBalance()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Chargeable user add balance is available only for OnDemand');
        }

        return self::shouldRecordChargableUserBalance()
            ? new ChargableUserAddBalance(
                self::getContainer()->get(AccountBalanceInterface::class),
                self::balanceCalculator(),
                self::accountSettings()->getAccountStatus()->getNextBillingDate(),
                self::calculateSubscriptionPricePerUser(),
                DateValue::now()->daysBetween(self::accountSettings()->getAccountStatus()->getNextBillingDate()),
                self::accountSettings(),
                self::getContainer()->get(ChargeableUsersResolverInterface::class),
                self::getContainer()->get(ChargeableUsersBeforeCoronaResolverInterface::class),
            )
            : function ($event) {};
    }

    /**
     * @var ChargableUserWithdrawBalanceInterface
     */
    private static $chargable_user_withdraw_balance;

    public static function unsetChargableUserWithdrawBalance()
    {
        self::$chargable_user_withdraw_balance = null;
    }

    public static function chargableUserWithdrawBalance()
    {
        if (!self::isOnDemand()) {
            throw new LogicException('Chargeable user withdraw balance is available only for OnDemand');
        }

        return self::shouldRecordChargableUserBalance()
            ? new ChargableUserWithdrawBalance(
                self::getContainer()->get(AccountBalanceInterface::class),
                self::balanceCalculator(),
                self::accountSettings()->getAccountStatus()->getNextBillingDate(),
                self::calculateSubscriptionPricePerUser(),
                DateValue::now()->daysBetween(self::accountSettings()->getAccountStatus()->getNextBillingDate()),
                self::accountSettings(),
                self::getContainer()->get(ChargeableUsersResolverInterface::class),
                self::getContainer()->get(ChargeableUsersBeforeCoronaResolverInterface::class),
            )
            : function ($event) {};
    }

    /**
     * @var ActionHandlerInterface
     */
    private static $fs_action_handler = null;

    public static function unsetFsActionHandler()
    {
        self::$fs_action_handler = null;
    }

    public static function &fsActionHandler(): ActionHandlerInterface
    {
        if (empty(self::$fs_action_handler)) {
            if (self::fsInflictsChanges()) {
                self::$fs_action_handler = new ServiceActionHandler(
                    self::$account_id,
                    self::accountSettings(),
                    self::accountsApiClient(),
                    self::getContainer()->get(ActivateAccountServiceInterface::class),
                    self::getContainer()->get(UnCancelAccountSubscriptionServiceInterface::class),
                    self::getContainer()->get(CancelAccountSubscriptionServiceInterface::class),
                    self::getContainer()->get(ChangeAccountSubscriptionServiceInterface::class),
                    self::getContainer()->get(ReactivateAccountSubscriptionServiceInterface::class),
                    self::getContainer()->get(SuspendAccountSubscriptionServiceInterface::class),
                    self::getContainer()->get(RetireAccountSubscriptionServiceInterface::class),
                    self::getContainer()->get(RebillFailedAccountSubscriptionServiceInterface::class),
                    self::getContainer()->get(RebillAccountSubscriptionServiceInterface::class),
                    new FastSpringOrderRecorder(self::fastSpringApiClient()),
                    self::getContainer()->get(SubscriptionBalanceRecorderInterface::class),
                    function () {
                        BillingBalanceRecords::deleteRecordsByType([
                            AccountBalanceInterface::FAILED_PAYMENT_ACTIVE_DAYS_FEE_BALANCE,
                            AccountBalanceInterface::SUBSCRIPTION_FEE_BALANCE,
                        ]);
                    },
                    self::log(),
                );
            } else {
                self::$fs_action_handler = new LoggerActionHandler(
                    self::$logger,
                );
            }
        }

        return self::$fs_action_handler;
    }

    /**
     * @var AccountsApiInterface
     */
    private static $accounts_api_client;

    private static function &accountsApiClient($is_on_demand = null): AccountsApiInterface
    {
        if ($is_on_demand === null) {
            $is_on_demand = self::isOnDemand();
        }

        if ($is_on_demand && !self::$accounts_api_client) {
            self::$accounts_api_client = new AccountsApi(
                new Client(
                    new Token(SHEPHERD_ACCESS_TOKEN),
                    SHEPHERD_URL,
                ),
                new UrlCreator(
                    SHEPHERD_URL,
                ),
            );
        }

        return self::$accounts_api_client;
    }

    public static function publicStripeApiKey(): string
    {
        return self::getStripeKey('public');
    }

    public static function secretStripeApiKey(): string
    {
        return self::getStripeKey('secret');
    }

    private static function getStripeKey(string $public_or_secret): string
    {
        if (!self::isOnDemand()) {
            throw new RuntimeException('Stripe keys are available in On-Demand version only');
        }

        if (!in_array($public_or_secret, ['public', 'secret'])) {
            throw new RuntimeException('Stripe key can be either public or secret');
        }

        $key_name = mb_strtoupper($public_or_secret, 'UTF-8') . '_STRIPE_API_KEY';

        if (self::getContainer()->get(OnDemandChannelInterface::class)->isEdgeChannel()) {
            $key_name = 'EDGE_' . $key_name;
        }

        if (!defined($key_name)) {
            throw new RuntimeException(ucfirst($public_or_secret) . ' Stripe key option not found');
        }

        return constant($key_name);
    }

    private static $new_features;

    public static function newFeatures()
    {
        if (empty(self::$new_features)) {
            self::$new_features = new NewFeaturesManager(
                new NewFeatureAnnouncementsFromFileLoader(
                    self::getModule('system')->getPath() . '/resources/new_features.php',
                ),
                self::isOnDemand()
                    ? NewFeatureAnnouncementInterface::CHANNEL_CLOUD
                    : NewFeatureAnnouncementInterface::CHANNEL_SELF_HOSTED,
                self::currentTimestamp(),
            );
        }

        return self::$new_features;
    }

    // ---------------------------------------------------
    //  Application Mode
    // ---------------------------------------------------

    /**
     * @deprecated
     */
    public static function isInDevelopment(): bool
    {
        return self::getContainer()->get(ApplicationModeInterface::class)->isInDevelopment();
    }

    /**
     * @deprecated
     */
    public static function isInDebugMode(): bool
    {
        return self::getContainer()->get(ApplicationModeInterface::class)->isInDebugMode();
    }

    /**
     * @deprecated
     */
    public static function isInProduction(): bool
    {
        return self::getContainer()->get(ApplicationModeInterface::class)->isInProduction();
    }

    /**
     * @deprecated
     */
    public static function isInTestMode(): bool
    {
        return self::getContainer()->get(ApplicationModeInterface::class)->isInTestMode();
    }

    public static function getCurrentPolicyVersion($is_on_demand = null): ?string
    {
        if ($is_on_demand === null) {
            $is_on_demand = self::isOnDemand();
        }

        if ($is_on_demand) {
            return AcceptPolicyServiceInterface::CURRENT_POLICY_VERSION;
        }

        return null;
    }

    /**
     * @return OnDemandStatusInterface|OverridableOnDemandStatusInterface
     */
    public static function onDemandStatus(): OnDemandStatusInterface
    {
        return self::getContainer()->get(OnDemandStatusInterface::class);
    }

    public static function isOnDemand()
    {
        return self::getContainer()->get(OnDemandStatusInterface::class)->isOnDemand();
    }

    public static function setFsInflictsChanges(bool $fs_inflicts_changes = false)
    {
        if (!self::isInTestMode()) {
            throw new BadMethodCallException('This method is available only when system is in test mode.');
        }

        self::$fs_inflicts_changes = $fs_inflicts_changes;
    }

    public static function isChristmasCampainActive(): bool
    {
        $current_date = DateTimeValue::now();
        $christmas_discount_starts = DateTimeValue::createFromTimeString('2020-12-20 12:00:00');
        $christmas_discount_ends = DateTimeValue::createFromTimeString('2020-12-22 12:00:00');

        return !$current_date->lessThan($christmas_discount_starts) && !$current_date->greaterThan($christmas_discount_ends);
    }

    /**
     * @var bool
     */
    private static $fs_inflicts_changes = null;

    public static function fsInflictsChanges(): bool
    {
        if (self::$fs_inflicts_changes === null) {
            self::$fs_inflicts_changes = self::isOnDemand();
        }

        return self::$fs_inflicts_changes;
    }

    /**
     * Return true if we have a paid on demand account here.
     *
     * @return bool
     */
    public static function isPaidOnDemand()
    {
        return self::isOnDemand() && self::accountSettings()->getAccountStatus()->isPaid();
    }

    public static function getWebhookSecret(): string
    {
        return self::isOnDemand() && defined('SHEPHERD_ACTIVECOLLAB_WEBHOOK_SECRET')
            ? SHEPHERD_ACTIVECOLLAB_WEBHOOK_SECRET
            : '';
    }

    public static function getDeploymentChannel(): int
    {
        if (self::getContainer()->get(OnDemandChannelInterface::class)->isEdgeChannel()) {
            return self::EDGE_CHANNEL;
        }

        if (self::getContainer()->get(OnDemandChannelInterface::class)->isBetaChannel()) {
            return self::BETA_CHANNEL;
        }

        return self::STABLE_CHANNEL;
    }

    // ---------------------------------------------------
    //  Request Handling
    // ---------------------------------------------------

    /**
     * Handle HTTP request.
     */
    public static function handleHttpRequest()
    {
        if (php_sapi_name() === 'cli' && !self::isInTestMode()) {
            throw new RuntimeException('HTTP request handler is available to CLI only for testing');
        }

        $request = (new RequestFactory())->createFromGlobals();
        $request = $request
            ->withAttribute('request_id', Uuid::uuid4());

        $response = new Response();
        $response = self::executeHttpMiddlewareStack($request, $response);
        self::log()->setAppResponse(new HttpResponse($response));

        (new SapiEmitter())->emit($response);

        exit();
    }

    public static function executeHttpMiddlewareStack(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface
    {
        self::log()->setAppRequest(new HttpRequest($request));

        $request_handler = new RequestHandler(
            self::authentication(),
            self::getContainer()->get(CookiesInterface::class),
            self::getContainer()->get(EncryptorInterface::class),
            self::getContainer()->get(RouterInterface::class),
            function ($controller_name, $module_name) {
                self::useController($module_name, $controller_name);
            },
            function ($ip_address) {
                self::securityLog()->setIpAddress($ip_address);
            },
            function ($user_agent) {
                self::securityLog()->setUserAgent($user_agent);
            },
            self::getContainer()->get(FirewallInterface::class),
            self::getContainer()->get(ApplicationModeInterface::class)->isInDevelopment()
            || self::getContainer()->get(ApplicationModeInterface::class)->isInDebugMode(),
            self::log(),
        );

        return $request_handler->handleRequest($request, $response);
    }

    /**
     * Return user IP address.
     *
     * @return string
     */
    public static function getVisitorIp()
    {
        return array_var($_SERVER, 'REMOTE_ADDR', '127.0.0.1');
    }

    /**
     * Return visitor's user agent string.
     *
     * @return string
     */
    public static function getVisitorUserAgent()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    }

    /**
     * Return request schema (http:// or https://).
     *
     * @return string
     */
    public static function getRequestSchema()
    {
        return ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) || (isset($_SERVER['HTTP_X_REAL_PORT']) && $_SERVER['HTTP_X_REAL_PORT'] == 443)) ? 'https://' : 'http://';
    }

    /**
     * Invalidate initial settings timestamp.
     */
    public static function invalidateInitialSettingsCache()
    {
        ConfigOptions::setValue('initial_settings_timestamp', self::currentTimestamp()->getCurrentTimestamp());
    }

    // ---------------------------------------------------
    //  Frameworks and modules
    // ---------------------------------------------------

    /**
     * Cached array of application frameworks.
     *
     * @var AngieFramework[]
     */
    private static $frameworks = false;

    /**
     * Return list of available application frameworks.
     *
     * @return AngieFramework[]
     */
    public static function &getFrameworks()
    {
        if (self::$frameworks === false) {
            self::$frameworks = [];

            foreach (self::getFrameworkNames() as $framework_name) {
                $framework_class = Inflector::camelize($framework_name) . 'Framework';
                $framework_class_path = sprintf(
                    '%s/frameworks/%s/%s.php',
                    ANGIE_PATH,
                    $framework_name,
                    $framework_class,
                );

                require_once $framework_class_path;

                $framework = new $framework_class();
                if ($framework instanceof AngieFramework) {
                    self::$frameworks[] = $framework;
                } else {
                    throw new ClassNotImplementedError($framework_class, $framework_class_path, 'Framwork definition class not found');
                }
            }
        }

        return self::$frameworks;
    }

    /**
     * Cached list of installed modules.
     *
     * @var AngieModule[]
     */
    private static ?array $modules = null;

    /**
     * Return list of installed application modules.
     *
     * @return AngieModule[]|iterable
     */
    public static function &getModules(): iterable
    {
        if (self::$modules === null) {
            $module_factory = new ModuleFactory(
                sprintf('%s/modules', APPLICATION_PATH),
            );

            require_once APPLICATION_PATH . '/modules/system/SystemModule.php';

            self::$modules = [
                'system' => $module_factory->createModule('system'),
            ];

            foreach (self::getModuleNames() as $module_name) {
                if ($module_name === SystemModule::NAME) {
                    continue;
                }

                self::$modules[] = $module_factory->createModule($module_name);
            }
        }

        return self::$modules;
    }

    public static function isFrameworkLoaded(string $name): bool
    {
        return isset(self::$loaded_frameworks_and_modules[$name])
            && self::$loaded_frameworks_and_modules[$name] instanceof AngieFramework;
    }

    public static function isModuleLoaded(string $name): bool
    {
        return isset(self::$loaded_frameworks_and_modules[$name])
            && self::$loaded_frameworks_and_modules[$name] instanceof AngieModule;
    }

    /**
     * Return module instance.
     *
     * @param  string         $name
     * @return AngieFramework
     */
    public static function &getModule($name)
    {
        if (isset(self::$loaded_frameworks_and_modules[$name])) {
            return self::$loaded_frameworks_and_modules[$name];
        }

        throw new InvalidParamError('name', $name, "Module '$name' is not defined");
    }

    public static function useController(string $controller_name, string $module_name = DEFAULT_MODULE): string
    {
        return self::mustGetFrameworkOrModule($module_name)->useController($controller_name);
    }

    public static function useModel(array $model_names, string $module_name = DEFAULT_MODULE): void
    {
        self::mustGetFrameworkOrModule($module_name)->useModel($model_names);
    }

    public static function useView($view_names, $module_name): void
    {
        if (empty($view_names)) {
            return;
        }

        self::mustGetFrameworkOrModule($module_name)->useView($view_names);
    }

    /**
     * Use helper file.
     */
    public static function useHelper(
        string $helper_name,
        string $module_name = DEFAULT_MODULE,
        string $helper_type = 'function'
    ): string
    {
        return self::mustGetFrameworkOrModule($module_name)->useHelper($helper_name, $helper_type);
    }

    public static function getProxyUrl(
        string $proxy_class,
        string $module_name = DEFAULT_MODULE,
        array $params = []
    ): string
    {
        return self::getContainer()
            ->get(ProxyUrlBuilderInterface::class)
                ->buildUrl(
                    $proxy_class,
                    $module_name,
                    $params,
                );
    }

    /**
     * Return email template path.
     */
    public static function getEmailTemplatePath(string $template, string $module_name = DEFAULT_MODULE): string
    {
        return self::mustGetFrameworkOrModule($module_name)->getEmailTemplatePath($template);
    }

    /**
     * Return handler file path based on event name.
     */
    public static function getEventHandlerPath(string $callback_name, string $module_name = DEFAULT_MODULE): string
    {
        return self::mustGetFrameworkOrModule($module_name)->getEventHandlerPath($callback_name);
    }

    private static function mustGetFrameworkOrModule(string $module_name): AngieFramework
    {
        if (isset(self::$loaded_frameworks_and_modules[$module_name])) {
            return self::$loaded_frameworks_and_modules[$module_name];
        }

        throw new InvalidParamError(
            'module_name',
            $module_name,
            sprintf('Module / framework "%s" not loaded', $module_name),
        );
    }

    // ---------------------------------------------------
    //  File management
    // ---------------------------------------------------

    /**
     * Return full file path based on file location.
     *
     * @param  string $location
     * @return string
     */
    public static function fileLocationToPath($location)
    {
        return UPLOAD_PATH . '/' . $location;
    }

    /**
     * Move or copy file to a permanent storage.
     *
     * Result is an array where first element is full path, and second is path relative to the upload folder
     *
     * @param  string $path
     * @param  bool   $is_uploaded_file
     * @return array
     */
    public static function storeFile($path, $is_uploaded_file = false)
    {
        $target_path = self::prepareTargetPath();

        if ($is_uploaded_file ? move_uploaded_file($path, $target_path) : copy($path, $target_path)) {
            return [$target_path, substr($target_path, strlen(UPLOAD_PATH) + 1)];
        }

        throw new FileCopyError($path, $target_path);
    }

    /**
     * Remove stored file from disk.
     *
     * @param string $location
     * @param string $in
     */
    public static function removeStoredFile($location, $in = UPLOAD_PATH)
    {
        if (empty($location)) {
            return; // Nothing to remove
        }

        if ($in !== UPLOAD_PATH && $in !== WORK_PATH) {
            throw new InvalidParamError('in', $in, '$in can be /upload or /work folder');
        }

        if (is_file($in . '/' . $location)) {
            @unlink($in . '/' . $location);
        }
    }

    /**
     * Prepare target path.
     *
     * @param  string $in
     * @return string
     */
    public static function prepareTargetPath($in = UPLOAD_PATH)
    {
        if ($in !== UPLOAD_PATH && $in !== WORK_PATH) {
            throw new InvalidParamError('in', $in, '$in can be /upload or /work folder');
        }

        $target_path = $in . '/' . date('Y-m');

        if (!is_dir($target_path)) {
            $old_umask = umask(0000);
            $dir_created = mkdir($target_path, 0777, true);
            umask($old_umask);

            if (empty($dir_created)) {
                throw new DirectoryCreateError($target_path);
            }
        }

        do {
            $filename = $target_path . '/' . self::getAccountId() . '-' . make_string(40);
        } while (is_file($filename));

        return $filename;
    }

    /**
     * Return available file name in /uploads folder.
     *
     * @return string
     */
    public static function getAvailableUploadsFileName()
    {
        do {
            $filename = UPLOAD_PATH . '/' . self::getAccountId() . '-' . make_string(10) . '-' . make_string(10) . '-' . make_string(10) . '-' . make_string(10);
        } while (is_file($filename));

        return $filename;
    }

    /**
     * Prepare a directory under work path with proper permissions.
     *
     * @param  string $dir_path
     * @param  string $prefix
     * @return string
     */
    public static function getAvailableDirName($dir_path, $prefix)
    {
        if (!in_array($dir_path, [UPLOAD_PATH, WORK_PATH])) {
            throw new LogicException('This method is available only for upload and work directories');
        }

        if ($prefix) {
            $prefix = self::getAccountId() . "_{$prefix}_";
        } else {
            $prefix = self::getAccountId() . '_';
        }

        do {
            $target_dir_path = $dir_path . '/' . uniqid($prefix);
        } while (is_dir($target_dir_path));

        $old_umask = umask(0);
        mkdir($target_dir_path, 0777);
        umask($old_umask);

        return $target_dir_path;
    }

    /**
     * Return unique filename in work folder.
     *
     * @param  string $prefix
     * @param  string $extension
     * @param  bool   $random_string
     * @return string
     */
    public static function getAvailableWorkFileName($prefix = null, $extension = null, $random_string = true)
    {
        return self::getAvailableFileName(WORK_PATH, $prefix, $extension, $random_string);
    }

    /**
     * Get Available file name in $folder.
     *
     * @param  string $dir_path
     * @param  string $prefix
     * @param  string $extension
     * @param  bool   $random_string
     * @return string
     */
    public static function getAvailableFileName($dir_path, $prefix = null, $extension = null, $random_string = true)
    {
        if ($prefix) {
            $prefix = self::getAccountId() . "-{$prefix}-";
        } else {
            $prefix = self::getAccountId() . '-';
        }

        if ($extension) {
            $extension = ".$extension";
        }

        if ($random_string) {
            do {
                $filename = $dir_path . '/' . $prefix . make_string(10) . $extension;
            } while (is_file($filename));
        } else {
            $filename = trim($dir_path . '/' . $prefix, '-') . $extension;
        }

        return $filename;
    }

    // ---------------------------------------------------
    //  Wallpapers
    // ---------------------------------------------------

    /**
     * Get Wallpaper url.
     *
     * @param $name
     * @return string
     */
    public static function getWallpaperUrl($name)
    {
        return ROOT_URL . "/wallpapers/$name";
    }

    // ---------------------------------------------------
    //  Installation
    // ---------------------------------------------------

    /**
     * Returns true if this application is installed.
     *
     * @return bool
     */
    public static function isInstalled()
    {
        return defined('CONFIG_PATH') && is_file(CONFIG_PATH . '/config.php');
    }

    // ---------------------------------------------------
    //  Autoload
    // ---------------------------------------------------

    /**
     * Array of registered classes that autoloader uses.
     */
    private static array $autoload_classes = [];

    /**
     * Automatically load requested class.
     *
     * @param string $class
     */
    public static function autoload($class)
    {
        if (in_array($class, ['CURLFile', 'PHP_Invoker', 'PHPUnit_Extensions_Database_TestCase'])) {
            return;
        }

        $path = array_var(self::$autoload_classes, $class);

        if ($path && is_file($path)) {
            require_once $path;
        } else {
            if (stripos($class, 'smarty') !== false) {
                return; // Ignore Smarty classes
            }
        }
    }

    /**
     * Register class to autoload array.
     *
     * $class can be an array of classes, where index is class name value is
     * path to the file where class is defined
     */
    public static function setForAutoload(array $classes_and_paths)
    {
        foreach ($classes_and_paths as $class => $path) {
            if (!empty(self::$autoload_classes[$class])) {
                throw new Error("Class '$class' already set for autoload (currently points to '" . self::$autoload_classes[$class] . "')");
            }

            self::$autoload_classes[$class] = $path;
        }
    }

    /**
     * Return used memory from moment script was loaded until now.
     *
     * @return float
     */
    public static function getMemoryUsage()
    {
        return memory_get_peak_usage(true);
    }

    /**
     * Return time spent from moment script was loaded until now.
     *
     * @return float
     */
    public static function getExecutionTime()
    {
        if (!defined('ANGIE_SCRIPT_TIME')) {
            throw new RuntimeException('Reference timestamp constant (ANGIE_SCRIPT_TIME) not found');
        }

        return round(microtime(true) - ANGIE_SCRIPT_TIME, 5);
    }

    /**
     * Called on application shutdown.
     */
    public static function shutdown()
    {
        try {
            if (self::$global_job_queue_connection instanceof mysqli) {
                self::$global_job_queue_connection->close();
            }

            if (DB::hasConnection() && DB::getConnection()->isConnected()) {
                DB::getConnection()->disconnect(); // Lets disconnect and kill a transaction if we have something open
            }

            self::log()->requestSummary(
                self::getExecutionTime(),
                self::getMemoryUsage(),
                DB::getQueryCount(),
                DB::getAllQueriesExecTime(),
            );

            if (!empty(self::log()->getBuffer())) {
                self::log()->flushBuffer(true);
            }
        } catch (Exception $e) {
            if (!self::isInProduction()) {
                throw $e;
            }
            trigger_error('Error detected on shutdown: ' . $e->getMessage());
        }
    }

    /**
     * TODO -- remove this workaround for AngieDeleteCache.
     */
    public static function initializeOnDemandModel()
    {
        require_once APPLICATION_PATH . '/modules/on_demand/models/OnDemand.class.php';
    }

    public static function TrialNotificationsDispatcher(): TrialNotificationDispatcher
    {
        return new TrialNotificationDispatcher(
            self::notifications(),
            self::subscriptionSectionService()->getChangePlanUrl(),
        );
    }

    public static function failedPaymentNotificationsDispatcher(): FailedPaymentNotificationDispatcherInterface
    {
        return new FailedPaymentNotificationDispatcher(
            self::notifications(),
            self::subscriptionSectionService()->getUrl(),
        );
    }

    public static function shepherdUsersApi() {
        return new UsersApi(
            new Client(
                new Token(SHEPHERD_ACCESS_TOKEN),
                SHEPHERD_URL,
            ),
            new UrlCreator(
                SHEPHERD_URL,
            ),
        );
    }

    public static function subscriptionSectionService()
    {
        return new SubscriptionSectionService(
            URL_BASE,
        );
    }

    private static $dates_reschedule_calculator;

    public static function datesRescheduleCalculator(): DatesRescheduleCalculatorInterface
    {
        if (empty(self::$dates_reschedule_calculator)) {
            self::$dates_reschedule_calculator = new DatesRescheduleCalculator();
        }

        return self::$dates_reschedule_calculator;
    }

    private static $addon_finder;

    public static function addOnFinder($is_on_demand = null)
    {
        if ($is_on_demand === null) {
            $is_on_demand = self::isOnDemand();
        }

        if ($is_on_demand && !self::$addon_finder) {
            return new AddOnFinder(
                function ($add_on_type) {
                    return AddOns::findAddOnByType($add_on_type);
                },
                function ($add_on_names) {
                    return AddOns::findAddOnsByAddOnNames($add_on_names);
                },
                function () {
                    return AddOns::find();
                },
                function () {
                    return AddOns::findStorageAddons();
                },
                function () {
                    return AddOns::findPaidAddOns();
                },
                function () {
                    return AddOns::findTrialAndEnabled();
                },
                function () {
                    return AddOns::findAdditionalUsersAddons();
                },
            );
        }

        return self::$addon_finder;
    }

    private static $accept_policy_service;

    public static function acceptPolicyService($is_on_demand = null)
    {
        if ($is_on_demand === null) {
            $is_on_demand = self::isOnDemand();
        }

        if ($is_on_demand && !self::$accept_policy_service) {
            return new AcceptPolicyService(
                self::eventsDispatcher(),
                self::getAccountId(),
            );
        }

        return self::$accept_policy_service;
    }

    private static $user_event_listener;

    public static function userEventsListener($is_on_demand = null) {
        if ($is_on_demand === null) {
            $is_on_demand = self::isOnDemand();
        }

        if ($is_on_demand && !self::$user_event_listener) {
            self::$user_event_listener = new UserSessionsEvents(
                self::isOnDemand(),
                self::eventsDispatcher(),
                self::log(),
            );
        }

        return self::$user_event_listener;
    }

    private static function getIsLegacyDevelopment(): bool
    {
        return defined('IS_LEGACY_DEV') ? IS_LEGACY_DEV : false;
    }
}

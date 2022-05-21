<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Http\RequestHandler;

use ActiveCollab\Authentication\AuthenticationInterface;
use ActiveCollab\Authentication\Middleware\ApplyAuthenticationMiddleware;
use ActiveCollab\Cookies\CookiesInterface;
use ActiveCollab\Encryptor\EncryptorInterface;
use ActiveCollab\Firewall\FirewallInterface;
use ActiveCollab\Foundation\Urls\Router\UrlMatcher\UrlMatcherInterface;
use ActiveCollab\MiddlewareStack\MiddlewareStack;
use ActiveCollab\ValueContainer\Request\RequestValueContainer;
use Angie\Http\Encoder\Encoder;
use Angie\Middleware\ActionResultEncoderMiddleware;
use Angie\Middleware\ApplicationEnvironmentMiddleware;
use Angie\Middleware\ControllerActionMiddleware;
use Angie\Middleware\CsrfApplyMiddleware;
use Angie\Middleware\CsrfValidationMiddleware;
use Angie\Middleware\ErrorHandlerMiddleware;
use Angie\Middleware\EtagMiddleware;
use Angie\Middleware\FirewallMiddleware;
use Angie\Middleware\IpAddressMiddleware;
use Angie\Middleware\LanguageCookieMiddleware;
use Angie\Middleware\LogAuthenticationDataMiddleware;
use Angie\Middleware\MiddlewareInterface;
use Angie\Middleware\RouterMiddleware;
use Angie\Middleware\UserAgentMiddleware;
use Angie\Utils\CurrentTimestamp;
use AngieApplication;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use UserInstancesCookieMiddleware;

class RequestHandler implements RequestHandlerInterface
{
    private MiddlewareStack $middleware_stack;

    public function __construct(
        AuthenticationInterface $authentication,
        CookiesInterface $cookies,
        EncryptorInterface $encryptor,
        UrlMatcherInterface $url_matcher,
        callable $controller_file_resolver,
        ?callable $on_ip_addresses_resolved,
        ?callable $on_user_agent_resolved,
        ?FirewallInterface $firewall,
        bool $is_debug_or_development,
        LoggerInterface $logger
    )
    {
        $encoder = new Encoder($is_debug_or_development);
        $error_handler = new ErrorHandlerMiddleware($encoder, $logger);

        $this->middleware_stack = (new MiddlewareStack())
            ->setExceptionHandler($error_handler)
            ->setPhpErrorHandler($error_handler);

        $current_timestamp = new CurrentTimestamp();
        $csrf_validator_cookie_name = AngieApplication::getCsrfValidatorCookieName();
        $language_cookie_name = AngieApplication::getLanguageCookieName();
        $built_in_locale = BUILT_IN_LOCALE;
        $request_value_container = new RequestValueContainer(MiddlewareInterface::ACTION_RESULT_ATTRIBUTE);

        // Middleware stack is LIFO stack, meaning that last middlewares that we added are considered to be "outer"
        // middlewares, so they run first when stack is executed, and last when execution gets out of the stack.
        $this->middleware_stack

            // Set language code cookie, so frontend always knows what to load, even for public pages.
            ->addMiddleware(new LanguageCookieMiddleware($cookies, $language_cookie_name, $built_in_locale, $logger))

            // Apply CSRF token if user is logging in, logging out, or we have to clean up authencation artifacts.
            ->addMiddleware(new CsrfApplyMiddleware($cookies, $csrf_validator_cookie_name, $encoder, $logger));

        if (AngieApplication::isOnDemand()) {
            require_once APPLICATION_PATH . '/modules/on_demand/middleware/UserInstancesCookieMiddleware.php';

            // Set user instance's data cookie, so website knows what to load.
            $this->middleware_stack->addMiddleware(new UserInstancesCookieMiddleware(
                $cookies,
                AngieApplication::getUserInstancesCookieName(),
                AngieApplication::getUserInstancesCookeDomain(),
                AngieApplication::getAccountId(),
                $current_timestamp,
                $logger
            ));
        }

        $this->middleware_stack
            // Apply log in, or log out result (remembered in request as action result).
            ->addMiddleware(new ApplyAuthenticationMiddleware($request_value_container))

            // Encode controller action result.
            ->addMiddleware(new ActionResultEncoderMiddleware($encoder, $current_timestamp, $logger))

            // Find controller, and execute action. Result is pased down the stack as request attribute.
            ->addMiddleware(new ControllerActionMiddleware($controller_file_resolver, $encoder, $logger))

            // Route path, and set module, controller and action request attributes.
            ->addMiddleware(new RouterMiddleware($encoder, $url_matcher, $logger))

            // Check Etag and break if requested resource was not updated.
            ->addMiddleware(new EtagMiddleware($current_timestamp, $logger))

            // Log session ID after authentication has been performed.
            ->addMiddleware(new LogAuthenticationDataMiddleware($logger))

            // Validate CSRF key for session authenticated requests.
            ->addMiddleware(new CsrfValidationMiddleware(
                $encryptor,
                $cookies,
                $csrf_validator_cookie_name,
                $encoder, $logger
            ))

            // Initialize authentication, by checking if request already have an ID included (user session, token etc).
            ->addMiddleware($authentication)

            // Set version number header (used when frontend check if it needs to reload the page).
            ->addMiddleware(new ApplicationEnvironmentMiddleware($logger));

        if ($firewall) {
            // Check IP address agains black, white, and brute force attempt lists.
            $this->middleware_stack->addMiddleware(
                new FirewallMiddleware(
                    $firewall,
                    'visitor_ip_address',
                    $logger
                )
            );
        }

        // Extract user's IP addresses from the request, and set them as request attributes.
        $this->middleware_stack->addMiddleware(
            new IpAddressMiddleware(
                'visitor_ip_addresses',
                'visitor_ip_address',
                $on_ip_addresses_resolved,
                null,
                $logger
            )
        );

        // Extract user agent from the request, and set it as a request attribute.
        $this->middleware_stack->addMiddleware(
            new UserAgentMiddleware(
                'visitor_user_agent',
                $on_user_agent_resolved,
                $logger
            )
        );
    }

    public function handleRequest(ServerRequestInterface &$request, ResponseInterface $response)
    {
        return $this->middleware_stack->process($request, $response);
    }
}

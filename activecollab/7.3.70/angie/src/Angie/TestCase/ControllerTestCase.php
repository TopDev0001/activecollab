<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\TestCase;

use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;
use ActiveCollab\Cookies\CookiesInterface;
use ActiveCollab\Encryptor\EncryptorInterface;
use Angie\Authentication\Repositories\SessionsRepository;
use Angie\Http\RequestFactory;
use AngieApplication;
use Laminas\Diactoros\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use UserSession;

abstract class ControllerTestCase extends EnvironmentTestCase
{
    protected string $session_id_cookie_name;
    protected CookiesInterface $cookies;
    protected EncryptorInterface $encryptor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session_id_cookie_name = AngieApplication::getSessionIdCookieName();
        $this->cookies = AngieApplication::getContainer()->get(CookiesInterface::class);
        $this->encryptor = AngieApplication::getContainer()->get(EncryptorInterface::class);
    }

    /**
     * Execute a GET request and return resulting request and response.
     *
     * @param  string            $path
     * @param  array             $query_params
     * @return ResponseInterface
     */
    protected function executeGetRequest($path, $query_params = [], callable $modify_request_and_response = null)
    {
        $query_params['path_info'] = trim((string) $path, '/');

        $request = (new RequestFactory())
            ->create($this->getServerParams(), [], ROOT_URL . '/api.php', 'GET', 'php://input', [], [], $query_params)
            ->withAttribute('test', 123);

        return $this->executeRequest($request, null, $modify_request_and_response);
    }

    /**
     * Execute GET request as a given user.
     *
     * @param  string            $path
     * @param  array             $query_params
     * @return ResponseInterface
     */
    public function executeGetRequestAs(AuthenticatedUserInterface $user, $path, $query_params = [], callable $modify_request_and_response = null)
    {
        $query_params['path_info'] = trim((string) $path, '/');
        $request = (new RequestFactory())
            ->create($this->getServerParams(), [], ROOT_URL . '/api.php', 'GET', 'php://input', [], [], $query_params);

        /** @var ServerRequestInterface $request */
        /** @var ResponseInterface $response */
        [$request, $response] = $this->prepareRequestAndResponseFor($user, $request);

        return $this->executeRequest($request, $response, $modify_request_and_response);
    }

    /**
     * Execute POST request.
     *
     * @param  string            $path
     * @param  array             $payload
     * @return ResponseInterface
     */
    public function executePostRequest($path, $payload = [], callable $modify_request_and_response = null)
    {
        $query_params['path_info'] = trim((string) $path, '/');
        $request = (new RequestFactory())
            ->create($this->getServerParams(), [], ROOT_URL . '/api.php', 'POST', 'php://input', [], [], $query_params, $payload);

        return $this->executeRequest($request, null, $modify_request_and_response);
    }

    /**
     * Execute POST request as $user.
     *
     * @param  string            $path
     * @param  array             $payload
     * @return ResponseInterface
     */
    public function executePostRequestAs(AuthenticatedUserInterface $user, $path, $payload = [], callable $modify_request_and_response = null)
    {
        $query_params['path_info'] = trim((string) $path, '/');
        $request = (new RequestFactory())
            ->create($this->getServerParams(), [], ROOT_URL . '/api.php', 'POST', 'php://input', [], [], $query_params, $payload);

        /** @var ServerRequestInterface $request */
        /** @var ResponseInterface $response */
        [$request, $response] = $this->prepareRequestAndResponseFor($user, $request);

        return $this->executeRequest($request, $response, $modify_request_and_response);
    }

    /**
     * Execute POST request.
     *
     * @param  string            $path
     * @param  array             $payload
     * @return ResponseInterface
     */
    public function executePutRequest($path, $payload = [], callable $modify_request_and_response = null)
    {
        $query_params['path_info'] = trim((string) $path, '/');
        $request = (new RequestFactory())
            ->create($this->getServerParams(), [], ROOT_URL . '/api.php', 'PUT', 'php://input', [], [], $query_params, $payload);

        return $this->executeRequest($request, null, $modify_request_and_response);
    }

    /**
     * Execute POST request as $user.
     *
     * @param  string            $path
     * @param  array             $payload
     * @return ResponseInterface
     */
    public function executePutRequestAs(AuthenticatedUserInterface $user, $path, $payload = [], callable $modify_request_and_response = null)
    {
        $query_params['path_info'] = trim((string) $path, '/');
        $request = (new RequestFactory())
            ->create($this->getServerParams(), [], ROOT_URL . '/api.php', 'PUT', 'php://input', [], [], $query_params, $payload);

        /** @var ServerRequestInterface $request */
        /** @var ResponseInterface $response */
        [$request, $response] = $this->prepareRequestAndResponseFor($user, $request);

        return $this->executeRequest($request, $response, $modify_request_and_response);
    }

    /**
     * Execute delete action.
     *
     * @param  string            $path
     * @param  array             $payload
     * @return ResponseInterface
     */
    public function executeDeleteRequest($path, $payload = [], callable $modify_request_and_response = null)
    {
        $query_params['path_info'] = trim((string) $path, '/');
        $request = (new RequestFactory())
            ->create(
                $this->getServerParams(),
                [],
                ROOT_URL . '/api.php',
                'DELETE',
                'php://input',
                [],
                [],
                $query_params,
                $payload
            );

        return $this->executeRequest($request, null, $modify_request_and_response);
    }

    public function executeDeleteRequestAs(
        AuthenticatedUserInterface $user,
        $path,
        $payload = [],
        callable $modify_request_and_response = null
    )
    {
        $query_params['path_info'] = trim((string) $path, '/');
        $request = (new RequestFactory())
            ->create(
                $this->getServerParams(),
                [],
                ROOT_URL . '/api.php',
                'DELETE',
                'php://input',
                [],
                [],
                $query_params,
                $payload
            );

        /** @var ServerRequestInterface $request */
        /** @var ResponseInterface $response */
        [$request, $response] = $this->prepareRequestAndResponseFor($user, $request);

        return $this->executeRequest($request, $response, $modify_request_and_response);
    }

    private function executeRequest(
        ServerRequestInterface $request,
        ResponseInterface $response = null,
        callable $modify_request_and_response = null
    ): ResponseInterface
    {
        if ($response === null) {
            $response = new Response();
        }

        if (is_callable($modify_request_and_response)) {
            [$request, $response] = $modify_request_and_response($request, $response);

            if (!$request instanceof RequestInterface || !$response instanceof ResponseInterface) {
                throw new RuntimeException('Request/response modification callback is expected to return a modified request');
            }
        }

        return AngieApplication::executeHttpMiddlewareStack($request, $response);
    }

    /**
     * Prepare request and response for requests that are being made by an authenticated user.
     *
     * @return array
     */
    private function prepareRequestAndResponseFor(
        AuthenticatedUserInterface $user,
        RequestInterface $request, ResponseInterface $response = null
    )
    {
        $session = $this->createUserSession($user);

        if ($response === null) {
            $response = new Response();
        }

        /** @var ServerRequestInterface $request */
        $request = $request->withHeader(
            'X-Angie-CsrfValidator',
            $this->encryptor->encrypt($session->getCsrfValidator())
        );

        return $this->cookies->set($request, $response, $this->session_id_cookie_name, $session->getSessionId());
    }

    private function createUserSession(AuthenticatedUserInterface $user, $remember = false): UserSession
    {
        return (new SessionsRepository())->createSession(
            $user,
            [
                'remember' => (bool) $remember,
            ]
        );
    }

    private function getServerParams(): array
    {
        return [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Mobile/7B405',
        ];
    }
}

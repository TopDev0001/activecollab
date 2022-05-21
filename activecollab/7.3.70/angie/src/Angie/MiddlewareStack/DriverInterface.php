<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\MiddlewareStack;

use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface DriverInterface
{
    /**
     * Execute a GET request and return resulting request and response.
     *
     * @param  string            $path
     * @param  array             $query_params
     * @return ResponseInterface
     */
    public function executeGetRequest($path, $query_params = [], callable $modify_request_and_response = null);

    /**
     * Execute GET request as a given user.
     *
     * @param  string            $path
     * @param  array             $query_params
     * @return ResponseInterface
     */
    public function executeGetRequestAs(
        AuthenticatedUserInterface $user,
        $path,
        $query_params = [],
        callable $modify_request_and_response = null
    );

    /**
     * Execute POST request.
     *
     * @param  string            $path
     * @param  array             $payload
     * @return ResponseInterface
     */
    public function executePostRequest($path, $payload = [], callable $modify_request_and_response = null);

    /**
     * Execute POST request as $user.
     *
     * @param  string            $path
     * @param  array             $payload
     * @return ResponseInterface
     */
    public function executePostRequestAs(
        AuthenticatedUserInterface $user,
        $path,
        $payload = [],
        callable $modify_request_and_response = null
    );

    /**
     * Execute POST request.
     *
     * @param  string            $path
     * @param  array             $payload
     * @return ResponseInterface
     */
    public function executePutRequest($path, $payload = [], callable $modify_request_and_response = null);

    /**
     * Execute POST request as $user.
     *
     * @param  string            $path
     * @param  array             $payload
     * @return ResponseInterface
     */
    public function executePutRequestAs(
        AuthenticatedUserInterface $user,
        $path,
        $payload = [],
        callable $modify_request_and_response = null
    );

    /**
     * Execute delete action.
     *
     * @param  string            $path
     * @return ResponseInterface
     */
    public function executeDeleteRequest(
        $path,
        callable $modify_request_and_response = null
    );

    /**
     * Execute DELETE request as $user.
     *
     * @param  string            $path
     * @return ResponseInterface
     */
    public function executeDeleteRequestAs(
        AuthenticatedUserInterface $user,
        $path,
        callable $modify_request_and_response = null
    );

    /**
     * @return ServerRequestInterface[]
     */
    public function getRequestsLog();

    /**
     * @return array
     */
    public function getLastRequestAndResponse();

    /**
     * @return ServerRequestInterface|null
     */
    public function getLastRequest();

    /**
     * @return ResponseInterface|null
     */
    public function getLastResponse();
}

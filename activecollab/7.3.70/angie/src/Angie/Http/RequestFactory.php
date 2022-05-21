<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Http;

use function Laminas\Diactoros\marshalHeadersFromSapi;
use function Laminas\Diactoros\marshalUriFromSapi;
use function Laminas\Diactoros\normalizeServer;
use function Laminas\Diactoros\normalizeUploadedFiles;
use Laminas\Diactoros\ServerRequestFactory;

class RequestFactory extends ServerRequestFactory
{
    /**
     * Create request from arguments.
     *
     * @param  null    $uri
     * @param  null    $method
     * @param  string  $body
     * @param  null    $parsed_body
     * @param  string  $protocol
     * @return Request
     */
    public function create(
        array $server_params = [],
        array $uploaded_files = [],
        $uri = null,
        $method = null,
        $body = 'php://input',
        array $headers = [],
        array $cookies = [],
        array $query_params = [],
        $parsed_body = null,
        $protocol = '1.1'
    )
    {
        return new Request(
            $server_params,
            $uploaded_files,
            $uri,
            $method,
            $body,
            $headers,
            $cookies,
            $query_params,
            $parsed_body,
            $protocol
        );
    }

    /**
     * Construct request object from superglobals.
     *
     * @return Request
     */
    public function createFromGlobals()
    {
        $post = [];
        $server = normalizeServer($_SERVER);
        $files = normalizeUploadedFiles($_FILES);
        $headers = marshalHeadersFromSapi($server);
        $request_method = array_var($server, 'REQUEST_METHOD', 'GET');
        $content_type = $this->resolveContentType();

        if ($request_method === 'PUT' || $request_method === 'DELETE') {
            if (strpos($content_type, 'multipart/form-data') !== false) {
                // NOOP
            } elseif (strpos($content_type, 'application/json') !== false) {
                $input = $this->getPhpInput();

                if ($input) {
                    $post = json_decode($input, true);
                }
            } else {
                $input = $this->getPhpInput();

                if ($input) {
                    parse_str($input, $post);
                }
            }
        } elseif ($request_method === 'POST') {
            if (strpos($content_type, 'application/json') !== false) {
                $input = $this->getPhpInput();

                if ($input) {
                    $post = json_decode($input, true);
                }
            } elseif (strpos($content_type, 'application/x-www-form-urlencoded') !== false) {
                $post = $_POST;
            }
        }

        return new Request(
            $server,
            $files,
            marshalUriFromSapi($server, $headers),
            $request_method,
            'php://input',
            $headers,
            $_COOKIE,
            $_GET,
            $post
        );
    }

    /**
     * Resolve request content type.
     *
     * @return string
     */
    private function resolveContentType()
    {
        // Most servers that we tested use CONTENT_TYPE
        if (array_key_exists('CONTENT_TYPE', $_SERVER)) {
            return $_SERVER['CONTENT_TYPE'];
        }

        // PHP built in server will send requests like this
        if (array_key_exists('HTTP_CONTENT_TYPE', $_SERVER)) {
            return $_SERVER['HTTP_CONTENT_TYPE'];
        }

        return '';
    }

    /**
     * Get input from STDIN.
     *
     * @return string
     */
    private function getPhpInput()
    {
        return trim(file_get_contents('php://input'));
    }
}

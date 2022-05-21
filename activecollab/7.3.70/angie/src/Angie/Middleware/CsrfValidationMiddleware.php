<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Middleware;

use ActiveCollab\Cookies\CookiesInterface;
use ActiveCollab\Encryptor\EncryptorInterface;
use Angie\Http\Encoder\EncoderInterface;
use Angie\Middleware\Base\CsrfMiddleware;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use UserSession;

/**
 * @package Angie\Middleware
 */
class CsrfValidationMiddleware extends CsrfMiddleware
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * CsrfMiddleware constructor.
     *
     * @param string $csrf_validator_cookie_name
     */
    public function __construct(EncryptorInterface $encryptor, CookiesInterface $cookies, $csrf_validator_cookie_name, EncoderInterface $encoder, LoggerInterface $logger = null)
    {
        parent::__construct($cookies, $csrf_validator_cookie_name, $encoder, $logger);

        $this->encryptor = $encryptor;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $authenticated_with = $request->getAttribute('authenticated_with');

        if ($authenticated_with instanceof UserSession) {
            if ($this->isWriteMethod($request->getMethod())) {
                $csrf_validator = $this->getCsrfValidatorFromRequest($request);

                if (!$this->isValidCsrf($csrf_validator, $authenticated_with)) {
                    if ($this->getLogger()) {
                        $this->getLogger()->warning('Invalid CSRF validator value', [
                            'csrf_validator' => $csrf_validator,
                            'path' => array_key_exists('path_info', $request->getQueryParams()) ? $request->getQueryParams()['path_info'] : '/',
                        ]);
                    }

                    return $response->withStatus(400);
                }
            }

            [$request, $response] = $this->cookies->set($request, $response, $this->getCsrfValidatorCookieName(), $authenticated_with->getCsrfValidator(), [
                'ttl' => $authenticated_with->getSessionTtl(),
                'http_only' => false,
            ]);
        }

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }

    /**
     * Return true if $method is write HTTP verb.
     *
     * @param  string $method
     * @return bool
     */
    private function isWriteMethod($method)
    {
        if (!ctype_upper($method)) {
            $method = strtoupper($method);
        }

        return in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']);
    }

    /**
     * @return string
     */
    private function getCsrfValidatorFromRequest(ServerRequestInterface $request)
    {
        $header_name = $this->getCsrfValidatorHeaderName();

        if ($request->hasHeader($header_name)) {
            $value_to_decrypt = $request->getHeaderLine($header_name);
            $sent_via = 'header';
        } else {
            $query_params = $request->getQueryParams();

            $value_to_decrypt = array_key_exists($header_name, $query_params) ? $query_params[$header_name] : '';
            $sent_via = 'query_param';
        }

        if (strpos($value_to_decrypt, '%') !== false) {
            $value_to_decrypt = urldecode($value_to_decrypt);
        }

        if ($value_to_decrypt) {
            try {
                return $this->encryptor->decrypt($value_to_decrypt);
            } catch (Exception $e) {
                if ($this->getLogger()) {
                    $this->getLogger()->error('Unencrypted CSRF validator sent', [
                        'value' => $value_to_decrypt,
                        'sent_via' => $sent_via,
                    ]);
                }
            }
        }

        return '';
    }

    /**
     * Return true if $csrf_validator matches validator value of the current user session.
     *
     * @param  string $csrf_validator
     * @return bool
     */
    private function isValidCsrf($csrf_validator, UserSession $session)
    {
        return $csrf_validator && $csrf_validator == $session->getCsrfValidator();
    }
}

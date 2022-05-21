<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Authentication\RequestProcessor;

use ActiveCollab\ActiveCollabJwt\Factory\JwtFactoryInterface;
use ActiveCollab\ActiveCollabJwt\Verifier\JwtVerifierInterface;
use ActiveCollab\Authentication\Authorizer\RequestProcessor\RequestProcessingResult\RequestProcessingResult;
use ActiveCollab\Authentication\Authorizer\RequestProcessor\RequestProcessorInterface;
use ActiveCollab\Authentication\Saml\SamlUtils;
use ActiveCollab\Authentication\Session\SessionInterface;
use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use Angie\Http\Response\MovedResource\MovedResource;
use AngieApplication;
use InvalidArgumentException;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Psr\Http\Message\ServerRequestInterface;

class ShepherdRequestProcessor implements RequestProcessorInterface
{
    const INTENT_ISSUER_SHEPHERD = 'https://accounts.activecollab.com';
    const INTENT_AUDIENCE_ACTIVECOLLAB = 'https://app.activecollab.com';

    /**
     * @var SamlUtils
     */
    private $saml_utils;

    /**
     * @var CurrentTimestampInterface
     */
    private $current_timestamp;

    private $jwt_verifier;

    public function __construct(
        CurrentTimestampInterface $current_timestamp,
        JwtVerifierInterface $jwt_verifier,
        SamlUtils $saml_utils
    ) {
        $this->jwt_verifier = $jwt_verifier;
        $this->saml_utils = $saml_utils;
        $this->current_timestamp = $current_timestamp;
    }

    public function processRequest(ServerRequestInterface $request)
    {
        $payload = null;
        $parsed_body_data = $request->getParsedBody();

        if ($this->isIntentRequest($request)) {
            $client_vendor = isset($parsed_body_data['client_vendor']) ? $parsed_body_data['client_vendor'] : null;
            $client_name = isset($parsed_body_data['client_name']) ? $parsed_body_data['client_name'] : null;

            try {
                AngieApplication::log()->warning('Verifying JWT token');
                $claims = $this->jwt_verifier
                    ->verify(
                        JwtFactoryInterface::SIGNER_HMAC_SHA256,
                        defined('SHEPHERD_JWT_KEY') ? SHEPHERD_JWT_KEY : '',
                        $parsed_body_data['intent'] ?? '',
                        self::INTENT_ISSUER_SHEPHERD
                    );
                AngieApplication::log()->warning('JWT token verified');
            } catch (InvalidTokenStructure $e) {
                AngieApplication::log()->notice(
                    'Client has tried to authorise with an old intent',
                    [
                        'request' => $request,
                    ]
                );

                throw $e;
            } catch (\Throwable $e) {
                AngieApplication::log()->warning('Token verification failed due to an error: {error}', [
                    'error' => $e->getMessage(),
                ]);
            }

            $email = $claims['email'] ?? '';

            if ($this->verifyEmail($email)) {
                $credentials = [
                    'username' => $email,
                    'client_vendor' => $client_vendor,
                    'client_name' => $client_name,
                ];
            } else {
                throw new InvalidArgumentException('Provided intent not valid or has expired');
            }
        } else {
            $response = $this->saml_utils->parseSamlResponse($parsed_body_data);

            if (empty($response)) {
                $response = [];
            }

            $credentials = [
                'username' => $this->saml_utils->getEmailAddress($response),
                'remember' => $this->saml_utils->getSessionDurationType($response) === SessionInterface::SESSION_DURATION_LONG,
            ];

            $redirect_url = rtrim($this->saml_utils->getIssuerUrl($response), '/');
            $redirect_url .= strpos($redirect_url, '?') === false
                ? '/?prevent_redirect=1'
                : '&prevent_redirect=1';
            $payload = new MovedResource($redirect_url, false);
        }

        return new RequestProcessingResult($credentials, $payload);
    }

    private function isIntentRequest(ServerRequestInterface $request): bool
    {
        return array_key_exists('intent', $request->getParsedBody());
    }

    private function verifyEmail(string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email is not valid');
        }

        return true;
    }
}

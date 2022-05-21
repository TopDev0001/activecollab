<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Authentication\Adapter\AdapterInterface;
use ActiveCollab\Authentication\Adapter\BrowserSessionAdapter;
use ActiveCollab\Authentication\Adapter\TokenBearerAdapter;
use ActiveCollab\Authentication\AuthenticationResult\AuthenticationResultInterface;
use ActiveCollab\Authentication\AuthenticationResult\Transport\Authentication\AuthenticationTransportInterface;
use ActiveCollab\Authentication\Authorizer\ExceptionAware\ExceptionAwareInterface as ExceptionAwareAuthorizerInterface;
use ActiveCollab\Authentication\Authorizer\RequestAware\RequestAwareInterface as RequestAwareAuthorizerInterface;
use ActiveCollab\Module\System\SystemModule;
use ActiveCollab\Module\System\Utils\JwtTokenIssuer\JwtTokenIssuerInterface;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;
use Psr\Http\Message\ServerRequestInterface;

AngieApplication::useController('auth_not_required', SystemModule::NAME);

class UserSessionController extends AuthNotRequiredController
{
    public function who_am_i(Request $request, User $user = null)
    {
        if ($user instanceof User) {
            return Users::prepareCollection('initial_for_logged_user', $user);
        }

        return $this->requireAuthorization();
    }

    public function login(Request $request)
    {
        if ($this->shouldBlockAuthorizationRequest($request, 'login')) {
            return Response::FORBIDDEN;
        }

        $authorizer = AngieApplication::authentication()->getAuthorizer();

        $credentials = $request->post();
        $payload = null;

        try {
            if ($authorizer instanceof RequestAwareAuthorizerInterface && $authorizer->getRequestProcessor()) {
                $request_processing_result = $authorizer->getRequestProcessor()->processRequest($request);

                $credentials = $request_processing_result->getCredentials();
                $payload = $request_processing_result->getDefaultPayload();
            }

            /** @var AuthenticationTransportInterface $auth */
            $auth = AngieApplication::authentication()->authorizeForAdapter(
                $credentials,
                BrowserSessionAdapter::class,
                $payload
            );

            /** @var User $user */
            $user = $auth->getAuthenticatedUser();

            if ($user instanceof User && !$user->getFirstLoginOn() instanceof DateTimeValue) {
                $user->setFirstLoginOn(DateTimeValue::now());
                $user->save();
            }

            return $auth;
        } catch (Exception $e) {
            $this->logFailedAuthorization($request, 'login', $credentials, $e);

            if ($authorizer instanceof ExceptionAwareAuthorizerInterface) {
                $exception_handling_result = $authorizer->handleException($credentials, $e);

                if ($exception_handling_result !== null) {
                    return $exception_handling_result;
                }
            }

            throw ($e);
        }
    }

    public function logout(Request $request, User $user = null)
    {
        if ($user) {
            /** @var AdapterInterface $authentication_adapter */
            $authentication_adapter = $request->getAttribute('authentication_adapter');

            /** @var AuthenticationResultInterface $authenticated_with */
            $authenticated_with = $request->getAttribute('authenticated_with');

            if ($authentication_adapter && $authenticated_with) {
                return AngieApplication::authentication()->terminate($authentication_adapter, $authenticated_with);
            }
        }

        return Response::BAD_REQUEST;
    }

    public function issue_token(Request $request)
    {
        if ($this->shouldBlockAuthorizationRequest($request, 'issue_token')) {
            return Response::FORBIDDEN;
        }

        $authorizer = AngieApplication::authentication()->getAuthorizer();

        $credentials = $request->post();
        $payload = null;

        try {
            if ($authorizer instanceof RequestAwareAuthorizerInterface && $authorizer->getRequestProcessor()) {
                $request_processing_result = $authorizer->getRequestProcessor()->processRequest($request);
                $credentials = $request_processing_result->getCredentials();

                $payload = $request_processing_result->getDefaultPayload();
            }

            AngieApplication::log()->debug(
                'Authorising user',
                [
                    'authorizer' => get_class($authorizer),
                ]
            );

            return AngieApplication::authentication()->authorizeForAdapter(
                $credentials,
                TokenBearerAdapter::class,
                $payload
            );
        } catch (Exception $e) {
            $this->logFailedAuthorization($request, 'issue_token', $credentials, $e);

            return Response::OPERATION_FAILED;
        }
    }

    public function issue_file_access_token(Request $request)
    {
        $user = AngieApplication::authentication()->getLoggedUser();

        if (!$user instanceof User) {
            return $this->requireAuthorization();
        }

        return [
            'thumb_token' => (string) AngieApplication::getContainer()->get(JwtTokenIssuerInterface::class)->issueThumbnailToken($user),
            'preview_token' => (string) AngieApplication::getContainer()->get(JwtTokenIssuerInterface::class)->issuePreviewToken($user),
            'download_token' => (string) AngieApplication::getContainer()->get(JwtTokenIssuerInterface::class)->issueDownloadToken($user),
            'ttl' => JwtTokenIssuerInterface::TOKEN_REFRESH_IN_SECONDS,
        ];
    }

    public function view_invitation(Request $request)
    {
        $invitation = $this->getInvitationFromParameters($request);

        if ($invitation instanceof UserInvitation) {
            $user = $invitation->getUser();
            $invited_by = $invitation->getCreatedBy();
            $invited_to = $invitation->getInvitedTo();

            return [
                'status' => $invitation->getStatus(),
                'user' => $user instanceof User && $user->isActive()
                    ? [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'first_name' => $user->getFirstName(),
                        'last_name' => $user->getLastName(),
                        'thumb_token' => (string) AngieApplication::getContainer()->get(JwtTokenIssuerInterface::class)->issueThumbnailToken($user),
                    ]
                    : null,
                'invited_by' => $invited_by instanceof User
                    ? [
                        'id' => $invited_by->getId(),
                        'full_name' => $invited_by->getDisplayName(),
                        'first_name' => $invited_by->getFirstName(),
                        'last_name' => $invited_by->getLastName(),
                    ]
                    : null,
                'invited_to' => $invited_to
                    ? [
                        'id' => $invited_to->getId(),
                        'class' => get_class($invited_to),
                        'name' => $invited_to->getName(),
                    ]
                    : null,
            ];
        }

        return Response::NOT_FOUND;
    }

    public function accept_invitation(Request $request)
    {
        $invitation = $this->getInvitationFromParameters($request);

        if (!$invitation instanceof UserInvitation) {
            return Response::NOT_FOUND;
        }

        if ($invitation->getStatus() !== UserInvitation::ACCEPTABLE) {
            return Response::GONE; // Expired
        }

        [
            $first_name,
            $last_name,
            $password,
            $language_id,
            $uploaded_avatar_code,
            $should_subscribe_to_newsletter
        ] = $this->validateInvitationParameters($request);

        if (mb_strlen($password) > 250) {
            return Response::BAD_REQUEST;
        }

        $invited_user = $invitation->getUser();

        Users::update(
            $invited_user,
            [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'password' => $password,
                'language_id' => $language_id >= 1 && Languages::findById($language_id) instanceof Language ? $language_id : 0,
                'uploaded_avatar_code' => $uploaded_avatar_code,
            ]
        );

        $invited_user->setAdditionalProperty('should_subscribe_to_newsletter', $should_subscribe_to_newsletter);
        $invited_user->invitationAccepted();

        return AngieApplication::authentication()->authorizeForAdapter(
            [
                'username' => $invited_user->getEmail(),
                'password' => $password,
            ],
            BrowserSessionAdapter::class,
            Users::prepareCollection('initial_for_logged_user', $invited_user)
        );
    }

    private function requireAuthorization()
    {
        return new StatusResponse(
            401,
            'User not authenticated.',
            Users::prepareCollection('initial_for_logged_user', null)
        );
    }

    private function shouldBlockAuthorizationRequest(ServerRequestInterface $request, $action)
    {
        $ip_address = $request->getAttribute('visitor_ip_address');

        if ($this->shouldBlockIpAddress($ip_address)) {
            AngieApplication::log()->notice(
                'Brute force protection blocked {action} request from {ip_address}.',
                [
                    'action' => $action,
                    'ip_address' => $ip_address,
                ]
            );

            return true;
        }

        return false;
    }

    private function logFailedAuthorization(ServerRequestInterface $request, $action, array $credentials, Exception $exception)
    {
        AngieApplication::log()->notice(
            'Authorization failed for {username} using {action}. Reason: {reason}.',
            [
                'action' => $action,
                'username' => array_var($credentials, 'username', '--username not set--'),
                'credentials' => $this->prepareCredentialsForLog($credentials),
                'reason' => $exception->getMessage(),
                'exception' => $exception,
            ]
        );

        $ip_address = $request->getAttribute('visitor_ip_address');

        if ($this->shouldBlockIpAddress($ip_address)) {
            /** @var FailedLoginNotification $failed_login_notification */
            $failed_login_notification = AngieApplication::notifications()->notifyAbout('failed_login');
            $failed_login_notification
                ->setUsername(array_var($credentials, 'username'))
                ->setMaxAttempts(AngieApplication::bruteForceProtector()->getMaxAttempts())
                ->setCooldownInMinutes(ceil(AngieApplication::bruteForceProtector()->getInTimeframe() / 60))
                ->setFromIP($ip_address)
                ->sendToAdministrators(true);
        }
    }

    private function prepareCredentialsForLog(array $credentials): array
    {
        $result = [];

        foreach ($credentials as $k => $v) {
            if (strtolower($k) === 'password') {
                $result[$k] = str_repeat('*', strlen((string) $v));
            } else {
                $result[$k] = $v;
            }
        }

        return $result;
    }

    private function shouldBlockIpAddress($ip_address): bool
    {
        return AngieApplication::bruteForceProtector()->shouldBlock($ip_address);
    }

    private function getInvitationFromParameters(Request $request): ?UserInvitation
    {
        $invitation = UserInvitations::findByUserIdAndCode(
            $request->get('user_id'),
            $request->get('code')
        );

        if ($invitation instanceof UserInvitation) {
            return $invitation;
        }

        return null;
    }

    private function validateInvitationParameters(Request $request): array
    {
        $first_name = trim($request->post('first_name'));
        $last_name = trim($request->post('last_name'));
        $password = $request->post('password');
        $language_id = (int) $request->post('language_id');
        $uploaded_avatar_code = $request->post('uploaded_avatar_code');
        $should_subscribe_to_newsletter = (bool) $request->post('should_subscribe_to_newsletter');

        $errors = new ValidationErrors();

        if (empty($first_name)) {
            $errors->fieldValueIsRequired('first_name');
        }

        if (empty($last_name)) {
            $errors->fieldValueIsRequired('last_name');
        }

        if (trim($password) === '') {
            $errors->fieldValueIsRequired('password');
        }

        if (empty($language_id)) {
            $errors->fieldValueIsRequired('language');
        }

        if ($errors->hasErrors()) {
            throw $errors;
        }

        return [
            $first_name,
            $last_name,
            $password,
            $language_id,
            $uploaded_avatar_code,
            $should_subscribe_to_newsletter,
        ];
    }
}

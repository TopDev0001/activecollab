<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Utils\Webhooks\Transformator\SlackWebhookPayloadTransformator;
use Angie\Http\Response;
use Frlnc\Slack\Core\Commander;
use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Http\SlackResponseFactory;

class SlackIntegration extends Integration
{
    /**
     * @var string
     */
    private $hash;

    /**
     * @var Commander
     */
    private $commander;

    /**
     * Initiate Slack SDK.
     *
     * @return Commander
     */
    public function commander()
    {
        if (empty($this->commander)) {
            $interactor = new CurlInteractor();
            $interactor->setResponseFactory(new SlackResponseFactory());

            $this->commander = new Commander($this->getAccessToken(), $interactor);
        }

        return $this->commander;
    }

    /**
     * Return client id.
     *
     * @return string
     */
    public function getClientId()
    {
        return defined('SLACK_CLIENT_KEY') ? SLACK_CLIENT_KEY : $this->getAdditionalProperty('client_id');
    }

    /**
     * Set client id.
     *
     * @param  string $value
     * @return string
     */
    public function setClientId($value)
    {
        if (!AngieApplication::isOnDemand()) {
            $this->setAdditionalProperty('client_id', $value);
        }

        return self::getClientId();
    }

    /**
     * Get client secret.
     *
     * @return string
     */
    public function getClientSecret()
    {
        return defined('SLACK_CLIENT_SECRET')
            ? SLACK_CLIENT_SECRET
            : $this->getAdditionalProperty('client_secret');
    }

    /**
     * Set client secret.
     *
     * @param $value
     * @return string
     */
    public function setClientSecret($value)
    {
        if (!AngieApplication::isOnDemand()) {
            $this->setAdditionalProperty('client_secret', $value);
        }

        return self::getClientSecret();
    }

    public function isSingleton(): bool
    {
        return true;
    }

    public function isInUse(User $user = null): bool
    {
        return $this->getClientId() && $this->getClientSecret() && (bool) Webhooks::countEnabledForIntegration($this);
    }

    /**
     * Return full name of the integration.
     *
     * @return string
     */
    public function getName()
    {
        return 'Slack';
    }

    public function getShortName(): string
    {
        return 'slack';
    }

    /**
     * Return integration description.
     *
     * @return string
     */
    public function getDescription()
    {
        return lang('Get ActiveCollab updates in Slack');
    }

    /**
     * Set code token.
     *
     * @param $code
     */
    public function setCode($code)
    {
        $this->setAdditionalProperty('code', $code);
    }

    /**
     * Return code token.
     *
     * @return mixed
     */
    public function getCode()
    {
        return $this->getAdditionalProperty('code');
    }

    /**
     * Save access parameters.
     *
     * @param $params
     */
    public function setAuthorizationParameters($params)
    {
        $this->setAdditionalProperty('params', $params);
    }

    /**
     * Return access parameters.
     *
     * @return mixed
     */
    public function getAuthorizationParameters()
    {
        return $this->getAdditionalProperty('params');
    }

    /**
     * Return access token.
     *
     * @return string|null
     */
    public function getAccessToken()
    {
        return $this->getAdditionalProperty('params')['access_token'];
    }

    /**
     * Add new notification channel.
     *
     * @param  string       $channel
     * @param  string       $url
     * @return SlackWebhook
     */
    public function addNotificationChannel($channel, $url, array $project_ids)
    {
        $webhook = new SlackWebhook();
        $webhook->setName($channel);
        $webhook->setIntegrationId($this->getId());
        $webhook->setUrl($url);
        $webhook->setIsEnabled(true);
        $webhook->setFilterEventTypes((new SlackWebhookPayloadTransformator())->getSupportedEvents());
        $webhook->setFilterProjects($project_ids);
        $webhook->save();

        return $webhook;
    }

    /**
     * Serialize.
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'notification_channels' => Webhooks::findEnabledForIntegration($this),
                'is_connected' => (bool) $this->getClientId() && (bool) $this->getClientSecret(),
                'authorization_url' => $this->getOAuthAuthorizationUrl(),
                'state' => $this->getStateHash(),
            ]
        );
    }

    /**
     * Compose OAuth authorization url.
     *
     * @return string
     */
    public function getOAuthAuthorizationUrl()
    {
        return 'https://slack.com/oauth/authorize?' . http_build_query([
            'scope' => 'incoming-webhook',
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getAbsoluteIntegrationUrl(),
            'state' => $this->getStateHash(),
        ]);
    }

    /**
     * Compose absolute url of an integration.
     *
     * @return string
     */
    public function getAbsoluteIntegrationUrl()
    {
        $url_path = $this->getUrlPath();

        // Make sure that we avoid // in generated URL.
        if (str_starts_with($url_path, '/') && str_ends_with(URL_BASE, '/')) {
            return rtrim(URL_BASE, '/') . $url_path;
        } else {
            return URL_BASE . $url_path;
        }
    }

    /**
     * Get state hash string.
     *
     * @return string
     */
    public function getStateHash()
    {
        if (empty($this->hash)) {
            $this->hash = make_string();
        }

        return $this->hash;
    }

    /**
     * Send channel authorization request.
     *
     * @return SlackWebhook
     */
    public function authorizeChannel(array $projects)
    {
        $response = $this->commander()->execute(
            'oauth.access',
            [
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'code' => $this->getCode(),
                'redirect_uri' => $this->getAbsoluteIntegrationUrl(),
            ]
        );

        if ($response->getStatusCode() == Response::OK && $response->getBody()['ok']) {
            $this->setAuthorizationParameters($response->getBody());
            $channel = $this->addNotificationChannel(
                $response->getBody()['incoming_webhook']['channel'],
                $response->getBody()['incoming_webhook']['url'],
                $projects
            );
            $this->save();

            return $channel;
        } else {
            throw new Exception($response->getBody()['error']);
        }
    }
}

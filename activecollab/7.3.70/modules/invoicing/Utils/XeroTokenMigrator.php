<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Utils;

use Exception;

class XeroTokenMigrator
{
    const MIGRATION_TOKEN_ENDPOINT = 'https://api.xero.com/oauth/migrate';
    private array $xeroApiSettings;

    public function __construct(array $xeroApiSettings)
    {
        $this->xeroApiSettings = $xeroApiSettings;
    }

    public function migrate(string $accessToken)
    {
        $ch = curl_init(self::MIGRATION_TOKEN_ENDPOINT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: OAuth ' . $this->buildOAuth1AuthorizationHeader($accessToken), 'Content-Type:application/json']);
        $payload = json_encode(
            [
                'scope' => 'offline_access accounting.transactions accounting.settings.read accounting.contacts.read',
                'client_id' => $this->xeroApiSettings['client_id'],
                'client_secret' => $this->xeroApiSettings['client_secret'],
            ]
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 2);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        unset($ch);

        if ($info['http_code'] !== 200) {
            throw new Exception($response . ' API Method: ' . __METHOD__ . ' Error code: ' . $info['http_code']);
        }

        return json_decode($response, true);
    }

    private function buildOAuth1AuthorizationHeader(string $accessToken)
    {
        $nonce = uniqid();
        $currentTimestamp = strval(time());
        $baseSignatureString = $this->generateBaseSignatureString($accessToken, $nonce, $currentTimestamp);
        $signatureString = $this->sign($baseSignatureString);

        return $this->generateAuthorizationHeader($accessToken, $nonce, $currentTimestamp, $signatureString);
    }

    private function generateBaseSignatureString(string $accessToken, string $nonce, string $currentTimestamp): string
    {
        $httpMethod = 'POST';

        $url = urlencode(self::MIGRATION_TOKEN_ENDPOINT);

        $oauthParameterString = '';
        $oauthParameterString .= "oauth_consumer_key={$this->xeroApiSettings['consumer_key']}&";
        $oauthParameterString .= "oauth_nonce={$nonce}&";
        $oauthParameterString .= 'oauth_signature_method=RSA-SHA1&';
        $oauthParameterString .= "oauth_timestamp={$currentTimestamp}&";
        $oauthParameterString .= "oauth_token={$accessToken}&";
        $oauthParameterString .= 'oauth_version=1.0';

        $oauthParameterString = urlencode($oauthParameterString);

        return "{$httpMethod}&{$url}&{$oauthParameterString}";
    }

    private function sign(string $signatureBaseString): string
    {
        // Fetch the private key
        if (false === $private_key_id = openssl_pkey_get_private('file://'.$this->xeroApiSettings['rsa_private_key'])) {
            throw new Exception('Cannot access private key for signing');
        }

        // Sign using the key
        if (false === openssl_sign($signatureBaseString, $signature, $private_key_id)) {
            throw new Exception('Cannot sign signature base string.');
        }

        // Release the key resource
        openssl_free_key($private_key_id);

        return base64_encode($signature);
    }

    private function generateAuthorizationHeader(string $accessToken, string $nonce, string $currentTimestamp, string $encodedSignature): string
    {
        $encodedSignature = urlencode($encodedSignature);

        $authorizationHeader = '';
        $authorizationHeader .= "oauth_consumer_key=\"{$this->xeroApiSettings['consumer_key']}\", ";
        $authorizationHeader .= "oauth_token=\"{$accessToken}\", ";
        $authorizationHeader .= 'oauth_signature_method="RSA-SHA1", ';
        $authorizationHeader .= "oauth_signature=\"{$encodedSignature}\", ";
        $authorizationHeader .= "oauth_timestamp=\"{$currentTimestamp}\", ";
        $authorizationHeader .= "oauth_nonce=\"{$nonce }\", ";
        $authorizationHeader .= 'oauth_version="1.0"';

        return $authorizationHeader;
    }
}

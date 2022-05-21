<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\JwtTokenIssuer;

use ActiveCollab\ActiveCollabJwt\Factory\JwtFactoryInterface;
use ActiveCollab\User\UserInterface;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Lcobucci\JWT\Token;

class FileAccessIntentIssuer implements JwtTokenIssuerInterface
{
    private string $file_access_key;
    private DateTimeImmutable $now;
    private JwtFactoryInterface $jwt_factory;
    private string $audience;
    private ?int $account_id;

    public function __construct(
        JwtFactoryInterface $jwt_factory,
        string $file_access_key,
        string $audience,
        DateTimeImmutable $now,
        ?int $account_id
    ) {
        $this->file_access_key = $file_access_key;
        $this->now = $now;
        $this->jwt_factory = $jwt_factory;
        $this->audience = $audience;
        $this->account_id = $account_id;
    }

    private function issue(UserInterface $user, string $intent, int $ttl): Token
    {
        if (!in_array($intent, self::INTENTS)) {
            throw new InvalidArgumentException('Unknown JWT File Access Token intent');
        }

        $expires_at = (new DateTimeImmutable())->setTimestamp($this->now->getTimestamp() + $ttl);
        $claims = [
            'email' => $user->getEmail(),
            'intent' => $intent,
        ];

        if ($this->account_id) {
            $claims['account_id'] = $this->account_id;
        }

        return $this->jwt_factory
            ->produceForSymmetricSigner(
                JwtFactoryInterface::SIGNER_HMAC_SHA256,
                $this->file_access_key,
                $claims,
                $expires_at,
                null,
                $this->audience
            );
    }

    public function issuePreviewToken(UserInterface $user): Token
    {
        return $this->issue($user, self::INTENT_PREVIEW, self::SHORT_LIVED_TOKEN_TTL_IN_SECONDS);
    }

    public function issueDownloadToken(UserInterface $user): Token
    {
        return $this->issue($user, self::INTENT_DOWNLOAD, self::SHORT_LIVED_TOKEN_TTL_IN_SECONDS);
    }

    public function issueThumbnailToken(UserInterface $user): Token
    {
        return $this->issue($user, self::INTENT_THUMBNAIL, self::LONG_LIVED_TOKEN_TTL_IN_SECONDS);
    }

    public function issueForIntent(string $intent, UserInterface $user): Token
    {
        switch ($intent) {
            case self::INTENT_PREVIEW:
                return $this->issuePreviewToken($user);
            case self::INTENT_DOWNLOAD:
                return $this->issueDownloadToken($user);
            case self::INTENT_THUMBNAIL:
                return $this->issueThumbnailToken($user);
            default:
                throw new Exception('Unhandled intent');
        }
    }
}

<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\JwtTokenIssuer;

use ActiveCollab\User\UserInterface;
use Lcobucci\JWT\Token;

interface JwtTokenIssuerInterface
{
    const WH_AUDIENCE = 'https://wh.activecollab.com';
    const TOKEN_REFRESH_IN_SECONDS = 3600;
    const SHORT_LIVED_TOKEN_TTL_IN_SECONDS = 86400;
    const LONG_LIVED_TOKEN_TTL_IN_SECONDS = self::SHORT_LIVED_TOKEN_TTL_IN_SECONDS * 14;

    const INTENT_THUMBNAIL = 'thumbnail';
    const INTENT_PREVIEW = 'preview';
    const INTENT_DOWNLOAD = 'download';

    const INTENTS = [
        self::INTENT_THUMBNAIL,
        self::INTENT_PREVIEW,
        self::INTENT_DOWNLOAD,
    ];
    const ISSUER = 'https://app.activecollab.com';

    public function issuePreviewToken(UserInterface $user): Token;
    public function issueThumbnailToken(UserInterface $user): Token;
    public function issueDownloadToken(UserInterface $user): Token;
    public function issueForIntent(string $intent, UserInterface $user): Token;
}

<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJwt\Factory;

use DateTimeImmutable;
use Lcobucci\JWT\Token;

interface JwtFactoryInterface
{
    const SIGNER_HMAC_SHA256 = 'HS256';

    public function produceForSymmetricSigner(
        string $signer,
        string $plaintext_key,
        array $payload = [],
        ?DateTimeImmutable $expires_at = null,
        ?DateTimeImmutable $not_available_before = null,
        ?string $audience = null,
        ?string $identified_by = null,
        ?array $headers = []
    ): Token;
}

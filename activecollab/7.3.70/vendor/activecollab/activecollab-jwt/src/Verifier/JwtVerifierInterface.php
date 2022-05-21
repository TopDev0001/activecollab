<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJwt\Verifier;

interface JwtVerifierInterface
{
    public function verify(
        string $signer,
        string $plaintext_key,
        string $raw_token,
        ?string $issuer = null
    ): array;
}
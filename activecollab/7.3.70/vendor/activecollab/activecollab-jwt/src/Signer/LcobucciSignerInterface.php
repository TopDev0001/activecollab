<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJwt\Signer;

use ActiveCollab\ActiveCollabJwt\Factory\JwtFactoryInterface;
use Lcobucci\JWT\Signer\Hmac\Sha256;

interface LcobucciSignerInterface
{
    const DEFAULT_SIGNER = JwtFactoryInterface::SIGNER_HMAC_SHA256;
    const SIGNERS = [
        JwtFactoryInterface::SIGNER_HMAC_SHA256 => Sha256::class,
    ];
}

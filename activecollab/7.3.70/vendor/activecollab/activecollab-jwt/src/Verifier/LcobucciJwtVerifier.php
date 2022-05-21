<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJwt\Verifier;

use ActiveCollab\ActiveCollabJwt\Signer\LcobucciSignerInterface;
use ActiveCollab\ActiveCollabJwt\Signer\SignerResolver;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\ConstraintViolation;

class LcobucciJwtVerifier implements JwtVerifierInterface, LcobucciSignerInterface
{
    use SignerResolver;

    private string $audience;
    private DateTimeImmutable $now;

    public function __construct(string $audience, ?DateTimeImmutable $now = null)
    {
        $this->audience = $audience;
        $this->now = $now ?: new DateTimeImmutable();
    }

    public function verify(
        string $signer,
        string $plaintext_key,
        string $raw_token,
        ?string $issuer = null
    ): array {
        $signer = $this->resolveSigner($signer);
        $key = InMemory::plainText($plaintext_key);

        $config = Configuration::forSymmetricSigner(
            $signer,
            $key
        );

        $token = $config->parser()->parse($raw_token);

        $constraints = [
            new PermittedFor($this->audience),
            new SignedWith($signer, $key),
        ];

        if (!$token->hasBeenIssuedBefore($this->now)) {
            throw new ConstraintViolation('The token was issued in the future');
        }

        if ($token->isExpired($this->now)) {
            throw new ConstraintViolation('The token is expired');
        }

        if (!$token->isMinimumTimeBefore($this->now)) {
            throw new ConstraintViolation('The token cannot be used yet');
        }

        if ($issuer) {
            $constraints[] = new IssuedBy($issuer);
        }

        $config
            ->validator()
            ->assert(
                $token,
                ...$constraints
            );

        return $token->claims()->all();
    }
}
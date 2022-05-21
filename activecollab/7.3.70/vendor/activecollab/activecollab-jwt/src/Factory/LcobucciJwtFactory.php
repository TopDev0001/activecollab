<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJwt\Factory;

use ActiveCollab\ActiveCollabJwt\Signer\LcobucciSignerInterface;
use ActiveCollab\ActiveCollabJwt\Signer\SignerResolver;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;

class LcobucciJwtFactory implements JwtFactoryInterface, LcobucciSignerInterface
{
    use SignerResolver;

    private string $issuer;
    private DateTimeImmutable $now;

    public function __construct(
        string $issuer,
        ?DateTimeImmutable $now = null
    ) {
        $this->issuer = $issuer;
        $this->now = $now ?: new DateTimeImmutable();
    }

    public function produceForSymmetricSigner(
        string $signer,
        string $plaintext_key,
        ?array $payload = [],
        ?DateTimeImmutable $expires_at = null,
        ?DateTimeImmutable $not_available_before = null,
        ?string $audience = null,
        ?string $identified_by = null,
        ?array $headers = []
    ): Token {
        $signer = $this->resolveSigner($signer);
        $key = InMemory::plainText($plaintext_key);

        $config = Configuration::forSymmetricSigner(
            $signer,
            $key
        );

        $builder = $config->builder()
            ->issuedBy($this->issuer)
            ->issuedAt($this->now);

        if ($expires_at instanceof DateTimeImmutable) {
            $builder->expiresAt($expires_at);
        }

        if ($not_available_before instanceof DateTimeImmutable) {
            $builder->canOnlyBeUsedAfter($not_available_before);
        }

        if ($audience) {
            $builder->permittedFor($audience);
        }

        if ($identified_by) {
            $builder->identifiedBy($identified_by);
        }

        if (!empty($headers)) {
            foreach ($headers as $claim => $value) {
              $builder->withHeader($claim, $value);
            }
        }

        if (!empty($payload)) {
            foreach ($payload as $claim => $value) {
                $builder->withClaim($claim, $value);
            }
        }

        return $builder->getToken($config->signer(), $config->signingKey());
    }
}

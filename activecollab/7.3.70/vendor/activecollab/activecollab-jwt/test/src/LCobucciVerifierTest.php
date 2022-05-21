<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJwt\Test;

use ActiveCollab\ActiveCollabJwt\Factory\JwtFactoryInterface;
use ActiveCollab\ActiveCollabJwt\Test\Base\TestCase;
use ActiveCollab\ActiveCollabJwt\Verifier\JwtVerifierInterface;
use ActiveCollab\ActiveCollabJwt\Verifier\LcobucciJwtVerifier;
use DateInterval;
use DateTimeImmutable;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\Validation\ConstraintViolation;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;

class LCobucciVerifierTest extends TestCase
{
    private string $audience;
    private JwtVerifierInterface $verifier;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->audience = 'https://foo.example.com';
        $this->now = new DateTimeImmutable('2021-07-05');
    }

    public function testIssuerMatchesGiven()
    {
        // headers:
        //   - typ: "JWT"
        //   - alg: "HS256"
        // claims:
        //   - iss: "https://app.example.com"
        //   - aud: "https://foo.example.com"
        //   - iat: 1609459201 (Friday, January 1, 2021 0:00:01)
        // key: 'test'
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FwcC5leGFtcGxlLmNvbSIsImlhdCI6MTYwOTQ1OTIwMSwiYXVkIjoiaHR0cHM6Ly9mb28uZXhhbXBsZS5jb20ifQ.jprNw3H26lvp75gRxqsP3EbjVaalri8X3V80r3c_VTU";

        $expected_issuer = 'https://app.example.com';
        $verifier = new LcobucciJwtVerifier($this->audience, $this->now);

        $claims = $verifier->verify(JwtFactoryInterface::SIGNER_HMAC_SHA256, 'test', $token);
        $this->assertArrayHasKey(RegisteredClaims::ISSUER, $claims);
        $this->assertEquals($expected_issuer, $claims[RegisteredClaims::ISSUER]);
    }

    public function testIssuedAtMatchesGiven()
    {
        // headers:
        //   - typ: "JWT"
        //   - alg: "HS256"
        // claims:
        //   - iss: "https://app.example.com"
        //   - aud: "https://foo.example.com"
        //   - iat: 1609459201 (Friday, January 1, 2021 0:00:01)
        // key: 'test'
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FwcC5leGFtcGxlLmNvbSIsImlhdCI6MTYwOTQ1OTIwMSwiYXVkIjoiaHR0cHM6Ly9mb28uZXhhbXBsZS5jb20ifQ.jprNw3H26lvp75gRxqsP3EbjVaalri8X3V80r3c_VTU";

        $expected_issued_at = new DateTimeImmutable('2021-01-01 00:00:01'); // must match the date from the token claims
        $now = $expected_issued_at->add(new DateInterval('P5D')); // +5 days

        $verifier = new LcobucciJwtVerifier($this->audience, $now);

        $claims = $verifier->verify(JwtFactoryInterface::SIGNER_HMAC_SHA256, 'test', $token);
        $this->assertArrayHasKey(RegisteredClaims::ISSUED_AT, $claims);
        $this->assertEquals($expected_issued_at, $claims[RegisteredClaims::ISSUED_AT]);
    }

    public function testAudienceMismatch()
    {
        $this->expectException(RequiredConstraintsViolated::class);
        $this->expectExceptionMessage("The token violates some mandatory constraints, details:\n- The token is not allowed to be used by this audience");

        // headers:
        //   - typ: "JWT"
        //   - alg: "HS256"
        // claims:
        //   - iss: "https://app.example.com"
        //   - aud: "https://foo.example.com"
        //   - iat: 1609459201 (Friday, January 1, 2021 0:00:01)
        // key: 'test'
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FwcC5leGFtcGxlLmNvbSIsImlhdCI6MTYwOTQ1OTIwMSwiYXVkIjoiaHR0cHM6Ly9mb28uZXhhbXBsZS5jb20ifQ.jprNw3H26lvp75gRxqsP3EbjVaalri8X3V80r3c_VTU";

        $expected_audience = 'https://bar.example.com';
        $verifier = new LcobucciJwtVerifier($expected_audience, $this->now);

        $verifier->verify(JwtFactoryInterface::SIGNER_HMAC_SHA256, 'test', $token);
    }

    public function testAudienceIsInClaims()
    {
        // headers:
        //   - typ: "JWT"
        //   - alg: "HS256"
        // claims:
        //   - iss: "https://app.example.com"
        //   - aud: "https://foo.example.com"
        //   - iat: 1609459201 (Friday, January 1, 2021 0:00:01)
        // key: 'test'
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FwcC5leGFtcGxlLmNvbSIsImlhdCI6MTYwOTQ1OTIwMSwiYXVkIjoiaHR0cHM6Ly9mb28uZXhhbXBsZS5jb20ifQ.jprNw3H26lvp75gRxqsP3EbjVaalri8X3V80r3c_VTU";
        $verifier = new LcobucciJwtVerifier($this->audience, $this->now);

        $claims = $verifier->verify(JwtFactoryInterface::SIGNER_HMAC_SHA256, 'test', $token);

        $this->assertIsArray($claims);
        $this->assertArrayHasKey(RegisteredClaims::AUDIENCE, $claims);
        $this->assertEquals([$this->audience], $claims[RegisteredClaims::AUDIENCE]);
    }
}

<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\ActiveCollabJwt\Test;

use ActiveCollab\ActiveCollabJwt\Factory\JwtFactoryInterface;
use ActiveCollab\ActiveCollabJwt\Factory\LcobucciJwtFactory;
use ActiveCollab\ActiveCollabJwt\Test\Base\TestCase;
use DateTimeImmutable;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\RegisteredClaims;

class LcobucciFactoryTest extends TestCase
{
    private LcobucciJwtFactory $factory;
    private $issuer;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->issuer = 'http://app.example.com';
        $this->now = new DateTimeImmutable();
        $this->factory = new LcobucciJwtFactory($this->issuer, $this->now);
    }

    public function testIssuerIsSetInClaims()
    {
        $token = $this->factory->produceForSymmetricSigner(JwtFactoryInterface::SIGNER_HMAC_SHA256, 'test');
        $this->assertTrue($token->hasBeenIssuedBy($this->issuer));
    }

    public function testTokenIsSignedUsingAppropriateSigner()
    {
        $hmac_sha256_signer = JwtFactoryInterface::SIGNER_HMAC_SHA256;

        $token = $this->factory->produceForSymmetricSigner($hmac_sha256_signer, 'test');

        $this->assertInstanceOf(Token::class, $token);
        $this->assertEquals($hmac_sha256_signer, $token->headers()->get('alg'));
    }

    public function testPayloadElementsAreSetAsClaims()
    {
        $token = $this->factory->produceForSymmetricSigner(
            JwtFactoryInterface::SIGNER_HMAC_SHA256,
            'test',
            [
                'foo' => 'bar',
                'baz' => 'fiz',
                'email' => 'example@example.com',
            ]
        );

        $this->assertTrue($token->claims()->has('foo'));
        $this->assertEquals('bar', $token->claims()->get('foo'));
        $this->assertTrue($token->claims()->has('baz'));
        $this->assertEquals('fiz', $token->claims()->get('baz'));
        $this->assertTrue($token->claims()->has('email'));
        $this->assertEquals('example@example.com', $token->claims()->get('email'));
    }

    public function testIssuedAtIsUsingGivenDateAndTime()
    {
        $issued_at = new DateTimeImmutable('2021-01-01 00:00:01');
        $token = (new LcobucciJwtFactory($this->issuer, $issued_at))->produceForSymmetricSigner(
            JwtFactoryInterface::SIGNER_HMAC_SHA256,
            'test',
            [],
            $this->now,
        );

        $this->assertTrue($token->claims()->has(RegisteredClaims::ISSUED_AT));
        $this->assertEquals($issued_at, $token->claims()->get(RegisteredClaims::ISSUED_AT));
    }

    public function testIssuedAtIsUsingCurrentDateAndTimeIfNoDateAndTimeGiven()
    {
        $token = $this->factory->produceForSymmetricSigner(
            JwtFactoryInterface::SIGNER_HMAC_SHA256,
            'test'
        );

        $this->assertTrue($token->claims()->has(RegisteredClaims::ISSUED_AT));
        $this->assertEquals($this->now->getTimestamp(), $token->claims()->get(RegisteredClaims::ISSUED_AT)->getTimestamp());
    }

    public function testIfExpirationIsGivenItIsSetAsClaim()
    {
        $token_without_expiration = $this->factory->produceForSymmetricSigner(
            JwtFactoryInterface::SIGNER_HMAC_SHA256,
            'test',
            [],
            null
        );

        $this->assertFalse($token_without_expiration->claims()->has(RegisteredClaims::EXPIRATION_TIME));

        $expires_at = new DateTimeImmutable('+1 day');
        $token_with_expiration = $this->factory->produceForSymmetricSigner(
            JwtFactoryInterface::SIGNER_HMAC_SHA256,
            'test',
            [],
            $expires_at
        );


        $this->assertTrue($token_with_expiration->claims()->has(RegisteredClaims::EXPIRATION_TIME));
        $this->assertEquals($expires_at, $token_with_expiration->claims()->get(RegisteredClaims::EXPIRATION_TIME));
    }

    public function testIfNotAvailableBeforeIsGivenItIsSetAsClaim()
    {
        $token_without_not_available_before = $this->factory->produceForSymmetricSigner(
            JwtFactoryInterface::SIGNER_HMAC_SHA256,
            'test',
            [],
            null,
            null
        );

        $this->assertFalse($token_without_not_available_before->claims()->has(RegisteredClaims::NOT_BEFORE));

        $not_available_before = new DateTimeImmutable('+1 day');
        $token_with_not_available_before = $this->factory->produceForSymmetricSigner(
            JwtFactoryInterface::SIGNER_HMAC_SHA256,
            'test',
            [],
            null,
            $not_available_before
        );

        $this->assertTrue($token_with_not_available_before->claims()->has(RegisteredClaims::NOT_BEFORE));
        $this->assertEquals($not_available_before, $token_with_not_available_before->claims()->get(RegisteredClaims::NOT_BEFORE));
    }

    /**
     * @dataProvider provideEmptyValues
     */
    public function testIfAudienceIsNotGivenItIsNotSetAsClaim($audience)
    {
        $token_without_audience = $this->factory->produceForSymmetricSigner(
            JwtFactoryInterface::SIGNER_HMAC_SHA256,
            'test',
            [],
            null,
            null,
            $audience
        );

        $this->assertFalse($token_without_audience->claims()->has(RegisteredClaims::AUDIENCE));
    }

    public function testIfAudienceIsGivenItIsSetAsClaim()
    {
        $audience = 'test.example.com';
        $token_with_audience = $this->factory->produceForSymmetricSigner(
            JwtFactoryInterface::SIGNER_HMAC_SHA256,
            'test',
            [],
            null,
            null,
            $audience
        );

        $this->assertTrue($token_with_audience->claims()->has(RegisteredClaims::AUDIENCE));
        $this->assertEquals([$audience], $token_with_audience->claims()->get(RegisteredClaims::AUDIENCE));
    }

    /**
     * @dataProvider provideEmptyValues
     */
    public function testIfIdentifiedByIsNotGivenItIsNotSetAsClaim($identified_by)
    {
        $token_without_identifier = $this->factory->produceForSymmetricSigner(
            JwtFactoryInterface::SIGNER_HMAC_SHA256,
            'test',
            [],
            null,
            null,
            null,
            $identified_by
        );

        $this->assertFalse($token_without_identifier->claims()->has(RegisteredClaims::ID));
    }

    public function testIfIdentifiedByIsGivenItIsSetAsClaim()
    {
        $token_with_identifier = $this->factory->produceForSymmetricSigner(
            JwtFactoryInterface::SIGNER_HMAC_SHA256,
            'test',
            [],
            null,
            null,
            null,
            'identified-by-string'
        );

        $this->assertTrue($token_with_identifier->claims()->has(RegisteredClaims::ID));
        $this->assertTrue($token_with_identifier->isIdentifiedBy('identified-by-string'));
    }

    public function provideEmptyValues(): array
    {
        return [
            [''],
            [null],
        ];
    }
}

<?php

declare(strict_types=1);

namespace DiceRobot\Util;

use Cake\Chronos\Chronos;
use InvalidArgumentException;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\{IssuedBy, PermittedFor, SignedWith, StrictValidAt};
use Ramsey\Uuid\Uuid;

/**
 * Class Jwt
 *
 * Util class. Jwt builder, parser and validator.
 *
 * @package DiceRobot\Util
 */
class Jwt
{
    /** @var string Issuer. */
    protected string $issuer = "dicerobot.net";

    /** @var string Audience. */
    protected string $audience = "panel.dicerobot.tech";

    /** @var Configuration JWT configuration. */
    protected Configuration $config;

    /**
     * The constructor.
     */
    public function __construct()
    {
        $signer = new Sha256();
        $key = InMemory::plainText(Uuid::uuid4()->toString());  // Generate random key at start up.

        $this->config = Configuration::forSymmetricSigner($signer, $key);
        $this->config->setValidationConstraints(
            new SignedWith($signer, $key),
            new IssuedBy($this->issuer),
            new PermittedFor($this->audience),
            new StrictValidAt(SystemClock::fromSystemTimezone())
        );
    }

    /**
     * Generate JWT token.
     *
     * @return string JWT token.
     */
    public function generate(): string
    {
        $now = Chronos::now();
        $token = $this->config->builder(ChainedFormatter::withUnixTimestampDates())
            ->issuedBy($this->issuer)
            ->permittedFor($this->audience)
            ->identifiedBy(Uuid::uuid4()->toString())
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+1 day'))
            ->getToken($this->config->signer(), $this->config->signingKey());

        return $token->toString();
    }

    /**
     * Parse and validate JWT token.
     *
     * @param string $token JWT token.
     *
     * @return bool Valid.
     */
    public function validate(string $token): bool
    {
        try {
            $parsedToken = $this->config->parser()->parse($token);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return $this->config->validator()->validate($parsedToken, ...$this->config->validationConstraints());
    }
}

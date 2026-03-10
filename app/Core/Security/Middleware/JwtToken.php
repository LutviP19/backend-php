<?php

namespace App\Core\Security\Middleware;

use App\Core\Support\Config;
use ReallySimpleJWT\Jwt;
use ReallySimpleJWT\Build;
use ReallySimpleJWT\Parse as ParseJwt;
use ReallySimpleJWT\Validate as ValidateJwt;
use ReallySimpleJWT\Decode as DecodeJwt;
use ReallySimpleJWT\Encoders\EncodeHS256 as EncodeHS256Jwt;
use ReallySimpleJWT\Helper\Validator as HelperValidator;
use Exception;

/**
 * JwtToken class
 * @author Lutvi <lutvip19@gmail.com>
 */
class JwtToken
{
    /**
     * Defines the JWT secret.
     */
    protected $secret;

    /**
     * Defines the expiration JWT payload.
     */
    protected $expiration;

    /**
     * Defines the identifier for the token.
     */
    protected $jwtId;

    /**
     * Defines the This defines issuer for the token.
     */
    protected $issuer;

    /**
     * Defines the This defines a list of 'principals'.
     * Eg a website or websites who will validate
     * users who use this token. This claim can either be a single string or an
     * array of strings.
     */
    protected $audience;


    public function __construct($secret = null, $expirationTime = 3600, $jwtId = null, $issuer = null, $audience = null)
    {
        $this->secret = (string) $secret ?: generateRandomString(16);
        $this->expirationTime = $expirationTime;
        $this->jwtId = $jwtId ?: generateUlid();
        $this->issuer = $issuer ?: clientIP();
        $this->audience = $audience ?: Config::get('app.url');
    }

    /**
     * create JWT token.
     *
     * @return string
     */
    public function createToken($userId, $info = null, $subject = null): string
    {
        if (! $userId) {
            throw new Exception('userId not set!');
        }

        $info = $info ?: Config::get('app.name');
        $subject = $subject ?: 'api access';

        $build = new Build('JWT', new HelperValidator(), new EncodeHS256Jwt($this->secret));

        $token = $build->setContentType('JWT')
                ->setHeaderClaim('info', $info)
                ->setIssuer($this->issuer)
                ->setSubject($subject)
                ->setAudience($this->audience)
                ->setExpiration(time() + $this->expirationTime)
                ->setNotBefore(time() - $this->expirationTime)
                ->setIssuedAt(time())
                ->setJwtId($this->jwtId)
                ->setPayloadClaim('uid', $userId)
                ->build();

        return $token->getToken();
    }

    /**
     * parse JWT values.
     *
     * @return array
     */
    public function parseJwt($token, $isArray = true)
    {
        if (! $token) {
            throw new Exception('token not set!');
        }

        try {
            $jwt = new Jwt($token);

            if ($isArray) {
                $parse = new ParseJwt($jwt, new DecodeJwt());
                $parsed = $parse->parse();

                return [
                    'header' => $parsed->getHeader(),
                    'payload' => $parsed->getPayload()
                ];
            }

            return $jwt;
        } catch (Exception $ex) {
            throw new Exception('Failed to parsing JWT token!, '.$ex->getMessage());
        }
    }

    /**
     * validate JWT values.
     *
     * @return bool
     */
    public function validateToken($token): bool
    {
        if (! $token) {
            throw new Exception('token not set!');
        }

        try {
            $jwt = $this->parseJwt($token, false);

            $parse = new ParseJwt($jwt, new DecodeJwt());
            $parsed = $parse->parse();

            $validate = new ValidateJwt(
                $parsed,
                new EncodeHS256Jwt($this->secret),
                new HelperValidator()
            );

            if (false === $validate->expiration() ||
                false === $validate->notBefore() ||
                false === $validate->audience($this->audience) ||
                false === $validate->signature()) {

                return false;
            }

            return true;
        } catch (Exception $ex) {
            throw new Exception('Failed to validated JWT token!, '.$ex->getMessage());
        }
    }
}

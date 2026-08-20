<?php

namespace App\Core\Security\Middleware;

use App\Core\Support\Config;
use App\Core\Support\Session;
use ReallySimpleJWT\Jwt;
use ReallySimpleJWT\Build;
use ReallySimpleJWT\Parse as ParseJwt;
use ReallySimpleJWT\Validate as ValidateJwt;
use ReallySimpleJWT\Decode as DecodeJwt;
use ReallySimpleJWT\Encoders\EncodeHS256 as EncodeHS256Jwt;
use ReallySimpleJWT\Helper\Validator as HelperValidator;
use Throwable;

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
    protected $expirationTime;

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


    public function __construct($secret = null, $expirationTime = 7200, $jwtId = null, $issuer = null, $audience = null)
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
            return '';
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

        $tokenJwt = $token->getToken();
        if(Session::has('jwtId')) {
            Session::set('tokenJwt', $tokenJwt);
        }

        return $tokenJwt;
    }

    /**
     * parse JWT values.
     *
     * @return array
     */
    public function parseJwt($token, $isArray = true)
    {
        if (! $token) {
            return null;
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
        } catch (Throwable $ex) {
            return null;
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
            return  false;
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
        } catch (Throwable $ex) {
            return false;
        }
    }
}

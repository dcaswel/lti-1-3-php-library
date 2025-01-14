<?php

namespace Packback\Lti1p3;

use Packback\Lti1p3\Interfaces\IDatabase;
use Packback\Lti1p3\Interfaces\ILtiRegistration;
use phpseclib3\Crypt\RSA;

class JwksEndpoint
{
    private $keys;

    public function __construct(array $keys)
    {
        $this->keys = $keys;
    }

    public static function new(array $keys)
    {
        return new JwksEndpoint($keys);
    }

    public static function fromIssuer(IDatabase $database, $issuer)
    {
        $registration = $database->findRegistrationByIssuer($issuer);

        return new JwksEndpoint([$registration->getKid() => $registration->getToolPrivateKey()]);
    }

    public static function fromRegistration(ILtiRegistration $registration)
    {
        return new JwksEndpoint([$registration->getKid() => $registration->getToolPrivateKey()]);
    }

    public function getPublicJwks()
    {
        $jwks = [];
        foreach ($this->keys as $kid => $private_key) {
            $key = RSA::load($private_key);
            $jwk = json_decode($key->getPublicKey()->toString('JWK'), true);
            $jwks[] = array_merge($jwk['keys'][0], [
                'alg' => 'RS256',
                'use' => 'sig',
                'kid' => $kid,
            ]);
        }

        return ['keys' => $jwks];
    }

    public function outputJwks()
    {
        echo json_encode($this->getPublicJwks());
    }
}

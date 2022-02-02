<?php

namespace App\Library;

use Illuminate\Support\Carbon;
use Laravel\Sanctum\Guard;

class JWT extends Guard
{
    function getSecretKey()
    {
        return env('JWT_KEY');
    }

    static function verify($token)
    {
        $secret = env('JWT_KEY');
        if(!isset($secret)) {
            exit('Please provide a key to verify');
        }

// split the token
        $tokenParts = explode('.', $token);
        $header = base64_decode($tokenParts[0]);
        $payload = base64_decode($tokenParts[1]);
        $signatureProvided = $tokenParts[2];

// check the expiration time - note this will cause an error if there is no 'exp' claim in the token
        $expiration = Carbon::createFromTimestamp(json_decode($payload)->exp);
        $tokenExpired = (Carbon::now()->diffInSeconds($expiration, false) < 0);

// build a signature based on the header and payload using the secret
        $base64UrlHeader = base_64_url_encode($header);
        $base64UrlPayload = base_64_url_encode($payload);
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = base_64_url_encode($signature);

        dump_json($base64UrlSignature, $signatureProvided);

// verify it matches the signature provided in the token
        $signatureValid = ($base64UrlSignature === $signatureProvided);

        echo "Header:\n" . $header . "\n";
        echo "Payload:\n" . $payload . "\n";

        if($tokenExpired) {
            echo "Token has expired.\n";
        } else {
            echo "Token has not expired yet.\n";
        }

        if($signatureValid) {
            echo "The signature is valid.\n";
        } else {
            echo "The signature is NOT valid\n";
        }
    }
}

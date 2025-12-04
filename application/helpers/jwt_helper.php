<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Minimal JWT helper (HS256) - no external dependencies.
 * Note: For production, prefer a well-tested library (firebase/php-jwt).
 */

if (!function_exists('base64url_encode')) {
    function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('base64url_decode')) {
    function base64url_decode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) $data .= str_repeat('=', 4 - $remainder);
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

if (!function_exists('jwt_encode')) {
    function jwt_encode(array $payload, $key, $exp_seconds = 3600) {
        $header = array('alg' => 'HS256', 'typ' => 'JWT');
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + (int)$exp_seconds;
        // include a JWT ID (jti) for revocation tracking
        if (empty($payload['jti'])) {
            $payload['jti'] = bin2hex(random_bytes(16));
        }

        $header_b64 = base64url_encode(json_encode($header));
        $payload_b64 = base64url_encode(json_encode($payload));
        $data = $header_b64 . '.' . $payload_b64;
        $sig = hash_hmac('sha256', $data, $key, true);
        $sig_b64 = base64url_encode($sig);
        return $data . '.' . $sig_b64;
    }
}

if (!function_exists('jwt_decode')) {
    function jwt_decode($jwt, $key) {
        if (!is_string($jwt)) return null;
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;
        list($header_b64, $payload_b64, $sig_b64) = $parts;
        $data = $header_b64 . '.' . $payload_b64;
        $sig = base64url_decode($sig_b64);
        $expected = hash_hmac('sha256', $data, $key, true);
        if (!hash_equals($expected, $sig)) return null;
        $payload_json = base64url_decode($payload_b64);
        $payload = json_decode($payload_json, true);
        if (!is_array($payload)) return null;
        if (isset($payload['exp']) && time() > (int)$payload['exp']) return null;
        return $payload;
    }
}

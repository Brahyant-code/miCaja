<?php
namespace Core;

class Auth {
    // Genera un JWT muy simple (no dependencias externas). El token expira en 8 horas.
    public static function generarToken(array $usuario) {
        $appConfig = require __DIR__ . '/../config/app.php';
        $secret = $appConfig['jwt_secret'] ?? 'cambiame';

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $iat = time();
        $exp = $iat + 60 * 60 * 8; // 8 horas

        $payload = [
            'sub' => (int) ($usuario['id'] ?? 0),
            'username' => $usuario['username'] ?? $usuario['nombre'] ?? null,
            'iat' => $iat,
            'exp' => $exp,
        ];

        $b64 = function($data) {
            return rtrim(strtr(base64_encode(json_encode($data, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
        };

        $unsigned = $b64($header) . '.' . $b64($payload);
        $sig = hash_hmac('sha256', $unsigned, $secret, true);
        $sigb64 = rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');

        return $unsigned . '.' . $sigb64;
    }

    public static function verificarToken($token) {
        $appConfig = require __DIR__ . '/../config/app.php';
        $secret = $appConfig['jwt_secret'] ?? 'cambiame';

        if (!is_string($token) || substr_count($token, '.') !== 2) return null;
        list($h, $p, $s) = explode('.', $token);

        $unsigned = $h . '.' . $p;
        $sig = base64_decode(strtr($s, '-_', '+/')); 
        if ($sig === false) return null;

        $calc = hash_hmac('sha256', $unsigned, $secret, true);
        if (!hash_equals($calc, $sig)) return null;

        $payload = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
        if (!is_array($payload) || empty($payload['sub'])) return null;
        if (isset($payload['exp']) && time() > (int) $payload['exp']) return null;

        return $payload; // contiene sub, username, iat, exp
    }

    public static function obtenerToken() {
        $authHeader = null;

        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            // Apache en CGI/FastCGI suele exponerla con prefijo REDIRECT_ tras el rewrite
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (!empty($headers['Authorization'])) {
                $authHeader = $headers['Authorization'];
            } elseif (!empty($headers['authorization'])) {
                $authHeader = $headers['authorization'];
            }
        }

        if (!is_string($authHeader)) {
            return null;
        }

        if (preg_match('/^Bearer\s+(.+)$/i', trim($authHeader), $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}

<?php
namespace Core;

class Response {

    // Respuesta exitosa con el sobre estándar { mensaje, datos }.
    public static function exito($datos = null, $mensaje = '', $codigo = 200) {
        self::salida([
            'mensaje' => $mensaje,
            'datos'   => $datos,
        ], $codigo);
    }

    // Respuesta de error: { mensaje, datos:null }. La llave "errores" solo se
    // incluye cuando hay detalle que mostrar (p. ej. errores de validación).
    public static function error($mensaje = '', $codigo = 400, $errores = null) {
        $cuerpo = [
            'mensaje' => $mensaje,
            'datos'   => null,
        ];
        if ($errores !== null) {
            $cuerpo['errores'] = $errores;
        }
        self::salida($cuerpo, $codigo);
    }

    // Punto único de salida JSON: headers (CORS configurable + nosniff),
    // código de estado, cuerpo JSON estándar y fin de la ejecución.
    private static function salida(array $cuerpo, $codigo) {
        if (!headers_sent()) {
            // Origen permitido para CORS (configurable en config/app.php)
            $appConfig = require __DIR__ . '/../config/app.php';
            $origin = $appConfig['cors_origin'] ?? '*';

            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            http_response_code($codigo);
        }

        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit; // Respuesta final
    }
}

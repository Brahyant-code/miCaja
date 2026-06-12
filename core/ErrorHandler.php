<?php
namespace Core;

use Throwable;
use ErrorException;

class ErrorHandler {

    private static $debug = false;

    // Registra el manejo centralizado de errores. Llamar una sola vez en el bootstrap.
    public static function register($debug = false) {
        self::$debug = (bool) $debug;

        set_exception_handler([self::class, 'manejarExcepcion']);
        set_error_handler([self::class, 'manejarError']);
        register_shutdown_function([self::class, 'manejarApagado']);
    }

    // Convierte los errores de PHP (los incluidos en error_reporting) en excepciones
    // para que pasen por el mismo flujo que el resto.
    public static function manejarError($nivel, $mensaje, $archivo = '', $linea = 0) {
        if (!(error_reporting() & $nivel)) {
            return false; // error silenciado (p. ej. con @): lo ignoramos
        }
        throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
    }

    // Manejador central de cualquier excepción no capturada.
    public static function manejarExcepcion(Throwable $e) {
        // El detalle real siempre va al log del servidor, nunca al cliente (salvo debug)
        error_log('Nova error: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());

        if (self::$debug) {
            // En desarrollo mostramos el error real (más la ubicación en "errores")
            Response::error($e->getMessage(), 500, [
                'archivo' => $e->getFile(),
                'linea'   => $e->getLine(),
            ]);
        } else {
            // En producción, mensaje genérico sin filtrar detalles
            Response::error('Error interno del servidor. Inténtalo más tarde.', 500);
        }
    }

    // Captura errores fatales (que no pasan por set_error_handler).
    public static function manejarApagado() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
            self::manejarExcepcion(new ErrorException(
                $error['message'], 0, $error['type'], $error['file'], $error['line']
            ));
        }
    }
}

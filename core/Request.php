<?php
namespace Core;

class Request {

    // Cuerpo JSON ya decodificado (se calcula una sola vez por petición).
    private static $body = null;

    // Devuelve el método HTTP de la petición (GET, POST, PUT, DELETE, ...)
    public static function method() {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    // Decodifica el cuerpo JSON de la petición de forma segura.
    // Devuelve un arreglo asociativo (vacío si no hay body o no es JSON válido).
    // El resultado se memoiza: php://input solo se lee una vez por petición.
    public static function body() {
        if (self::$body !== null) {
            return self::$body;
        }
        $raw = file_get_contents('php://input');
        $data = ($raw === '' || $raw === false) ? [] : json_decode($raw, true);
        return self::$body = is_array($data) ? $data : [];
    }

    // Obtiene un valor de entrada buscando primero en el body JSON y luego
    // en los datos de formulario ($_POST / $_GET). Devuelve $default si no existe.
    public static function input($clave, $default = null) {
        $body = self::body();
        if (array_key_exists($clave, $body)) {
            return $body[$clave];
        }
        if (array_key_exists($clave, $_POST)) {
            return $_POST[$clave];
        }
        if (array_key_exists($clave, $_GET)) {
            return $_GET[$clave];
        }
        return $default;
    }

    // Devuelve todos los datos de entrada combinados (body JSON + $_POST + $_GET).
    public static function all() {
        return array_merge($_GET, $_POST, self::body());
    }

    // Valida los datos de entrada según un arreglo de reglas.
    // Si la validación falla, responde 422 JSON automáticamente y detiene la ejecución.
    // Si pasa, devuelve un arreglo solo con los campos validados.
    public static function validar(array $reglas) {
        $datos = self::all();
        $validador = new Validator($datos, $reglas);

        if (!$validador->pasa()) {
            Response::error('Datos inválidos', 422, $validador->errores());
        }

        return $validador->validados();
    }
}

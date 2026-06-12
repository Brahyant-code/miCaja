<?php
namespace Core;

use PDO;

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            // Importar el archivo de configuración que creamos recién
            $config = require __DIR__ . '/../config/database.php';

            $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";

            // Si la conexión falla, la PDOException burbujea al ErrorHandler central,
            // que la registra en el log y responde un 500 genérico.
            self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Reportar errores como excepciones
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devolver arreglos asociativos limpios
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Desactivar emulación para mitigar SQLi de raíz
            ]);
        }
        return self::$instance;
    }
}
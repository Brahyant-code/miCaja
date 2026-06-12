<?php
// public/index.php

// 1. Cargador automático de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Configuración general (modo debug, CORS, etc.)
$appConfig = require __DIR__ . '/../config/app.php';
$debug = !empty($appConfig['debug']);

// 3. Configurar el reporte de errores según el entorno
error_reporting(E_ALL);
// Nunca mostramos errores crudos al cliente: el ErrorHandler decide qué responder
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// 4. Registrar el manejo de errores central (excepciones, errores y fatales)
\Core\ErrorHandler::register($debug);

// 5. Responder al preflight CORS (OPTIONS) sin entrar al enrutador
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    $origin = $appConfig['cors_origin'] ?? '*';
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 86400');
    http_response_code(204);
    exit;
}

// 6. Despachar la petición
$router = new \Core\Router();
require __DIR__ . '/../routes/api.php'; // registra las rutas definidas en $router
$router->run();

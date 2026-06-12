<?php

// routes/api.php
//
// Aquí defines TODAS las rutas de tu API de forma amigable.
// Cada ruta apunta a 'Controlador@metodo' y soporta parámetros dinámicos con {nombre}.
//
// La variable $router viene desde public/index.php.

/** @var \Core\Router $router */

// Página de inicio / verificación de conexión
$router->get('/',     'HomeController@index');
$router->get('/home', 'HomeController@index');

// --- CRUD de ejemplo: Tareas (requiere la tabla de database/nova.sql) ---
$router->get('/tareas',         'TareaController@index');    // listar
$router->get('/tareas/{id}',    'TareaController@ver');      // ver una
$router->post('/tareas',        'TareaController@crear');    // crear
$router->put('/tareas/{id}',    'TareaController@editar');   // editar
$router->delete('/tareas/{id}', 'TareaController@eliminar'); // eliminar

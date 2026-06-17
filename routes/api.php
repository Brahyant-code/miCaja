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

// --- Panel de control / indicadores ---
$router->get('/dashboard', 'DashboardController@index');

// --- Autenticación ---
$router->post('/login', 'AuthController@login');

// --- Categorías ---
$router->get('/categorias',         'CategoriaController@index');    // listar (admite ?activo=1)
$router->get('/categorias/{id}',    'CategoriaController@ver');      // ver una
$router->post('/categorias',        'CategoriaController@crear');    // crear
$router->put('/categorias/{id}',    'CategoriaController@editar');   // editar
$router->delete('/categorias/{id}', 'CategoriaController@eliminar'); // eliminar

// --- Productos --- (admite ?activo=1&categoria_id=N en el listado)
$router->get('/productos',              'ProductoController@index');     // listar
$router->post('/productos/importar',    'ProductoController@importar');  // importación masiva (JSON)
$router->get('/productos/{id}',         'ProductoController@ver');       // ver uno
$router->post('/productos',             'ProductoController@crear');     // crear (JSON)
$router->put('/productos/{id}',         'ProductoController@editar');    // editar (JSON)
$router->delete('/productos/{id}',      'ProductoController@eliminar');  // eliminar
$router->post('/productos/{id}/imagen', 'ProductoController@subirImagen'); // subir imagen (multipart)

// --- Ventas ---
$router->get('/ventas',         'VentaController@index');   // listar cabeceras
$router->get('/ventas/{id}',    'VentaController@ver');     // ver con detalle
$router->post('/ventas',        'VentaController@crear');   // registrar venta atómica
$router->delete('/ventas/{id}', 'VentaController@anular');  // anular (no borra)

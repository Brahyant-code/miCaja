<?php

// config/app.php
//
// Configuración general de la aplicación.
return [
    // En desarrollo: true (muestra detalles de errores).
    // En producción: false (oculta detalles y los manda al log).
    'debug' => true,

    // Origen permitido para CORS.
    // En desarrollo puedes dejar '*'. En producción pon el dominio de tu
    // frontend, por ejemplo: 'https://mi-app.com'
    'cors_origin' => '*',
];

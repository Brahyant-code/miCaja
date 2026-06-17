<?php

// config/app.php
//
// Configuración general de la aplicación.

// El secreto para firmar los JWT NO se versiona: vive en config/database.php
// (archivo local, ignorado por git), junto al resto de credenciales. Así no
// queda expuesto en el repositorio público. Si ese archivo no define
// 'jwt_secret', se usa un placeholder inseguro (cámbialo en producción).
$local = is_file(__DIR__ . '/database.php') ? (array) (require __DIR__ . '/database.php') : [];

return [
    // En desarrollo: true (muestra detalles de errores).
    // En producción: false (oculta detalles y los manda al log).
    'debug' => false,

    // Origen permitido para CORS.
    // En desarrollo puedes dejar '*'. En producción pon el dominio de tu
    // frontend, por ejemplo: 'https://mi-app.com'
    'cors_origin' => '*',

    // Secreto para firmar JWT: se toma de config/database.php (local, no versionado).
    // El placeholder solo aplica si olvidaste definirlo allí; cámbialo en producción.
    'jwt_secret' => $local['jwt_secret'] ?? 'cambiame_por_un_valor_seguro',
];

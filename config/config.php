<?php
/**
 * SoportesRD - Configuración general
 * Los valores pueden sobrescribirse con variables de entorno (útil en Docker / Render).
 */
return [
    'app_name'  => 'SoportesRD',
    'base_url'  => getenv('SRD_BASE_URL') ?: '',          // Ej: '' si está en la raíz, '/SoportesRD' si está en una subcarpeta
    'timezone'  => 'America/Santo_Domingo',

    // Base de datos: 'sqlite' (sin configuración) o 'mysql'
    'db' => [
        'driver'   => getenv('SRD_DB_DRIVER') ?: 'sqlite',
        'sqlite'   => __DIR__ . '/../storage/soportesrd.sqlite',
        'host'     => getenv('SRD_DB_HOST') ?: '127.0.0.1',
        'port'     => getenv('SRD_DB_PORT') ?: '3306',
        'name'     => getenv('SRD_DB_NAME') ?: 'soportesrd',
        'user'     => getenv('SRD_DB_USER') ?: 'root',
        'pass'     => getenv('SRD_DB_PASS') ?: '',
    ],

    // Cargar datos de demostración la primera vez que se crea la base
    'demo_data' => (getenv('SRD_DEMO') ?: '1') === '1',

    // Adjuntos
    'upload_dir'      => __DIR__ . '/../uploads',
    'upload_max_mb'   => 8,
    'upload_max_files'=> 5,
    'upload_ext'      => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'],

    // Correo: si 'enabled' es false, los correos se guardan en storage/mail.log
    'mail' => [
        'enabled' => (getenv('SRD_MAIL') ?: '0') === '1',
        'from'    => getenv('SRD_MAIL_FROM') ?: 'no-responder@soportesrd.com',
    ],
];

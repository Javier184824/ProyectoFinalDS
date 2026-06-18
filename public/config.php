<?php
// ====================================================
// config.php - configuracion local (NO subir a GITHUB)
// ====================================================

// --- Base de datos ---
define('DB_HOST',    'localhost');
define('DB_NAME',    'sistema_entregas');
define('DB_USER',    'admin');
define('DB_PASS',    'password');
define('DB_CHARSET', 'utf8mb4');

// --- URL base de la aplicacion (sin trailing slash) ---
// Cambia BASE_PATH si la app vive en una subcarpeta de Apache
define('BASE_URL',  'http://localhost/sistema_entregas');
define('BASE_PATH', '/sistema_entregas');

// --- API externa de geolocalizacion ---
define('IP_API_BASE', 'http://ip-api.com/json/');

// --- Entorno ---
define('APP_ENV', 'development');

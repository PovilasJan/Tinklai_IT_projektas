<?php
// DB config - can be overridden by environment variables (useful for Docker)
define('DB_HOST', getenv('DB_HOST') ? getenv('DB_HOST') : '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ? getenv('DB_NAME') : 'viesbuciu_tinklas');
define('DB_USER', getenv('DB_USER') ? getenv('DB_USER') : 'root');
define('DB_PASS', getenv('DB_PASS') ? getenv('DB_PASS') : '');

// Reservation settings
define('RESERVATION_DEPOSIT_RATE', 0.20); // new client pays 20% reservation fee

// Simple site settings
define('SITE_TITLE', 'Viešbučių tinklas');

// BASE_PATH: set this to the web path where the project is hosted.
// If you serve the project from the web server root (e.g. php -S localhost:8000 -t .), leave as empty string ''.
// If you're using Laragon with URL http://localhost/viesbuciu_tinklas, set to '/viesbuciu_tinklas'
define('BASE_PATH', '');

session_start();
?>

<?php
// Router for PHP built-in server
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

// If it's a real file (not a PHP file), serve it
if ($path !== '/' && file_exists($file) && !is_dir($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false; // Serve the file as-is
}

// If it's a PHP file that exists, include it
if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    include $file;
    return true;
}

// If it's a directory and index.php exists, serve it
if (is_dir($file) && file_exists($file . '/index.php')) {
    include $file . '/index.php';
    return true;
}

// 404 - File not found
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Puslapis nerastas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="text-center">
            <h1 class="display-1">404</h1>
            <h2>Puslapis nerastas</h2>
            <p class="lead">Atsiprašome, bet puslapis, kurio ieškote, neegzistuoja.</p>
            <a href="/" class="btn btn-primary">Grįžti į pradžią</a>
        </div>
    </div>
</body>
</html>

<?php

// Router for `php -S -t public router.php` local development only. The
// Docker/Apache runtime uses public/.htaccess instead; this file is not
// copied into that image.

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = $path === '' ? '/' : $path;

if (str_starts_with($path, '/api/')) {
    require __DIR__ . '/public/index.php';
    return;
}

if ($path !== '/' && is_file(__DIR__ . '/public' . $path)) {
    return false;
}

header('Content-Type: text/html; charset=utf-8');
readfile(__DIR__ . '/public/index.html');

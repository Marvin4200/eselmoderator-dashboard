<?php
// Front-Controller fuer den PHP-eingebauten Server (siehe Dockerfile: php -S ... router.php).
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path !== '/' && file_exists(__DIR__ . $path) && is_file(__DIR__ . $path)) {
    return false; // Statische Datei (CSS/JS/Bild) direkt ausliefern.
}

if ($path === '/' || $path === '/index.php') {
    require __DIR__ . '/index.php';
    return true;
}

if ($path === '/logout.php') {
    require __DIR__ . '/logout.php';
    return true;
}

if (preg_match('#^/pages/([a-z0-9\-]+)\.php$#', $path, $m)) {
    $file = __DIR__ . '/pages/' . $m[1] . '.php';
    if (is_file($file)) {
        require $file;
        return true;
    }
}

http_response_code(404);
echo '404 Not Found';
return true;

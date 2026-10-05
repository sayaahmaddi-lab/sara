<?php
// Vercel PHP router - forwards all requests to the correct PHP file
$uri  = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$path = ltrim($path, '/');

// remove query string already handled by parse_url
if ($path === '') {
    $path = 'index.php';
}

// Static assets: let Vercel serve, but if we reach here, serve them manually
$staticExtensions = ['css','js','png','jpg','jpeg','gif','svg','ico','woff','woff2','ttf','eot'];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$rootFile = __DIR__ . '/../' . $path;

if (in_array($ext, $staticExtensions) && file_exists($rootFile)) {
    $mime = [
        'css'=>'text/css','js'=>'application/javascript','png'=>'image/png',
        'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','svg'=>'image/svg+xml',
        'ico'=>'image/x-icon','woff'=>'font/woff','woff2'=>'font/woff2','ttf'=>'font/ttf','eot'=>'application/vnd.ms-fontobject'
    ];
    header('Content-Type: ' . ($mime[$ext] ?? 'application/octet-stream'));
    readfile($rootFile);
    exit;
}

// Resolve target PHP file
$target = __DIR__ . '/../' . $path;

// If path is a directory, try index.php inside it
if (is_dir($target)) {
    $target = rtrim($target, '/') . '/index.php';
}

// If no extension, try adding .php
if (!file_exists($target) && !str_contains($path, '.')) {
    if (file_exists($target . '.php')) {
        $target = $target . '.php';
    }
}

// If file exists and is PHP, include it
if (file_exists($target) && is_file($target) && substr($target, -4) === '.php') {
    // Change working directory so relative includes (config/database.php, etc.) resolve correctly
    chdir(dirname($target));
    // Make sure the included file sees the correct SCRIPT_NAME / PHP_SELF
    $_SERVER['SCRIPT_NAME'] = '/' . $path;
    $_SERVER['SCRIPT_FILENAME'] = $target;
    require $target;
    exit;
}

// Fallback: file not found
if (file_exists($rootFile) && is_file($rootFile)) {
    // Serve as static
    readfile($rootFile);
    exit;
}

http_response_code(404);
echo "404 - File tidak ditemukan: " . htmlspecialchars($path);

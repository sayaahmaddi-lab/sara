<?php
// Vercel PHP router - forwards all requests to the correct PHP file
$uri  = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$path = ltrim($path, '/');

// Filter out vercel internal /api/index.php self-reference (avoid infinite loop)
if ($path === 'api/index.php' || $path === 'api/index') {
    $path = 'index.php';
}
if ($path === '') {
    $path = 'index.php';
}

// Static assets: serve manually if we reach here
$staticExtensions = ['css','js','png','jpg','jpeg','gif','svg','ico','woff','woff2','ttf','eot','map'];
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
    $path = rtrim($path, '/') . '/index.php';
}

// If no extension, try adding .php
if (!file_exists($target) && strpos($path, '.') === false) {
    if (file_exists($target . '.php')) {
        $target = $target . '.php';
        $path = $path . '.php';
    }
}

// If file exists and is PHP, include it
if (file_exists($target) && is_file($target) && substr($target, -4) === '.php') {
    // Prevent self-include
    if (realpath($target) === realpath(__FILE__)) {
        $target = __DIR__ . '/../index.php';
        $path = 'index.php';
    }
    chdir(dirname($target));
    $_SERVER['SCRIPT_NAME'] = '/' . $path;
    $_SERVER['SCRIPT_FILENAME'] = $target;
    $_SERVER['PHP_SELF'] = '/' . $path;
    require $target;
    exit;
}

// Fallback: file not found
if (file_exists($rootFile) && is_file($rootFile)) {
    readfile($rootFile);
    exit;
}

http_response_code(404);
echo "404 - File tidak ditemukan: " . htmlspecialchars($path);

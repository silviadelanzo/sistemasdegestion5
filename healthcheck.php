<?php
// Healthcheck simple del sistema
header('Content-Type: application/json; charset=utf-8');
$result = [
    'app' => 'sistemadgestion5',
    'time' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'db' => ['ok' => false, 'error' => null, 'db_name' => null],
    'paths' => []
];

// Comprobar conexión a DB
try {
    require_once __DIR__ . '/config/config.php';
    $pdo = conectarDB();
    $stmt = $pdo->query('SELECT 1');
    $stmt->fetch();
    $result['db']['ok'] = true;
    $result['db']['db_name'] = defined('DB_NAME') ? DB_NAME : null;
} catch (Throwable $e) {
    $result['db']['ok'] = false;
    $result['db']['error'] = $e->getMessage();
}

// Comprobar permisos de carpetas clave
$dirs = [
    'assets/uploads',
    'assets/scanner_input',
    'assets/scanner_processed'
];
foreach ($dirs as $d) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $d;
    if (!is_dir($path)) {
        $result['paths'][$d] = ['exists' => false, 'writable' => false];
        continue;
    }
    $result['paths'][$d] = [
        'exists' => true,
        'writable' => is_writable($path)
    ];
}

http_response_code($result['db']['ok'] ? 200 : 503);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

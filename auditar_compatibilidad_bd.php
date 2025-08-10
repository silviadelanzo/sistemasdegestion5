<?php
// Auditoría de compatibilidad entre código y base de datos
// - Escanea archivos PHP para detectar tablas referenciadas (FROM/JOIN/INSERT/UPDATE/DELETE)
// - Compara contra las tablas reales en la BD configurada en config/config.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

function obtenerPdoSeguro() {
    try {
        return conectarDB();
    } catch (Throwable $e) {
        echo "ERROR: no se pudo conectar a la BD: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}

function listarTablasBD(PDO $pdo) {
    $stmt = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = ? ORDER BY table_name");
    $stmt->execute([DB_NAME]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function listarColumnasPorTabla(PDO $pdo) {
    $stmt = $pdo->prepare("SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = ?");
    $stmt->execute([DB_NAME]);
    $map = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $t = $row['table_name'];
        $c = $row['column_name'];
        $map[$t][] = $c;
    }
    return $map;
}

function escanearTablasEnCodigo($root) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    $tablas = [];
    $regexes = [
        '/\bFROM\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
        '/\bJOIN\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
        '/\bINSERT\s+INTO\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
        '/\bUPDATE\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
        '/\bDELETE\s+FROM\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
        '/\bDESCRIBE\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
        '/\bSHOW\s+TABLES\s+LIKE\s+\'?([a-zA-Z_][a-zA-Z0-9_]*)\'?/i',
    ];

    foreach ($it as $file) {
        $path = $file->getPathname();
        if (!preg_match('/\.php$/i', $path)) continue;
        if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;
        if (strpos($path, DIRECTORY_SEPARATOR . '.vscode' . DIRECTORY_SEPARATOR) !== false) continue;
        $code = @file_get_contents($path);
        if ($code === false) continue;
        foreach ($regexes as $rx) {
            if (preg_match_all($rx, $code, $m)) {
                foreach ($m[1] as $name) {
                    $name = strtolower($name);
                    // Filtrar palabras reservadas comunes por falsos positivos
                    if (in_array($name, ['select','where','set','on','values','into','left','right','inner','outer'])) continue;
                    $tablas[$name] = true;
                }
            }
        }
    }
    ksort($tablas);
    return array_keys($tablas);
}

$pdo = obtenerPdoSeguro();
$tablasBD = listarTablasBD($pdo);
$colsBD = listarColumnasPorTabla($pdo);
$tablasCodigo = escanearTablasEnCodigo(__DIR__);

$setBD = array_fill_keys(array_map('strtolower', $tablasBD), true);

$faltantes = [];
foreach ($tablasCodigo as $t) {
    if (!isset($setBD[$t])) {
        $faltantes[] = $t;
    }
}

// Salida legible en CLI o navegador
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    echo '<meta charset="utf-8">';
    echo '<style>body{font-family:Segoe UI,Arial,sans-serif;padding:20px} .ok{color:green} .warn{color:#b36b00} .err{color:#b00020} code{background:#f5f5f5;padding:2px 4px;border-radius:3px}</style>';
}

echo ($isCli ? "\n=== Auditoria de compatibilidad BD ===\n" : '<h2>Auditoría de compatibilidad BD</h2>');
echo ($isCli ? '' : '<p>BD configurada: <strong>'.htmlspecialchars(DB_NAME).'</strong></p>');

echo ($isCli ? "\nTablas en BD (".count($tablasBD)."):\n" : '<h3>Tablas en BD ('.count($tablasBD).')</h3>');
echo ($isCli ? implode(', ', $tablasBD)."\n\n" : '<p>'.implode(', ', array_map('htmlspecialchars',$tablasBD)).'</p>');

echo ($isCli ? "Tablas referenciadas por el código (".count($tablasCodigo)."):\n" : '<h3>Tablas referenciadas por el código ('.count($tablasCodigo).')</h3>');
echo ($isCli ? implode(', ', $tablasCodigo)."\n\n" : '<p>'.implode(', ', array_map('htmlspecialchars',$tablasCodigo)).'</p>');

if (count($faltantes) === 0) {
    echo ($isCli ? "No faltan tablas.\n" : '<p class="ok">No faltan tablas: el esquema cubre lo que el código usa.</p>');
} else {
    echo ($isCli ? "Faltan en BD (".count($faltantes)."):\n" : '<h3 class="err">Faltan en BD ('.count($faltantes).')</h3>');
    if ($isCli) {
        echo implode(', ', $faltantes)."\n";
    } else {
        echo '<ul>'; foreach ($faltantes as $t) echo '<li><code>'.htmlspecialchars($t).'</code></li>'; echo '</ul>';
    }
}

// Tip: ver columnas por tabla si hace falta profundizar
if (!$isCli) {
    echo '<details><summary>Columnas por tabla (BD)</summary><pre style="white-space:pre-wrap">';
    foreach ($colsBD as $t=>$cols) {
        echo $t.': '.implode(', ', $cols)."\n";
    }
    echo '</pre></details>';
}

echo ($isCli ? "\nListo.\n" : '<p>Listo.</p>');

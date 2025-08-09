<?php
echo "<h2>Verificación de Extensiones PHP para Excel</h2>";
echo "<h3>Versión PHP: " . phpversion() . "</h3>";

echo "<h4>Extensiones necesarias para Excel:</h4>";
$extensiones_necesarias = ['zip', 'xml', 'mbstring', 'gd', 'xmlreader', 'xmlwriter'];

foreach ($extensiones_necesarias as $ext) {
    $estado = extension_loaded($ext) ? '✅ INSTALADA' : '❌ NO INSTALADA';
    echo "<p><strong>{$ext}:</strong> {$estado}</p>";
}

echo "<h4>Todas las extensiones instaladas:</h4>";
$extensiones = get_loaded_extensions();
sort($extensiones);
foreach ($extensiones as $ext) {
    echo "<span style='margin-right: 10px; background: #e7f3ff; padding: 2px 5px; border-radius: 3px;'>{$ext}</span>";
}

echo "<h4>Información del sistema:</h4>";
echo "<p><strong>PHP CLI:</strong> " . PHP_BINARY . "</p>";
echo "<p><strong>PHP.ini:</strong> " . php_ini_loaded_file() . "</p>";
echo "<p><strong>Sistema:</strong> " . PHP_OS . "</p>";

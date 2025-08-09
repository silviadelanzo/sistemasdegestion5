<?php
// Verificar si GD está instalada
if (extension_loaded('gd')) {
    echo "<h2 style='color: green;'>✅ Extensión GD está ACTIVA</h2>";

    // Información detallada de GD
    $gd_info = gd_info();
    echo "<h3>Información de GD:</h3>";
    echo "<ul>";
    foreach ($gd_info as $key => $value) {
        echo "<li><strong>{$key}:</strong> " . (is_bool($value) ? ($value ? 'Sí' : 'No') : $value) . "</li>";
    }
    echo "</ul>";

    echo "<h3>Formatos soportados:</h3>";
    echo "<ul>";
    if (function_exists('imagecreatefrompng')) echo "<li>✅ PNG</li>";
    if (function_exists('imagecreatefromjpeg')) echo "<li>✅ JPEG</li>";
    if (function_exists('imagecreatefromgif')) echo "<li>✅ GIF</li>";
    echo "</ul>";
} else {
    echo "<h2 style='color: red;'>❌ Extensión GD NO está activa</h2>";
    echo "<p>Necesitas activar la extensión GD en php.ini</p>";
    echo "<p>Busca la línea <code>;extension=gd</code> y quita el punto y coma</p>";
}

echo "<hr>";
echo "<h3>Otras extensiones necesarias para Excel:</h3>";
$extensiones = ['zip', 'xml', 'mbstring', 'xmlreader', 'xmlwriter'];
foreach ($extensiones as $ext) {
    $estado = extension_loaded($ext) ? '✅' : '❌';
    echo "<p>{$estado} <strong>{$ext}</strong></p>";
}

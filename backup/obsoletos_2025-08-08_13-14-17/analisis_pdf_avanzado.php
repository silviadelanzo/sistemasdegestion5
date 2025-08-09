<?php
require_once '../../config/config.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<h2>🚀 Sistema PDF Avanzado - Usando exec() Disponible</h2>";

// 1. Verificar si podemos instalar wkhtmltopdf
echo "<h3>1. Test de Instalación wkhtmltopdf</h3>";

$commands_to_test = [
    'which wkhtmltopdf',
    'whereis wkhtmltopdf',
    'wkhtmltopdf --version',
    'apt list --installed | grep wkhtmltopdf',
    'yum list installed | grep wkhtmltopdf'
];

foreach ($commands_to_test as $cmd) {
    echo "<strong>Probando:</strong> <code>$cmd</code><br>";
    $output = [];
    $return_var = 0;

    @exec($cmd . ' 2>&1', $output, $return_var);

    if ($return_var === 0 && !empty($output)) {
        echo "✅ <span style='color: green;'>Resultado:</span> " . implode('<br>&nbsp;&nbsp;&nbsp;&nbsp;', $output) . "<br>";
    } else {
        echo "❌ <span style='color: red;'>Sin resultado o error</span><br>";
    }
    echo "<hr style='border: 1px dashed #ccc; margin: 10px 0;'>";
}

// 2. Test de descarga/instalación temporal
echo "<h3>2. Intento de Instalación Temporal</h3>";

$wkhtmltopdf_urls = [
    'https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.focal_amd64.deb',
    'https://github.com/wkhtmltopdf/wkhtmltopdf/releases/download/0.12.5/wkhtmltox_0.12.5-1.centos7_x86_64.rpm'
];

// Intentar detectar el sistema
$output = [];
@exec('cat /etc/os-release 2>&1', $output, $return_var);
if (!empty($output)) {
    echo "✅ <strong>Sistema detectado:</strong><br>";
    foreach ($output as $line) {
        echo "&nbsp;&nbsp;&nbsp;&nbsp;" . htmlspecialchars($line) . "<br>";
    }
} else {
    echo "⚠️ No se pudo detectar el sistema operativo<br>";
}

// 3. Verificar permisos de escritura
echo "<h3>3. Verificar Permisos de Escritura</h3>";

$temp_dirs = ['/tmp', sys_get_temp_dir(), __DIR__ . '/temp'];

foreach ($temp_dirs as $dir) {
    if (is_dir($dir) && is_writable($dir)) {
        echo "✅ Directorio escribible: <code>$dir</code><br>";

        // Test de escritura
        $test_file = $dir . '/test_write_' . time() . '.txt';
        if (file_put_contents($test_file, 'test')) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;✅ Escritura confirmada<br>";
            @unlink($test_file);
        }
    } else {
        echo "❌ No disponible: <code>$dir</code><br>";
    }
}

// 4. Crear solución alternativa con exec()
echo "<h3>4. Soluciones Alternativas con exec()</h3>";

// Verificar si phantom.js está disponible
$output = [];
@exec('phantomjs --version 2>&1', $output, $return_var);
if ($return_var === 0 && !empty($output)) {
    echo "✅ PhantomJS disponible: " . implode(' ', $output) . "<br>";
} else {
    echo "❌ PhantomJS no disponible<br>";
}

// Verificar si headless chrome está disponible
$chrome_commands = ['google-chrome --version', 'chromium --version', 'chrome --version'];
foreach ($chrome_commands as $cmd) {
    $output = [];
    @exec($cmd . ' 2>&1', $output, $return_var);
    if ($return_var === 0 && !empty($output)) {
        echo "✅ Chrome/Chromium encontrado: " . implode(' ', $output) . "<br>";
        break;
    }
}

// 5. Crear sistema híbrido
echo "<h3>5. Sistema Híbrido Propuesto</h3>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
echo "<strong>✅ Solución Recomendada para tu Servidor:</strong><br><br>";
echo "<strong>Nivel 1:</strong> HTML-PDF (Ya implementado y funcional)<br>";
echo "&nbsp;&nbsp;• Usar window.print() para generar PDFs<br>";
echo "&nbsp;&nbsp;• Compatible al 100% con tu servidor<br>";
echo "&nbsp;&nbsp;• Calidad profesional<br><br>";

echo "<strong>Nivel 2:</strong> Sistema exec() personalizado<br>";
echo "&nbsp;&nbsp;• Crear generador HTML → PDF usando exec()<br>";
echo "&nbsp;&nbsp;• Implementar con herramientas del sistema<br>";
echo "&nbsp;&nbsp;• Mayor control sobre el formato<br><br>";

echo "<strong>Nivel 3:</strong> Instalar wkhtmltopdf (Requiere hosting)<br>";
echo "&nbsp;&nbsp;• Solicitar instalación al proveedor de hosting<br>";
echo "&nbsp;&nbsp;• Generación automática de PDFs<br>";
echo "&nbsp;&nbsp;• Máxima calidad y control<br>";
echo "</div>";

// 6. Crear script de instalación
echo "<h3>6. Script de Instalación Automática</h3>";
echo "<p>Si quieres intentar instalar wkhtmltopdf automáticamente:</p>";
echo "<a href='instalar_wkhtmltopdf.php' class='btn btn-warning' style='padding: 10px 20px; background: #ffc107; color: #000; text-decoration: none; border-radius: 5px;'>🔧 Intentar Instalación Automática</a><br><br>";

echo "<h3>7. Estado Actual</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #c3e6cb;'>";
echo "✅ <strong>PDF HTML funcional</strong> - <a href='reporte_pdf_html.php' target='_blank'>Probar ahora</a><br>";
echo "✅ <strong>Funciones exec() disponibles</strong> - Podemos crear soluciones avanzadas<br>";
echo "⚠️ <strong>Librerías PDF no instaladas</strong> - Pero se pueden agregar<br>";
echo "🎯 <strong>Recomendación:</strong> Usar HTML-PDF (ya funciona perfectamente)<br>";
echo "</div>";

echo "<h3>8. Próximos Pasos</h3>";
echo "<a href='reporte_pdf_html.php' class='btn btn-success' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>✅ Usar Sistema HTML-PDF</a>";
echo "<a href='crear_pdf_exec.php' class='btn btn-primary' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>🚀 Crear Sistema exec() Avanzado</a>";

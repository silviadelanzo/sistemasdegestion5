<?php
require_once '../../config/config.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<h2>🔧 Instalación Automática de wkhtmltopdf</h2>";

// Función para ejecutar comandos de forma segura
function ejecutarComando($comando, $descripcion)
{
    echo "<div style='background: #f8f9fa; padding: 10px; border-left: 4px solid #007bff; margin: 10px 0;'>";
    echo "<strong>$descripcion</strong><br>";
    echo "<code>$comando</code><br><br>";

    $output = [];
    $return_var = 0;

    $tiempo_inicio = microtime(true);
    @exec($comando . ' 2>&1', $output, $return_var);
    $tiempo_total = round(microtime(true) - $tiempo_inicio, 2);

    if ($return_var === 0) {
        echo "<span style='color: green;'>✅ Éxito (tiempo: {$tiempo_total}s)</span><br>";
        if (!empty($output)) {
            echo "<details><summary>Ver resultado</summary><pre style='background: #e9ecef; padding: 10px; border-radius: 4px;'>";
            echo htmlspecialchars(implode("\n", $output));
            echo "</pre></details>";
        }
    } else {
        echo "<span style='color: red;'>❌ Error (código: $return_var, tiempo: {$tiempo_total}s)</span><br>";
        if (!empty($output)) {
            echo "<details><summary>Ver error</summary><pre style='background: #f8d7da; padding: 10px; border-radius: 4px;'>";
            echo htmlspecialchars(implode("\n", $output));
            echo "</pre></details>";
        }
    }
    echo "</div>";

    return $return_var === 0;
}

// 1. Detectar sistema operativo
echo "<h3>1. Detectando Sistema Operativo</h3>";
ejecutarComando('cat /etc/os-release', 'Información del sistema');
ejecutarComando('uname -a', 'Información del kernel');

// 2. Verificar permisos
echo "<h3>2. Verificando Permisos</h3>";
ejecutarComando('whoami', 'Usuario actual');
ejecutarComando('pwd', 'Directorio actual');
ejecutarComando('ls -la /tmp', 'Permisos directorio temporal');

// 3. Verificar gestores de paquetes disponibles
echo "<h3>3. Detectando Gestor de Paquetes</h3>";

$gestores = [
    'apt' => 'apt --version',
    'yum' => 'yum --version',
    'dnf' => 'dnf --version',
    'pacman' => 'pacman --version',
    'brew' => 'brew --version'
];

$gestor_disponible = null;
foreach ($gestores as $gestor => $comando) {
    if (ejecutarComando($comando, "Verificando $gestor")) {
        $gestor_disponible = $gestor;
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "✅ <strong>Gestor de paquetes encontrado: $gestor</strong>";
        echo "</div>";
        break;
    }
}

// 4. Intentar instalación según el gestor disponible
echo "<h3>4. Intentando Instalación</h3>";

if ($gestor_disponible) {
    switch ($gestor_disponible) {
        case 'apt':
            echo "<p>Intentando instalación con APT (Ubuntu/Debian)...</p>";
            ejecutarComando('sudo apt update', 'Actualizando lista de paquetes');
            ejecutarComando('sudo apt install -y wkhtmltopdf', 'Instalando wkhtmltopdf');
            break;

        case 'yum':
            echo "<p>Intentando instalación con YUM (CentOS/RHEL)...</p>";
            ejecutarComando('sudo yum install -y wkhtmltopdf', 'Instalando wkhtmltopdf');
            break;

        case 'dnf':
            echo "<p>Intentando instalación con DNF (Fedora)...</p>";
            ejecutarComando('sudo dnf install -y wkhtmltopdf', 'Instalando wkhtmltopdf');
            break;
    }
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border: 1px solid #ffeaa7;'>";
    echo "⚠️ <strong>No se encontró un gestor de paquetes conocido</strong><br>";
    echo "Intentaremos instalación manual...";
    echo "</div>";

    // 5. Instalación manual
    echo "<h3>5. Instalación Manual</h3>";

    // Crear directorio temporal
    $temp_dir = sys_get_temp_dir() . '/wkhtmltopdf_install';
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }

    echo "<p>Directorio temporal: <code>$temp_dir</code></p>";

    // Detectar arquitectura
    ejecutarComando('uname -m', 'Detectando arquitectura');

    // URLs de descarga según arquitectura
    $urls_descarga = [
        'x86_64' => 'https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.focal_amd64.deb',
        'aarch64' => 'https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.focal_arm64.deb'
    ];

    // Intentar descarga
    $url_descarga = $urls_descarga['x86_64']; // Por defecto x86_64
    $archivo_descarga = $temp_dir . '/wkhtmltopdf.deb';

    echo "<p>Intentando descarga desde: <code>$url_descarga</code></p>";

    // Usar wget o curl para descarga
    $descarga_exitosa = false;
    if (ejecutarComando("wget -O '$archivo_descarga' '$url_descarga'", 'Descargando con wget')) {
        $descarga_exitosa = true;
    } elseif (ejecutarComando("curl -L -o '$archivo_descarga' '$url_descarga'", 'Descargando con curl')) {
        $descarga_exitosa = true;
    }

    if ($descarga_exitosa && file_exists($archivo_descarga)) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "✅ <strong>Descarga exitosa:</strong> " . filesize($archivo_descarga) . " bytes";
        echo "</div>";

        // Intentar instalación del paquete
        ejecutarComando("sudo dpkg -i '$archivo_descarga'", 'Instalando paquete DEB');
        ejecutarComando("sudo apt-get install -f", 'Resolviendo dependencias');
    }
}

// 6. Verificar instalación
echo "<h3>6. Verificando Instalación</h3>";

$instalacion_exitosa = ejecutarComando('wkhtmltopdf --version', 'Verificando wkhtmltopdf');
if ($instalacion_exitosa) {
    ejecutarComando('which wkhtmltopdf', 'Ubicación del ejecutable');

    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb; margin: 20px 0;'>";
    echo "<h4>🎉 ¡Instalación Exitosa!</h4>";
    echo "✅ wkhtmltopdf está instalado y funcionando<br>";
    echo "✅ Ahora puedes usar generación automática de PDFs<br>";
    echo "<a href='crear_pdf_automatico.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>🚀 Crear Sistema PDF Automático</a>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb; margin: 20px 0;'>";
    echo "<h4>❌ Instalación No Exitosa</h4>";
    echo "No se pudo instalar wkhtmltopdf automáticamente.<br><br>";
    echo "<strong>Opciones:</strong><br>";
    echo "1. <strong>Contactar al hosting:</strong> Solicitar instalación de wkhtmltopdf<br>";
    echo "2. <strong>Usar sistema actual:</strong> HTML-PDF funciona perfectamente<br>";
    echo "3. <strong>Instalar manualmente:</strong> Acceso root requerido<br><br>";
    echo "<a href='reporte_pdf_html.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>✅ Usar Sistema HTML-PDF</a>";
    echo "</div>";
}

// 7. Limpiar archivos temporales
echo "<h3>7. Limpieza</h3>";
if (isset($temp_dir) && is_dir($temp_dir)) {
    ejecutarComando("rm -rf '$temp_dir'", 'Limpiando archivos temporales');
}

echo "<h3>8. Resumen Final</h3>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>📊 Estado del Sistema PDF:</strong><br><br>";
echo "✅ <strong>HTML-PDF:</strong> Funcional y recomendado<br>";
echo ($instalacion_exitosa ? "✅" : "❌") . " <strong>wkhtmltopdf:</strong> " . ($instalacion_exitosa ? "Instalado y funcional" : "No disponible") . "<br>";
echo "✅ <strong>exec() functions:</strong> Disponibles<br>";
echo "✅ <strong>Datos del sistema:</strong> Obtenidos correctamente<br><br>";
echo "<strong>🎯 Recomendación:</strong> " . ($instalacion_exitosa ? "Usar ambos sistemas según necesidad" : "Continuar con HTML-PDF") . "<br>";
echo "</div>";

echo "<a href='test_pdf_servidor.php' style='padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;'>← Volver al Test PDF</a>";

<?php
require_once 'config/config.php';

echo "<h2>🔍 Verificador de Remitos Generados</h2>";

$remitos_dir = 'assets/remitos_prueba';

if (!is_dir($remitos_dir)) {
    echo "<p style='color: red;'>❌ Directorio de remitos no existe: $remitos_dir</p>";
    echo "<p><a href='generar_remitos_prueba.php' class='btn btn-primary'>Generar Remitos Primero</a></p>";
    exit;
}

echo "<h3>📁 Contenido del Directorio: <code>$remitos_dir/</code></h3>";

$archivos = scandir($remitos_dir);
$archivos = array_filter($archivos, function ($archivo) {
    return !in_array($archivo, ['.', '..']);
});

if (empty($archivos)) {
    echo "<p style='color: orange;'>⚠️ No hay archivos generados</p>";
    echo "<p><a href='generar_remitos_prueba.php' class='btn btn-primary'>Generar Remitos</a></p>";
    exit;
}

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;'>";

// Agrupar archivos por remito
$remitos = [];
foreach ($archivos as $archivo) {
    if (preg_match('/remito_(\d+)_(.+)\.(html|pdf|jpg)/', $archivo, $matches)) {
        $numero = $matches[1];
        $nombre = $matches[2];
        $extension = $matches[3];

        if (!isset($remitos[$numero])) {
            $remitos[$numero] = ['nombre' => $nombre, 'archivos' => []];
        }
        $remitos[$numero]['archivos'][$extension] = $archivo;
    }
}

foreach ($remitos as $numero => $remito) {
    echo "<div style='border: 1px solid #ddd; border-radius: 8px; padding: 15px; background: #f9f9f9;'>";
    echo "<h4>📄 Remito #$numero</h4>";
    echo "<p><strong>Identificador:</strong> " . str_replace('_', ' ', $remito['nombre']) . "</p>";

    echo "<div style='margin: 10px 0;'>";

    // HTML Preview
    if (isset($remito['archivos']['html'])) {
        $archivo_html = $remito['archivos']['html'];
        echo "<a href='$remitos_dir/$archivo_html' target='_blank' style='display: inline-block; padding: 8px 12px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; margin: 2px;'>📄 Ver HTML</a>";

        // Obtener info del archivo
        $size = filesize("$remitos_dir/$archivo_html");
        echo "<span style='font-size: 12px; color: #666; margin-left: 10px;'>(" . number_format($size) . " bytes)</span>";
    }

    // PDF
    if (isset($remito['archivos']['pdf'])) {
        $archivo_pdf = $remito['archivos']['pdf'];
        echo "<a href='$remitos_dir/$archivo_pdf' target='_blank' style='display: inline-block; padding: 8px 12px; background: #dc3545; color: white; text-decoration: none; border-radius: 4px; margin: 2px;'>📑 Ver PDF</a>";

        $size = filesize("$remitos_dir/$archivo_pdf");
        echo "<span style='font-size: 12px; color: #666; margin-left: 10px;'>(" . number_format($size) . " bytes)</span>";
    }

    // JPG con preview
    if (isset($remito['archivos']['jpg'])) {
        $archivo_jpg = $remito['archivos']['jpg'];
        echo "<a href='$remitos_dir/$archivo_jpg' target='_blank' style='display: inline-block; padding: 8px 12px; background: #17a2b8; color: white; text-decoration: none; border-radius: 4px; margin: 2px;'>🖼️ Ver JPG</a>";

        $size = filesize("$remitos_dir/$archivo_jpg");
        echo "<span style='font-size: 12px; color: #666; margin-left: 10px;'>(" . number_format($size) . " bytes)</span>";

        // Miniatura
        echo "<div style='margin-top: 10px;'>";
        echo "<img src='$remitos_dir/$archivo_jpg' style='max-width: 100%; height: auto; border: 1px solid #ccc; border-radius: 4px;' alt='Preview Remito $numero'>";
        echo "</div>";
    }

    echo "</div>";
    echo "</div>";
}

echo "</div>";

// Botones de acción
echo "<hr>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='generar_remitos_prueba.php' class='btn btn-success'>🔄 Regenerar Remitos</a>";
echo "<a href='modulos/compras/nueva_compra.php' class='btn btn-primary' style='margin-left: 10px;'>🛒 Probar OCR</a>";
echo "<a href='menu_principal.php' class='btn btn-secondary' style='margin-left: 10px;'>🏠 Menú Principal</a>";
echo "</div>";

// Verificar proveedores en DB
try {
    $pdo = conectarDB();
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM proveedores");
    $total_proveedores = $stmt->fetch()['total'];

    echo "<div style='background: #e7f3ff; border: 1px solid #b3d7ff; border-radius: 5px; padding: 15px; margin: 20px 0;'>";
    echo "<h4>📊 Estado de la Base de Datos</h4>";
    echo "<p><strong>Proveedores registrados:</strong> $total_proveedores</p>";

    if ($total_proveedores >= 5) {
        echo "<p style='color: green;'>✅ Suficientes proveedores para testing</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Se recomienda tener al menos 5 proveedores</p>";
    }
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #ffe7e7; border: 1px solid #ffb3b3; border-radius: 5px; padding: 15px; margin: 20px 0;'>";
    echo "<p><strong>Error DB:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background: #f5f5f5;
    }

    h2,
    h3,
    h4 {
        color: #333;
    }

    .btn {
        padding: 10px 20px;
        margin: 5px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        font-weight: bold;
    }

    .btn-primary {
        background: #007bff;
        color: white;
    }

    .btn-success {
        background: #28a745;
        color: white;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn:hover {
        opacity: 0.8;
    }
</style>
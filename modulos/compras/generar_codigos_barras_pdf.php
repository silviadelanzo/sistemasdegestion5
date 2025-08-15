<?php
require_once '../../config/config.php';
require_once '../../dompdf/autoload.inc.php';
require_once '../../barcode.php'; // Ruta corregida: ahora apunta directamente a la raíz

use Dompdf\Dompdf;
use Dompdf\Options;

// Configuración de la sesión y conexión a la DB
iniciarSesionSegura();
// No requerimos login aquí si queremos que sea accesible directamente para generar
// requireLogin('../../login.php');

$pdo = conectarDB();

// Directorio para guardar las imágenes temporales de códigos de barras
$barcode_dir = __DIR__ . '/codigos/';
if (!is_dir($barcode_dir)) {
    mkdir($barcode_dir, 0777, true);
}

$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Códigos de Barras de Productos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .barcode-item {
            display: inline-block;
            width: 32%;
            min-width: 180px;
            max-width: 220px;
            height: 110px;
            margin: 0.5% 0.5% 20px 0.5%;
            border: 1px solid #ccc;
            padding: 5px;
            text-align: center;
            box-sizing: border-box;
            vertical-align: top;
            page-break-inside: avoid;
        }
        .clearfix {
            text-align: center;
        }
        .barcode-item img {
            max-width: 100%;
            height: auto;
            display: block; /* Eliminar espacio extra debajo de la imagen */
            margin: 0 auto; /* Centrar imagen */
        }
        .barcode-item p {
            margin: 5px 0 0 0;
            font-size: 10px;
            font-weight: bold;
            word-wrap: break-word; /* Para nombres largos */
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <h1>Códigos de Barras de Productos</h1>
    <div class="clearfix">
HTML;

$image_files_to_delete = [];

try {
    // Obtener los primeros 10 productos con código de barras
    $stmt = $pdo->query("SELECT id, nombre, codigo_barra FROM productos WHERE codigo_barra IS NOT NULL AND codigo_barra != '' LIMIT 10");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($productos)) {
        $html .= '<p>No se encontraron productos con códigos de barras.</p>';
    } else {
        foreach ($productos as $producto) {
            $product_name = htmlspecialchars($producto['nombre']);
            $barcode_data = htmlspecialchars($producto['codigo_barra']);
            $barcode_filename = $barcode_dir . 'barcode_' . $producto['id'] . '.png';

            // Generar la imagen del código de barras
            // Los parámetros son: ruta_archivo, datos, altura, orientación, tipo_codigo, mostrar_texto, grosor_barra
            barcode($barcode_filename, $barcode_data, 20, 'horizontal', 'code128', true, 1);

            // Añadir el archivo a la lista para borrar después
            $image_files_to_delete[] = $barcode_filename;

            $html .= "
                <div class=\"barcode-item\">
                    <p>" . $product_name . "</p>
                    <img src=\"data:image/png;base64," . base64_encode(file_get_contents($barcode_filename)) . "\" alt=\"Código de Barras: " . $barcode_data . "\">
                    <p>" . $barcode_data . "</p>
                </div>";
        }
    }

    $html .= <<<HTML
    </div>
</body>
</html>
HTML;

    // Configurar Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); // Necesario para cargar imágenes locales con 'file:///'
    $options->set('debugCss', true);
    $options->set('debugLayout', true);
    $options->set('debugPng', true); // Para depurar problemas con imágenes PNG
    $options->set('debugKeepTemp', true); // Mantener archivos temporales para inspección
    $options->set('logOutputFile', __DIR__ . '/dompdf_log.html'); // Archivo de log
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html);

    // Renderizar PDF
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Limpiar cualquier salida previa antes de enviar el PDF
    if (ob_get_length()) ob_clean();
    $dompdf->stream('codigos_barras_productos.pdf', ['Attachment' => 0]);
} catch (PDOException $e) {
    echo 'Error de base de datos: ' . $e->getMessage();
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
} finally {
    // Limpiar las imágenes temporales de códigos de barras
    foreach ($image_files_to_delete as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}

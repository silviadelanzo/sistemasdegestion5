<?php
// Generador de Excel SIN dependencias externas
// Utiliza solo funciones nativas de PHP

function generarExcelNativo($datos, $nombre_archivo = null)
{
    $nombre_archivo = $nombre_archivo ?? 'reporte_' . date('Y-m-d_H-i-s') . '.xls';

    // Headers para Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $nombre_archivo . '"');
    header('Cache-Control: max-age=0');

    // Inicio del archivo Excel (formato HTML que Excel interpreta)
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
    echo ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
    echo ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
    echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
    echo ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";

    // Estilos
    echo '<Styles>' . "\n";

    // Estilo para encabezado
    echo '<Style ss:ID="header">' . "\n";
    echo '<Font ss:Bold="1" ss:Color="#FFFFFF"/>' . "\n";
    echo '<Interior ss:Color="#007BFF" ss:Pattern="Solid"/>' . "\n";
    echo '<Alignment ss:Horizontal="Center"/>' . "\n";
    echo '<Borders>' . "\n";
    echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
    echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
    echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
    echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
    echo '</Borders>' . "\n";
    echo '</Style>' . "\n";

    // Estilo para título
    echo '<Style ss:ID="titulo">' . "\n";
    echo '<Font ss:Bold="1" ss:Size="16" ss:Color="#FFFFFF"/>' . "\n";
    echo '<Interior ss:Color="#28A745" ss:Pattern="Solid"/>' . "\n";
    echo '<Alignment ss:Horizontal="Center"/>' . "\n";
    echo '</Style>' . "\n";

    // Estilo para datos
    echo '<Style ss:ID="data">' . "\n";
    echo '<Borders>' . "\n";
    echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
    echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
    echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
    echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
    echo '</Borders>' . "\n";
    echo '</Style>' . "\n";

    // Estilo para números
    echo '<Style ss:ID="currency">' . "\n";
    echo '<NumberFormat ss:Format="$#,##0.00"/>' . "\n";
    echo '<Borders>' . "\n";
    echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
    echo '<Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
    echo '<Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
    echo '<Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
    echo '</Borders>' . "\n";
    echo '</Style>' . "\n";

    echo '</Styles>' . "\n";

    // Hoja de trabajo
    echo '<Worksheet ss:Name="Inventario">' . "\n";
    echo '<Table>' . "\n";

    // Título principal
    echo '<Row>' . "\n";
    echo '<Cell ss:MergeAcross="7" ss:StyleID="titulo">' . "\n";
    echo '<Data ss:Type="String">REPORTE DE INVENTARIO - SERVIDOR</Data>' . "\n";
    echo '</Cell>' . "\n";
    echo '</Row>' . "\n";

    // Fecha
    echo '<Row>' . "\n";
    echo '<Cell ss:MergeAcross="7">' . "\n";
    echo '<Data ss:Type="String">Generado: ' . date('d/m/Y H:i:s') . '</Data>' . "\n";
    echo '</Cell>' . "\n";
    echo '</Row>' . "\n";

    // Fila vacía
    echo '<Row></Row>' . "\n";

    // Encabezados
    $encabezados = ['ID', 'Código', 'Nombre', 'Categoría', 'Stock', 'Precio Compra', 'Precio Venta', 'Total'];
    echo '<Row>' . "\n";
    foreach ($encabezados as $encabezado) {
        echo '<Cell ss:StyleID="header">' . "\n";
        echo '<Data ss:Type="String">' . htmlspecialchars($encabezado) . '</Data>' . "\n";
        echo '</Cell>' . "\n";
    }
    echo '</Row>' . "\n";

    // Datos
    $total_general = 0;
    foreach ($datos as $fila) {
        $total_producto = $fila['precio_venta'] * $fila['stock'];
        $total_general += $total_producto;

        echo '<Row>' . "\n";
        echo '<Cell ss:StyleID="data"><Data ss:Type="Number">' . $fila['id'] . '</Data></Cell>' . "\n";
        echo '<Cell ss:StyleID="data"><Data ss:Type="String">' . htmlspecialchars($fila['codigo']) . '</Data></Cell>' . "\n";
        echo '<Cell ss:StyleID="data"><Data ss:Type="String">' . htmlspecialchars($fila['nombre']) . '</Data></Cell>' . "\n";
        echo '<Cell ss:StyleID="data"><Data ss:Type="String">' . htmlspecialchars($fila['categoria'] ?? 'Sin categoría') . '</Data></Cell>' . "\n";
        echo '<Cell ss:StyleID="data"><Data ss:Type="Number">' . $fila['stock'] . '</Data></Cell>' . "\n";
        echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . $fila['precio_compra'] . '</Data></Cell>' . "\n";
        echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . $fila['precio_venta'] . '</Data></Cell>' . "\n";
        echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . $total_producto . '</Data></Cell>' . "\n";
        echo '</Row>' . "\n";
    }

    // Total general
    echo '<Row>' . "\n";
    echo '<Cell ss:MergeAcross="6" ss:StyleID="header">' . "\n";
    echo '<Data ss:Type="String">TOTAL GENERAL</Data>' . "\n";
    echo '</Cell>' . "\n";
    echo '<Cell ss:StyleID="currency">' . "\n";
    echo '<Data ss:Type="Number">' . $total_general . '</Data>' . "\n";
    echo '</Cell>' . "\n";
    echo '</Row>' . "\n";

    echo '</Table>' . "\n";
    echo '</Worksheet>' . "\n";
    echo '</Workbook>' . "\n";
}

// Verificar si se quiere generar el Excel
if (isset($_GET['generar']) && $_GET['generar'] === 'si') {

    // Conectar a la base de datos
    require_once 'config/config.php';

    try {
        $pdo = conectarDB();

        // Obtener productos
        $sql = "SELECT p.id, p.codigo, p.nombre, c.nombre as categoria, 
                       p.stock, p.precio_compra, p.precio_venta
                FROM productos p 
                LEFT JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.activo = 1 
                ORDER BY p.nombre
                LIMIT 1000"; // Limitar para evitar problemas de memoria

        $stmt = $pdo->query($sql);
        $productos = $stmt->fetchAll();

        // Generar Excel
        generarExcelNativo($productos, 'inventario_servidor_' . date('Y-m-d_H-i-s') . '.xls');
        exit;
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel sin Dependencias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">📊 Generador de Excel Nativo</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5>🚀 Excel sin dependencias</h5>
                            <p>Este generador crea archivos Excel usando solo PHP nativo, sin necesidad de composer o librerías externas.</p>
                        </div>

                        <div class="alert alert-success">
                            <h6>✅ Características:</h6>
                            <ul class="mb-0">
                                <li>No requiere vendor/ ni composer</li>
                                <li>Compatible con cualquier servidor</li>
                                <li>Genera archivos .xls reales</li>
                                <li>Formato profesional con estilos</li>
                                <li>Funciona en Excel, LibreOffice, Google Sheets</li>
                            </ul>
                        </div>

                        <div class="text-center">
                            <a href="?generar=si" class="btn btn-success btn-lg">
                                📥 Descargar Excel de Inventario
                            </a>
                        </div>

                        <hr>

                        <div class="alert alert-warning">
                            <h6>💡 Comparación de métodos:</h6>
                            <ul class="mb-0">
                                <li><strong>PhpSpreadsheet:</strong> Más funciones, requiere vendor/</li>
                                <li><strong>Excel Nativo:</strong> Básico pero funciona en cualquier lugar</li>
                                <li><strong>CSV:</strong> Compatible universal, sin formato</li>
                            </ul>
                        </div>

                        <div class="text-center mt-3">
                            <a href="test_excel_servidor.php" class="btn btn-outline-primary">
                                ← Volver al Test Principal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
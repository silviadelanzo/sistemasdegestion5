<?php
// Generador de Excel compatible con PHP 8.1+
// Versión optimizada para servidores con PHP 8.1.33

require_once '../config/config.php';

// Verificar versión de PHP
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    die('Error: Se requiere PHP 8.1 o superior. Versión actual: ' . PHP_VERSION);
}

// Verificar que PhpSpreadsheet esté disponible
if (!file_exists('vendor/autoload.php')) {
    die('Error: PhpSpreadsheet no está instalado. Sube la carpeta vendor/ al servidor.');
}

// Cargar dependencias con manejo de errores
try {
    require_once 'vendor/autoload.php';
} catch (Error $e) {
    die('Error al cargar vendor/autoload.php: ' . $e->getMessage() . '<br>Versión PHP requerida por las librerías puede ser superior.');
}

// Verificar que las clases existan antes de usarlas
$clases_necesarias = [
    'PhpOffice\\PhpSpreadsheet\\Spreadsheet',
    'PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx',
    'PhpOffice\\PhpSpreadsheet\\Style\\Alignment',
    'PhpOffice\\PhpSpreadsheet\\Style\\Border',
    'PhpOffice\\PhpSpreadsheet\\Style\\Fill'
];

foreach ($clases_necesarias as $clase) {
    if (!class_exists($clase)) {
        die("Error: La clase {$clase} no está disponible. Verifica la instalación de PhpSpreadsheet.");
    }
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Verificar si se quiere generar el Excel
if (isset($_GET['generar']) && $_GET['generar'] === 'si') {

    try {
        // Aumentar límites para el proceso
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', 120);

        // Conectar a la base de datos
        $pdo = conectarDB();

        // Crear nueva hoja de cálculo
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar propiedades del documento
        $spreadsheet->getProperties()
            ->setCreator("Sistema de Gestión - PHP " . PHP_VERSION)
            ->setTitle("Inventario Completo")
            ->setSubject("Reporte de Inventario")
            ->setDescription("Reporte completo de inventario compatible con PHP 8.1+")
            ->setKeywords("inventario productos excel")
            ->setCategory("Reportes");

        // Configurar la hoja
        $sheet->setTitle('Inventario');

        // TÍTULO PRINCIPAL
        $sheet->setCellValue('A1', 'REPORTE DE INVENTARIO COMPLETO');
        $sheet->mergeCells('A1:H1');
        $sheet->getRowDimension('1')->setRowHeight(25);

        // Estilo del título con compatibilidad PHP 8.1
        $titulo_style = [
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
                'name' => 'Arial'
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E4BC6']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THICK,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];

        $sheet->getStyle('A1')->applyFromArray($titulo_style);

        // INFORMACIÓN DEL REPORTE
        $sheet->setCellValue('A2', 'Fecha: ' . date('d/m/Y H:i:s') . ' | PHP: ' . PHP_VERSION);
        $sheet->setCellValue('A3', 'Sistema: ' . (defined('SISTEMA_NOMBRE') ? SISTEMA_NOMBRE : 'Sistema de Gestión'));
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');

        $info_style = [
            'font' => ['italic' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ];

        $sheet->getStyle('A2:A3')->applyFromArray($info_style);

        // Fila vacía
        $sheet->setCellValue('A4', '');

        // ENCABEZADOS DE COLUMNAS
        $encabezados = [
            'A5' => 'ID',
            'B5' => 'Código',
            'C5' => 'Producto',
            'D5' => 'Categoría',
            'E5' => 'Stock',
            'F5' => 'Precio Compra',
            'G5' => 'Precio Venta',
            'H5' => 'Valor Total'
        ];

        foreach ($encabezados as $celda => $texto) {
            $sheet->setCellValue($celda, $texto);
        }

        // Estilo de encabezados compatible
        $header_style = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
                'name' => 'Arial'
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '28A745']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THICK,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];

        $sheet->getStyle('A5:H5')->applyFromArray($header_style);
        $sheet->getRowDimension('5')->setRowHeight(20);

        // OBTENER DATOS CON LÍMITE DE SEGURIDAD
        $sql = "SELECT p.id, p.codigo, p.nombre, c.nombre as categoria, 
                       p.stock, p.precio_compra, p.precio_venta,
                       (p.stock * p.precio_venta) as total
                FROM productos p 
                LEFT JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.activo = 1 
                ORDER BY p.nombre
                LIMIT 3000"; // Límite conservador para PHP 8.1

        $stmt = $pdo->query($sql);
        $productos = $stmt->fetchAll();

        // LLENAR DATOS
        $fila = 6;
        $total_general = 0;
        $total_stock = 0;

        foreach ($productos as $producto) {
            $valor_total = floatval($producto['precio_venta']) * intval($producto['stock']);
            $total_general += $valor_total;
            $total_stock += intval($producto['stock']);

            // Asignar valores de forma compatible
            $sheet->setCellValue('A' . $fila, $producto['id']);
            $sheet->setCellValue('B' . $fila, $producto['codigo'] ?? '');
            $sheet->setCellValue('C' . $fila, $producto['nombre'] ?? '');
            $sheet->setCellValue('D' . $fila, $producto['categoria'] ?? 'Sin categoría');
            $sheet->setCellValue('E' . $fila, intval($producto['stock']));
            $sheet->setCellValue('F' . $fila, floatval($producto['precio_compra']));
            $sheet->setCellValue('G' . $fila, floatval($producto['precio_venta']));
            $sheet->setCellValue('H' . $fila, $valor_total);

            // Alternar colores de filas
            $color_fondo = ($fila % 2 == 0) ? 'F8F9FA' : 'FFFFFF';

            $row_style = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $color_fondo]
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ],
                'font' => [
                    'size' => 10,
                    'name' => 'Arial'
                ]
            ];

            $sheet->getStyle('A' . $fila . ':H' . $fila)->applyFromArray($row_style);

            $fila++;

            // Liberar memoria cada 100 filas
            if ($fila % 100 == 0) {
                gc_collect_cycles();
            }
        }

        // FILA DE TOTALES
        $sheet->setCellValue('A' . $fila, '');
        $sheet->setCellValue('B' . $fila, '');
        $sheet->setCellValue('C' . $fila, 'TOTALES:');
        $sheet->setCellValue('D' . $fila, count($productos) . ' productos');
        $sheet->setCellValue('E' . $fila, $total_stock);
        $sheet->setCellValue('F' . $fila, '');
        $sheet->setCellValue('G' . $fila, 'TOTAL GENERAL →');
        $sheet->setCellValue('H' . $fila, $total_general);

        $total_style = [
            'font' => [
                'bold' => true,
                'size' => 12,
                'name' => 'Arial'
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFC107']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THICK,
                    'color' => ['rgb' => '000000']
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];

        $sheet->getStyle('A' . $fila . ':H' . $fila)->applyFromArray($total_style);

        // AJUSTAR ANCHOS DE COLUMNAS
        $anchos = [
            'A' => 8,
            'B' => 15,
            'C' => 35,
            'D' => 20,
            'E' => 10,
            'F' => 15,
            'G' => 15,
            'H' => 18
        ];

        foreach ($anchos as $columna => $ancho) {
            $sheet->getColumnDimension($columna)->setWidth($ancho);
        }

        // FORMATO DE NÚMEROS
        $sheet->getStyle('F6:H' . $fila)->getNumberFormat()->setFormatCode('#,##0.00"$"');

        // CONFIGURAR DESCARGA
        $filename = 'inventario_php81_' . date('Y-m-d_H-i-s') . '.xlsx';

        // Headers optimizados para PHP 8.1
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        // Crear writer compatible con PHP 8.1
        $writer = new Xlsx($spreadsheet);

        // Configuraciones específicas para compatibilidad
        $writer->setPreCalculateFormulas(false);

        // Guardar con manejo de errores
        try {
            $writer->save('php://output');
        } catch (Exception $e) {
            die('Error al generar archivo: ' . $e->getMessage());
        }

        // Limpiar memoria
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $writer, $productos);
        gc_collect_cycles();

        exit;
    } catch (Exception $e) {
        die('Error al generar Excel: ' . $e->getMessage() . '<br>Línea: ' . $e->getLine());
    } catch (Error $e) {
        die('Error fatal: ' . $e->getMessage() . '<br>Verifica la compatibilidad de PHP.');
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel Compatible PHP 8.1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-file-earmark-excel me-2"></i>
                            Excel Compatible PHP 8.1+
                        </h4>
                    </div>
                    <div class="card-body">

                        <!-- Información del Sistema -->
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle me-2"></i>Información del Sistema:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>PHP Actual:</strong> <?php echo PHP_VERSION; ?></p>
                                    <p><strong>Sistema:</strong> <?php echo PHP_OS; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Memoria:</strong> <?php echo ini_get('memory_limit'); ?></p>
                                    <p><strong>Tiempo máx:</strong> <?php echo ini_get('max_execution_time'); ?>s</p>
                                </div>
                            </div>
                        </div>

                        <?php
                        // Verificar compatibilidad
                        $compatible = true;
                        $errores = [];

                        // Verificar PHP
                        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                            $compatible = false;
                            $errores[] = 'PHP 8.1+ requerido (actual: ' . PHP_VERSION . ')';
                        }

                        // Verificar vendor
                        if (!file_exists('vendor/autoload.php')) {
                            $compatible = false;
                            $errores[] = 'Carpeta vendor/ no encontrada';
                        }

                        // Verificar clases
                        if (file_exists('vendor/autoload.php')) {
                            try {
                                require_once 'vendor/autoload.php';
                                if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
                                    $compatible = false;
                                    $errores[] = 'PhpSpreadsheet no disponible';
                                }
                            } catch (Error $e) {
                                $compatible = false;
                                $errores[] = 'Error de compatibilidad: ' . $e->getMessage();
                            }
                        }

                        // Verificar base de datos
                        try {
                            $pdo = conectarDB();
                            $sql = "SELECT COUNT(*) FROM productos WHERE activo = 1";
                            $stmt = $pdo->query($sql);
                            $total_productos = $stmt->fetchColumn();
                        } catch (Exception $e) {
                            $compatible = false;
                            $errores[] = 'Error de base de datos: ' . $e->getMessage();
                            $total_productos = 0;
                        }
                        ?>

                        <?php if ($compatible): ?>
                            <div class="alert alert-success">
                                <h6><i class="bi bi-check-circle me-2"></i>¡Sistema Compatible!</h6>
                                <p class="mb-1">✅ PHP <?php echo PHP_VERSION; ?> compatible</p>
                                <p class="mb-1">✅ PhpSpreadsheet disponible</p>
                                <p class="mb-0">✅ Base de datos: <strong><?php echo number_format($total_productos); ?> productos</strong></p>
                            </div>

                            <div class="text-center">
                                <a href="?generar=si" class="btn btn-success btn-lg">
                                    <i class="bi bi-download me-2"></i>
                                    Generar Excel Inventario
                                </a>
                                <p class="text-muted mt-2">
                                    <small>Compatible con PlanMaker, Excel, LibreOffice</small>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h6><i class="bi bi-exclamation-triangle me-2"></i>Problemas detectados:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($errores as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <div class="alert alert-warning">
                                <h6><i class="bi bi-lightbulb me-2"></i>Soluciones recomendadas:</h6>
                                <ol class="mb-0">
                                    <li>Actualizar PHP a versión 8.2+ en el servidor</li>
                                    <li>Usar una versión anterior de PhpSpreadsheet compatible con PHP 8.1</li>
                                    <li>Utilizar el generador nativo sin dependencias</li>
                                </ol>
                            </div>
                        <?php endif; ?>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <h6>Alternativas disponibles:</h6>
                                <div class="d-grid gap-2">
                                    <a href="excel_nativo.php" class="btn btn-warning">
                                        <i class="bi bi-file-earmark-excel me-2"></i>
                                        Excel Nativo (Sin vendor)
                                    </a>
                                    <a href="modulos/Inventario/reporte_inventario_csv.php" class="btn btn-info">
                                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                                        Reporte CSV
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Herramientas:</h6>
                                <div class="d-grid gap-2">
                                    <a href="test_excel_servidor.php" class="btn btn-outline-primary">
                                        <i class="bi bi-server me-2"></i>
                                        Test del Servidor
                                    </a>
                                    <a href="verificar_permisos.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-shield-check me-2"></i>
                                        Verificar Permisos
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
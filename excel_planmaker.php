<?php
// Generador de Excel compatible con PlanMaker/SoftMaker
// Utiliza PhpSpreadsheet para generar formato XLSX real

require_once 'config/config.php';

// Verificar que PhpSpreadsheet esté disponible
if (!file_exists('vendor/autoload.php')) {
    die('Error: PhpSpreadsheet no está instalado. Sube la carpeta vendor/ al servidor.');
}

require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Verificar si se quiere generar el Excel
if (isset($_GET['descargar']) && $_GET['descargar'] === 'si') {

    try {
        // Conectar a la base de datos
        $pdo = conectarDB();

        // Crear nueva hoja de cálculo
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar propiedades del documento para máxima compatibilidad
        $spreadsheet->getProperties()
            ->setCreator("Sistema de Gestión")
            ->setTitle("Inventario Completo")
            ->setSubject("Reporte de Inventario")
            ->setDescription("Reporte completo de inventario compatible con PlanMaker")
            ->setKeywords("inventario productos excel planmaker")
            ->setCategory("Reportes");

        // Configurar la hoja
        $sheet->setTitle('Inventario');

        // TÍTULO PRINCIPAL
        $sheet->setCellValue('A1', 'REPORTE DE INVENTARIO COMPLETO');
        $sheet->mergeCells('A1:H1');
        $sheet->getRowDimension('1')->setRowHeight(25);

        // Estilo del título
        $sheet->getStyle('A1')->applyFromArray([
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
        ]);

        // INFORMACIÓN DEL REPORTE
        $sheet->setCellValue('A2', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
        $sheet->setCellValue('A3', 'Sistema: ' . (defined('SISTEMA_NOMBRE') ? SISTEMA_NOMBRE : 'Sistema de Gestión'));
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');

        $sheet->getStyle('A2:A3')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

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

        // Estilo de encabezados
        $sheet->getStyle('A5:H5')->applyFromArray([
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
        ]);

        $sheet->getRowDimension('5')->setRowHeight(20);

        // OBTENER DATOS
        $sql = "SELECT p.id, p.codigo, p.nombre, c.nombre as categoria, 
                       p.stock, p.precio_compra, p.precio_venta,
                       (p.stock * p.precio_venta) as total
                FROM productos p 
                LEFT JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.activo = 1 
                ORDER BY p.nombre
                LIMIT 5000"; // Límite para evitar problemas de memoria

        $stmt = $pdo->query($sql);
        $productos = $stmt->fetchAll();

        // LLENAR DATOS
        $fila = 6;
        $total_general = 0;
        $total_stock = 0;

        foreach ($productos as $producto) {
            $valor_total = $producto['precio_venta'] * $producto['stock'];
            $total_general += $valor_total;
            $total_stock += $producto['stock'];

            $sheet->setCellValue('A' . $fila, $producto['id']);
            $sheet->setCellValue('B' . $fila, $producto['codigo']);
            $sheet->setCellValue('C' . $fila, $producto['nombre']);
            $sheet->setCellValue('D' . $fila, $producto['categoria'] ?? 'Sin categoría');
            $sheet->setCellValue('E' . $fila, $producto['stock']);
            $sheet->setCellValue('F' . $fila, $producto['precio_compra']);
            $sheet->setCellValue('G' . $fila, $producto['precio_venta']);
            $sheet->setCellValue('H' . $fila, $valor_total);

            // Alternar colores de filas para mejor legibilidad
            $color_fondo = ($fila % 2 == 0) ? 'F8F9FA' : 'FFFFFF';

            $sheet->getStyle('A' . $fila . ':H' . $fila)->applyFromArray([
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
            ]);

            $fila++;
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

        $sheet->getStyle('A' . $fila . ':H' . $fila)->applyFromArray([
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
        ]);

        // AJUSTAR ANCHOS DE COLUMNAS
        $anchos = [
            'A' => 8,   // ID
            'B' => 15,  // Código
            'C' => 30,  // Producto
            'D' => 20,  // Categoría
            'E' => 10,  // Stock
            'F' => 15,  // Precio Compra
            'G' => 15,  // Precio Venta
            'H' => 18   // Total
        ];

        foreach ($anchos as $columna => $ancho) {
            $sheet->getColumnDimension($columna)->setWidth($ancho);
        }

        // FORMATO DE NÚMEROS PARA PRECIOS
        $sheet->getStyle('F6:H' . $fila)->getNumberFormat()->setFormatCode('#,##0.00"$"');

        // CONFIGURAR PARA DESCARGA
        $filename = 'inventario_planmaker_' . date('Y-m-d_H-i-s') . '.xlsx';

        // Headers específicos para máxima compatibilidad
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        // Crear writer y guardar
        $writer = new Xlsx($spreadsheet);

        // Configuraciones específicas para compatibilidad
        $writer->setPreCalculateFormulas(false);

        $writer->save('php://output');

        // Limpiar memoria
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        exit;
    } catch (Exception $e) {
        die('Error al generar Excel: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel para PlanMaker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-file-earmark-excel me-2"></i>
                            Excel Compatible con PlanMaker
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5><i class="bi bi-info-circle me-2"></i>Formato XLSX Real</h5>
                            <p>Este generador crea archivos Excel en formato XLSX binario real, totalmente compatible con:</p>
                            <ul class="mb-0">
                                <li>✅ <strong>PlanMaker</strong> (SoftMaker Office)</li>
                                <li>✅ <strong>Microsoft Excel</strong></li>
                                <li>✅ <strong>LibreOffice Calc</strong></li>
                                <li>✅ <strong>Google Sheets</strong></li>
                                <li>✅ <strong>OpenOffice Calc</strong></li>
                            </ul>
                        </div>

                        <div class="alert alert-success">
                            <h6><i class="bi bi-check-circle me-2"></i>Características del archivo:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="mb-0">
                                        <li>Formato: <code>.xlsx</code> real</li>
                                        <li>Codificación: UTF-8</li>
                                        <li>Estilos: Colores y bordes</li>
                                        <li>Fórmulas: Compatibles</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="mb-0">
                                        <li>Datos: Inventario completo</li>
                                        <li>Cálculos: Totales automáticos</li>
                                        <li>Diseño: Profesional</li>
                                        <li>Tamaño: Optimizado</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <?php
                        // Verificar estado del sistema
                        $sistema_ok = true;
                        $mensajes = [];

                        if (!file_exists('vendor/autoload.php')) {
                            $sistema_ok = false;
                            $mensajes[] = 'PhpSpreadsheet no está instalado';
                        }

                        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
                            try {
                                require_once 'vendor/autoload.php';
                            } catch (Exception $e) {
                                $sistema_ok = false;
                                $mensajes[] = 'Error al cargar PhpSpreadsheet: ' . $e->getMessage();
                            }
                        }

                        try {
                            $pdo = conectarDB();
                            $sql = "SELECT COUNT(*) FROM productos WHERE activo = 1";
                            $stmt = $pdo->query($sql);
                            $total_productos = $stmt->fetchColumn();
                        } catch (Exception $e) {
                            $sistema_ok = false;
                            $mensajes[] = 'Error de base de datos: ' . $e->getMessage();
                            $total_productos = 0;
                        }
                        ?>

                        <?php if ($sistema_ok): ?>
                            <div class="alert alert-primary">
                                <h6><i class="bi bi-database me-2"></i>Datos disponibles:</h6>
                                <p class="mb-0">Se generará un Excel con <strong><?php echo number_format($total_productos); ?> productos</strong> del inventario.</p>
                            </div>

                            <div class="text-center">
                                <a href="?descargar=si" class="btn btn-success btn-lg">
                                    <i class="bi bi-download me-2"></i>
                                    Descargar Excel para PlanMaker
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h6><i class="bi bi-exclamation-triangle me-2"></i>Problemas detectados:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($mensajes as $mensaje): ?>
                                        <li><?php echo htmlspecialchars($mensaje); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <h6>Alternativas disponibles:</h6>
                                <div class="d-grid gap-2">
                                    <a href="excel_nativo.php" class="btn btn-outline-warning">
                                        Excel Nativo (XML)
                                    </a>
                                    <a href="modulos/Inventario/reporte_inventario_csv.php" class="btn btn-outline-info">
                                        Reporte CSV
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Herramientas:</h6>
                                <div class="d-grid gap-2">
                                    <a href="test_excel_servidor.php" class="btn btn-outline-primary">
                                        Test del Servidor
                                    </a>
                                    <a href="verificar_permisos.php" class="btn btn-outline-secondary">
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
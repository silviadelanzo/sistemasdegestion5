<?php
// Test básico de Excel para servidor
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Intentar cargar composer
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
} else {
    die('Error: vendor/autoload.php no encontrado. Ejecuta "composer install" en el servidor.');
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    // Crear nueva hoja de cálculo
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Configurar propiedades del documento
    $spreadsheet->getProperties()
        ->setCreator("Sistema de Gestión - Servidor")
        ->setTitle("Prueba de Excel en Servidor")
        ->setSubject("Test")
        ->setDescription("Prueba de generación de Excel en servidor web");

    // Título del reporte
    $sheet->setCellValue('A1', 'PRUEBA DE EXCEL EN SERVIDOR');
    $sheet->mergeCells('A1:F1');

    // Estilo del título
    $sheet->getStyle('A1')->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 16,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '007BFF']
        ]
    ]);

    // Información del servidor
    $sheet->setCellValue('A3', 'Fecha:');
    $sheet->setCellValue('B3', date('d/m/Y H:i:s'));
    $sheet->setCellValue('A4', 'PHP Version:');
    $sheet->setCellValue('B4', phpversion());
    $sheet->setCellValue('A5', 'Servidor:');
    $sheet->setCellValue('B5', $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido');
    $sheet->setCellValue('A6', 'Sistema:');
    $sheet->setCellValue('B6', PHP_OS);

    // Encabezados de datos de prueba
    $headers = ['ID', 'Producto', 'Precio', 'Stock', 'Total', 'Estado'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '8', $header);
        $col++;
    }

    // Estilo de encabezados
    $sheet->getStyle('A8:F8')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '28A745']
        ],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ]);

    // Datos de prueba
    $datos_prueba = [
        [1, 'Producto Test 1', 100.50, 25, 2512.50, 'Activo'],
        [2, 'Producto Test 2', 75.25, 10, 752.50, 'Activo'],
        [3, 'Producto Test 3', 200.00, 5, 1000.00, 'Stock Bajo'],
        [4, 'Producto Test 4', 50.75, 0, 0.00, 'Sin Stock'],
        [5, 'Producto Test 5', 150.00, 30, 4500.00, 'Activo']
    ];

    // Llenar datos
    $row = 9;
    $total_general = 0;

    foreach ($datos_prueba as $datos) {
        $sheet->setCellValue('A' . $row, $datos[0]);
        $sheet->setCellValue('B' . $row, $datos[1]);
        $sheet->setCellValue('C' . $row, $datos[2]);
        $sheet->setCellValue('D' . $row, $datos[3]);
        $sheet->setCellValue('E' . $row, $datos[4]);
        $sheet->setCellValue('F' . $row, $datos[5]);

        $total_general += $datos[4];

        // Color según estado
        if ($datos[5] == 'Sin Stock') {
            $color = 'FFEBEE';
        } elseif ($datos[5] == 'Stock Bajo') {
            $color = 'FFF3E0';
        } else {
            $color = $row % 2 == 0 ? 'F8F9FA' : 'FFFFFF';
        }

        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $color]
            ]
        ]);

        $row++;
    }

    // Fila de totales
    $sheet->setCellValue('D' . $row, 'TOTAL GENERAL:');
    $sheet->setCellValue('E' . $row, $total_general);

    $sheet->getStyle('D' . $row . ':E' . $row)->applyFromArray([
        'font' => ['bold' => true, 'size' => 12],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFC107']
        ],
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THICK]
        ]
    ]);

    // Ajustar ancho de columnas
    foreach (range('A', 'F') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Aplicar bordes a toda la tabla
    $sheet->getStyle('A8:F' . ($row))->applyFromArray([
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ]);

    // Configurar formato de números para precios
    $sheet->getStyle('C9:E' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');

    // Nombre del archivo
    $filename = 'prueba_servidor_' . date('Y-m-d_H-i-s') . '.xlsx';

    // Configurar headers para descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Expires: 0');
    header('Pragma: public');

    // Crear el writer y enviar el archivo
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

    // Limpiar memoria
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
} catch (Exception $e) {
    // Si hay error, mostrar página de error
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Error en Prueba de Excel</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "</head><body>";
    echo "<div class='container py-5'>";
    echo "<div class='alert alert-danger'>";
    echo "<h4>Error al generar Excel</h4>";
    echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Archivo:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
    echo "<a href='test_excel_servidor.php' class='btn btn-primary'>Volver a la prueba</a>";
    echo "</div></body></html>";
}

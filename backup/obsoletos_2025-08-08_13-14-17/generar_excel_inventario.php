<?php
require_once 'vendor/autoload.php';
require_once 'config/config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

iniciarSesionSegura();
requireLogin('login.php');

try {
    $pdo = conectarDB();

    // Crear nueva hoja de cálculo
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Configurar propiedades del documento
    $spreadsheet->getProperties()
        ->setCreator("Sistema de Gestión")
        ->setTitle("Reporte de Productos")
        ->setSubject("Inventario")
        ->setDescription("Reporte completo de productos del inventario");

    // Título del reporte
    $sheet->setCellValue('A1', 'REPORTE DE INVENTARIO');
    $sheet->mergeCells('A1:H1');

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

    // Fecha del reporte
    $sheet->setCellValue('A2', 'Fecha: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A2:H2');

    // Encabezados de columnas
    $headers = ['ID', 'Código', 'Nombre', 'Categoría', 'Stock', 'Precio Compra', 'Precio Venta', 'Total'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '4', $header);
        $col++;
    }

    // Estilo de encabezados
    $sheet->getStyle('A4:H4')->applyFromArray([
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

    // Obtener datos de productos
    $sql = "SELECT p.id, p.codigo, p.nombre, c.nombre as categoria, 
                   p.stock, p.precio_compra, p.precio_venta,
                   (p.stock * p.precio_venta) as total
            FROM productos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.activo = 1 
            ORDER BY p.nombre";

    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll();

    // Llenar datos
    $row = 5;
    $total_general = 0;

    foreach ($productos as $producto) {
        $sheet->setCellValue('A' . $row, $producto['id']);
        $sheet->setCellValue('B' . $row, $producto['codigo']);
        $sheet->setCellValue('C' . $row, $producto['nombre']);
        $sheet->setCellValue('D' . $row, $producto['categoria'] ?? 'Sin categoría');
        $sheet->setCellValue('E' . $row, $producto['stock']);
        $sheet->setCellValue('F' . $row, $producto['precio_compra']);
        $sheet->setCellValue('G' . $row, $producto['precio_venta']);
        $sheet->setCellValue('H' . $row, $producto['total']);

        $total_general += $producto['total'];

        // Alternar colores de filas
        if ($row % 2 == 0) {
            $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8F9FA']
                ]
            ]);
        }

        $row++;
    }

    // Fila de totales
    $sheet->setCellValue('G' . $row, 'TOTAL GENERAL:');
    $sheet->setCellValue('H' . $row, $total_general);

    $sheet->getStyle('G' . $row . ':H' . $row)->applyFromArray([
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
    foreach (range('A', 'H') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Aplicar bordes a toda la tabla
    $sheet->getStyle('A4:H' . ($row))->applyFromArray([
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ]);

    // Configurar formato de números para precios
    $sheet->getStyle('F5:H' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');

    // Nombre del archivo
    $filename = 'inventario_' . date('Y-m-d_H-i-s') . '.xlsx';

    // Configurar headers para descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Crear el writer y enviar el archivo
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

    // Limpiar memoria
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

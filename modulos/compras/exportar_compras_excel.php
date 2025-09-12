<?php
require_once '../../config/config.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

iniciarSesionSegura();
requireLogin('../../login.php');

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Replicar filtros de compras.php
    $filtro_busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
    $filtro_estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
    $filtro_proveedor = isset($_GET['proveedor']) ? trim($_GET['proveedor']) : '';

    $where_conditions = [];
    $params = [];

    if ($filtro_busqueda !== '') {
        $where_conditions[] = "(c.codigo LIKE ? OR p.razon_social LIKE ? OR p.nombre_comercial LIKE ?)";
        $busqueda = "%{$filtro_busqueda}%";
        array_push($params, $busqueda, $busqueda, $busqueda);
    }
    if ($filtro_estado !== '' && $filtro_estado !== 'todos') {
        $where_conditions[] = "c.estado = ?";
        $params[] = $filtro_estado;
    }
    if ($filtro_proveedor !== '' && $filtro_proveedor !== 'todos') {
        $where_conditions[] = "c.proveedor_id = ?";
        $params[] = $filtro_proveedor;
    }
    $where_clause = $where_conditions ? implode(' AND ', $where_conditions) : '1';

    // Obtener datos sin paginación
    $sql = "SELECT c.*, p.razon_social as proveedor_nombre, u.nombre as usuario_nombre
            FROM compras c
            LEFT JOIN proveedores p ON c.proveedor_id = p.id
            LEFT JOIN usuarios u ON c.usuario_id = u.id
            WHERE $where_clause
            ORDER BY c.fecha_compra DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear hoja de cálculo
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $spreadsheet->getProperties()->setTitle("Reporte de Compras");
    $sheet->setTitle('Compras');

    // Título
    $sheet->mergeCells('A1:G1');
    $sheet->setCellValue('A1', 'REPORTE DE COMPRAS');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '007BFF']]
    ]);

    // Encabezados
    $headers = ['ID', 'Código', 'Proveedor', 'Fecha', 'Estado', 'Total', 'Usuario'];
    $sheet->fromArray($headers, NULL, 'A3');
    $sheet->getStyle('A3:G3')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '28A745']]
    ]);

    // Datos
    $row = 4;
    $total_general = 0;
    foreach ($compras as $compra) {
        $sheet->setCellValue('A' . $row, $compra['id']);
        $sheet->setCellValue('B' . $row, $compra['codigo']);
        $sheet->setCellValue('C' . $row, $compra['proveedor_nombre']);
        $sheet->setCellValue('D' . $row, date('d/m/Y', strtotime($compra['fecha_compra'])));
        $sheet->setCellValue('E' . $row, $compra['estado']);
        $sheet->setCellValue('F' . $row, $compra['total']);
        $sheet->setCellValue('G' . $row, $compra['usuario_nombre']);
        $total_general += $compra['total'];
        $row++;
    }

    // Total
    $sheet->setCellValue('E' . $row, 'TOTAL GENERAL:');
    $sheet->setCellValue('F' . $row, $total_general);
    $sheet->getStyle('E' . $row . ':F' . $row)->applyFromArray([
        'font' => ['bold' => true, 'size' => 12],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC107']]
    ]);

    // Estilos finales
    $sheet->getStyle('F4:F' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Salida
    $filename = 'reporte_compras_' . date('Y-m-d') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

} catch (Exception $e) {
    // Manejo de errores
    error_log("Error al generar Excel de compras: " . $e->getMessage());
    die("Error: " . $e->getMessage());
}

<?php
require_once '../../config/config.php';
require_once '../../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

iniciarSesionSegura();
requireLogin('../../login.php');

$pedido_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pedido_id === 0) {
    die("ID de pedido no válido.");
}

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES 'utf8';");

    // Cargar datos del pedido y del cliente
    $stmt = $pdo->prepare("
        SELECT p.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.direccion as cliente_direccion, c.email as cliente_email, c.telefono as cliente_telefono
        FROM pedidos p
        JOIN clientes c ON p.cliente_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        die("Pedido no encontrado.");
    }

    // Cargar detalles del pedido
    $stmt_detalles = $pdo->prepare("
        SELECT pd.*, pr.nombre as producto_nombre, pr.codigo as producto_codigo
        FROM pedido_detalles pd
        JOIN productos pr ON pd.producto_id = pr.id
        WHERE pd.pedido_id = ?
    ");
    $stmt_detalles->execute([$pedido_id]);
    $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error al cargar datos del pedido: " . $e->getMessage());
}

// --- Generar el HTML para el PDF ---

$company_logo_path = '../../assets/img/logo.png'; // Ruta a un logo, usar placeholder si no existe
$company_logo_base64 = '';
if (file_exists($company_logo_path)) {
    $company_logo_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($company_logo_path));
}

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Pedido</title><style>';
$html .= '@page { margin: 20px 25px; }';
$html .= 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color: #333; font-size: 12px; }';
$html .= '.header { display: table; width: 100%; margin-bottom: 20px; }';
$html .= '.header-left, .header-right { display: table-cell; vertical-align: top; }';
$html .= '.header-left { width: 50%; }';
$html .= '.header-right { width: 50%; text-align: right; }';
$html .= '.header-right h1 { margin: 0; color: #0d6efd; font-size: 24px; }';
$html .= '.header-right p { margin: 0; font-size: 14px; }';
$html .= '.company-logo { max-width: 150px; max-height: 70px; margin-bottom: 10px; }';
$html .= '.info-section { display: table; width: 100%; margin-bottom: 20px; border-top: 2px solid #0d6efd; padding-top: 10px; }';
$html .= '.customer-info { display: table-cell; width: 50%; }';
$html .= 'h2 { font-size: 16px; margin-top: 0; margin-bottom: 5px; color: #0d6efd; }';
$html .= '.products-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }';
$html .= '.products-table th, .products-table td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }';
$html .= '.products-table thead { background-color: #f8f9fa; }';
$html .= '.products-table th { font-weight: bold; }';
$html .= '.text-right { text-align: right; }';
$html .= '.total-section { float: right; width: 40%; margin-top: 20px; }';
$html .= '.total-table { width: 100%; }';
$html .= '.total-table td { padding: 5px; }';
$html .= '.total-table .total-label { font-weight: bold; }';
$html .= '.total-table .total-amount { font-size: 18px; font-weight: bold; color: #0d6efd; }';
$html .= '.footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #888; }';
$html .= '</style></head><body>';

// Header
$html .= '<div class="header">';
$html .= '<div class="header-left">';
if ($company_logo_base64) {
    $html .= '<img src="' . $company_logo_base64 . '" alt="Logo" class="company-logo">';
}
$html .= '<p><strong>MI EMPRESA S.A.</strong><br>Dirección de la Empresa<br>Teléfono: (123) 456-7890<br>Email: info@miempresa.com</p>';
$html .= '</div>';
$html .= '<div class="header-right">';
$html .= '<h1>PEDIDO</h1>';
$html .= '<p><strong>Nro:</strong> ' . htmlspecialchars($pedido['codigo']) . '</p>';
$html .= '<p><strong>Fecha:</strong> ' . date('d/m/Y', strtotime($pedido['fecha_pedido'])) . '</p>';
$html .= '<p><strong>Estado:</strong> ' . htmlspecialchars(ucfirst($pedido['estado'])) . '</p>';
$html .= '</div>';
$html .= '</div>';

// Customer Info
$html .= '<div class="info-section">';
$html .= '<div class="customer-info">';
$html .= '<h2>Cliente</h2>';
$html .= '<p><strong>Nombre:</strong> ' . htmlspecialchars($pedido['cliente_nombre'] . ' ' . $pedido['cliente_apellido']) . '<br>';
$html .= '<strong>Dirección:</strong> ' . htmlspecialchars($pedido['cliente_direccion'] ?? 'No especificada') . '<br>';
$html .= '<strong>Teléfono:</strong> ' . htmlspecialchars($pedido['cliente_telefono'] ?? 'No especificado') . '<br>';
$html .= '<strong>Email:</strong> ' . htmlspecialchars($pedido['cliente_email'] ?? 'No especificado') . '</p>';
$html .= '</div>';
$html .= '</div>';

// Products Table
$html .= '<h2>Detalle del Pedido</h2>';
$html .= '<table class="products-table">';
$html .= '<thead><tr><th>Código</th><th>Producto</th><th class="text-right">Cantidad</th><th class="text-right">Precio Unit.</th><th class="text-right">Subtotal</th></tr></thead><tbody>';

$subtotal_general = 0;
foreach ($detalles as $detalle) {
    $subtotal_item = (int)$detalle['cantidad'] * $detalle['precio_unitario'];
    $subtotal_general += $subtotal_item;
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($detalle['producto_codigo']) . '</td>';
    $html .= '<td>' . htmlspecialchars($detalle['producto_nombre']) . '</td>';
    $html .= '<td class="text-right">' . (int)$detalle['cantidad'] . '</td>';
    $html .= '<td class="text-right">$' . number_format($detalle['precio_unitario'], 2, ',', '.') . '</td>';
    $html .= '<td class="text-right">$' . number_format($subtotal_item, 2, ',', '.') . '</td>';
    $html .= '</tr>';
}

$html .= '</tbody></table>';

// Totals
$html .= '<div class="total-section">';
$html .= '<table class="total-table">';
$html .= '<tr><td>Subtotal:</td><td class="text-right">$' . number_format($subtotal_general, 2, ',', '.') . '</td></tr>';
$html .= '<tr><td>Impuestos:</td><td class="text-right">$' . number_format($pedido['impuestos'], 2, ',', '.') . '</td></tr>';
$html .= '<tr><td class="total-label">TOTAL:</td><td class="text-right total-amount">$' . number_format($pedido['total'], 2, ',', '.') . '</td></tr>';
$html .= '</table>';
$html .= '</div>';

// Notes
if (!empty($pedido['notas'])) {
    $html .= '<div style="margin-top: 80px; clear:both;">';
    $html .= '<h2>Notas</h2>';
    $html .= '<p>' . nl2br(htmlspecialchars($pedido['notas'])) . '</p>';
    $html .= '</div>';
}

$html .= '<div class="footer">Este documento es un comprobante de pedido.</div>';
$html .= '</body></html>';

// --- Renderizar PDF con Dompdf ---
try {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    if (ob_get_length()) ob_clean();
    
    $dompdf->stream('Pedido_' . $pedido['codigo'] . '.pdf', ['Attachment' => 0]);

} catch (Exception $e) {
    echo 'Error al generar el PDF: ' . $e->getMessage();
}
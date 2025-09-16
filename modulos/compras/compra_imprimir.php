<?php
require_once '../../config/config.php';
require_once '../../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();
$id = $_GET['id'] ?? 0;

if ($id == 0) {
    die("ID de orden no proporcionado.");
}

// --- Cargar datos de la orden y detalles ---
$stmt = $pdo->prepare("SELECT oc.*, p.razon_social, p.direccion, p.telefono, p.email, e.nombre_estado 
                       FROM oc_ordenes oc
                       LEFT JOIN proveedores p ON oc.proveedor_id = p.id
                       LEFT JOIN oc_estados e ON oc.estado_id = e.id_estado
                       WHERE oc.id_orden = ?");
$stmt->execute([$id]);
$compra = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$compra) {
    die("Orden de compra no encontrada.");
}

$stmt_detalles = $pdo->prepare("SELECT cd.*, p.nombre, p.codigo_barra 
                               FROM oc_detalle cd 
                               JOIN productos p ON cd.producto_id = p.id 
                               WHERE cd.id_orden = ?");
$stmt_detalles->execute([$id]);
$detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

// --- Generar el HTML para el PDF ---

$company_logo_path = '../../assets/img/logo.png'; // Ruta a un logo, usar placeholder si no existe
$company_logo_base64 = '';
if (file_exists($company_logo_path)) {
    $company_logo_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($company_logo_path));
}

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Orden de Compra</title><style>';
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
$html .= '.supplier-info { display: table-cell; width: 50%; }';
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
$html .= '<h1>ORDEN DE COMPRA</h1>';
$html .= '<p><strong>Nro:</strong> ' . htmlspecialchars($compra['numero_orden']) . '</p>';
$html .= '<p><strong>Fecha:</strong> ' . date('d/m/Y', strtotime($compra['fecha_orden'])) . '</p>';
$html .= '<p><strong>Estado:</strong> ' . htmlspecialchars($compra['nombre_estado']) . '</p>';
$html .= '</div>';
$html .= '</div>';

// Supplier Info
$html .= '<div class="info-section">';
$html .= '<div class="supplier-info">';
$html .= '<h2>Proveedor</h2>';
$html .= '<p><strong>Razón Social:</strong> ' . htmlspecialchars($compra['razon_social']) . '<br>';
$html .= '<strong>Dirección:</strong> ' . htmlspecialchars($compra['direccion']) . '<br>';
$html .= '<strong>Teléfono:</strong> ' . htmlspecialchars($compra['telefono']) . '<br>';
$html .= '<strong>Email:</strong> ' . htmlspecialchars($compra['email']) . '</p>';
$html .= '</div>';
$html .= '</div>';

// Products Table
$html .= '<h2>Detalle de Productos</h2>';
$html .= '<table class="products-table">';
$html .= '<thead><tr><th>Código</th><th>Producto</th><th class="text-right">Cantidad</th><th class="text-right">Precio Unit.</th><th class="text-right">Subtotal</th></tr></thead><tbody>';

$total_general = 0;
foreach ($detalles as $detalle) {
    $subtotal = (int)$detalle['cantidad'] * $detalle['precio_unitario'];
    $total_general += $subtotal;
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($detalle['codigo_barra']) . '</td>';
    $html .= '<td>' . htmlspecialchars($detalle['nombre']) . '</td>';
    $html .= '<td class="text-right">' . (int)$detalle['cantidad'] . '</td>';
    $html .= '<td class="text-right">$' . number_format($detalle['precio_unitario'], 2) . '</td>';
    $html .= '<td class="text-right">$' . number_format($subtotal, 2) . '</td>';
    $html .= '</tr>';
}

$html .= '</tbody></table>';

// Totals
$html .= '<div class="total-section">';
$html .= '<table class="total-table">';
$html .= '<tr><td class="total-label">TOTAL:</td><td class="text-right total-amount">$' . number_format($total_general, 2) . '</td></tr>';
$html .= '</table>';
$html .= '</div>';

// Observations
if (!empty($compra['observaciones'])) {
    $html .= '<div style="margin-top: 80px; clear:both;">';
    $html .= '<h2>Observaciones</h2>';
    $html .= '<p>' . nl2br(htmlspecialchars($compra['observaciones'])) . '</p>';
    $html .= '</div>';
}

$html .= '<div class="footer">Documento no válido como factura.</div>';
$html .= '</body></html>';

// --- Renderizar PDF con Dompdf ---
try {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    if (ob_get_length()) ob_clean(); // Limpiar buffer de salida
    
    $dompdf->stream('Orden_de_Compra_' . $compra['numero_orden'] . '.pdf', ['Attachment' => 0]);

} catch (Exception $e) {
    echo 'Error al generar el PDF: ' . $e->getMessage();
}

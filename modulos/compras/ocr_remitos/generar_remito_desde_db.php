<?php
// Genera un remito desde datos REALES de la base (productos existentes y proveedor)
// Uso (navegador):
//   /modulos/compras/ocr_remitos/generar_remito_desde_db.php?proveedor_id=123&items=12&format=png|pdf
// Salida: guarda PNG en assets/demo/remitos/remito_db_YYYYmmdd_HHMMSS.png (y PDF si format=pdf y hay Imagick)

session_start();
require_once '../../../config/config.php';

// Verificar sesión (acepta usuario_id o id_usuario por compatibilidad)
if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['id_usuario'])) {
    header('Location: ../../../login.php');
    exit;
}

// Obtener conexión PDO
$pdo = isset($conexion) && $conexion instanceof PDO ? $conexion : conectarDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Helpers de archivos/imágenes
function ensureDir($path) {
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
}
function drawText($img, $x, $y, $text, $color, $font = 5) {
    imagestring($img, $font, $x, $y, (string)$text, $color);
}

// Buscar proveedor válido (con productos asignados)
function pickProveedor(PDO $db, ?int $proveedorId): ?array {
    if ($proveedorId) {
        $stmt = $db->prepare('SELECT id, codigo, razon_social, cuit FROM proveedores WHERE id = ?');
        $stmt->execute([$proveedorId]);
        $prov = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($prov) return $prov;
    }
    // Con productos vinculados por proveedor_principal_id
    $sql = "SELECT pr.id, pr.codigo, pr.razon_social, pr.cuit, COUNT(p.id) cant
            FROM proveedores pr
            JOIN productos p ON p.proveedor_principal_id = pr.id AND p.activo = 1
            GROUP BY pr.id, pr.codigo, pr.razon_social, pr.cuit
            ORDER BY cant DESC LIMIT 1";
    $row = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    if ($row) return $row;
    // Fallback: cualquier proveedor
    $row = $db->query('SELECT id, codigo, razon_social, cuit FROM proveedores ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

// Traer productos reales del proveedor (o los mejores disponibles)
function obtenerProductosDesdeDB(PDO $db, int $proveedorId, int $limit = 12): array {
    // Preferir productos activos del proveedor con código de proveedor si lo hay
    $limit = max(1, (int)$limit);

    // 1) Productos del proveedor dado
  $sql1 = "SELECT p.*
      FROM productos p
          WHERE p.activo = 1 AND p.proveedor_principal_id = ?
          ORDER BY p.id DESC LIMIT " . $limit;
    $stmt1 = $db->prepare($sql1);
    $stmt1->execute([$proveedorId]);
    $rows = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    if ($rows && count($rows) > 0) return $rows;

    // 2) Productos activos con código_proveedor
  $sql2 = "SELECT p.*
      FROM productos p
          WHERE p.activo = 1 AND (p.codigo_proveedor IS NOT NULL AND p.codigo_proveedor <> '')
          ORDER BY p.id DESC LIMIT " . $limit;
    $rows = $db->query($sql2)->fetchAll(PDO::FETCH_ASSOC);
    if ($rows && count($rows) > 0) return $rows;

    // 3) Cualquier producto activo
  $sql3 = "SELECT p.*
      FROM productos p
          WHERE p.activo = 1
          ORDER BY p.id DESC LIMIT " . $limit;
    $rows = $db->query($sql3)->fetchAll(PDO::FETCH_ASSOC);
    if ($rows && count($rows) > 0) return $rows;

    return [];
}

function normalizarItem(array $p): array {
    $codigo = $p['codigo_proveedor'] ?? '';
    if (!$codigo) $codigo = $p['codigo'] ?? '';
    if (!$codigo) $codigo = $p['codigo_interno'] ?? '';
    if (!$codigo) $codigo = $p['ean'] ?? ($p['codigo_barras'] ?? '');

    $desc = $p['nombre'] ?? '';
    if (!$desc) $desc = $p['descripcion'] ?? '';
    if (!$desc) $desc = 'Producto sin nombre';

    $precio = (float)($p['precio_compra'] ?? 0);
    if ($precio <= 0) $precio = 100.0 + (rand(0, 900) / 10.0); // fallback razonable

    $ean = $p['ean'] ?? ($p['codigo_barras'] ?? '');

    return [
        'codigo' => (string)$codigo,
        'descripcion' => (string)$desc,
        'cantidad' => rand(1, 24),
        'precio' => $precio,
        'ean' => (string)$ean,
    ];
}

function crearRemitoPNG(array $proveedor, array $items, string $savePath) {
    $width = 1200; $height = 1700;
    $img = imagecreatetruecolor($width, $height);

    // Colores
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    $gray  = imagecolorallocate($img, 230, 230, 230);

    // Fondo
    imagefilledrectangle($img, 0, 0, $width, $height, $white);

    // Encabezado
    imagerectangle($img, 30, 30, $width-30, 200, $black);
    drawText($img, 50, 50, 'REMITO DE COMPRA - SistemaDGestion5', $black, 5);

    $fecha = date('d/m/Y');
    $numero = 'RC-' . date('Ymd') . '-' . str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT);

    drawText($img, 50, 90,  'Fecha: ' . $fecha, $black, 5);
    drawText($img, 50, 120, 'Proveedor: ' . ($proveedor['codigo'] ?? 'N/D') . ' - ' . ($proveedor['razon_social'] ?? 'N/D'), $black, 5);
    drawText($img, 50, 150, 'CUIT: ' . ($proveedor['cuit'] ?? 'N/D'), $black, 5);
    drawText($img, 800, 50,  'Remito: ' . $numero, $black, 5);

    // Cabecera tabla
    $top = 240; $left = 30; $right = $width - 30;
    imagerectangle($img, $left, $top, $right, $top + 40, $black);
    imagefilledrectangle($img, $left+1, $top+1, $right-1, $top + 39, $gray);
    drawText($img, 60,  $top + 12, 'CODIGO PROV.', $black, 5);
    drawText($img, 260, $top + 12, 'DESCRIPCION', $black, 5);
    drawText($img, 900, $top + 12, 'CANT', $black, 5);
    drawText($img, 1000, $top + 12, 'PRECIO', $black, 5);

    // Filas
    $rowTop = $top + 60; $rowH = 56; $total = 0.0;
    foreach ($items as $i => $it) {
        $y1 = $rowTop + $i * $rowH; $y2 = $y1 + $rowH;
        imagerectangle($img, $left, $y1, $right, $y2, $black);
        drawText($img, 60,  $y1 + 18, substr($it['codigo'], 0, 18), $black, 5);
        drawText($img, 260, $y1 + 18, substr($it['descripcion'], 0, 52), $black, 5);
        if (!empty($it['ean'])) {
            // Mostrar EAN pequeño en gris, ayuda al OCR
            $g = imagecolorallocate($img, 120, 120, 120);
            drawText($img, 260, $y1 + 36, 'EAN: ' . substr($it['ean'], 0, 18), $g, 3);
        }
        drawText($img, 900, $y1 + 18, (string)$it['cantidad'], $black, 5);
        drawText($img, 1000, $y1 + 18, '$' . number_format($it['precio'], 2, ',', '.'), $black, 5);
        $total += $it['cantidad'] * $it['precio'];
    }

    // Total
    $totTop = $rowTop + count($items) * $rowH + 20;
    imagerectangle($img, 700, $totTop, $right, $totTop + 70, $black);
    drawText($img, 720, $totTop + 25, 'TOTAL:', $black, 5);
    drawText($img, 900, $totTop + 25, '$' . number_format($total, 2, ',', '.'), $black, 5);

    ensureDir(dirname($savePath));
    imagepng($img, $savePath);
    imagedestroy($img);
}

function maybeCreatePdfFromPng($pngPath, $pdfPath): bool {
    if (class_exists('Imagick')) {
        try {
            $im = new Imagick();
            $im->readImage($pngPath);
            $im->setImageFormat('pdf');
            $im->writeImage($pdfPath);
            $im->clear(); $im->destroy();
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
    return false;
}

// Parámetros
$proveedor_id = isset($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : 0;
$cant_items   = isset($_GET['items']) ? max(1, min(25, (int)$_GET['items'])) : 12;
$format       = isset($_GET['format']) ? strtolower($_GET['format']) : 'png';

$proveedor = pickProveedor($pdo, $proveedor_id ?: null);
if (!$proveedor) {
    http_response_code(400);
    echo 'No se encontró un proveedor válido en la base.';
    exit;
}

$productos = obtenerProductosDesdeDB($pdo, (int)$proveedor['id'], $cant_items);
if (!$productos) {
    http_response_code(400);
    echo 'No se encontraron productos activos para generar el remito.';
    exit;
}

// Normalizar items
$items = array_map('normalizarItem', $productos);

// Crear archivos
$baseDir = __DIR__ . '/../../../assets/demo/remitos/';
ensureDir($baseDir);
$ts = date('Ymd_His');
$png = $baseDir . 'remito_db_' . $ts . '.png';
crearRemitoPNG($proveedor, $items, $png);

$pdf = null; $madePdf = false;
if ($format === 'pdf') {
    $pdf = $baseDir . 'remito_db_' . $ts . '.pdf';
    $madePdf = maybeCreatePdfFromPng($png, $pdf);
}

// Respuesta HTML sencilla con enlaces
$pngLink = str_replace(__DIR__ . '/../../../', '', $png);
$pdfLink = $pdf ? str_replace(__DIR__ . '/../../../', '', $pdf) : null;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Remito desde DB generado</title>
  <style> body{font-family:Arial, sans-serif; padding:16px} a{color:#0a58ca} code{background:#f5f5f5; padding:2px 4px} </style>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <style>.hint{color:#555}</style>
  <meta http-equiv="Cache-Control" content="no-store" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <meta name="robots" content="noindex" />
  <script>function copiar(u){navigator.clipboard.writeText(u);alert('Copiado: '+u)}</script>
  <script>function abrir(u){window.open(u,'_blank')}</script>
</head>
<body>
  <h3>✅ Remito generado con productos reales</h3>
  <p class="mb-1">Proveedor: <strong><?php echo htmlspecialchars(($proveedor['codigo'] ?? 'N/D') . ' - ' . ($proveedor['razon_social'] ?? '')); ?></strong></p>
  <p class="hint">Todos los ítems provienen de la tabla <code>productos</code> y usan <code>codigo_proveedor</code> cuando exista.</p>

  <div class="card p-3 mb-3">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <a class="btn btn-sm btn-primary" href="/<?php echo htmlspecialchars($pngLink); ?>" target="_blank"><i class="bi bi-image"></i> Ver PNG</a>
      <?php if ($madePdf && $pdfLink): ?>
        <a class="btn btn-sm btn-success" href="/<?php echo htmlspecialchars($pdfLink); ?>" target="_blank"><i class="bi bi-file-pdf"></i> Ver PDF</a>
      <?php else: ?>
        <a class="btn btn-sm btn-outline-secondary" href="?proveedor_id=<?php echo (int)$proveedor['id']; ?>&items=<?php echo (int)$cant_items; ?>&format=pdf"><i class="bi bi-filetype-pdf"></i> Reintentar PDF</a>
      <?php endif; ?>
      <button class="btn btn-sm btn-outline-dark" onclick="copiar('/<?php echo htmlspecialchars($pngLink); ?>')"><i class="bi bi-clipboard"></i> Copiar ruta PNG</button>
      <a class="btn btn-sm btn-outline-primary" href="../compras_ocr.php?proveedor=<?php echo (int)$proveedor['id']; ?>" target="_blank"><i class="bi bi-upload"></i> Ir a Subir Remito</a>
    </div>
  </div>

  <details class="mb-3">
    <summary class="h6">Ver items incluidos</summary>
    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead><tr><th>#</th><th>Código</th><th>Descripción</th><th>EAN</th><th>Cantidad</th><th>Precio</th></tr></thead>
        <tbody>
        <?php foreach ($items as $i => $it): ?>
          <tr>
            <td><?php echo $i+1; ?></td>
            <td><code><?php echo htmlspecialchars($it['codigo']); ?></code></td>
            <td><?php echo htmlspecialchars($it['descripcion']); ?></td>
            <td><?php echo htmlspecialchars($it['ean']); ?></td>
            <td><?php echo (int)$it['cantidad']; ?></td>
            <td>$<?php echo number_format($it['precio'], 2, ',', '.'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </details>

  <p class="hint">Sugerencia: cargá este PNG en <em>Compras OCR</em> con el mismo proveedor para que el sistema detecte coincidencias exactas y marque cualquier divergencia.</p>
</body>
</html>

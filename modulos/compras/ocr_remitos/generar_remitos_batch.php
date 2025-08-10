<?php
// Genera automáticamente 3 remitos de distintos proveedores, sin UI
// Formatos: PDF (si hay Imagick, además PNG)
// Archivos en: assets/demo/remitos/

declare(strict_types=1);
session_start();
require_once __DIR__ . '/../../../config/config.php';

// Permitir uso por CLI o navegador, sin exigir UI
if (!isset($_SESSION['id_usuario']) && !isset($_SESSION['usuario_id'])) {
    // Continuar igual: sólo generación de archivos
}

$pdo = conectarDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function ensureDir(string $path): void {
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
}

function pickDistinctProveedores(PDO $db, int $count = 3): array {
    $sql = "SELECT pr.id, pr.codigo, pr.razon_social, pr.cuit, COUNT(p.id) as cant
            FROM proveedores pr
            JOIN productos p ON p.proveedor_principal_id = pr.id AND p.activo = 1
            GROUP BY pr.id, pr.codigo, pr.razon_social, pr.cuit
            ORDER BY cant DESC, pr.id ASC
            LIMIT " . (int)$count;
    $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows || count($rows) < $count) {
        // Fallback: completar con los primeros proveedores
        $faltan = $count - (count($rows) ?: 0);
        $extra = $db->query("SELECT id, codigo, razon_social, cuit FROM proveedores ORDER BY id ASC LIMIT " . (int)$faltan)->fetchAll(PDO::FETCH_ASSOC);
        $rows = array_merge($rows ?: [], $extra ?: []);
    }
    return array_slice($rows ?: [], 0, $count);
}

function obtenerProductosDesdeDB(PDO $db, int $proveedorId, int $limit = 12): array {
    $limit = max(1, (int)$limit);
    // Preferir del proveedor
    $sql1 = "SELECT p.* FROM productos p WHERE p.activo = 1 AND p.proveedor_principal_id = ? ORDER BY p.id DESC LIMIT " . $limit;
    $st = $db->prepare($sql1); $st->execute([$proveedorId]); $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) return $rows;
    // Luego con codigo_proveedor
    $sql2 = "SELECT p.* FROM productos p WHERE p.activo = 1 AND (p.codigo_proveedor IS NOT NULL AND p.codigo_proveedor <> '') ORDER BY p.id DESC LIMIT " . $limit;
    $rows = $db->query($sql2)->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) return $rows;
    // Cualquier activo
    $sql3 = "SELECT p.* FROM productos p WHERE p.activo = 1 ORDER BY p.id DESC LIMIT " . $limit;
    return $db->query($sql3)->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function normalizarItem(array $p): array {
    $codigo = $p['codigo_proveedor'] ?? '';
    if (!$codigo) $codigo = $p['codigo'] ?? '';
    if (!$codigo) $codigo = $p['codigo_interno'] ?? '';
    if (!$codigo) $codigo = $p['ean'] ?? ($p['codigo_barras'] ?? '');
    $desc = $p['nombre'] ?? ($p['descripcion'] ?? 'Producto sin nombre');
    $precio = (float)($p['precio_compra'] ?? 0);
    if ($precio <= 0) $precio = 100.0 + (rand(0, 900) / 10.0);
    $ean = $p['ean'] ?? ($p['codigo_barras'] ?? '');
    return [
        'codigo' => (string)$codigo,
        'descripcion' => (string)$desc,
        'cantidad' => rand(1, 24),
        'precio' => $precio,
        'ean' => (string)$ean,
    ];
}

// Generador PDF minimalista (sin librerías)
function pdf_escape(string $s): string { return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s); }

function crearPDFSimple(string $savePath, array $proveedor, array $items): void {
    // Página A4: 595x842 pt
    $lines = [];
    $lines[] = 'REMITO DE COMPRA - SistemaDGestion5';
    $lines[] = 'Fecha: ' . date('d/m/Y');
    $prov = trim(($proveedor['codigo'] ?? 'N/D') . ' - ' . ($proveedor['razon_social'] ?? 'N/D'));
    $lines[] = 'Proveedor: ' . $prov;
    $lines[] = 'CUIT: ' . ($proveedor['cuit'] ?? 'N/D');
    $lines[] = '';
    $lines[] = 'CODIGO PROV. | DESCRIPCION | CANT | PRECIO';
    $total = 0.0;
    foreach ($items as $it) {
        $total += $it['cantidad'] * $it['precio'];
        $lines[] = sprintf('%s | %s | %d | $%0.2f',
            mb_substr($it['codigo'], 0, 18),
            mb_substr($it['descripcion'], 0, 52),
            (int)$it['cantidad'],
            (float)$it['precio']
        );
    }
    $lines[] = '';
    $lines[] = sprintf('TOTAL: $%0.2f', $total);

    // Construir contenido de texto PDF
    $content = "BT\n/F1 12 Tf\n";
    $x = 50; $y = 800; $lineH = 16;
    foreach ($lines as $ln) {
        $esc = pdf_escape($ln);
        $content .= sprintf("1 0 0 1 %d %d Tm (%s) Tj\n", $x, $y, $esc);
        $y -= $lineH;
    }
    $content .= "ET\n";
    $len = strlen($content);

    $objs = [];
    $objs[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objs[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $objs[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
    $objs[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    $objs[] = "5 0 obj\n<< /Length $len >>\nstream\n" . $content . "endstream\nendobj\n";

    $pdf = "%PDF-1.4\n";
    $offsets = []; $pos = strlen($pdf);
    foreach ($objs as $o) { $offsets[] = $pos; $pdf .= $o; $pos = strlen($pdf); }

    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objs)+1) . "\n";
    $pdf .= str_pad('0', 10, '0', STR_PAD_LEFT) . " 65535 f \n";
    foreach ($offsets as $off) {
        $pdf .= str_pad((string)$off, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }
    $pdf .= "trailer\n<< /Size " . (count($objs)+1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";

    ensureDir(dirname($savePath));
    file_put_contents($savePath, $pdf);
}

function maybeCreatePngFromPdf(string $pdfPath, string $pngPath): bool {
    if (class_exists('Imagick')) {
        try {
            $im = new Imagick();
            $im->setResolution(200, 200);
            $im->readImage($pdfPath . '[0]');
            $im->setImageFormat('png');
            $im->writeImage($pngPath);
            $im->clear(); $im->destroy();
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
    return false;
}

function maybeCreateJpgFromPng(string $pngPath, string $jpgPath): bool {
    if (class_exists('Imagick')) {
        try {
            $im = new Imagick();
            $im->readImage($pngPath);
            $im->setImageFormat('jpeg');
            $im->setImageCompressionQuality(90);
            $im->writeImage($jpgPath);
            $im->clear(); $im->destroy();
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
    return false;
}

// Proceso principal
$proveedores = pickDistinctProveedores($pdo, 3);
$baseDir = __DIR__ . '/../../../assets/demo/remitos/';
ensureDir($baseDir);

$generados = [];
foreach ($proveedores as $prov) {
    if (!$prov) continue;
    $items = array_map('normalizarItem', obtenerProductosDesdeDB($pdo, (int)$prov['id'], rand(10, 12)));
    if (!$items) continue;
    $ts = date('Ymd_His');
    $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '_', ($prov['codigo'] ?: ('prov_' . $prov['id'])));
    $pdf = $baseDir . 'remito_' . $slug . '_' . $ts . '.pdf';
    crearPDFSimple($pdf, $prov, $items);
    // Guardar TXT siempre (útil para pruebas / OCR manual)
    $txt = $baseDir . 'remito_' . $slug . '_' . $ts . '.txt';
    $fh = fopen($txt, 'w');
    fwrite($fh, "REMITO DE COMPRA - SistemaDGestion5\n");
    fwrite($fh, 'Fecha: ' . date('d/m/Y') . "\n");
    fwrite($fh, 'Proveedor: ' . (($prov['codigo'] ?? 'N/D') . ' - ' . ($prov['razon_social'] ?? 'N/D')) . "\n");
    fwrite($fh, 'CUIT: ' . ($prov['cuit'] ?? 'N/D') . "\n\n");
    fwrite($fh, "CODIGO PROV. | DESCRIPCION | CANT | PRECIO\n");
    $totalTmp = 0.0;
    foreach ($items as $it) {
        $totalTmp += $it['cantidad'] * $it['precio'];
        fwrite($fh, sprintf('%s | %s | %d | $%0.2f',
            mb_substr($it['codigo'], 0, 18),
            mb_substr($it['descripcion'], 0, 52),
            (int)$it['cantidad'],
            (float)$it['precio']
        ) . "\n");
    }
    fwrite($fh, "\n" . sprintf('TOTAL: $%0.2f', $totalTmp) . "\n");
    fclose($fh);
    $png = $baseDir . 'remito_' . $slug . '_' . $ts . '.png';
    $pngOk = maybeCreatePngFromPdf($pdf, $png);
    $jpg = null;
    if ($pngOk) {
        $jpg = $baseDir . 'remito_' . $slug . '_' . $ts . '.jpg';
        if (!maybeCreateJpgFromPng($png, $jpg)) { $jpg = null; }
    }
    $generados[] = [
        'proveedor' => $prov,
        'pdf' => $pdf,
        'png' => $pngOk ? $png : null,
        'jpg' => $jpg,
        'txt' => $txt,
    ];
    // Pequeña pausa para variar timestamp
    usleep(200000);
}

// Salida mínima (para uso por navegador o CLI)
header('Content-Type: text/plain; charset=utf-8');
echo "Generados: " . count($generados) . " remitos\n";
foreach ($generados as $g) {
    echo "- Proveedor: " . (($g['proveedor']['codigo'] ?? 'N/D') . ' - ' . ($g['proveedor']['razon_social'] ?? '')) . "\n";
    echo "  PDF: " . $g['pdf'] . "\n";
    if ($g['png']) echo "  PNG: " . $g['png'] . "\n";
}
?>

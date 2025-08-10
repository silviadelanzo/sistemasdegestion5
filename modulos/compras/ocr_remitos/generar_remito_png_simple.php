<?php
// Genera un remito DEMO legible por OCR como imagen PNG (opcional PDF si hay Imagick)
// Salida: guarda en assets/demo/remitos/remito_demo_YYYYmmdd_HHMMSS.png (y PDF si ?format=pdf y hay Imagick)

function ensureDir($path) {
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
}

function drawText($img, $x, $y, $text, $color, $font = 5) {
    imagestring($img, $font, $x, $y, $text, $color);
}

function generateDemoData() {
    $fecha = date('d/m/Y');
    $proveedor = 'DISTRIBUIDORA CENTRAL S.A.';
    $cuit = '30-12345678-9';
    $numero = '0001-'.str_pad(strval(rand(1000,9999)), 8, '0', STR_PAD_LEFT);
    $items = [
        ['COD001', 'Arroz Largo Fino 1kg', 25, 850.00],
        ['ACE900', 'Aceite Girasol 900ml', 12, 1200.00],
        ['AZU001', 'Azucar Comun 1kg', 30, 950.00],
        ['FID500', 'Fideos Mostachol 500g', 20, 700.00],
        ['HAR100', 'Harina 0000 1kg', 15, 680.00],
    ];
    return [$fecha, $proveedor, $cuit, $numero, $items];
}

function createRemitoPng($savePath) {
    // Lienzo A4 aproximado escalado (px)
    $width = 1200; $height = 1700;
    $img = imagecreatetruecolor($width, $height);

    // Colores
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    $gray  = imagecolorallocate($img, 220, 220, 220);

    // Fondo
    imagefilledrectangle($img, 0, 0, $width, $height, $white);

    // Encabezado
    imagerectangle($img, 30, 30, $width-30, 200, $black);
    drawText($img, 50, 50, 'REMITO DE ENTREGA', $black, 5);

    list($fecha, $proveedor, $cuit, $numero, $items) = generateDemoData();

    drawText($img, 50, 90, 'Fecha: '.$fecha, $black, 5);
    drawText($img, 50, 120, 'Proveedor: '.$proveedor, $black, 5);
    drawText($img, 50, 150, 'CUIT: '.$cuit, $black, 5);
    drawText($img, 800, 50, 'Remito: '.$numero, $black, 5);

    // Cabecera tabla
    $top = 240; $left = 30; $right = $width - 30;
    imagerectangle($img, $left, $top, $right, $top + 40, $black);
    imagefilledrectangle($img, $left+1, $top+1, $right-1, $top + 39, $gray);
    drawText($img, 60, $top + 12, 'CODIGO', $black, 5);
    drawText($img, 250, $top + 12, 'DESCRIPCION', $black, 5);
    drawText($img, 900, $top + 12, 'CANT', $black, 5);
    drawText($img, 1000, $top + 12, 'PRECIO', $black, 5);

    // Filas
    $rowTop = $top + 60;
    $rowH = 60;
    $total = 0.0;
    foreach ($items as $i => $it) {
        list($cod, $desc, $cant, $precio) = $it;
        $y1 = $rowTop + $i * $rowH;
        $y2 = $y1 + $rowH;
        imagerectangle($img, $left, $y1, $right, $y2, $black);

        drawText($img, 60,  $y1 + 20, $cod, $black, 5);
        drawText($img, 250, $y1 + 20, $desc, $black, 5);
        drawText($img, 900, $y1 + 20, strval($cant), $black, 5);
        drawText($img, 1000, $y1 + 20, '$'.number_format($precio, 2, ',', '.'), $black, 5);
        $total += $cant * $precio;
    }

    // Total
    $totTop = $rowTop + count($items) * $rowH + 20;
    imagerectangle($img, 700, $totTop, $right, $totTop + 70, $black);
    drawText($img, 720, $totTop + 25, 'TOTAL:', $black, 5);
    drawText($img, 900, $totTop + 25, '$'.number_format($total, 2, ',', '.'), $black, 5);

    ensureDir(dirname($savePath));
    imagepng($img, $savePath);
    imagedestroy($img);
}

function maybeCreatePdfFromPng($pngPath, $pdfPath) {
    if (class_exists('Imagick')) {
        $im = new Imagick();
        $im->readImage($pngPath);
        $im->setImageFormat('pdf');
        $im->writeImage($pdfPath);
        $im->clear();
        $im->destroy();
        return true;
    }
    return false;
}

$baseDir = __DIR__.'/../../../assets/demo/remitos/';
ensureDir($baseDir);
$ts = date('Ymd_His');
$png = $baseDir.'remito_demo_'.$ts.'.png';
createRemitoPng($png);

$madePdf = false;
if ((isset($_GET['format']) && $_GET['format'] === 'pdf')) {
    $pdf = $baseDir.'remito_demo_'.$ts.'.pdf';
    $madePdf = maybeCreatePdfFromPng($png, $pdf);
}

if (php_sapi_name() === 'cli') {
    echo "PNG generado: ".$png.(isset($pdf) && $madePdf ? "\nPDF generado: ".$pdf : "")."\n";
} else {
    header('Content-Type: text/html; charset=utf-8');
    echo '<h3>Remito DEMO generado</h3>';
    echo '<p><a href="'.str_replace(__DIR__.'/../../../', '', $png).'" target="_blank">Ver PNG</a></p>';
    if (isset($pdf) && $madePdf) {
        echo '<p><a href="'.str_replace(__DIR__.'/../../../', '', $pdf).'" target="_blank">Ver PDF</a></p>';
    } else {
        echo '<p><em>PDF no generado (requiere Imagick). Puedes subir el PNG al OCR.</em></p>';
    }
}

<?php
// ocr_test_simple.php: Página de prueba rápida para OCR
require_once '../../../config/config.php';
require_once 'ocr_processor.php';

$textoExtraido = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $file = $_FILES['archivo'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $file['tmp_name'];
        try {
            $ocr = new OCRProcessor();
            $textoExtraido = $ocr->extractText($tmpPath);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'Error al subir el archivo';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test OCR Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4">Test OCR Simple</h2>
    <form method="post" enctype="multipart/form-data" class="mb-4">
        <div class="mb-3">
            <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png,.tiff,.tif" required class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Extraer Texto</button>
    </form>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($textoExtraido): ?>
        <h5>Texto extraído:</h5>
        <pre class="bg-white p-3 border rounded" style="white-space: pre-wrap;"><?= htmlspecialchars($textoExtraido) ?></pre>
    <?php endif; ?>
</div>
</body>
</html>

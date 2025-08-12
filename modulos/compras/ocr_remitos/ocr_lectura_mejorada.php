
<?php
// ocr_lectura_mejorada.php: Interfaz moderna y robusta para lectura OCR y alta de remito/compra
require_once '../../../config/config.php';
iniciarSesionSegura();
requireLogin('../../../login.php');
require_once 'ocr_processor.php';

$pdo = conectarDB();
$textoExtraido = '';
$error = '';
$remitoSiguiente = '';
$compraSiguiente = '';

// Calcular próximo número de remito
try {
    $max = $pdo->query("SELECT MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) FROM remitos WHERE codigo LIKE 'REM-%'")->fetchColumn();
    $next = (int)$max + 1;
    $remitoSiguiente = 'REM-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
} catch (Throwable $e) {
    $remitoSiguiente = 'REM-000001';
}
// Calcular próximo número de compra
try {
    $maxC = $pdo->query("SELECT MAX(CAST(SUBSTRING(codigo, 6) AS UNSIGNED)) FROM compras WHERE codigo LIKE 'COMP-%'")->fetchColumn();
    $nextC = (int)$maxC + 1;
    $compraSiguiente = 'COMP-' . str_pad((string)$nextC, 6, '0', STR_PAD_LEFT);
} catch (Throwable $e) {
    $compraSiguiente = 'COMP-000001';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $file = $_FILES['archivo'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $file['tmp_name'];
        try {
            $ocr = new OCRProcessor();
            $textoExtraido = $ocr->extractText($tmpPath);
            // Forzar UTF-8
            $textoExtraido = mb_convert_encoding($textoExtraido, 'UTF-8', 'auto');
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'Error al subir el archivo';
    }
}

// Confirmar y dar de alta compra desde remito
if (isset($_POST['confirmar_remito']) && isset($_POST['texto_remito'])) {
    $textoRemito = trim($_POST['texto_remito']);
    // Aquí deberías parsear productos y proveedor, pero para demo solo crea la compra y el remito
    try {
        $pdo->beginTransaction();
        // Alta de remito
        $stmtR = $pdo->prepare("INSERT INTO remitos (codigo, numero_remito_proveedor, estado, observaciones) VALUES (?, ?, ?, ?)");
        $stmtR->execute([$remitoSiguiente, 'OCR', 'confirmado', 'Alta desde OCR mejorado']);
        $remitoId = $pdo->lastInsertId();
        // Alta de compra vinculada
        $stmtC = $pdo->prepare("INSERT INTO compras (codigo, remito_id, observaciones) VALUES (?, ?, ?)");
        $stmtC->execute([$compraSiguiente, $remitoId, 'Compra generada desde remito ' . $remitoSiguiente]);
        $pdo->commit();
        $successMsg = "Compra $compraSiguiente generada desde el remito $remitoSiguiente.\n\n(En demo: no se parsean productos ni se actualiza stock)";
        $textoExtraido = '';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lectura OCR Mejorada</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }
    .ocr-box { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px #0001; padding: 2.2rem 2.5rem; max-width: 900px; min-width: 340px; }
        textarea { font-family: 'Fira Mono', monospace; font-size: 1rem; min-height: 220px; }
        .ocr-nav-btns { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
    </style>
    <script>
    // Cierra el modal desde el iframe
    function cerrarModalOCR() {
        if (window.parent && window.parent.$) {
            window.parent.$('#ocrModal').modal('hide');
        } else if (window.parent && window.parent.closeOCRModal) {
            window.parent.closeOCRModal();
        } else {
            window.close();
        }
    }
    // Redirige el padre al listado de remitos y resalta el nuevo
    function irAListadoRemitos(remito) {
        if (window.parent && window.parent.location) {
            window.parent.location.href = '/sistemadgestion5/remitos.php?highlight=' + encodeURIComponent(remito);
        }
    }
    // Recarga el iframe para cargar un nuevo remito
    function cargarNuevoRemito() {
        if (window.location) window.location.reload();
    }
    </script>
</head>
<body style="background: transparent<?= (isset($_GET['fondo']) && $_GET['fondo']==='transparente') ? ';box-shadow:none' : '' ?>;">
<div class="container py-5" style="background: transparent<?= (isset($_GET['fondo']) && $_GET['fondo']==='transparente') ? '' : '' ?>;">
    <div class="ocr-box mx-auto" style="box-shadow: 0 2px 12px #0001; background: #fff;<?= (isset($_GET['fondo']) && $_GET['fondo']==='transparente') ? 'box-shadow:none;background:rgba(255,255,255,0.97);' : '' ?>">
        <div class="ocr-nav-btns">
            <button type="button" class="btn btn-outline-secondary" onclick="cerrarModalOCR()">Volver</button>
            <button type="button" class="btn btn-outline-primary" onclick="cargarNuevoRemito()">Cargar nuevo remito</button>
        </div>
        <h2 class="mb-3">Lectura OCR Mejorada</h2>
        <p class="text-muted">Prueba rápida de OCR, alta de remito y compra vinculada.</p>
        <div class="mb-3">
            <strong>Próximo remito:</strong> <span class="badge bg-primary"><?= htmlspecialchars($remitoSiguiente) ?></span>
            <strong class="ms-3">Próxima compra:</strong> <span class="badge bg-success"><?= htmlspecialchars($compraSiguiente) ?></span>
        </div>
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success"> <?= nl2br(htmlspecialchars($successMsg)) ?> </div>
            <script>
                // Al confirmar, cerrar modal y redirigir padre al listado de remitos resaltando el nuevo
                setTimeout(function() {
                    irAListadoRemitos('<?= htmlspecialchars($remitoSiguiente) ?>');
                    cerrarModalOCR();
                }, 1200);
            </script>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"> <?= htmlspecialchars($error) ?> </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="mb-3">
            <div class="input-group mb-2">
                <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png,.tiff,.tif" required class="form-control">
                <button type="submit" class="btn btn-primary">Extraer Texto</button>
            </div>
        </form>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Texto extraído (editable):</label>
                <textarea name="texto_remito" class="form-control" id="texto_remito" spellcheck="false" placeholder="Aquí aparecerá el texto extraído..." required><?= htmlspecialchars($textoExtraido) ?></textarea>
            </div>
            <div class="mb-3 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('texto_remito').value=''">Limpiar</button>
                <button type="button" class="btn btn-outline-info" onclick="navigator.clipboard.writeText(document.getElementById('texto_remito').value)">Copiar</button>
                <button type="submit" name="confirmar_remito" class="btn btn-success ms-auto">Confirmar y Generar Compra</button>
            </div>
            <div class="form-text">Al confirmar, se dará de alta el remito <b><?= htmlspecialchars($remitoSiguiente) ?></b> y la compra <b><?= htmlspecialchars($compraSiguiente) ?></b> vinculada.<br>Esta compra se generó desde el remito <b><?= htmlspecialchars($remitoSiguiente) ?></b>.</div>
        </form>
    </div>
</div>
</body>
</html>

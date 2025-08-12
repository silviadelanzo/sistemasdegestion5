
<?php
// ocr_confirmacion_tabla.php: Interfaz avanzada para revisión y confirmación de productos detectados por OCR
require_once '../../../config/config.php';
require_once 'ocr_processor.php';
session_start();
if (!isset($_SESSION['id_usuario']) && !isset($_SESSION['usuario_id'])) {
    header('Location: ../../../login.php');
    exit;
}

$pdo = conectarDB();
$textoExtraido = '';
$error = '';
$productosDetectados = [];
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

// Simulación de parser: extrae productos de texto plano (mejorar con tu parser real)
function parsearProductos($texto) {
    $lineas = preg_split('/\r?\n/', $texto);
    $productos = [];
    foreach ($lineas as $l) {
        if (preg_match('/^([A-Z0-9]{3,})\s+(.+?)\s+(\d+)\s+\$([0-9.,]+)/u', $l, $m)) {
            $productos[] = [
                'codigo' => $m[1],
                'descripcion' => $m[2],
                'cantidad' => (int)$m[3],
                'precio' => (float)str_replace([','], ['.'], $m[4]),
                'unidad' => 'unidad',
                'nuevo' => false,
                'no_entregado' => false,
            ];
        }
    }
    return $productos;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $file = $_FILES['archivo'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $file['tmp_name'];
        try {
            $ocr = new OCRProcessor();
            $textoExtraido = $ocr->extractText($tmpPath);
            $textoExtraido = mb_convert_encoding($textoExtraido, 'UTF-8', 'auto');
            $productosDetectados = parsearProductos($textoExtraido);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'Error al subir el archivo';
    }
}


if (isset($_POST['confirmar']) && isset($_POST['productos'])) {
    $productos = json_decode($_POST['productos'], true);
    // Registrar solo el remito como pendiente, sin crear compra ni actualizar stock
    try {
        $pdo->beginTransaction();
        $usuarioId = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : (isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 1);
        $stmtR = $pdo->prepare("INSERT INTO remitos (codigo, estado, observaciones, usuario_id) VALUES (?, ?, ?, ?)");
        $stmtR->execute([$remitoSiguiente, 'pendiente', 'Alta desde OCR, pendiente de confirmación', $usuarioId]);
        $remitoId = $pdo->lastInsertId();
        // Guardar productos en tabla remito_detalles (si existe)
        if ($remitoId && $productos) {
            foreach ($productos as $p) {
                if (!isset($p['no_entregado']) || !$p['no_entregado']) {
                    $stmtD = $pdo->prepare("INSERT INTO remito_detalles (remito_id, producto_id, cantidad, codigo_producto_proveedor, observaciones) VALUES (?, NULL, ?, ?, ?)");
                    $stmtD->execute([$remitoId, $p['cantidad_final'], $p['codigo'], $p['nuevo'] ? 'Producto nuevo' : '']);
                }
            }
        }
        $pdo->commit();
        $successMsg = "Remito $remitoSiguiente registrado como pendiente.\nLa compra y el ingreso a stock se confirman desde el listado de remitos.";
        $productosDetectados = [];
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
    <title>Confirmación de Productos OCR</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }
        .ocr-box { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px #0001; padding: 2rem; }
        .tabla-scroll { max-height: 400px; overflow-y: auto; }
        textarea { font-family: 'Fira Mono', monospace; font-size: 1rem; min-height: 120px; }
        th, td { vertical-align: middle !important; }
    </style>
</head>
<body>
<?php include '../../../config/navbar_code.php'; ?>
<div class="container py-5">
    <div class="ocr-box mx-auto" style="max-width:900px;">
        <h2 class="mb-3">Confirmación de Productos OCR</h2>
        <div class="mb-3">
            <strong>Próximo remito:</strong> <span class="badge bg-primary"><?= htmlspecialchars($remitoSiguiente) ?></span>
            <strong class="ms-3">Próxima compra:</strong> <span class="badge bg-success"><?= htmlspecialchars($compraSiguiente) ?></span>
        </div>
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success"> <?= nl2br(htmlspecialchars($successMsg)) ?> </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"> <?= htmlspecialchars($error) ?> </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="mb-3">
            <div class="input-group mb-2">
                <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png,.tiff,.tif" required class="form-control">
                <button type="submit" class="btn btn-primary">Extraer y Detectar Productos</button>
            </div>
        </form>
        <?php if ($productosDetectados): ?>
        <form method="post" onsubmit="return prepararConfirmacion()">
            <div class="tabla-scroll mb-3">
                <table class="table table-bordered table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th style="width:110px">Cantidad</th>
                            <th style="width:120px">Unidad</th>
                            <th style="width:120px">Conversión</th>
                            <th>Nuevo</th>
                            <th>No Entregado</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-productos">
                    <?php foreach ($productosDetectados as $i => $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['codigo']) ?></td>
                            <td><?= htmlspecialchars($p['descripcion']) ?></td>
                            <td><input type="number" class="form-control form-control-sm" min="0" value="<?= $p['cantidad'] ?>" onchange="actualizarCantidad(<?= $i ?>, this.value)"></td>
                            <td>
                                <select class="form-select form-select-sm" onchange="actualizarUnidad(<?= $i ?>, this.value)">
                                    <option value="unidad"<?= $p['unidad']==='unidad'?' selected':'' ?>>Unidad</option>
                                    <option value="caja">Caja x12</option>
                                    <option value="bolsa">Bolsa x50</option>
                                </select>
                            </td>
                            <td><span id="conv-<?= $i ?>">1</span></td>
                            <td class="text-center"><input type="checkbox" onchange="actualizarNuevo(<?= $i ?>, this.checked)" <?= !empty($p['nuevo']) ? 'checked' : '' ?>></td>
                            <td class="text-center"><input type="checkbox" onchange="actualizarNoEntregado(<?= $i ?>, this.checked)" id="noentregado-<?= $i ?>" <?= !empty($p['no_entregado']) ? 'checked' : '' ?>></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <input type="hidden" name="productos" id="productos-json">
            <button type="submit" name="confirmar" class="btn btn-success">Confirmar Remito Pendiente</button>
        </form>
        <script>
            let productos = <?= json_encode($productosDetectados) ?>;
            const conversiones = { unidad: 1, caja: 12, bolsa: 50 };
            function actualizarCantidad(i, val) {
                productos[i].cantidad = parseInt(val)||0;
                if (productos[i].cantidad > 0) {
                    productos[i].no_entregado = false;
                    document.getElementById('noentregado-'+i).checked = false;
                }
                actualizarConversion(i);
            }
            function actualizarUnidad(i, val) { productos[i].unidad = val; actualizarConversion(i); }
            function actualizarNuevo(i, val) { productos[i].nuevo = !!val; }
            function actualizarNoEntregado(i, val) {
                productos[i].no_entregado = !!val;
                if (val) {
                    productos[i].cantidad = 0;
                    actualizarConversion(i);
                    // Actualizar input cantidad a 0
                    let inputs = document.querySelectorAll('input[type=number]');
                    if (inputs[i]) inputs[i].value = 0;
                }
            }
            function actualizarConversion(i) {
                let conv = conversiones[productos[i].unidad] || 1;
                document.getElementById('conv-'+i).textContent = conv;
            }
            function prepararConfirmacion() {
                // Convertir cantidades a unidades base
                productos.forEach((p,i) => {
                    let conv = conversiones[p.unidad]||1;
                    p.cantidad_final = p.cantidad * conv;
                });
                document.getElementById('productos-json').value = JSON.stringify(productos);
                return true;
            }
            // Inicializar conversiones
            productos.forEach((p,i)=>{
                if (typeof p.nuevo === 'undefined') p.nuevo = false;
                if (typeof p.no_entregado === 'undefined') p.no_entregado = false;
                actualizarConversion(i);
            });
        </script>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

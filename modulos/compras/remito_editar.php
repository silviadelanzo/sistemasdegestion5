<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();

// Obtener ID del remito a editar
$remito_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$remito_id) {
    echo '<div class="alert alert-danger">ID de remito no especificado</div>';
    exit;
}

// Obtener datos actuales del remito
$sql = "SELECT * FROM remitos WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$remito_id]);
$remito = $stmt->fetch();
if (!$remito) {
    echo '<div class="alert alert-danger">Remito no encontrado</div>';
    exit;
}

// Obtener proveedores
$proveedores = $pdo->query("SELECT id, COALESCE(razon_social, nombre_comercial, 'Proveedor') as nombre FROM proveedores WHERE eliminado=0 AND activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
// Obtener productos del remito
$sql_detalles = "SELECT rd.*, p.nombre as producto_nombre, p.codigo as producto_codigo, p.descripcion as producto_descripcion, c.nombre as categoria_nombre FROM remito_detalles rd LEFT JOIN productos p ON rd.producto_id = p.id LEFT JOIN categorias c ON p.categoria_id = c.id WHERE rd.remito_id = ? ORDER BY p.nombre";
$stmt = $pdo->prepare($sql_detalles);
$stmt->execute([$remito_id]);
$detalles = $stmt->fetchAll();
// Obtener todos los productos para el selector
$productos = $pdo->query("SELECT id, nombre, codigo FROM productos WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Definir estados válidos como en remitos.php
$estados = [
    'pendiente' => 'Pendiente',
    'confirmado' => 'Confirmado',
    'recibido' => 'Recibido',
    'cancelado' => 'Cancelado'
];

// Procesar formulario (actualización de proveedor y productos)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proveedor_id = (int)($_POST['proveedor_id'] ?? 0);
    $numero_remito_proveedor = trim($_POST['numero_remito_proveedor'] ?? '');
    $fecha_entrega = trim($_POST['fecha_entrega'] ?? '');
    $estado = trim($_POST['estado'] ?? 'pendiente');
    $observaciones = trim($_POST['observaciones'] ?? '');
    // Actualizar remito
    $sql_update = "UPDATE remitos SET proveedor_id=?, numero_remito_proveedor=?, fecha_entrega=?, estado=?, observaciones=? WHERE id=?";
    $stmt = $pdo->prepare($sql_update);
    $stmt->execute([$proveedor_id, $numero_remito_proveedor, $fecha_entrega, $estado, $observaciones, $remito_id]);
    // Actualizar productos
    if (isset($_POST['producto_id']) && is_array($_POST['producto_id'])) {
        foreach ($_POST['producto_id'] as $i => $prod_id) {
            $detalle_id = (int)$_POST['detalle_id'][$i];
            $cantidad = (float)$_POST['cantidad'][$i];
            $obs = trim($_POST['detalle_observaciones'][$i]);
            $sql_upd_det = "UPDATE remito_detalles SET producto_id=?, cantidad=?, observaciones=? WHERE id=? AND remito_id=?";
            $stmt = $pdo->prepare($sql_upd_det);
            $stmt->execute([(int)$prod_id, $cantidad, $obs, $detalle_id, $remito_id]);
        }
    }
    echo '<div class="alert alert-success">Remito actualizado correctamente.</div>';
    // Recargar datos
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$remito_id]);
    $remito = $stmt->fetch();
    $stmt = $pdo->prepare($sql_detalles);
    $stmt->execute([$remito_id]);
    $detalles = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Remito</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .main-container { margin: 0 auto; max-width: 1000px; }
        .table-container { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-top: 30px; }
        .table th, .table td { vertical-align: middle; }
        .pagination-container { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-top: 30px; padding: 15px 0; }
    </style>
</head>
<body>
<div class="main-container">
    <h2 class="mb-4">Editar Remito</h2>
    <form method="post">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Proveedor</label>
                <select name="proveedor_id" class="form-select" required>
                    <option value="">Seleccione proveedor...</option>
                    <?php foreach ($proveedores as $prov): ?>
                        <option value="<?= $prov['id'] ?>" <?= $remito['proveedor_id'] == $prov['id'] ? 'selected' : '' ?>><?= htmlspecialchars($prov['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Nro. Remito Proveedor</label>
                <input type="text" name="numero_remito_proveedor" class="form-control" value="<?= htmlspecialchars($remito['numero_remito_proveedor']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha de Entrega</label>
                <input type="date" name="fecha_entrega" class="form-control" value="<?= htmlspecialchars(substr($remito['fecha_entrega'],0,10)) ?>">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select" required>
                    <?php foreach ($estados as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $remito['estado'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-9">
                <label class="form-label">Observaciones</label>
                <input type="text" name="observaciones" class="form-control" value="<?= htmlspecialchars($remito['observaciones']) ?>">
            </div>
        </div>
        <h5 class="mt-4 mb-2">Productos del Remito</h5>
        <div class="table-container p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $productos_por_pagina = 20;
                    $pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
                    $total_productos = count($detalles);
                    $total_paginas = ceil($total_productos / $productos_por_pagina);
                    $offset = ($pagina - 1) * $productos_por_pagina;
                    foreach (array_slice($detalles, $offset, $productos_por_pagina) as $i => $detalle): ?>
                        <tr>
                            <td>
                                <input type="hidden" name="detalle_id[]" value="<?= $detalle['id'] ?>">
                                <select name="producto_id[]" class="form-select" required>
                                    <?php foreach ($productos as $prod): ?>
                                        <option value="<?= $prod['id'] ?>" <?= $detalle['producto_id'] == $prod['id'] ? 'selected' : '' ?>><?= htmlspecialchars($prod['nombre']) ?> (<?= htmlspecialchars($prod['codigo']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="cantidad[]" class="form-control" value="<?= htmlspecialchars($detalle['cantidad']) ?>">
                            </td>
                            <td>
                                <input type="text" name="detalle_observaciones[]" class="form-control" value="<?= htmlspecialchars($detalle['observaciones']) ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_paginas > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Navegación de productos">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($pagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?id=<?= $remito_id ?>&pagina=1">&laquo;</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?id=<?= $remito_id ?>&pagina=<?= $pagina - 1 ?>">&lsaquo;</a>
                                </li>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                    <a class="page-link" href="?id=<?= $remito_id ?>&pagina=<?= $i ?>"> <?= $i ?> </a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($pagina < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?id=<?= $remito_id ?>&pagina=<?= $pagina + 1 ?>">&rsaquo;</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?id=<?= $remito_id ?>&pagina=<?= $total_paginas ?>">&raquo;</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <div class="text-center mt-2">
                        <small class="text-muted">
                            Mostrando <?= min($productos_por_pagina * ($pagina - 1) + 1, $total_productos) ?> - <?= min($productos_por_pagina * $pagina, $total_productos) ?> de <?= number_format($total_productos) ?> productos
                        </small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Guardar Cambios</button>
        <a href="remitos.php" class="btn btn-secondary mt-3">Cancelar</a>
    </form>
</div>
</body>
</html>

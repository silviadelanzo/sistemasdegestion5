<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="alert alert-danger">ID de remito no especificado</div>';
    exit;
}

$pdo = conectarDB();
$remito_id = (int)$_GET['id'];

// Obtener datos del remito
$sql_remito = "SELECT r.*, p.razon_social as proveedor_nombre, p.id as proveedor_id, p.telefono, p.email FROM remitos r LEFT JOIN proveedores p ON r.proveedor_id = p.id WHERE r.id = ?";
$stmt = $pdo->prepare($sql_remito);
$stmt->execute([$remito_id]);
$remito = $stmt->fetch();

if (!$remito) {
    echo '<div class="alert alert-danger">Remito no encontrado</div>';
    exit;
}
if ($remito['estado'] !== 'pendiente') {
    echo '<div class="alert alert-warning">Solo se pueden editar remitos en estado pendiente.</div>';
    exit;
}

// Obtener productos del remito
$sql_detalles = "SELECT rd.*, p.nombre as producto_nombre, p.codigo as producto_codigo FROM remito_detalles rd LEFT JOIN productos p ON rd.producto_id = p.id WHERE rd.remito_id = ? ORDER BY p.nombre";
$stmt = $pdo->prepare($sql_detalles);
$stmt->execute([$remito_id]);
$detalles = $stmt->fetchAll();

// Obtener lista de proveedores para select
$proveedores = $pdo->query("SELECT id, razon_social FROM proveedores ORDER BY razon_social")->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Remito <?= htmlspecialchars($remito['codigo']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-4">Editar Remito <span class="text-primary"><?= htmlspecialchars($remito['codigo']) ?></span></h2>
    <form method="post" action="guardar_remito_editado.php">
        <input type="hidden" name="remito_id" value="<?= $remito_id ?>">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Proveedor</label>
                <select name="proveedor_id" class="form-select" required>
                    <?php foreach ($proveedores as $prov): ?>
                        <option value="<?= $prov['id'] ?>" <?= $remito['proveedor_id'] == $prov['id'] ? 'selected' : '' ?>><?= htmlspecialchars($prov['razon_social']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha de Entrega</label>
                <input type="date" name="fecha_entrega" class="form-control" value="<?= htmlspecialchars($remito['fecha_entrega']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Nro. Remito Proveedor</label>
                <input type="text" name="numero_remito_proveedor" class="form-control" value="<?= htmlspecialchars($remito['numero_remito_proveedor']) ?>">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Observaciones</label>
            <textarea name="observaciones" class="form-control" rows="2"><?= htmlspecialchars($remito['observaciones']) ?></textarea>
        </div>
        <h5 class="mt-4">Productos del Remito</h5>
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Observaciones</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($detalles as $detalle): ?>
                <tr>
                    <td><code><?= htmlspecialchars($detalle['producto_codigo']) ?></code></td>
                    <td><?= htmlspecialchars($detalle['producto_nombre']) ?></td>
                    <td><input type="number" step="0.01" min="0" name="cantidades[<?= $detalle['id'] ?>]" value="<?= $detalle['cantidad'] ?>" class="form-control" required></td>
                    <td><input type="text" name="obs[<?= $detalle['id'] ?>]" value="<?= htmlspecialchars($detalle['observaciones']) ?>" class="form-control"></td>
                    <td><a href="eliminar_producto_remito.php?detalle_id=<?= $detalle['id'] ?>&remito_id=<?= $remito_id ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este producto del remito?')"><i class="fas fa-trash"></i></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="mb-3">
            <a href="agregar_producto_remito.php?remito_id=<?= $remito_id ?>" class="btn btn-outline-primary btn-sm">Agregar Producto</a>
        </div>
        <div class="mt-4">
            <button type="submit" class="btn btn-success">Guardar Cambios</button>
            <a href="remitos.php" class="btn btn-secondary ms-2">Cancelar</a>
        </div>
    </form>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>

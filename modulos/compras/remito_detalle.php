<?php
require_once '../../config/config.php';

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">ID de remito no especificado</div>';
    exit;
}

$pdo = conectarDB();
$remito_id = (int)$_GET['id'];

// Obtener datos del remito
$sql_remito = "SELECT 
    r.*,
    p.razon_social as proveedor_nombre,
    p.telefono as proveedor_telefono,
    p.email as proveedor_email,
    u.nombre as usuario_nombre
FROM remitos r
LEFT JOIN proveedores p ON r.proveedor_id = p.id
LEFT JOIN usuarios u ON r.usuario_id = u.id
WHERE r.id = ?";

$stmt = $pdo->prepare($sql_remito);
$stmt->execute([$remito_id]);
$remito = $stmt->fetch();

if (!$remito) {
    echo '<div class="alert alert-danger">Remito no encontrado</div>';
    exit;
}

// Obtener detalles de productos

$sql_detalles = "SELECT 
    rd.*,
    p.nombre as producto_nombre,
    p.codigo as producto_codigo,
    p.descripcion as producto_descripcion,
    c.nombre as categoria_nombre
FROM remito_detalles rd
LEFT JOIN productos p ON rd.producto_id = p.id
LEFT JOIN categorias c ON p.categoria_id = c.id
WHERE rd.remito_id = ?
ORDER BY producto_nombre";

$stmt = $pdo->prepare($sql_detalles);
$stmt->execute([$remito_id]);
$detalles = $stmt->fetchAll();
?>



<style>
body {
    background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
    min-height: 100vh;
}
.remito-modal-bg {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.25);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.remito-modal {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    max-width: 900px;
    width: 98%;
    padding: 2.5rem 2rem 2rem 2rem;
    position: relative;
    animation: fadeIn .3s;
}
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.98); }
    to { opacity: 1; transform: scale(1); }
}
.remito-close {
    position: absolute;
    top: 18px;
    right: 22px;
    font-size: 1.7rem;
    color: #888;
    background: none;
    border: none;
    cursor: pointer;
    z-index: 10;
    transition: color 0.2s;
}
.remito-close:hover { color: #dc3545; }
</style>

<div class="remito-modal-bg">
  <div class="remito-modal">
    <button class="remito-close" onclick="window.location.href='/sistemadgestion5/modulos/compras/remitos.php'" title="Cerrar">&times;</button>
    <div class="row">
        <div class="col-md-6 mb-3">
            <h5 class="text-primary"><i class="fas fa-file-alt me-2"></i>Información del Remito</h5>
            <table class="table table-bordered table-sm">
                <tr><th>Código:</th><td><?= htmlspecialchars($remito['codigo']) ?></td></tr>
                <tr><th>Nro. Remito Proveedor:</th><td><?= htmlspecialchars($remito['numero_remito_proveedor'] ?: 'No especificado') ?></td></tr>
                <tr><th>Fecha de Entrega:</th><td><?= date('d/m/Y', strtotime($remito['fecha_entrega'])) ?></td></tr>
                <tr><th>Estado:</th>
                    <td>
                        <span class="badge <?= $badge_class[$remito['estado']] ?? 'bg-secondary' ?>">
                            <?= ucfirst($remito['estado']) ?>
                        </span>
                    </td>
                </tr>
                <tr><th>Creado:</th><td><?= date('d/m/Y H:i', strtotime($remito['fecha_creacion'])) ?></td></tr>
                <tr><th>Usuario:</th><td><?= htmlspecialchars($remito['usuario_nombre']) ?></td></tr>
            </table>
        </div>
        <div class="col-md-6 mb-3">
            <h5 class="text-primary"><i class="fas fa-truck me-2"></i>Información del Proveedor</h5>
            <table class="table table-bordered table-sm">
                <tr><th>Nombre:</th><td><?= htmlspecialchars($remito['proveedor_nombre']) ?></td></tr>
                <tr><th>Teléfono:</th><td><?= htmlspecialchars($remito['proveedor_telefono'] ?: 'No especificado') ?></td></tr>
                <tr><th>Email:</th><td><?= htmlspecialchars($remito['proveedor_email'] ?: 'No especificado') ?></td></tr>
            </table>
        </div>
    </div>

    <?php if (!empty($remito['observaciones'])): ?>
    <div class="row mb-3">
        <div class="col-12">
            <h6 class="text-primary"><i class="fas fa-comment me-2"></i>Observaciones</h6>
            <div class="alert alert-light"><?= nl2br(htmlspecialchars($remito['observaciones'])) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <h5 class="text-primary"><i class="fas fa-boxes me-2"></i>Productos del Remito</h5>
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="text-center">Cantidad</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $total_cantidad = 0; foreach ($detalles as $detalle): $total_cantidad += $detalle['cantidad']; ?>
                        <tr>
                            <td><code><?= htmlspecialchars($detalle['producto_codigo']) ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($detalle['producto_nombre']) ?></strong>
                                <?php if (!empty($detalle['producto_descripcion'])): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($detalle['producto_descripcion']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark"><?= htmlspecialchars($detalle['categoria_nombre'] ?: 'Sin categoría') ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary"><?= number_format($detalle['cantidad'], 2) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($detalle['observaciones'])): ?>
                                    <small><?= htmlspecialchars($detalle['observaciones']) ?></small>
                                <?php else: ?>
                                    <small class="text-muted">Sin observaciones</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Total:</th>
                            <th class="text-center">
                                <span class="badge bg-success"><?= number_format($total_cantidad, 2) ?> unidades</span>
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div style="display: flex; justify-content: center; align-items: center; width: 100%; gap: 1rem;">
                <button class="btn btn-secondary px-4 py-2" style="font-size:1.15rem; font-weight:500;" onclick="imprimirRemito(<?= $remito['id'] ?>)">
                    <i class="fas fa-print me-1"></i>Imprimir Remito
                </button>
                <?php if ($remito['estado'] === 'borrador' || $remito['estado'] === 'pendiente'): ?>
                    <a href="remito_editar.php?id=<?= $remito['id'] ?>" class="btn btn-warning px-3 py-2" style="font-size:1rem;">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php endif; ?>
                <?php if ($remito['estado'] === 'confirmado'): ?>
                    <button class="btn btn-info px-3 py-2" style="font-size:1rem;" onclick="marcarRecibido(<?= $remito['id'] ?>)">
                        <i class="fas fa-check me-1"></i>Marcar como Recibido
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
  </div>
</div>



<script>
function imprimirRemito(id) {
    window.open('remito_imprimir.php?id=' + id, '_blank');
}

function marcarRecibido(id) {
    if (confirm('¿Confirmar que el remito ha sido recibido?')) {
        fetch('cambiar_estado_remito.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id + '&estado=recibido'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>

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
    p.nombre as proveedor_nombre,
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
ORDER BY p.nombre";

$stmt = $pdo->prepare($sql_detalles);
$stmt->execute([$remito_id]);
$detalles = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-primary mb-3">
            <i class="fas fa-file-alt me-2"></i>Información del Remito
        </h6>
        <table class="table table-sm">
            <tr>
                <td class="fw-bold">Código:</td>
                <td><?= htmlspecialchars($remito['codigo']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Nro. Remito Proveedor:</td>
                <td><?= htmlspecialchars($remito['numero_remito_proveedor'] ?: 'No especificado') ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Fecha de Entrega:</td>
                <td><?= date('d/m/Y', strtotime($remito['fecha_entrega'])) ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Estado:</td>
                <td>
                    <?php
                    $badge_class = [
                        'borrador' => 'bg-warning',
                        'confirmado' => 'bg-success',
                        'recibido' => 'bg-info'
                    ];
                    ?>
                    <span class="badge <?= $badge_class[$remito['estado']] ?? 'bg-secondary' ?>">
                        <?= ucfirst($remito['estado']) ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td class="fw-bold">Creado:</td>
                <td><?= date('d/m/Y H:i', strtotime($remito['fecha_creacion'])) ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Usuario:</td>
                <td><?= htmlspecialchars($remito['usuario_nombre']) ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6 class="text-primary mb-3">
            <i class="fas fa-truck me-2"></i>Información del Proveedor
        </h6>
        <table class="table table-sm">
            <tr>
                <td class="fw-bold">Nombre:</td>
                <td><?= htmlspecialchars($remito['proveedor_nombre']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Teléfono:</td>
                <td><?= htmlspecialchars($remito['proveedor_telefono'] ?: 'No especificado') ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Email:</td>
                <td><?= htmlspecialchars($remito['proveedor_email'] ?: 'No especificado') ?></td>
            </tr>
        </table>
    </div>
</div>

<?php if (!empty($remito['observaciones'])): ?>
<div class="row mt-3">
    <div class="col-12">
        <h6 class="text-primary mb-2">
            <i class="fas fa-comment me-2"></i>Observaciones
        </h6>
        <div class="alert alert-light">
            <?= nl2br(htmlspecialchars($remito['observaciones'])) ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-12">
        <h6 class="text-primary mb-3">
            <i class="fas fa-boxes me-2"></i>Productos del Remito
        </h6>
        
        <?php if (empty($detalles)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No hay productos en este remito.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
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
                        <?php 
                        $total_cantidad = 0;
                        foreach ($detalles as $detalle): 
                            $total_cantidad += $detalle['cantidad'];
                        ?>
                            <tr>
                                <td>
                                    <code><?= htmlspecialchars($detalle['producto_codigo']) ?></code>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($detalle['producto_nombre']) ?></strong>
                                    <?php if (!empty($detalle['producto_descripcion'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($detalle['producto_descripcion']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($detalle['categoria_nombre'] ?: 'Sin categoría') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary">
                                        <?= number_format($detalle['cantidad'], 2) ?>
                                    </span>
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
                                <span class="badge bg-success">
                                    <?= number_format($total_cantidad, 2) ?> unidades
                                </span>
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12 text-center">
        <button class="btn btn-success me-2" onclick="imprimirRemito(<?= $remito['id'] ?>)">
            <i class="fas fa-print me-1"></i>Imprimir Remito
        </button>
        <?php if ($remito['estado'] === 'borrador'): ?>
            <a href="compras_form.php?editar=<?= $remito['id'] ?>" class="btn btn-warning me-2">
                <i class="fas fa-edit me-1"></i>Editar
            </a>
        <?php endif; ?>
        <?php if ($remito['estado'] === 'confirmado'): ?>
            <button class="btn btn-info" onclick="marcarRecibido(<?= $remito['id'] ?>)">
                <i class="fas fa-check me-1"></i>Marcar como Recibido
            </button>
        <?php endif; ?>
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

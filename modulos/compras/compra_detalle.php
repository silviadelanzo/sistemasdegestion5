<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();
$id = $_GET['id'] ?? 0;

if ($id <= 0) {
    header("Location: compras.php");
    exit;
}

// Obtener datos de la compra
$stmt = $pdo->prepare("
    SELECT c.*, p.razon_social as proveedor_nombre 
    FROM compras c 
    LEFT JOIN proveedores p ON c.proveedor_id = p.id 
    WHERE c.id = ?
");
$stmt->execute([$id]);
$compra = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$compra) {
    header("Location: compras.php");
    exit;
}

// Obtener detalles de la compra (productos)
$stmt_detalles = $pdo->prepare("
    SELECT 
        cd.cantidad_pedida, 
        cd.cantidad_recibida, 
        cd.precio_unitario, 
        p.nombre as producto_nombre,
        p.codigo as producto_codigo
    FROM compra_detalles cd
    LEFT JOIN productos p ON cd.producto_id = p.id
    WHERE cd.compra_id = ?
");
$stmt_detalles->execute([$id]);
$detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Compra #<?php echo htmlspecialchars($compra['codigo'] ?? ''); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-eye"></i> Detalle de Compra #<?php echo htmlspecialchars($compra['codigo'] ?? ''); ?></h1>
                    <div>
                        <a href="compra_form.php?id=<?php echo htmlspecialchars($compra['id'] ?? ''); ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="compras.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-info-circle"></i> Información General</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Código:</strong></td>
                                        <td><?php echo htmlspecialchars($compra['codigo'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Proveedor:</strong></td>
                                        <td><?php echo htmlspecialchars($compra['proveedor_nombre'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Fecha:</strong></td>
                                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($compra['fecha_compra']))); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Estado:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($compra['estado']) {
                                                    'pendiente' => 'warning',
                                                    'confirmada' => 'info',
                                                    'parcial' => 'primary',
                                                    'recibida' => 'success',
                                                    'cancelada' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo htmlspecialchars(ucfirst($compra['estado'] ?? '')); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total:</strong></td>
                                        <td><strong>$<?php echo htmlspecialchars(number_format($compra['total'], 2)); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-calendar"></i> Fechas</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Entrega Estimada:</strong></td>
                                        <td><?php echo htmlspecialchars($compra['fecha_entrega_estimada'] ? date('d/m/Y', strtotime($compra['fecha_entrega_estimada'])) : 'No definida'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Entrega Real:</strong></td>
                                        <td><?php echo htmlspecialchars($compra['fecha_entrega_real'] ? date('d/m/Y', strtotime($compra['fecha_entrega_real'])) : 'Pendiente'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Creación:</strong></td>
                                        <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($compra['fecha_creacion']))); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-box"></i> Productos</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cantidad Pedida</th>
                                        <th>Cantidad Recibida</th>
                                        <th>Precio Unitario</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($detalles)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No hay productos en esta compra.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($detalles as $detalle): ?>
                                            <tr>
                                                <td>
                                                    <?= htmlspecialchars($detalle['producto_nombre']) ?><br>
                                                    <small class="text-muted"><?= htmlspecialchars($detalle['producto_codigo']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($detalle['cantidad_pedida']) ?></td>
                                                <td><?= htmlspecialchars($detalle['cantidad_recibida'] ?? '0') ?></td>
                                                <td>$<?= htmlspecialchars(number_format($detalle['precio_unitario'], 2)) ?></td>
                                                <td>$<?= htmlspecialchars(number_format($detalle['cantidad_pedida'] * $detalle['precio_unitario'], 2)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <?php if ($compra['observaciones']): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-comment"></i> Observaciones</h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($compra['observaciones'] ?? '')); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
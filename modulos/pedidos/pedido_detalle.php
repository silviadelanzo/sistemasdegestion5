<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
// iniciarSesionSegura();
// requireLogin('../../login.php'); // Ajustar la ruta si es necesario
header('Content-Type: text/html; charset=UTF-8');
require_once '../../config/config.php'; // Ajusta la ruta si es necesario

// Verificar si se recibió un ID de pedido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: pedidos.php"); // Redirigir si no hay ID válido
    exit;
}

$pedido_id = $_GET['id'];
$pedido = null;
$cliente = null;
$detalles = [];
$historial = [];
$error_message = '';

try {
    $pdo = conectarDB();

    // Obtener detalles del pedido y del cliente
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.empresa as cliente_empresa,
            c.email as cliente_email, c.telefono as cliente_telefono, c.direccion as cliente_direccion,
            c.ciudad as cliente_ciudad, c.provincia as cliente_provincia, c.pais as cliente_pais
        FROM pedidos p
        JOIN clientes c ON p.cliente_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        $error_message = "Pedido no encontrado.";
    } else {
        // Obtener detalles de los productos del pedido
        $stmt_detalles = $pdo->prepare("
            SELECT 
                pd.*, 
                prod.codigo as producto_codigo, prod.nombre as producto_nombre
            FROM pedido_detalles pd
            JOIN productos prod ON pd.producto_id = prod.id
            WHERE pd.pedido_id = ?
        ");
        $stmt_detalles->execute([$pedido_id]);
        $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

        // Obtener historial del pedido
        $stmt_historial = $pdo->prepare("
            SELECT 
                ph.*, 
                u.nombre as usuario_nombre
            FROM pedido_historial ph
            JOIN usuarios u ON ph.usuario_id = u.id
            WHERE ph.pedido_id = ?
            ORDER BY ph.fecha_cambio DESC
        ");
        $stmt_historial->execute([$pedido_id]);
        $historial = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error_message = "Error de base de datos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Pedido - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 1rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1); }
        .card-header { background-color: #007bff; color: white; border-radius: 1rem 1rem 0 0 !important; }
        .section-title { color: #007bff; margin-top: 1.5rem; margin-bottom: 1rem; border-bottom: 2px solid #007bff; padding-bottom: 0.5rem; }
        .table th { background-color: #e9ecef; }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card shadow">
                    <div class="card-header text-center py-3">
                <?php if ($pedido): ?>
                    <h2 class="mb-0"><i class="fas fa-receipt me-2"></i>Detalle del Pedido #<?= htmlspecialchars($pedido['codigo']) ?></h2>
                <?php else: ?>
                    <h2 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Error</h2>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger text-center" role="alert">
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="pedidos.php" class="btn btn-primary"><i class="fas fa-arrow-left me-2"></i>Volver a Pedidos</a>
                    </div>
                <?php elseif ($pedido): ?>
                    <!-- Información General del Pedido -->
                    <h3 class="section-title">Información del Pedido</h3>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Código:</strong> <?= htmlspecialchars($pedido['codigo']) ?></p>
                            <p><strong>Fecha de Pedido:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($pedido['fecha_pedido']))) ?></p>
                            <p><strong>Fecha de Entrega:</strong> <?= $pedido['fecha_entrega'] ? htmlspecialchars(date('d/m/Y', strtotime($pedido['fecha_entrega']))) : 'Pendiente' ?></p>
                            <p><strong>Estado:</strong> <span class="badge bg-<?php 
                                switch($pedido['estado']) {
                                    case 'pendiente': echo 'warning'; break;
                                    case 'procesando': echo 'info'; break;
                                    case 'enviado': echo 'primary'; break;
                                    case 'entregado': echo 'success'; break;
                                    case 'cancelado': echo 'danger'; break;
                                    default: echo 'secondary';
                                }
                            ?>"><?= htmlspecialchars(ucfirst($pedido['estado'])) ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Subtotal:</strong> $<?= number_format($pedido['subtotal'], 2, ',', '.') ?></p>
                            <p><strong>Impuestos:</strong> $<?= number_format($pedido['impuestos'], 2, ',', '.') ?></p>
                            <p><strong>Total:</strong> $<?= number_format($pedido['total'], 2, ',', '.') ?></p>
                            <p><strong>Notas:</strong> <?= $pedido['notas'] ? htmlspecialchars($pedido['notas']) : 'N/A' ?></p>
                        </div>
                    </div>

                    <!-- Información del Cliente -->
                    <h3 class="section-title">Información del Cliente</h3>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> <?= htmlspecialchars($pedido['cliente_nombre'] . ' ' . $pedido['cliente_apellido']) ?></p>
                            <p><strong>Empresa:</strong> <?= htmlspecialchars($pedido['cliente_empresa']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($pedido['cliente_email']) ?></p>
                            <p><strong>Teléfono:</strong> <?= htmlspecialchars($pedido['cliente_telefono']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Dirección:</strong> <?= htmlspecialchars($pedido['cliente_direccion']) ?></p>
                            <p><strong>Ciudad:</strong> <?= htmlspecialchars($pedido['cliente_ciudad']) ?></p>
                            <p><strong>Provincia:</strong> <?= htmlspecialchars($pedido['cliente_provincia']) ?></p>
                            <p><strong>País:</strong> <?= htmlspecialchars($pedido['cliente_pais']) ?></p>
                        </div>
                    </div>

                    <!-- Detalles del Pedido (Productos) -->
                    <h3 class="section-title">Productos del Pedido</h3>
                    <?php if (!empty($detalles)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unitario</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detalles as $detalle): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($detalle['producto_codigo']) ?></td>
                                            <td><?= htmlspecialchars($detalle['producto_nombre']) ?></td>
                                            <td><?= htmlspecialchars($detalle['cantidad']) ?></td>
                                            <td>$<?= number_format($detalle['precio_unitario'], 2, ',', '.') ?></td>
                                            <td>$<?= number_format($detalle['subtotal'], 2, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">No hay productos en este pedido.</div>
                    <?php endif; ?>

                    <!-- Historial del Pedido -->
                    <h3 class="section-title">Historial del Pedido</h3>
                    <?php if (!empty($historial)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Usuario</th>
                                        <th>Estado Anterior</th>
                                        <th>Estado Nuevo</th>
                                        <th>Comentario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial as $registro): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($registro['fecha_cambio']))) ?></td>
                                            <td><?= htmlspecialchars($registro['usuario_nombre']) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($registro['estado_anterior'] ?? 'N/A')) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($registro['estado_nuevo'])) ?></td>
                                            <td><?= htmlspecialchars($registro['comentario'] ?? 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">No hay historial para este pedido.</div>
                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <a href="pedidos.php" class="btn btn-primary"><i class="fas fa-arrow-left me-2"></i>Volver a Pedidos</a>
                        <!-- Aquí se pueden añadir botones para Editar, Cambiar Estado, Imprimir -->
                        <a href="pedido_editar.php?id=<?= $pedido_id ?>" class="btn btn-warning"><i class="fas fa-edit me-2"></i>Editar Pedido</a>
                        <a href="pedido_cambiar_estado.php?id=<?= $pedido_id ?>" class="btn btn-info"><i class="fas fa-sync-alt me-2"></i>Cambiar Estado</a>
                        <a href="pedido_imprimir.php?id=<?= $pedido_id ?>" class="btn btn-success"><i class="fas fa-print me-2"></i>Imprimir Pedido</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
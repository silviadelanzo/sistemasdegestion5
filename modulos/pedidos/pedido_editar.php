<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Seguridad: Asegurar que solo usuarios logueados puedan acceder
iniciarSesionSegura();
requireLogin('../../login.php'); // Ajustar la ruta si es necesario
header('Content-Type: text/html; charset=UTF-8');

require_once '../../config/config.php'; // Ajusta la ruta si es necesario

// Definir variables para el navbar
$base_path = '../../modulos/';
$dashboard_path = '../../menu_principal.php';
$logout_path = '../../logout.php';
$es_administrador = (isset($_SESSION['rol_usuario']) && ($_SESSION['rol_usuario'] === 'admin' || $_SESSION['rol_usuario'] === 'administrador'));
$menuActivo = 'ventas'; // Para activar el menú de Ventas

$pedido_id = null;
$pedido = null;
$detalles = [];
$historial = [];
$clientes_list = [];
$productos_list = [];
$estados_posibles = ['pendiente', 'procesando', 'enviado', 'entregado', 'cancelado'];
$message = '';
$message_type = '';

// Obtener ID del pedido de la URL o del POST
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $pedido_id = $_GET['id'];
} elseif (isset($_POST['pedido_id']) && is_numeric($_POST['pedido_id'])) {
    $pedido_id = $_POST['pedido_id'];
}

// Procesar formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pedido_id) {
    try {
        $pdo = conectarDB();
        $pdo->beginTransaction();

        // 1. Actualizar datos del pedido principal
        $codigo = $_POST['codigo'] ?? '';
        $cliente_id = $_POST['cliente_id'] ?? null;
        $fecha_pedido = $_POST['fecha_pedido'] ?? date('Y-m-d H:i:s');
        $fecha_entrega = $_POST['fecha_entrega'] ?? null;
        $estado = $_POST['estado'] ?? 'pendiente';
        $notas = $_POST['notas'] ?? '';
        $subtotal = 0;
        $impuestos = 0;
        $total = 0;

        // Validar campos básicos
        if (empty($codigo) || empty($cliente_id) || empty($fecha_pedido)) {
            throw new Exception("Código, Cliente y Fecha de Pedido son obligatorios.");
        }

        // Actualizar pedido
        $stmt = $pdo->prepare("
            UPDATE pedidos SET
                codigo = ?,
                cliente_id = ?,
                fecha_pedido = ?,
                fecha_entrega = ?,
                estado = ?,
                notas = ?
            WHERE id = ?
        ");
        $stmt->execute([$codigo, $cliente_id, $fecha_pedido, $fecha_entrega, $estado, $notas, $pedido_id]);

        // 2. Actualizar detalles del pedido (productos)
        // Simplificado: Borrar todos los detalles existentes e insertar los nuevos
        $stmt_delete_detalles = $pdo->prepare("DELETE FROM pedido_detalles WHERE pedido_id = ?");
        $stmt_delete_detalles->execute([$pedido_id]);

        $productos_ids = $_POST['producto_id'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $precios_unitarios = $_POST['precio_unitario'] ?? [];

        $stmt_insert_detalle = $pdo->prepare("
            INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($productos_ids as $index => $prod_id) {
            $qty = (float)($cantidades[$index] ?? 0);
            $price = (float)($precios_unitarios[$index] ?? 0);
            $item_subtotal = $qty * $price;

            if ($prod_id && $qty > 0 && $price >= 0) {
                $stmt_insert_detalle->execute([$pedido_id, $prod_id, $qty, $price, $item_subtotal]);
                $subtotal += $item_subtotal;
            }
        }

        // Recalcular total e impuestos (simplificado, asumiendo impuestos 0 por ahora)
        $total = $subtotal; // Aquí iría la lógica de impuestos si fuera más compleja
        $impuestos = 0; // Asumimos 0 por ahora

        $stmt_update_total = $pdo->prepare("UPDATE pedidos SET subtotal = ?, impuestos = ?, total = ? WHERE id = ?");
        $stmt_update_total->execute([$subtotal, $impuestos, $total, $pedido_id]);

        // 3. Registrar en historial (si el estado cambió)
        $old_estado = $_POST['old_estado'] ?? '';
        if ($old_estado !== $estado) {
            $stmt_historial_insert = $pdo->prepare("
                INSERT INTO pedido_historial (pedido_id, estado_anterior, estado_nuevo, comentario, usuario_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt_historial_insert->execute([$pedido_id, $old_estado, $estado, "Cambio de estado a $estado", $_SESSION['usuario_id'] ?? 1]);
        }

        $pdo->commit();
        $message = "Pedido actualizado exitosamente.";
        $message_type = "success";

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error al actualizar el pedido: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Cargar datos del pedido para mostrar en el formulario
if ($pedido_id) {
    try {
        $pdo = conectarDB();

        // Obtener datos del pedido y cliente
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
            $message = "Pedido no encontrado.";
            $message_type = "danger";
        } else {
            // Obtener detalles de los productos
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

            // Obtener historial
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
        $message = "Error al cargar el pedido: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Cargar listas para selects
try {
    $pdo = conectarDB();
    $clientes_list = $pdo->query("SELECT id, CONCAT(nombre, ' ', apellido, ' (', empresa, ')') as full_name FROM clientes ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
    $productos_list = $pdo->query("SELECT id, CONCAT(codigo, ' - ', nombre) as full_name FROM productos ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error al cargar listas: " . $e->getMessage();
    $message_type = "danger";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pedido - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 1rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1); }
        .card-header { background-color: #007bff; color: white; border-radius: 1rem 1rem 0 0 !important; }
        .section-title { color: #007bff; margin-top: 1.5rem; margin-bottom: 1rem; border-bottom: 2px solid #007bff; padding-bottom: 0.5rem; }
        .table th { background-color: #e9ecef; }
        .btn-add-item { margin-top: 1rem; }
    </style>
</head>
<body>
    <!-- NAVBAR UNIFICADO -->
    <?php include "../../config/navbar_code.php"; ?>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="card shadow">
                    <div class="card-header text-center py-3">
                        <h2 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Pedido #<?= htmlspecialchars($pedido['codigo'] ?? '') ?></h2>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $message_type ?>" role="alert">
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$pedido_id): ?>
                            <div class="alert alert-info text-center">Por favor, proporcione un ID de pedido válido en la URL (ej: ?id=1).</div>
                        <?php elseif (!$pedido): ?>
                            <div class="alert alert-danger text-center">Pedido no encontrado.</div>
                            <div class="text-center mt-4">
                                <a href="pedidos.php" class="btn btn-primary"><i class="fas fa-arrow-left me-2"></i>Volver a Pedidos</a>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="pedido_id" value="<?= htmlspecialchars($pedido['id']) ?>">
                                <input type="hidden" name="old_estado" value="<?= htmlspecialchars($pedido['estado']) ?>">

                                <h3 class="section-title">Información General</h3>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="codigo" class="form-label">Código de Pedido</label>
                                        <input type="text" class="form-control" id="codigo" name="codigo" value="<?= htmlspecialchars($pedido['codigo']) ?>" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="cliente_id" class="form-label">Cliente</label>
                                        <select class="form-select" id="cliente_id" name="cliente_id" required>
                                            <?php foreach ($clientes_list as $cliente_item): ?>
                                                <option value="<?= htmlspecialchars($cliente_item['id']) ?>" <?= ($cliente_item['id'] == $pedido['cliente_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cliente_item['full_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="fecha_pedido" class="form-label">Fecha de Pedido</label>
                                        <input type="datetime-local" class="form-control" id="fecha_pedido" name="fecha_pedido" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($pedido['fecha_pedido']))) ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="fecha_entrega" class="form-label">Fecha de Entrega</label>
                                        <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" value="<?= htmlspecialchars($pedido['fecha_entrega'] ? date('Y-m-d', strtotime($pedido['fecha_entrega'])) : '') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="estado" class="form-label">Estado</label>
                                        <select class="form-select" id="estado" name="estado" required>
                                            <?php foreach ($estados_posibles as $estado_opt): ?>
                                                <option value="<?= htmlspecialchars($estado_opt) ?>" <?= ($estado_opt == $pedido['estado']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars(ucfirst($estado_opt)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="notas" class="form-label">Notas</label>
                                    <textarea class="form-control" id="notas" name="notas" rows="3"><?= htmlspecialchars($pedido['notas']) ?></textarea>
                                </div>

                                <h3 class="section-title">Productos del Pedido</h3>
                                <div id="productos-container">
                                    <?php if (!empty($detalles)): ?>
                                        <?php foreach ($detalles as $index => $detalle): ?>
                                            <div class="row mb-2 product-item" data-index="<?= $index ?>">
                                                <div class="col-md-6">
                                                    <label for="producto_id_<?= $index ?>" class="form-label">Producto</label>
                                                    <select class="form-select product-select" id="producto_id_<?= $index ?>" name="producto_id[]" required>
                                                        <option value="">Seleccione un producto</option>
                                                        <?php foreach ($productos_list as $prod_item): ?>
                                                            <option value="<?= htmlspecialchars($prod_item['id']) ?>" <?= ($prod_item['id'] == $detalle['producto_id']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($prod_item['full_name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="cantidad_<?= $index ?>" class="form-label">Cantidad</label>
                                                    <input type="number" class="form-control quantity-input" id="cantidad_<?= $index ?>" name="cantidad[]" value="<?= htmlspecialchars($detalle['cantidad']) ?>" min="1" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="precio_unitario_<?= $index ?>" class="form-label">Precio Unitario</label>
                                                    <input type="number" step="0.01" class="form-control price-input" id="precio_unitario_<?= $index ?>" name="precio_unitario[]" value="<?= htmlspecialchars($detalle['precio_unitario']) ?>" min="0" required>
                                                </div>
                                                <div class="col-md-1 d-flex align-items-end">
                                                    <button type="button" class="btn btn-danger remove-item"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-success btn-add-item"><i class="fas fa-plus-circle me-2"></i>Agregar Producto</button>

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
                                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Guardar Cambios</button>
                                    <a href="pedido_detalle.php?id=<?= htmlspecialchars($pedido['id']) ?>" class="btn btn-secondary btn-lg ms-2"><i class="fas fa-times-circle me-2"></i>Cancelar</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productosContainer = document.getElementById('productos-container');
            const addProductBtn = document.querySelector('.btn-add-item');
            let itemIndex = productosContainer.children.length > 0 ? parseInt(productosContainer.lastElementChild.dataset.index) + 1 : 0;

            addProductBtn.addEventListener('click', function() {
                const newItemHtml = `
                    <div class="row mb-2 product-item" data-index="${itemIndex}">
                        <div class="col-md-6">
                            <label for="producto_id_${itemIndex}" class="form-label">Producto</label>
                            <select class="form-select product-select" id="producto_id_${itemIndex}" name="producto_id[]" required>
                                <option value="">Seleccione un producto</option>
                                <?php foreach ($productos_list as $prod_item): ?>
                                    <option value="<?= htmlspecialchars($prod_item['id']) ?>">
                                        <?= htmlspecialchars($prod_item['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="cantidad_${itemIndex}" class="form-label">Cantidad</label>
                            <input type="number" class="form-control quantity-input" id="cantidad_${itemIndex}" name="cantidad[]" value="1" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label for="precio_unitario_${itemIndex}" class="form-label">Precio Unitario</label>
                            <input type="number" step="0.01" class="form-control price-input" id="precio_unitario_${itemIndex}" name="precio_unitario[]" value="0.00" min="0" required>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-danger remove-item"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                `;
                productosContainer.insertAdjacentHTML('beforeend', newItemHtml);
                itemIndex++;
                attachRemoveListeners();
            });

            function attachRemoveListeners() {
                document.querySelectorAll('.remove-item').forEach(button => {
                    button.onclick = function() {
                        this.closest('.product-item').remove();
                    };
                });
            }

            attachRemoveListeners(); // Attach listeners for existing items
        });
    </script>
</body>
</html>

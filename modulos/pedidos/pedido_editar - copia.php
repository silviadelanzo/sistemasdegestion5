<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
// iniciarSesionSegura();
// requireLogin('../../login.php'); // Ajustar la ruta si es necesario
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

        // 2. Procesar detalles del pedido para calcular totales ANTES de guardar
        $productos_ids = $_POST['producto_id'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $precios_unitarios = $_POST['precio_unitario'] ?? [];

        foreach ($productos_ids as $index => $prod_id) {
            $qty = (float)($cantidades[$index] ?? 0);
            $price = (float)($precios_unitarios[$index] ?? 0);
            $item_subtotal = $qty * $price;
            if ($prod_id && $qty > 0) {
                $subtotal += $item_subtotal;
            }
        }

        // Por ahora, impuestos y total son simples, pero podrían tener lógica más compleja
        $impuestos = 0; // Ejemplo: $subtotal * 0.21;
        $total = $subtotal + $impuestos;

        // Actualizar pedido con los totales calculados
        $stmt = $pdo->prepare("
            UPDATE pedidos SET
                codigo = ?, cliente_id = ?, fecha_pedido = ?, fecha_entrega = ?,
                estado = ?, notas = ?, subtotal = ?, impuestos = ?, total = ?
            WHERE id = ?
        ");
        $stmt->execute([$codigo, $cliente_id, $fecha_pedido, $fecha_entrega, $estado, $notas, $subtotal, $impuestos, $total, $pedido_id]);

        // 3. Actualizar detalles del pedido (productos)
        $stmt_delete_detalles = $pdo->prepare("DELETE FROM pedido_detalles WHERE pedido_id = ?");
        $stmt_delete_detalles->execute([$pedido_id]);

        $stmt_insert_detalle = $pdo->prepare("
            INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($productos_ids as $index => $prod_id) {
            $qty = (float)($cantidades[$index] ?? 0);
            $price = (float)($precios_unitarios[$index] ?? 0);
            $item_subtotal = $qty * $price;

            if ($prod_id && $qty > 0) {
                $stmt_insert_detalle->execute([$pedido_id, $prod_id, $qty, $price, $item_subtotal]);
            }
        }

        // 4. Registrar en historial (si el estado cambió)
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
        $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
        $stmt->execute([$pedido_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            $message = "Pedido no encontrado.";
            $message_type = "danger";
        } else {
            $stmt_detalles = $pdo->prepare("SELECT pd.*, p.nombre as producto_nombre, p.codigo as producto_codigo FROM pedido_detalles pd JOIN productos p ON pd.producto_id = p.id WHERE pd.pedido_id = ?");
            $stmt_detalles->execute([$pedido_id]);
            $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
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
    $productos_list = $pdo->query("SELECT id, CONCAT(codigo, ' - ', nombre) as full_name, precio_venta FROM productos ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
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
        .total-section { background-color: #e9ecef; padding: 1rem; border-radius: 0.5rem; }
    </style>
</head>
<body>
    <?php include "../../config/navbar_code.php"; ?>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header text-center py-3">
                        <h2 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Pedido #<?= htmlspecialchars($pedido['codigo'] ?? '') ?></h2>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $message_type ?>" role="alert"><?= htmlspecialchars($message) ?></div>
                        <?php endif; ?>

                        <?php if (!$pedido_id || !$pedido): ?>
                            <div class="alert alert-danger text-center">Pedido no encontrado.</div>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="pedido_id" value="<?= htmlspecialchars($pedido['id']) ?>">
                                <input type="hidden" name="old_estado" value="<?= htmlspecialchars($pedido['estado']) ?>">

                                <h3 class="section-title">Información General</h3>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="codigo" class="form-label">Código de Pedido</label>
                                        <input type="text" class="form-control" id="codigo" name="codigo" value="<?= htmlspecialchars($pedido['codigo'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="cliente_id" class="form-label">Cliente</label>
                                        <select class="form-select" id="cliente_id" name="cliente_id" required>
                                            <?php foreach ($clientes_list as $cliente_item): ?>
                                                <option value="<?= htmlspecialchars($cliente_item['id']) ?>" <?= ($cliente_item['id'] == $pedido['cliente_id']) ? 'selected' : '' ?>><?= htmlspecialchars($cliente_item['full_name']) ?></option>
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
                                                <option value="<?= htmlspecialchars($estado_opt) ?>" <?= ($estado_opt == $pedido['estado']) ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($estado_opt)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="notas" class="form-label">Notas</label>
                                    <textarea class="form-control" id="notas" name="notas" rows="3"><?= htmlspecialchars($pedido['notas'] ?? '') ?></textarea>
                                </div>

                                <h3 class="section-title">Productos del Pedido</h3>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th style="width: 120px;">Cantidad</th>
                                                <th style="width: 150px;">Precio Unit.</th>
                                                <th style="width: 150px;">Subtotal</th>
                                                <th style="width: 50px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="productos-container">
                                            <?php if (!empty($detalles)): ?>
                                                <?php foreach ($detalles as $index => $detalle): ?>
                                                    <tr class="product-item">
                                                        <td>
                                                            <select class="form-select product-select" name="producto_id[]" required>
                                                                <option value="">Seleccione</option>
                                                                <?php foreach ($productos_list as $prod_item): ?>
                                                                    <option value="<?= htmlspecialchars($prod_item['id']) ?>" data-precio="<?= htmlspecialchars($prod_item['precio_venta']) ?>" <?= ($prod_item['id'] == $detalle['producto_id']) ? 'selected' : '' ?>><?= htmlspecialchars($prod_item['full_name']) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </td>
                                                        <td><input type="number" class="form-control quantity-input" name="cantidad[]" value="<?= htmlspecialchars($detalle['cantidad']) ?>" min="0.01" step="0.01" required></td>
                                                        <td><input type="number" class="form-control price-input" name="precio_unitario[]" value="<?= htmlspecialchars($detalle['precio_unitario']) ?>" min="0.00" step="0.01" required></td>
                                                        <td><input type="text" class="form-control subtotal-input" value="<?= htmlspecialchars(number_format($detalle['subtotal'], 2)) ?>" readonly></td>
                                                        <td><button type="button" class="btn btn-danger remove-item"><i class="fas fa-trash"></i></button></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-success btn-add-item"><i class="fas fa-plus-circle me-2"></i>Agregar Producto</button>

                                <div class="row justify-content-end mt-4">
                                    <div class="col-md-5">
                                        <div class="total-section">
                                            <div class="row mb-2">
                                                <strong class="col-6">Subtotal:</strong>
                                                <div class="col-6 text-end" id="subtotal-pedido">$0.00</div>
                                            </div>
                                            <div class="row mb-2">
                                                <strong class="col-6">Impuestos (IVA 21%):</strong>
                                                <div class="col-6 text-end" id="impuestos-pedido">$0.00</div>
                                            </div>
                                            <hr>
                                            <div class="row fw-bold fs-5">
                                                <strong class="col-6">Total:</strong>
                                                <div class="col-6 text-end" id="total-pedido">$0.00</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Guardar Cambios</button>
                                    <a href="pedidos.php" class="btn btn-secondary btn-lg ms-2">Cancelar</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productosContainer = document.getElementById('productos-container');
            const addProductBtn = document.querySelector('.btn-add-item');
            const productosData = <?= json_encode($productos_list) ?>;

            function updateTotals() {
                let subtotalPedido = 0;
                document.querySelectorAll('.product-item').forEach(item => {
                    const cantidad = parseFloat(item.querySelector('.quantity-input').value) || 0;
                    const precio = parseFloat(item.querySelector('.price-input').value) || 0;
                    const subtotalItem = cantidad * precio;
                    item.querySelector('.subtotal-input').value = subtotalItem.toFixed(2);
                    subtotalPedido += subtotalItem;
                });

                const impuestos = subtotalPedido * 0.21; // Asumiendo IVA 21%
                const totalPedido = subtotalPedido + impuestos;

                document.getElementById('subtotal-pedido').textContent = `${subtotalPedido.toFixed(2)}`;
                document.getElementById('impuestos-pedido').textContent = `${impuestos.toFixed(2)}`;
                document.getElementById('total-pedido').textContent = `${totalPedido.toFixed(2)}`;
            }

            function attachItemListeners(item) {
                item.querySelector('.quantity-input').addEventListener('input', updateTotals);
                item.querySelector('.price-input').addEventListener('input', updateTotals);
                item.querySelector('.remove-item').addEventListener('click', function() {
                    item.remove();
                    updateTotals();
                });
                item.querySelector('.product-select').addEventListener('change', function(e) {
                    const selectedOption = e.target.options[e.target.selectedIndex];
                    const precio = selectedOption.dataset.precio || 0;
                    item.querySelector('.price-input').value = parseFloat(precio).toFixed(2);
                    updateTotals();
                });
            }

            addProductBtn.addEventListener('click', function() {
                const newRow = document.createElement('tr');
                newRow.classList.add('product-item');
                
                let options = '<option value="">Seleccione</option>';
                productosData.forEach(prod => {
                    options += `<option value="${prod.id}" data-precio="${prod.precio_venta}">${prod.full_name}</option>`;
                });

                newRow.innerHTML = `
                    <td><select class="form-select product-select" name="producto_id[]" required>${options}</select></td>
                    <td><input type="number" class="form-control quantity-input" name="cantidad[]" value="1" min="0.01" step="0.01" required></td>
                    <td><input type="number" class="form-control price-input" name="precio_unitario[]" value="0.00" min="0.00" step="0.01" required></td>
                    <td><input type="text" class="form-control subtotal-input" readonly></td>
                    <td><button type="button" class="btn btn-danger remove-item"><i class="fas fa-trash"></i></button></td>
                `;
                productosContainer.appendChild(newRow);
                attachItemListeners(newRow);
            });

            document.querySelectorAll('.product-item').forEach(attachItemListeners);
            updateTotals();
        });
    </script>
</body>
</html>

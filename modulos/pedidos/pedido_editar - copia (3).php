<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
header('Content-Type: text/html; charset=UTF-8');
require_once '../../config/config.php';

// Navbar
$base_path = '../../modulos/';
$dashboard_path = '../../menu_principal.php';
$logout_path = '../../logout.php';
$es_administrador = (isset($_SESSION['rol_usuario']) && ($_SESSION['rol_usuario'] === 'admin' || $_SESSION['rol_usuario'] === 'administrador'));
$menuActivo = 'ventas';

$pedido_id = null;
$pedido = null;
$detalles = [];
$historial = [];
$clientes_list = [];
$productos_list = [];
$estados_posibles = ['pendiente', 'procesando', 'enviado', 'entregado', 'cancelado'];
$message = '';
$message_type = '';

// Obtener ID del pedido
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $pedido_id = $_GET['id'];
} elseif (isset($_POST['pedido_id']) && is_numeric($_POST['pedido_id'])) {
    $pedido_id = $_POST['pedido_id'];
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pedido_id) {
    try {
        $pdo = conectarDB();
        $pdo->beginTransaction();

        $codigo = $_POST['codigo'] ?? '';
        $cliente_id = $_POST['cliente_id'] ?? null;
        $fecha_pedido = $_POST['fecha_pedido'] ?? date('Y-m-d H:i:s');
        $fecha_entrega = $_POST['fecha_entrega'] ?? null;
        $estado = $_POST['estado'] ?? 'pendiente';
        $notas = $_POST['notas'] ?? '';
        $subtotal = 0.0;
        $impuestos = 0.0;
        $total = 0.0;

        if (empty($codigo) || empty($cliente_id) || empty($fecha_pedido)) {
            throw new Exception("Código, Cliente y Fecha de Pedido son obligatorios.");
        }

        $productos_ids = $_POST['producto_id'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $precios_unitarios = $_POST['precio_unitario'] ?? [];

        foreach ($productos_ids as $index => $prod_id) {
            $qty = (float)($cantidades[$index] ?? 0);
            $price = (float)($precios_unitarios[$index] ?? 0);
            if ($prod_id && $qty > 0) {
                $subtotal += $qty * $price; // sin IVA
            }
        }

        $impuestos = round($subtotal * 0.21, 2);
        $total = round($subtotal + $impuestos, 2);

        $stmt = $pdo->prepare("
            UPDATE pedidos SET
                codigo = ?, cliente_id = ?, fecha_pedido = ?, fecha_entrega = ?,
                estado = ?, notas = ?, subtotal = ?, impuestos = ?, total = ?
            WHERE id = ?
        ");
        $stmt->execute([$codigo, $cliente_id, $fecha_pedido, $fecha_entrega, $estado, $notas, $subtotal, $impuestos, $total, $pedido_id]);

        // Reemplazar detalles
        $pdo->prepare("DELETE FROM pedido_detalles WHERE pedido_id = ?")->execute([$pedido_id]);
        $stmt_insert_detalle = $pdo->prepare("
            INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($productos_ids as $index => $prod_id) {
            $qty = (float)($cantidades[$index] ?? 0);
            $price = (float)($precios_unitarios[$index] ?? 0);
            if ($prod_id && $qty > 0) {
                $item_subtotal = $qty * $price; // sin IVA
                $stmt_insert_detalle->execute([$pedido_id, $prod_id, $qty, $price, $item_subtotal]);
            }
        }

        // Historial cambio de estado
        $old_estado = $_POST['old_estado'] ?? '';
        if ($old_estado !== $estado) {
            $pdo->prepare("
                INSERT INTO pedido_historial (pedido_id, estado_anterior, estado_nuevo, comentario, usuario_id)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([$pedido_id, $old_estado, $estado, "Cambio de estado a $estado", $_SESSION['usuario_id'] ?? 1]);
        }

        $pdo->commit();
        $message = "Pedido actualizado exitosamente.";
        $message_type = "success";

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        $message = "Error al actualizar el pedido: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Cargar datos del pedido
if ($pedido_id) {
    try {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
        $stmt->execute([$pedido_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pedido) {
            $stmt_detalles = $pdo->prepare("
                SELECT pd.*, p.nombre as producto_nombre, p.codigo as producto_codigo
                FROM pedido_detalles pd
                JOIN productos p ON pd.producto_id = p.id
                WHERE pd.pedido_id = ?
            ");
            $stmt_detalles->execute([$pedido_id]);
            $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $message = "Pedido no encontrado.";
            $message_type = "danger";
        }
    } catch (PDOException $e) {
        $message = "Error al cargar el pedido: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Listas
try {
    $pdo = conectarDB();
    $clientes_list = $pdo->query("SELECT id, CONCAT(nombre, ' ', apellido, ' (', empresa, ')') as full_name FROM clientes ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
    // Suponemos que precio_venta es sin IVA
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
        .section-title { color: #007bff; margin-top: 1rem; margin-bottom: .5rem; border-bottom: 2px solid #007bff; padding-bottom: .25rem; }

        /* Información General más compacta y con campos angostos */
        .info-compact .form-label { font-size: .9rem; margin-bottom: .15rem; }
        .info-compact .form-control, .info-compact .form-select {
            padding: .25rem .5rem;
            height: calc(1.2em + .5rem + 2px);
            font-size: .9rem;
        }
        .info-compact .col-md-2, .info-compact .col-md-4 { min-width: 0; }

        /* Tabla compacta (-1 punto aprox) */
        .compact-table, .compact-table .form-control, .compact-table .form-select { font-size: 0.9rem; }
        .compact-table .form-control, .compact-table .form-select {
            padding: .25rem .5rem;
            height: calc(1.2em + .5rem + 2px);
        }
        .table.table-sm th, .table.table-sm td { padding: .3rem .5rem; }

        /* Anchos de columnas */
        table.fixed-layout { table-layout: fixed; width: 100%; }
        th.col-producto { width: 32%; }
        th.col-cantidad { width: 11%; }
        th.col-precio   { width: 13%; }
        th.col-impuesto { width: 12%; }
        th.col-total    { width: 12%; }
        th.col-acciones { width: 3%; }

        .readonly-input { background-color: #f8f9fa; }
        .text-end { text-align: end; }

        /* Notas en un solo renglón */
        .one-line { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body>
    <?php include "../../config/navbar_code.php"; ?>

    <div class="container mt-4 mb-4">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                <div class="card shadow">
                    <div class="card-header text-center py-2">
                        <h2 class="mb-0" style="font-size:1.25rem"><i class="fas fa-edit me-2"></i>Editar Pedido #<?= htmlspecialchars($pedido['codigo'] ?? '') ?></h2>
                    </div>
                    <div class="card-body p-3">
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
                            <div class="row g-2 info-compact align-items-end mb-2">
                                <div class="col-md-2">
                                    <label for="codigo" class="form-label">Código de Pedido</label>
                                    <input type="text" class="form-control" id="codigo" name="codigo"
                                           value="<?= htmlspecialchars($pedido['codigo'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="cliente_id" class="form-label">Cliente</label>
                                    <select class="form-select" id="cliente_id" name="cliente_id" required>
                                        <?php foreach ($clientes_list as $cliente_item): ?>
                                            <option value="<?= htmlspecialchars($cliente_item['id']) ?>"
                                                <?= ($cliente_item['id'] == $pedido['cliente_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cliente_item['full_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="fecha_pedido" class="form-label">Fecha de Pedido</label>
                                    <input type="datetime-local" class="form-control" id="fecha_pedido" name="fecha_pedido"
                                           value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($pedido['fecha_pedido']))) ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="fecha_entrega" class="form-label">Fecha de Entrega</label>
                                    <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega"
                                           value="<?= htmlspecialchars($pedido['fecha_entrega'] ? date('Y-m-d', strtotime($pedido['fecha_entrega'])) : '') ?>">
                                </div>
                                <div class="col-md-2">
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

                            <!-- Notas en una sola línea -->
                            <div class="mb-3">
                                <label for="notas" class="form-label">Notas</label>
                                <input type="text" class="form-control one-line" id="notas" name="notas" value="<?= htmlspecialchars($pedido['notas'] ?? '') ?>" placeholder="Ingrese una nota (máx. una línea)">
                            </div>

                            <h3 class="section-title">Productos del Pedido</h3>

                            <!-- Controles de paginación -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <label for="page-size" class="mb-0">Mostrar</label>
                                    <select id="page-size" class="form-select form-select-sm" style="width:auto">
                                        <option value="10">10</option>
                                        <option value="20" selected>20</option>
                                        <option value="50">50</option>
                                    </select>
                                    <span class="ms-1">filas</span>
                                </div>
                                <div id="pagination-controls" class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-first" title="Primera">&laquo;</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-prev" title="Anterior">&lsaquo;</button>
                                    <span id="page-info" class="small text-muted">Página 1 de 1</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-next" title="Siguiente">&rsaquo;</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-last" title="Última">&raquo;</button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm fixed-layout compact-table">
                                    <thead>
                                        <tr>
                                            <th class="col-producto">Producto</th>
                                            <th class="col-cantidad text-center">Cantidad</th>
                                            <th class="col-precio text-center">Precio s/IVA</th>
                                            <th class="col-impuesto text-center">Impuesto</th>
                                            <th class="col-total text-center">Total</th>
                                            <th class="col-acciones"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="productos-container">
                                    <?php if (!empty($detalles)): ?>
                                        <?php foreach ($detalles as $index => $detalle):
                                            $qty = (float)$detalle['cantidad'];
                                            $price = (float)$detalle['precio_unitario']; // s/IVA
                                            $item_subtotal = $qty * $price;
                                            $item_impuesto = $item_subtotal * 0.21;
                                            $item_total = $item_subtotal + $item_impuesto;
                                        ?>
                                        <tr class="product-item">
                                            <td>
                                                <select class="form-select form-select-sm product-select" name="producto_id[]" required>
                                                    <option value="">Seleccione</option>
                                                    <?php foreach ($productos_list as $prod_item): ?>
                                                        <option value="<?= htmlspecialchars($prod_item['id']) ?>"
                                                                data-precio="<?= htmlspecialchars($prod_item['precio_venta']) ?>"
                                                                <?= ($prod_item['id'] == $detalle['producto_id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($prod_item['full_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-end quantity-input" name="cantidad[]"
                                                       value="<?= htmlspecialchars(number_format($qty, 2, '.', '')) ?>" min="0.01" step="0.01" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-end price-input" name="precio_unitario[]"
                                                       value="<?= htmlspecialchars(number_format($price, 2, '.', '')) ?>" min="0.00" step="0.01" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm text-end readonly-input tax-input"
                                                       value="<?= htmlspecialchars(number_format($item_impuesto, 2, '.', '')) ?>" readonly>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm text-end readonly-input total-item-input"
                                                       value="<?= htmlspecialchars(number_format($item_total, 2, '.', '')) ?>" readonly>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-danger remove-item"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <button type="button" class="btn btn-success btn-sm btn-add-item">
                                <i class="fas fa-plus-circle me-2"></i>Agregar Producto
                            </button>

                            <div class="row justify-content-end mt-3">
                                <div class="col-md-4">
                                    <div class="p-3" style="background:#e9ecef;border-radius:.5rem">
                                        <div class="d-flex justify-content-between mb-1">
                                            <strong>Subtotal:</strong>
                                            <div id="subtotal-pedido">$0.00</div>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <strong>Impuestos (IVA 21%):</strong>
                                            <div id="impuestos-pedido">$0.00</div>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between fw-bold">
                                            <strong>Total:</strong>
                                            <div id="total-pedido">$0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-3">
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
        const TAX_RATE = 0.21;

        // Paginación
        const pageSizeSelect = document.getElementById('page-size');
        const btnFirst = document.getElementById('btn-first');
        const btnPrev = document.getElementById('btn-prev');
        const btnNext = document.getElementById('btn-next');
        const btnLast = document.getElementById('btn-last');
        const pageInfo = document.getElementById('page-info');

        let currentPage = 1;
        let totalPages = 1;

        const fmt = (n) => {
            if (isNaN(n)) n = 0;
            return n.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        };

        function updateTotals() {
            let subtotalPedido = 0;
            let impuestosPedido = 0;

            // Contabiliza TODAS las filas (incluidas las ocultas por paginación)
            document.querySelectorAll('.product-item').forEach(item => {
                const cantidad = parseFloat(item.querySelector('.quantity-input').value) || 0;
                const precio = parseFloat(item.querySelector('.price-input').value) || 0;
                const subtotalItem = cantidad * precio; // s/IVA
                const impuestoItem = subtotalItem * TAX_RATE;
                const totalItem = subtotalItem + impuestoItem;

                // Pintar por fila
                item.querySelector('.tax-input').value = fmt(impuestoItem);
                item.querySelector('.total-item-input').value = fmt(totalItem);

                subtotalPedido += subtotalItem;
                impuestosPedido += impuestoItem;
            });

            const totalPedido = subtotalPedido + impuestosPedido;
            document.getElementById('subtotal-pedido').textContent = fmt(subtotalPedido);
            document.getElementById('impuestos-pedido').textContent = fmt(impuestosPedido);
            document.getElementById('total-pedido').textContent = fmt(totalPedido);
        }

        function attachItemListeners(item) {
            item.querySelector('.quantity-input').addEventListener('input', () => { updateTotals(); });
            item.querySelector('.price-input').addEventListener('input', () => { updateTotals(); });
            item.querySelector('.remove-item').addEventListener('click', function() {
                item.remove();
                paginate(); // recalcula y repinta páginas
            });
            item.querySelector('.product-select').addEventListener('change', function(e) {
                const selectedOption = e.target.options[e.target.selectedIndex];
                const precio = parseFloat(selectedOption.dataset.precio || '0');
                item.querySelector('.price-input').value = precio.toFixed(2);
                updateTotals();
            });
        }

        function showPage(page) {
            const pageSize = parseInt(pageSizeSelect.value, 10);
            const rows = Array.from(document.querySelectorAll('.product-item'));
            totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
            currentPage = Math.min(Math.max(1, page), totalPages);

            rows.forEach((row, idx) => {
                const pageForRow = Math.floor(idx / pageSize) + 1;
                row.style.display = (pageForRow === currentPage) ? '' : 'none';
            });

            pageInfo.textContent = `Página ${currentPage} de ${totalPages}`;
            btnPrev.disabled = btnFirst.disabled = (currentPage === 1);
            btnNext.disabled = btnLast.disabled = (currentPage === totalPages);
        }

        function paginate() {
            showPage(currentPage);
            updateTotals();
        }

        // Botones de paginación
        pageSizeSelect.addEventListener('change', () => showPage(1));
        btnFirst.addEventListener('click', () => showPage(1));
        btnPrev.addEventListener('click', () => showPage(currentPage - 1));
        btnNext.addEventListener('click', () => showPage(currentPage + 1));
        btnLast.addEventListener('click', () => showPage(totalPages));

        addProductBtn.addEventListener('click', function() {
            const newRow = document.createElement('tr');
            newRow.classList.add('product-item');

            let options = '<option value="">Seleccione</option>';
            productosData.forEach(prod => {
                options += `<option value="${prod.id}" data-precio="${prod.precio_venta}">${prod.full_name}</option>`;
            });

            newRow.innerHTML = `
                <td>
                    <select class="form-select form-select-sm product-select" name="producto_id[]" required>${options}</select>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm text-end quantity-input" name="cantidad[]" value="1" min="0.01" step="0.01" required>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm text-end price-input" name="precio_unitario[]" value="0.00" min="0.00" step="0.01" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm text-end readonly-input tax-input" value="0,00" readonly>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm text-end readonly-input total-item-input" value="0,00" readonly>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-item"><i class="fas fa-trash"></i></button>
                </td>
            `;
            productosContainer.appendChild(newRow);
            attachItemListeners(newRow);
            paginate();
        });

        // Inicializar
        document.querySelectorAll('.product-item').forEach(attachItemListeners);
        paginate();
    });
    </script>
</body>
</html>
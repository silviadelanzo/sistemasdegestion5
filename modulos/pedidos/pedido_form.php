<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

$errores = [];
$mensaje_exito = '';
$codigo_pedido = '';
$cuenta_id_actual = $_SESSION['cuenta_id'];

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    die('Error de conexión: ' . htmlspecialchars($e->getMessage()));
}

// Generar código de pedido para mostrar en el formulario (SECURED)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    try {
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) as max_correlativo FROM pedidos WHERE codigo LIKE 'PED-%' AND cuenta_id = ?");
        $stmt->execute([$cuenta_id_actual]);
        $max_correlativo = $stmt->fetchColumn() ?? 0;
        $siguiente_correlativo = $max_correlativo + 1;
        $codigo_pedido = 'PED-' . str_pad($siguiente_correlativo, 7, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        $errores[] = 'Error al generar el código de pedido: ' . $e->getMessage();
    }
}

// Obtener clientes y productos (SECURED)
$clientes = $productos = [];
try {
    $stmt_clientes = $pdo->prepare("SELECT id, CONCAT(nombre, ' ', apellido, ' - ', COALESCE(empresa, '')) as nombre_completo, tipo_cliente FROM clientes WHERE activo = 1 AND eliminado = 0 AND cuenta_id = ? ORDER BY nombre, apellido");
    $stmt_clientes->execute([$cuenta_id_actual]);
    $clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

    $stmt_productos = $pdo->prepare("SELECT p.id, p.codigo, p.nombre, p.precio_minorista, p.precio_mayorista FROM productos p WHERE p.activo = 1 AND p.cuenta_id = ? ORDER BY p.nombre");
    $stmt_productos->execute([$cuenta_id_actual]);
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errores[] = 'Error al cargar datos: ' . $e->getMessage();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = $_POST['cliente_id'] ?? null;
    $productos_pedido = $_POST['productos'] ?? [];
    $codigo_pedido = $_POST['codigo_pedido'] ?? '';
    
    if (empty($cliente_id)) {
        $errores[] = 'Seleccione un cliente';
    }
    
    if (empty($productos_pedido)) {
        $errores[] = 'Agregue al menos un producto al pedido';
    }
    
    if (empty($errores)) {
        try {
            $pdo->beginTransaction();
            
            // Obtener tipo de cliente y verificar que pertenezca a la cuenta
            $stmt_cliente_check = $pdo->prepare("SELECT tipo_cliente, cuenta_id FROM clientes WHERE id = ?");
            $stmt_cliente_check->execute([$cliente_id]);
            $cliente = $stmt_cliente_check->fetch();

            if (!$cliente || $cliente['cuenta_id'] != $cuenta_id_actual) {
                throw new Exception("El cliente seleccionado no es válido o no pertenece a su cuenta.");
            }
            $tipo_cliente = $cliente['tipo_cliente'];
            
            // Calcular totales
            $subtotal = 0;
            foreach ($productos_pedido as $producto) {
                $precio = $tipo_cliente === 'mayorista' ? $producto['precio_mayorista'] : $producto['precio_minorista'];
                $subtotal += $precio * $producto['cantidad'];
            }
            
            // Insertar pedido (SECURED)
            $stmt = $pdo->prepare("INSERT INTO pedidos (cuenta_id, codigo, cliente_id, subtotal, total, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cuenta_id_actual, $codigo_pedido, $cliente_id, $subtotal, $subtotal, $_SESSION['id_usuario']]);
            $pedido_id = $pdo->lastInsertId();
            
            // Insertar detalles del pedido
            $stmt_detalle = $pdo->prepare("INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($productos_pedido as $producto) {
                $precio = $tipo_cliente === 'mayorista' ? $producto['precio_mayorista'] : $producto['precio_minorista'];
                $subtotal_producto = $precio * $producto['cantidad'];
                
                $stmt_detalle->execute([
                    $pedido_id,
                    $producto['id'],
                    $producto['cantidad'],
                    $precio,
                    $subtotal_producto
                ]);
            }
            
            $pdo->commit();
            
            header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1&pedido=' . urlencode($codigo_pedido));
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = 'Error al crear pedido: ' . $e->getMessage();
        }
    }
}

if (isset($_GET['success']) && isset($_GET['pedido'])) {
    $mensaje_exito = 'Pedido creado exitosamente: ' . htmlspecialchars($_GET['pedido']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Pedido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .hero-blue {
            background: linear-gradient(90deg, #0d6efd, #0a58ca);
            color: #fff;
            border-radius: 12px;
            padding: 16px 20px;
            margin: 18px 0 10px;
        }
        .table-responsive { border: 1px solid #e9ecef; border-radius: 6px; }
        .badge-mayorista { background-color: #198754; }
        .badge-minorista { background-color: #6f42c1; }
        .badge-may-min { background-color: #fd7e14; }
        #tabla-productos td { vertical-align: middle; }
    </style>
</head>
<body class="bg-light">
<?php include "../../config/navbar_code.php"; ?>
<div class="container">

    <div class="hero-blue d-flex justify-content-between align-items-center">
        <h3>Nuevo Pedido</h3>
        <div class="d-flex align-items-center">
            <h5 class="mb-0 me-2">Nº:</h5>
            <input type="text" class="form-control form-control-lg text-center fw-bold" style="width: 220px; background-color: #fff;" value="<?php echo htmlspecialchars($codigo_pedido); ?>" readonly>
        </div>
    </div>

    <?php if ($errores): ?>
    <div class="alert alert-danger">
        <?php foreach ($errores as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?>
    </div>
    <?php endif; ?>

    <?php if ($mensaje_exito): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($mensaje_exito); ?></div>
    <?php endif; ?>

    <form method="post" class="card p-4 bg-white">
        <input type="hidden" name="codigo_pedido" value="<?php echo htmlspecialchars($codigo_pedido); ?>">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Cliente *</label>
                <select id="cliente_id" name="cliente_id" class="form-select" required onchange="actualizarTipoCliente()">
                    <option value="">-- Seleccionar Cliente --</option>
                    <?php foreach ($clientes as $cliente): ?>
                    <option value="<?= $cliente['id'] ?>" data-tipo="<?= $cliente['tipo_cliente'] ?>">
                        <?= htmlspecialchars($cliente['nombre_completo']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Tipo de Cliente</label>
                <div id="tipo_cliente_display" class="form-control" style="height: auto; min-height: 38px;">
                    <span class="text-muted">Seleccione un cliente</span>
                </div>
            </div>
        </div>

        <h5>Productos del Pedido</h5>
        <div class="table-responsive">
            <table class="table table-bordered" id="tabla-productos">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th width="100">Cantidad</th>
                        <th width="120">Precio Unitario</th>
                        <th width="120">Subtotal</th>
                        <th width="50">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <tr id="fila-nueva">
                        <td>
                            <select class="form-select" id="producto_id" onchange="actualizarPrecio()">
                                <option value="">-- Seleccionar Producto --</option>
                                <?php foreach ($productos as $producto): ?>
                                <option value="<?= $producto['id'] ?>" 
                                        data-precio-minorista="<?= $producto['precio_minorista'] ?>"
                                        data-precio-mayorista="<?= $producto['precio_mayorista'] ?>">
                                    <?= htmlspecialchars($producto['nombre']) ?> (<?= $producto['codigo'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" class="form-control" id="cantidad" value="1" min="1" onchange="actualizarSubtotal()"></td>
                        <td><input type="number" class="form-control" id="precio_unitario" step="0.01" readonly></td>
                        <td><input type="number" class="form-control" id="subtotal" step="0.01" readonly></td>
                        <td><button type="button" class="btn btn-success btn-sm" onclick="agregarProducto()">+</button></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total del Pedido:</strong></td>
                        <td><input type="number" id="total_pedido" name="total_pedido" class="form-control" step="0.01" readonly value="0.00"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="text-center mt-3">
            <button type="submit" class="btn btn-primary">Crear Pedido</button>
            <a href="pedidos.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Variables globales
let tipoClienteActual = '';
let productosAgregados = [];

function actualizarTipoCliente() {
    const clienteSelect = document.getElementById('cliente_id');
    const tipoClienteDisplay = document.getElementById('tipo_cliente_display');
    const selectedOption = clienteSelect.options[clienteSelect.selectedIndex];
    
    tipoClienteActual = selectedOption?.dataset.tipo || '';
    
    let badgeClass = 'badge ';
    let tipoText = '';
    
    switch(tipoClienteActual) {
        case 'mayorista':
            badgeClass += 'bg-success';
            tipoText = 'MAYORISTA';
            break;
        case 'minorista':
            badgeClass += 'bg-primary';
            tipoText = 'MINORISTA';
            break;
        case 'may_min':
            badgeClass += 'bg-warning text-dark';
            tipoText = 'MAYORISTA/MINORISTA';
            break;
        default:
            badgeClass += 'bg-secondary';
            tipoText = 'No especificado';
    }
    
    tipoClienteDisplay.innerHTML = tipoClienteActual ? 
        `<span class="${badgeClass}">${tipoText}</span>` : 
        '<span class="text-muted">Seleccione un cliente</span>';
    
    // Actualizar precio si hay producto seleccionado
    actualizarPrecio();
}

function actualizarPrecio() {
    const productoSelect = document.getElementById('producto_id');
    const precioInput = document.getElementById('precio_unitario');
    
    if (!productoSelect.value || !tipoClienteActual) {
        precioInput.value = '';
        return;
    }
    
    const selectedOption = productoSelect.options[productoSelect.selectedIndex];
    const esMayorista = tipoClienteActual === 'mayorista';
    const precio = esMayorista ? selectedOption.dataset.precioMayorista : selectedOption.dataset.precioMinorista;
    
    precioInput.value = parseFloat(precio).toFixed(2);
    actualizarSubtotal();
}

function actualizarSubtotal() {
    const precioInput = document.getElementById('precio_unitario');
    const cantidadInput = document.getElementById('cantidad');
    const subtotalInput = document.getElementById('subtotal');
    
    const precio = parseFloat(precioInput.value) || 0;
    const cantidad = parseInt(cantidadInput.value) || 0;
    const subtotal = precio * cantidad;
    
    subtotalInput.value = subtotal.toFixed(2);
}

function agregarProducto() {
    const productoSelect = document.getElementById('producto_id');
    const cantidadInput = document.getElementById('cantidad');
    const precioInput = document.getElementById('precio_unitario');
    
    if (!productoSelect.value) {
        alert('Seleccione un producto');
        return;
    }
    
    if (!tipoClienteActual) {
        alert('Seleccione un cliente primero');
        return;
    }
    
    if (cantidadInput.value < 1) {
        alert('Ingrese una cantidad válida');
        return;
    }
    
    const selectedOption = productoSelect.options[productoSelect.selectedIndex];
    const productoId = productoSelect.value;
    const productoNombre = selectedOption.text;
    const cantidad = parseInt(cantidadInput.value);
    const precio = parseFloat(precioInput.value);
    const subtotal = precio * cantidad;
    
    // Agregar a la lista de productos
    productosAgregados.push({
        id: productoId,
        nombre: productoNombre,
        cantidad: cantidad,
        precio_minorista: selectedOption.dataset.precioMinorista,
        precio_mayorista: selectedOption.dataset.precioMayorista,
        precio: precio,
        subtotal: subtotal
    });
    
    // Crear fila en la tabla
    const tablaBody = document.querySelector('#tabla-productos tbody');
    const nuevaFila = document.createElement('tr');
    nuevaFila.innerHTML = `
        <td>${productoNombre}</td>
        <td>${cantidad}</td>
        <td>${precio.toFixed(2)}</td>
        <td>${subtotal.toFixed(2)}</td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="eliminarProducto(this, ${productosAgregados.length - 1})">✕</button></td>
        <input type="hidden" name="productos[${productosAgregados.length - 1}][id]" value="${productoId}">
        <input type="hidden" name="productos[${productosAgregados.length - 1}][cantidad]" value="${cantidad}">
        <input type="hidden" name="productos[${productosAgregados.length - 1}][precio_minorista]" value="${selectedOption.dataset.precioMinorista}">
        <input type="hidden" name="productos[${productosAgregados.length - 1}][precio_mayorista]" value="${selectedOption.dataset.precioMayorista}">
    `;
    
    tablaBody.insertBefore(nuevaFila, document.getElementById('fila-nueva'));
    
    // Limpiar campos
    productoSelect.value = '';
    cantidadInput.value = 1;
    precioInput.value = '';
    document.getElementById('subtotal').value = '';
    
    // Actualizar total
    calcularTotal();
}

function eliminarProducto(boton, index) {
    // Eliminar de la lista
    productosAgregados.splice(index, 1);
    
    // Eliminar fila de la tabla
    const fila = boton.parentNode.parentNode;
    fila.parentNode.removeChild(fila);
    
    // Reindexar los inputs hidden
    const filas = document.querySelectorAll('#tabla-productos tbody tr');
    filas.forEach((fila, i) => {
        if (fila.id !== 'fila-nueva') {
            const inputs = fila.querySelectorAll('input[type="hidden"]');
            inputs[0].name = `productos[${i}][id]`;
            inputs[1].name = `productos[${i}][cantidad]`;
            inputs[2].name = `productos[${i}][precio_minorista]`;
            inputs[3].name = `productos[${i}][precio_mayorista]`;
        }
    });
    
    // Actualizar total
    calcularTotal();
}

function calcularTotal() {
    let total = 0;
    productosAgregados.forEach(producto => {
        total += producto.subtotal;
    });
    
    document.getElementById('total_pedido').value = total.toFixed(2);
}

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    actualizarTipoCliente();
});
</script>
</body>
</html>

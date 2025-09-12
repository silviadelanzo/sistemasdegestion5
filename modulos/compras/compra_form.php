<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();
$id = $_GET['id'] ?? 0;
$compra = null;
$detalles = [];

// --- INICIO: Generar nuevo número de Orden de Compra ---
if (!$id) { // Solo generar para nuevas órdenes
    $stmt_oc = $pdo->query("SELECT numero_orden FROM oc_ordenes ORDER BY id_orden DESC LIMIT 1");
    $ultimo_oc = $stmt_oc->fetchColumn();
    
    if ($ultimo_oc) {
        $numero = intval(substr($ultimo_oc, 3)) + 1;
    } else {
        $numero = 1;
    }
    $nuevo_numero_oc = 'OC-' . str_pad($numero, 7, '0', STR_PAD_LEFT);
}
// --- FIN: Generar número ---

if ($id > 0) {
    // Cargar datos de la orden de compra existente desde las tablas oc_ 
    $stmt = $pdo->prepare("SELECT * FROM oc_ordenes WHERE id_orden = ?");
    $stmt->execute([$id]);
    $compra = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt_detalles = $pdo->prepare("SELECT cd.*, p.nombre, p.codigo, p.stock, p.stock_minimo, p.codigo_barra FROM oc_detalle cd JOIN productos p ON cd.producto_id = p.id WHERE cd.id_orden = ?");
    $stmt_detalles->execute([$id]);
    $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
    
    // Asignar el numero_orden existente para mostrarlo en el form
    if ($compra) {
        $nuevo_numero_oc = $compra['numero_orden'];
    }
}

// Fetch data for dropdowns
$stmt_proveedores = $pdo->query("SELECT id, razon_social, condiciones_pago FROM proveedores WHERE activo = 1 ORDER BY razon_social");
$proveedores = $stmt_proveedores->fetchAll(PDO::FETCH_ASSOC);

// --- MODIFICADO: Usar la nueva tabla de depósitos ---
$stmt_depositos = $pdo->query("SELECT id_deposito, nombre_deposito FROM oc_depositos WHERE activo = 1 ORDER BY nombre_deposito");
$depositos = $stmt_depositos->fetchAll(PDO::FETCH_ASSOC);

$condiciones_pago = ['Contado', '7 días', '15 días', '30 días', '60 días'];

$stmt_estados = $pdo->query("SELECT id_estado, nombre_estado FROM oc_estados ORDER BY id_estado");
$estados = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $compra ? 'Editar' : 'Nueva'; ?> Orden de Compra</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .form-container { max-width: 900px; margin: 30px auto; background: #fff; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,.08); overflow: hidden; }
        .form-header { background: #0d6efd; color: #fff; padding: 16px 20px; }
        
        /* --- Estilos para la tabla de productos --- */
        /* Hacer la tabla más compacta */
        #form-compra .table > :not(caption) > * > * {
            padding: .4rem .4rem; /* Reduce el padding de todas las celdas */
            font-size: 0.9rem;     /* Reduce el tamaño de la fuente */
            vertical-align: middle;
        }

        /* Ajustar el ancho de las columnas */
        #form-compra .table th:nth-child(1) { width: 10%; } /* CB */
        #form-compra .table th:nth-child(2) { width: 25%; } /* Producto */
        #form-compra .table th:nth-child(3) { width: 15%; } /* Precio */
        #form-compra .table th:nth-child(4) { width: 10%; } /* Stock Actual */
        #form-compra .table th:nth-child(5) { width: 10%; } /* Stock Mínimo */
        #form-compra .table th:nth-child(6) { width: 10%; } /* Cantidad */
        #form-compra .table th:nth-child(7) { width: 15%; } /* Subtotal */
        #form-compra .table th:nth-child(8) { width: 5%; }  /* Acción */

        /* Ajustar los inputs para que no sean tan altos */
        #form-compra .table .form-control {
            font-size: 0.9rem;
            padding: .25rem .5rem;
            height: auto;
        }

        /* --- Nuevas reglas de alineación --- */
        #form-compra .table th {
            text-align: center;
        }
        #form-compra .table td:nth-child(3),
        #form-compra .table td:nth-child(4),
        #form-compra .table td:nth-child(5),
        #form-compra .table td:nth-child(6),
        #form-compra .table td:nth-child(7) {
            text-align: right;
        }
        #form-compra .table input[type="number"] {
            text-align: right;
        }
    </style>
</head>
<body>
<?php include "../../config/navbar_code.php"; ?>
    <div class="container form-container">
        <?php if (isset($_SESSION['error_message'])) : ?>
            <div class="alert alert-danger" role="alert">
                <?php
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success_message'])) : ?>
            <div class="alert alert-success" role="alert">
                <?php
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-12">
                <div class="form-header">
                    <h4 class="mb-0"><i class="fas fa-edit"></i> <?php echo $compra ? 'Editar' : 'Nueva'; ?> Orden de Compra</h4>
                </div>
                
                <form id="form-compra" action="gestionar_compra.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $compra['id'] ?? ''; ?>">
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle"></i> Información General</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Número de Orden</label>
                                    <input type="text" class="form-control" name="numero_orden" value="<?= htmlspecialchars($nuevo_numero_oc ?? '') ?>" readonly style="background-color: #e9ecef; font-weight: bold;">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Proveedor *</label>
                                    <select class="form-control" id="proveedor_id" name="proveedor_id" required>
                                        <option value="">Seleccionar proveedor</option>
                                        <?php foreach ($proveedores as $proveedor): ?>
                                            <option value="<?= $proveedor['id'] ?>" <?= ($compra && $compra['proveedor_id'] == $proveedor['id']) ? 'selected' : '' ?> data-condicion="<?= htmlspecialchars($proveedor['condiciones_pago']) ?>">
                                                <?= htmlspecialchars($proveedor['razon_social']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Fecha de O.Compra *</label>
                                    <input type="date" class="form-control" name="fecha_compra" 
                                           value="<?php echo $compra['fecha_compra'] ?? date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Condición de Pago</label>
                                    <select class="form-select" id="condicion_pago" name="condicion_pago">
                                        <?php foreach ($condiciones_pago as $condicion): ?>
                                            <option value="<?= $condicion ?>" <?= (($compra && $compra['condicion_pago'] == $condicion) || (!$compra && $condicion == 'Contado')) ? 'selected' : '' ?>>
                                                <?= $condicion ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select" name="estado_id" style="font-weight: bold;">
                                        <?php 
                                        $estado_seleccionado = $compra['estado_id'] ?? 1; // Asumimos 1 para el estado por defecto
                                        foreach ($estados as $estado): ?>
                                            <option value="<?= $estado['id_estado'] ?>" <?= ($estado_seleccionado == $estado['id_estado']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($estado['nombre_estado']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Depósito de Entrega</label>
                                    <select class="form-select" name="deposito_id">
                                        <option value="">Seleccionar depósito</option>
                                        <?php foreach ($depositos as $deposito): ?>
                                            <option value="<?= $deposito['id_deposito'] ?>" <?= ($compra && isset($compra['deposito_id']) && $compra['deposito_id'] == $deposito['id_deposito']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($deposito['nombre_deposito']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Observaciones</label>
                                    <textarea class="form-control" name="observaciones" rows="2"><?php echo $compra['observaciones'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5><i class="fas fa-box"></i> Productos</h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-end">
                                <div class="col-md-6">
                                    <label for="barcode-input" class="form-label">Buscar por Código de Barras</label>
                                    <input type="number" id="barcode-input" class="form-control" placeholder="Ingrese código de barras...">
                                </div>
                                <div class="col-md-6">
                                    <button type="button" id="open-search-modal-btn" class="btn btn-primary w-100">
                                        <i class="fas fa-search"></i> Buscar Producto (Nombre o Código)
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>CB</th>
                                            <th>Producto</th>
                                            <th>Precio Neto</th>
                                            <th>Stock Actual</th>
                                            <th>Stock Mínimo</th>
                                            <th>Cantidad</th>
                                            <th>Subtotal</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productos-tbody">
                                        <?php foreach ($detalles as $detalle): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($detalle['codigo_barra'] ?? 'N/A') ?></td>
                                                <td>
                                                    <input type="hidden" name="productos[id][]" value="<?= $detalle['producto_id'] ?>">
                                                    <input type="hidden" name="productos[codigo_barra][]" value="<?= htmlspecialchars($detalle['codigo_barra'] ?? '') ?>">
                                                    <?= htmlspecialchars($detalle['nombre']) ?>
                                                </td>
                                                <td><input type="number" class="form-control precio" name="productos[precio][]" value="<?= $detalle['precio_unitario'] ?>" step="0.01"></td>
                                                <td><input type="number" class="form-control" value="<?= $detalle['stock'] ?>" readonly></td>
                                                <td><input type="number" class="form-control" value="<?= $detalle['stock_minimo'] ?>" readonly></td>
                                                <td><input type="number" class="form-control cantidad" name="productos[cantidad][]" value="<?= $detalle['cantidad_pedida'] ?>"></td>
                                                <td class="subtotal">$<?= number_format($detalle['cantidad_pedida'] * $detalle['precio_unitario'], 2) ?></td>
                                                <td><button type="button" class="btn btn-danger btn-sm btn-remove">X</button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="row justify-content-end">
                                <div class="col-md-5">
                                    <div class="d-flex justify-content-between h5">
                                        <strong>Total:</strong>
                                        <span id="total-general">$0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Orden
                        </button>
                        <a href="compras.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Product Search Modal -->
    <div class="modal fade" id="productSearchModal" tabindex="-1" aria-labelledby="productSearchModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="productSearchModalLabel">Buscar Producto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
                <input type="text" id="modal-search-input" class="form-control" placeholder="Buscar por código o descripción...">
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="modal-search-results-tbody">
                        <!-- Results will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const proveedorSelect = document.getElementById('proveedor_id');
        const productosTbody = document.getElementById('productos-tbody');
        const condicionPagoSelect = document.getElementById('condicion_pago');
        const openModalBtn = document.getElementById('open-search-modal-btn');
        const modalSearchInput = document.getElementById('modal-search-input');
        const modalResultsTbody = document.getElementById('modal-search-results-tbody');

        function toggleSearchButtons() {
            const isProviderSelected = !!proveedorSelect.value;
            openModalBtn.disabled = !isProviderSelected;
        }

        proveedorSelect.addEventListener('change', function() {
            toggleSearchButtons();
            const selectedOption = this.options[this.selectedIndex];
            const condicion = selectedOption.getAttribute('data-condicion');
            if (condicion) {
                condicionPagoSelect.value = condicion;
            }
        });

        openModalBtn.addEventListener('click', function() {
            const productSearchModalEl = document.getElementById('productSearchModal');
            if (productSearchModalEl && typeof bootstrap !== 'undefined') {
                const productSearchModal = new bootstrap.Modal(productSearchModalEl);
                productSearchModal.show();
            } else {
                alert('Error al inicializar la ventana de búsqueda. Verifique la librería de Bootstrap.');
            }
        });

        const productSearchModalEl = document.getElementById('productSearchModal');
        productSearchModalEl.addEventListener('shown.bs.modal', function () {
            modalSearchInput.focus();
            const barcodeValue = document.getElementById('barcode-input').value;
            if(barcodeValue) {
                modalSearchInput.value = barcodeValue;
                performModalSearch();
            }
        });

        // Abrir modal con Enter desde el campo de código de barras
        const barcodeInput = document.getElementById('barcode-input');
        barcodeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                openModalBtn.click();
            }
        });

        modalSearchInput.addEventListener('keyup', performModalSearch);

        function performModalSearch() {
            const term = modalSearchInput.value;
            const proveedor_id = proveedorSelect.value;

            // Mostrar guía si no hay término aún
            if (term.length === 0) {
                 modalResultsTbody.innerHTML = '<tr><td colspan="6">Ingrese un término para buscar...</td></tr>';
            }

            fetch(`../../ajax/buscar_productos.php?term=${encodeURIComponent(term)}&proveedor_id=${encodeURIComponent(proveedor_id)}`)
                .then(response => {
                    if (!response.ok) throw new Error('Error en la búsqueda');
                    return response.json();
                })
                .then(data => {
                    let html = '';
                    if(Array.isArray(data) && data.length > 0) {
                        data.forEach(prod => {
                            html += `<tr>
                                <td>${prod.codigo || 'N/A'}</td>
                                <td>${prod.nombre || 'N/A'}</td>
                                <td>${prod.categoria_nombre || 'N/A'}</td>
                                <td>${prod.precio_compra || '0.00'}</td>
                                <td>${prod.stock || '0'}</td>
                                <td><button type="button" class="btn btn-success btn-sm select-product-btn" data-producto='${JSON.stringify(prod)}'>Seleccionar</button></td>
                            </tr>`;
                        });
                    } else {
                        html = '<tr><td colspan="6">No se encontraron productos.</td></tr>';
                    }
                    modalResultsTbody.innerHTML = html;
                })
                .catch(err => {
                    console.error(err);
                    modalResultsTbody.innerHTML = '<tr><td colspan="6" class="text-danger">Ocurrió un error al buscar. Intente nuevamente.</td></tr>';
                });
        }

        modalResultsTbody.addEventListener('click', function(e) {
            if (e.target.classList.contains('select-product-btn')) {
                const producto = JSON.parse(e.target.getAttribute('data-producto'));
                agregarFilaProducto(producto);
                const productSearchModal = bootstrap.Modal.getInstance(productSearchModalEl);
                productSearchModal.hide();
                modalSearchInput.value = '';
                modalResultsTbody.innerHTML = '';
            }
        });

        productosTbody.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remove')) {
                e.target.closest('tr').remove();
                calcularTotales();
            }
        });

        productosTbody.addEventListener('input', function(e) {
            if (e.target.classList.contains('cantidad') || e.target.classList.contains('precio')) {
                const tr = e.target.closest('tr');
                const cantidad = tr.querySelector('.cantidad').value;
                const precio = tr.querySelector('.precio').value;
                const subtotal = (cantidad * precio).toFixed(2);
                tr.querySelector('.subtotal').textContent = `$${subtotal}`;
                calcularTotales();
            }
        });

        function agregarFilaProducto(producto) {
            const necesidad = (parseFloat(producto.stock_minimo) || 0) - (parseFloat(producto.stock) || 0);
            const cantidad_a_comprar = necesidad > 0 ? necesidad : 1;

            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>${producto.codigo_barra || 'N/A'}</td>
                <td>
                    <input type="hidden" name="productos[id][]" value="${producto.id}">
                    <input type="hidden" name="productos[codigo_barra][]" value="${producto.codigo_barra || ''}">
                    ${producto.nombre}
                </td>
                <td><input type="number" class="form-control precio" name="productos[precio][]" value="${producto.precio_compra || '0.00'}" step="0.01"></td>
                <td><input type="number" class="form-control" value="${producto.stock || 0}" readonly></td>
                <td><input type="number" class="form-control" value="${producto.stock_minimo || 0}" readonly></td>
                <td><input type="number" class="form-control cantidad" name="productos[cantidad][]" value="${cantidad_a_comprar}"></td>
                <td class="subtotal">${(cantidad_a_comprar * (parseFloat(producto.precio_compra) || 0)).toFixed(2)}</td>
                <td><button type="button" class="btn btn-danger btn-sm btn-remove">X</button></td>
            `;
            productosTbody.appendChild(newRow);
            calcularTotales();
        }

        function calcularTotales() {
            let subtotal = 0;
            productosTbody.querySelectorAll('tr').forEach(tr => {
                const cantidad = parseFloat(tr.querySelector('.cantidad').value) || 0;
                const precio = parseFloat(tr.querySelector('.precio').value) || 0;
                subtotal += cantidad * precio;
            });

            const total = subtotal; // El total es igual al subtotal

            // document.getElementById('total-subtotal').textContent = `${subtotal.toFixed(2)}`;
            document.getElementById('total-general').textContent = `${total.toFixed(2)}`;
        }

        // Inicializar
        toggleSearchButtons();
        calcularTotales();
    });
    </script>
</body>
</html>
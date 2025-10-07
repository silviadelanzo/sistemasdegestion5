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
    // Buscar el número más alto usado tanto en órdenes existentes como en auditoría
    // Solo considerar números con formato OC-0000000 (7 dígitos)
    $stmt_oc = $pdo->query("
        SELECT MAX(CAST(SUBSTRING(numero_orden, 4) AS UNSIGNED)) as max_numero
        FROM (
            SELECT numero_orden FROM oc_ordenes 
            WHERE numero_orden REGEXP '^OC-[0-9]{7}$'
            UNION ALL
            SELECT JSON_UNQUOTE(JSON_EXTRACT(detalle, '$.numero_orden')) as numero_orden 
            FROM auditoria 
            WHERE tabla_afectada = 'oc_ordenes' 
            AND JSON_EXTRACT(detalle, '$.numero_orden') IS NOT NULL
            AND JSON_UNQUOTE(JSON_EXTRACT(detalle, '$.numero_orden')) REGEXP '^OC-[0-9]{7}$'
        ) AS todos_numeros
    ");
    $max_numero = $stmt_oc->fetchColumn();
    
    $numero = ($max_numero ? $max_numero : 0) + 1;
    $nuevo_numero_oc = 'OC-' . str_pad($numero, 7, '0', STR_PAD_LEFT);
}
// --- FIN: Generar número ---

if ($id > 0) {
    // Cargar datos de la orden de compra existente desde las tablas oc_ 
    $stmt = $pdo->prepare("SELECT *, fecha_orden as fecha_compra FROM oc_ordenes WHERE id_orden = ?");
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
        
        /* Estilos para el modal de selección múltiple */
        #selectMultipleProductsModal .table-responsive {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }
        
        #selectMultipleProductsModal .sticky-top {
            background: #f8f9fa !important;
            z-index: 10;
        }
        
        .cantidad-modal {
            text-align: center;
        }
        
        .product-checkbox {
            transform: scale(1.2);
        }
        
        /* Indicadores visuales para stock */
        .text-danger {
            font-weight: bold;
        }
        
        .text-warning {
            font-weight: bold;
        }
        
        .text-success {
            font-weight: bold;
        }
        
        /* Proveedor deshabilitado */
        select:disabled {
            background-color: #e9ecef;
            opacity: 0.7;
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
                
                <form id="form-compra" action="gestionar_compra_oc.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $compra['id_orden'] ?? ''; ?>">
                    <!-- Campo oculto para el proveedor cuando está deshabilitado -->
                    <input type="hidden" id="proveedor_id_hidden" name="proveedor_id_hidden" value="<?php echo $compra['proveedor_id'] ?? ''; ?>">
                    
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
                                            <option value="<?= $proveedor['id'] ?>" <?= ($compra && $compra['proveedor_id'] == $proveedor['id']) ? 'selected' : '' ?> data-condicion="<?= htmlspecialchars($proveedor['condiciones_pago'] ?? '') ?>">
                                                <?= htmlspecialchars($proveedor['razon_social'] ?? '') ?>
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
                                    <label class="form-label">Condición de Pago *</label>
                                    <select class="form-select" id="condicion_pago" name="condicion_pago" required>
                                        <option value="">Seleccionar condición</option>
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
                                    <label class="form-label">Depósito de Entrega *</label>
                                    <select class="form-select" name="deposito_id" required>
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
                            <div class="row align-items-end mb-3">
                                <div class="col-md-4">
                                    <label for="barcode-input" class="form-label">Buscar por Código de Barras</label>
                                    <input type="number" id="barcode-input" class="form-control" placeholder="Ingrese código de barras...">
                                </div>
                                <div class="col-md-4">
                                    <button type="button" id="open-search-modal-btn" class="btn btn-primary w-100">
                                        <i class="fas fa-search"></i> Buscar Producto Específico
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" id="cargar-todos-productos-btn" class="btn btn-success w-100" disabled>
                                        <i class="fas fa-list"></i> Ver Todos los Productos del Proveedor
                                    </button>
                                </div>
                            </div>
                            <div id="productos-info" class="alert alert-info" style="display: none;">
                                <i class="fas fa-info-circle"></i> Los productos con stock bajo se cargan automáticamente al seleccionar el proveedor.
                                <button type="button" id="limpiar-productos-btn" class="btn btn-sm btn-outline-secondary float-end">
                                    <i class="fas fa-trash"></i> Limpiar Lista
                                </button>
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
                                                <td><input type="number" class="form-control precio" name="productos[precio][]" value="<?= $detalle['precio_unitario'] ?>" step="0.01" readonly></td>
                                                <td><input type="number" class="form-control" value="<?= $detalle['stock'] ?>" readonly></td>
                                                <td><input type="number" class="form-control" value="<?= $detalle['stock_minimo'] ?>" readonly></td>
                                                <td><input type="number" class="form-control cantidad" name="productos[cantidad][]" value="<?= (int)$detalle['cantidad'] ?>" min="1" step="1"></td>
                                                <td class="subtotal">$<?= number_format((int)$detalle['cantidad'] * $detalle['precio_unitario'], 2) ?></td>
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
                            <th>Tipo</th>
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

    <!-- Modal para seleccionar múltiples productos -->
    <div class="modal fade" id="selectMultipleProductsModal" tabindex="-1" aria-labelledby="selectMultipleProductsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="selectMultipleProductsModalLabel">Seleccionar Productos del Proveedor</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Seleccione los productos que desea agregar a la orden de compra. Los productos con stock bajo aparecen marcados por defecto.
            </div>
            <div class="mb-3">
                <button type="button" id="select-all-products" class="btn btn-sm btn-outline-primary me-2">
                    <i class="fas fa-check-square"></i> Seleccionar Todos
                </button>
                <button type="button" id="unselect-all-products" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="fas fa-square"></i> Deseleccionar Todos
                </button>
                <button type="button" id="select-low-stock-products" class="btn btn-sm btn-outline-warning">
                    <i class="fas fa-exclamation-triangle"></i> Solo Stock Bajo
                </button>
            </div>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover table-sm">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th width="5%">
                                <input type="checkbox" id="select-all-checkbox" class="form-check-input">
                            </th>
                            <th width="10%">Código</th>
                            <th width="25%">Descripción</th>
                            <th width="15%">Categoría</th>
                            <th width="10%">Precio</th>
                            <th width="8%">Stock</th>
                            <th width="8%">Min</th>
                            <th width="8%">Cantidad</th>
                            <th width="11%">Estado Stock</th>
                        </tr>
                    </thead>
                    <tbody id="multiple-products-tbody">
                        <!-- Products will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" id="add-selected-products" class="btn btn-primary">
                <i class="fas fa-plus"></i> Agregar Productos Seleccionados
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para seleccionar proveedor de un producto -->
    <div class="modal fade" id="selectProviderModal" tabindex="-1" aria-labelledby="selectProviderModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="selectProviderModalLabel">
                <i class="fas fa-truck"></i> Seleccionar Proveedor para el Producto
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong id="producto-info"></strong> está disponible con múltiples proveedores. 
                Seleccione el proveedor desde el cual desea comprarlo.
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">Sel.</th>
                            <th width="20%">Proveedor</th>
                            <th width="10%">Tipo</th>
                            <th width="15%">Precio</th>
                            <th width="15%">Condiciones</th>
                            <th width="10%">Entrega</th>
                            <th width="15%">Última Compra</th>
                            <th width="10%">Contacto</th>
                        </tr>
                    </thead>
                    <tbody id="provider-options-tbody">
                        <!-- Provider options will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" id="confirm-provider-selection" class="btn btn-primary" disabled>
                <i class="fas fa-check"></i> Agregar con Proveedor Seleccionado
            </button>
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
        const cargarTodosBtn = document.getElementById('cargar-todos-productos-btn');
        const modalSearchInput = document.getElementById('modal-search-input');
        const modalResultsTbody = document.getElementById('modal-search-results-tbody');
        const productosInfo = document.getElementById('productos-info');
        
        // Nuevos elementos para el modal de selección múltiple
        const multipleProductsModal = document.getElementById('selectMultipleProductsModal');
        const multipleProductsTbody = document.getElementById('multiple-products-tbody');
        const selectAllCheckbox = document.getElementById('select-all-checkbox');
        const addSelectedProductsBtn = document.getElementById('add-selected-products');

        function toggleSearchButtons() {
            const isProviderSelected = !!proveedorSelect.value;
            const hasProducts = productosTbody.children.length > 0;
            
            openModalBtn.disabled = !isProviderSelected;
            cargarTodosBtn.disabled = !isProviderSelected;
            
            // Bloquear el cambio de proveedor si hay productos cargados
            proveedorSelect.disabled = hasProducts;
            
            // Actualizar campo oculto cuando el proveedor está deshabilitado
            const hiddenProveedorField = document.getElementById('proveedor_id_hidden');
            if (hasProducts && isProviderSelected) {
                hiddenProveedorField.value = proveedorSelect.value;
            } else if (!hasProducts) {
                hiddenProveedorField.value = '';
            }
            
            if (isProviderSelected) {
                productosInfo.style.display = 'block';
            } else {
                productosInfo.style.display = 'none';
            }
        }

        proveedorSelect.addEventListener('change', function() {
            toggleSearchButtons();
            const selectedOption = this.options[this.selectedIndex];
            const condicion = selectedOption.getAttribute('data-condicion');
            if (condicion && condicionPagoSelect.value === '') {
                condicionPagoSelect.value = condicion;
            }
            
            // Nueva funcionalidad: Cargar productos relacionados con el proveedor
            if (this.value) {
                cargarProductosProveedor(this.value);
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

        cargarTodosBtn.addEventListener('click', function() {
            if (proveedorSelect.value) {
                abrirModalSeleccionMultiple(proveedorSelect.value);
            }
        });

        document.getElementById('limpiar-productos-btn').addEventListener('click', function() {
            if (confirm('¿Está seguro de que desea limpiar todos los productos de la lista?')) {
                productosTbody.innerHTML = '';
                calcularTotales();
                toggleSearchButtons(); // Reactivar el select de proveedor
            }
        });

        // Event listeners para el modal de selección múltiple
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = multipleProductsTbody.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            actualizarContadorSeleccionados();
        });

        document.getElementById('select-all-products').addEventListener('click', function() {
            const checkboxes = multipleProductsTbody.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = true);
            selectAllCheckbox.checked = true;
            actualizarContadorSeleccionados();
        });

        document.getElementById('unselect-all-products').addEventListener('click', function() {
            const checkboxes = multipleProductsTbody.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = false);
            selectAllCheckbox.checked = false;
            actualizarContadorSeleccionados();
        });

        document.getElementById('select-low-stock-products').addEventListener('click', function() {
            const checkboxes = multipleProductsTbody.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const statusCell = row.querySelector('td:last-child');
                checkbox.checked = statusCell.textContent.includes('Stock Bajo');
            });
            actualizarContadorSeleccionados();
        });

        // Event listener para cambios en checkboxes individuales
        multipleProductsTbody.addEventListener('change', function(e) {
            if (e.target.classList.contains('product-checkbox')) {
                actualizarContadorSeleccionados();
                
                // Actualizar el checkbox maestro
                const checkboxes = multipleProductsTbody.querySelectorAll('.product-checkbox');
                const checkedBoxes = multipleProductsTbody.querySelectorAll('.product-checkbox:checked');
                selectAllCheckbox.checked = checkboxes.length === checkedBoxes.length;
                selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
            }
        });

        // Agregar productos seleccionados
        addSelectedProductsBtn.addEventListener('click', function() {
            const checkboxesSeleccionados = multipleProductsTbody.querySelectorAll('.product-checkbox:checked');
            let productosAgregados = 0;
            
            checkboxesSeleccionados.forEach(checkbox => {
                const producto = JSON.parse(checkbox.getAttribute('data-producto'));
                const row = checkbox.closest('tr');
                const cantidadInput = row.querySelector('.cantidad-modal');
                const cantidad = parseInt(cantidadInput.value) || 1;
                
                // Establecer la cantidad en el objeto producto
                producto.cantidad_sugerida = cantidad;
                // En el contexto de selección múltiple ya sabemos el proveedor
                producto.proveedor_contexto = proveedorSelect.value;
                
                agregarFilaProductoDirecto(producto);
                productosAgregados++;
            });
            
            if (productosAgregados > 0) {
                calcularTotales();
                toggleSearchButtons(); // Actualizar estado de los botones
                
                // Cerrar el modal
                const modal = bootstrap.Modal.getInstance(multipleProductsModal);
                modal.hide();
                
                // Mostrar mensaje de éxito
                const successRow = document.createElement('tr');
                successRow.innerHTML = `<td colspan="8" class="text-center text-success"><i class="fas fa-check-circle"></i> ${productosAgregados} productos agregados exitosamente.</td>`;
                productosTbody.insertBefore(successRow, productosTbody.firstChild);
                
                setTimeout(() => {
                    successRow.remove();
                }, 3000);
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
                 modalResultsTbody.innerHTML = '<tr><td colspan="7">Ingrese un término para buscar...</td></tr>';
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
                                <td><strong>${prod.proveedor_tipo || 'A'}</strong></td>
                                <td><button type="button" class="btn btn-success btn-sm select-product-btn" data-producto='${JSON.stringify(prod)}'>Seleccionar</button></td>
                            </tr>`;
                        });
                    } else {
                        html = '<tr><td colspan="7">No se encontraron productos.</td></tr>';
                    }
                    modalResultsTbody.innerHTML = html;
                })
                .catch(err => {
                    console.error(err);
                    modalResultsTbody.innerHTML = '<tr><td colspan="7" class="text-danger">Ocurrió un error al buscar. Intente nuevamente.</td></tr>';
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
                toggleSearchButtons(); // Actualizar estado después de remover producto
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

        // Variables para el modal de selección de proveedor
        let productoParaAgregar = null;
        const selectProviderModal = document.getElementById('selectProviderModal');
        const providerOptionsModal = new bootstrap.Modal(selectProviderModal);
        const providerOptionsTbody = document.getElementById('provider-options-tbody');
        const confirmProviderBtn = document.getElementById('confirm-provider-selection');

        function verificarYAgregarProducto(producto) {
            // Si ya se especificó un proveedor en el contexto de una orden específica, agregar directamente
            const proveedorActual = proveedorSelect.value;
            if (proveedorActual && producto.proveedor_contexto === proveedorActual) {
                agregarFilaProductoDirecto(producto);
                return;
            }

            // Verificar si el producto tiene proveedores alternativos
            fetch(`../../ajax/buscar_proveedores_producto.php?producto_id=${producto.id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.proveedores.length > 1) {
                        // Mostrar modal de selección de proveedor
                        mostrarModalSeleccionProveedor(data.producto, data.proveedores);
                    } else {
                        // Solo tiene un proveedor, agregar directamente
                        agregarFilaProductoDirecto(producto);
                    }
                })
                .catch(error => {
                    console.error('Error al verificar proveedores:', error);
                    // En caso de error, agregar directamente
                    agregarFilaProductoDirecto(producto);
                });
        }

        function mostrarModalSeleccionProveedor(producto, proveedores) {
            productoParaAgregar = producto;
            
            // Actualizar información del producto en el modal
            document.getElementById('producto-info').textContent = `${producto.codigo || ''} - ${producto.producto_nombre}`;
            
            // Limpiar tabla de proveedores
            providerOptionsTbody.innerHTML = '';
            
            // Poblar tabla con proveedores
            proveedores.forEach(proveedor => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <input type="radio" name="proveedor_seleccionado" value="${proveedor.id}" 
                               class="form-check-input provider-radio" data-proveedor='${JSON.stringify(proveedor)}'>
                    </td>
                    <td><strong>${proveedor.razon_social}</strong></td>
                    <td>
                        <span class="badge ${proveedor.tipo_proveedor === 'Principal' ? 'bg-primary' : 'bg-secondary'}">
                            ${proveedor.tipo_proveedor}
                        </span>
                    </td>
                    <td>
                        <strong>$${proveedor.precio_compra ? parseFloat(proveedor.precio_compra).toFixed(2) : 'N/D'}</strong>
                    </td>
                    <td>${proveedor.condiciones_pago || 'N/D'}</td>
                    <td>${proveedor.tiempo_entrega_dias ? proveedor.tiempo_entrega_dias + ' días' : 'N/D'}</td>
                    <td>${proveedor.fecha_ultima_compra || 'Nunca'}</td>
                    <td>
                        <small>
                            ${proveedor.telefono ? '📞 ' + proveedor.telefono + '<br>' : ''}
                            ${proveedor.email ? '📧 ' + proveedor.email : ''}
                        </small>
                    </td>
                `;
                providerOptionsTbody.appendChild(row);
            });
            
            // Mostrar el modal
            providerOptionsModal.show();
        }

        // Event listener para los radio buttons de proveedores
        providerOptionsTbody.addEventListener('change', function(e) {
            if (e.target.classList.contains('provider-radio')) {
                confirmProviderBtn.disabled = false;
            }
        });

        // Event listener para confirmar selección de proveedor
        confirmProviderBtn.addEventListener('click', function() {
            const selectedRadio = providerOptionsTbody.querySelector('input[name="proveedor_seleccionado"]:checked');
            if (selectedRadio && productoParaAgregar) {
                const proveedorSeleccionado = JSON.parse(selectedRadio.getAttribute('data-proveedor'));
                
                // Actualizar el producto con información del proveedor seleccionado
                productoParaAgregar.precio_compra = proveedorSeleccionado.precio_compra || productoParaAgregar.precio_compra;
                productoParaAgregar.proveedor_seleccionado = proveedorSeleccionado;
                
                // Agregar el producto
                agregarFilaProductoDirecto(productoParaAgregar);
                
                // Cerrar modal y limpiar
                providerOptionsModal.hide();
                productoParaAgregar = null;
                confirmProviderBtn.disabled = true;
            }
        });

        function agregarFilaProductoDirecto(producto) {
            // Usar cantidad sugerida del modal si existe, sino calcular automáticamente
            let cantidad_a_comprar;
            if (producto.cantidad_sugerida) {
                cantidad_a_comprar = parseInt(producto.cantidad_sugerida);
            } else {
                const necesidad = (parseFloat(producto.stock_minimo) || 0) - (parseFloat(producto.stock) || 0);
                cantidad_a_comprar = necesidad > 0 ? Math.ceil(necesidad) : 1;
            }

            const newRow = document.createElement('tr');
            const proveedorInfo = producto.proveedor_seleccionado ? 
                `<small class="text-muted">Proveedor: ${producto.proveedor_seleccionado.razon_social}</small><br>` : '';
            
            newRow.innerHTML = `
                <td>${producto.codigo_barra || 'N/A'}</td>
                <td>
                    <input type="hidden" name="productos[id][]" value="${producto.id}">
                    <input type="hidden" name="productos[codigo_barra][]" value="${producto.codigo_barra || ''}">
                    ${proveedorInfo}${producto.nombre}
                </td>
                <td><input type="number" class="form-control precio" name="productos[precio][]" value="${producto.precio_compra || '0.00'}" step="0.01" readonly></td>
                <td><input type="number" class="form-control" value="${producto.stock || 0}" readonly></td>
                <td><input type="number" class="form-control" value="${producto.stock_minimo || 0}" readonly></td>
                <td><input type="number" class="form-control cantidad" name="productos[cantidad][]" value="${cantidad_a_comprar}" min="1" step="1"></td>
                <td class="subtotal">$${(cantidad_a_comprar * (parseFloat(producto.precio_compra) || 0)).toFixed(2)}</td>
                <td><button type="button" class="btn btn-danger btn-sm btn-remove">X</button></td>
            `;
            productosTbody.appendChild(newRow);
            calcularTotales();
        }

        // Función de compatibilidad (mantener la función original)
        function agregarFilaProducto(producto) {
            verificarYAgregarProducto(producto);
        }

        function cargarProductosProveedor(proveedorId) {
            console.log('Cargando productos para proveedor ID:', proveedorId);
            
            // Mostrar indicador de carga
            const loadingRow = document.createElement('tr');
            loadingRow.innerHTML = '<td colspan="8" class="text-center text-info"><i class="fas fa-spinner fa-spin"></i> Cargando productos del proveedor...</td>';
            productosTbody.appendChild(loadingRow);
            
            const url = `../../ajax/buscar_productos.php?proveedor_id=${encodeURIComponent(proveedorId)}`;
            console.log('URL de consulta:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Respuesta recibida:', response.status);
                    if (!response.ok) throw new Error('Error al cargar productos');
                    return response.json();
                })
                .then(data => {
                    console.log('Datos recibidos:', data);
                    // Remover indicador de carga
                    loadingRow.remove();
                    
                    if (Array.isArray(data) && data.length > 0) {
                        // Limpiar productos existentes primero (opcional)
                        // productosTbody.innerHTML = '';
                        
                        // Agregar productos del proveedor que necesiten stock o sean importantes
                        data.forEach(producto => {
                            const stockActual = parseFloat(producto.stock) || 0;
                            const stockMinimo = parseFloat(producto.stock_minimo) || 0;
                            
                            // Solo agregar productos que necesiten reposición o tengan stock bajo
                            if (stockActual <= stockMinimo) {
                                // En este contexto ya sabemos el proveedor, agregar directamente
                                producto.proveedor_contexto = proveedorId;
                                agregarFilaProductoDirecto(producto);
                            }
                        });
                        
                        if (data.filter(p => (parseFloat(p.stock) || 0) <= (parseFloat(p.stock_minimo) || 0)).length === 0) {
                            // Si no hay productos con stock bajo, mostrar mensaje informativo
                            const infoRow = document.createElement('tr');
                            infoRow.innerHTML = '<td colspan="8" class="text-center text-success"><i class="fas fa-check-circle"></i> Todos los productos de este proveedor tienen stock suficiente. Use el botón "Buscar Producto" para agregar productos específicos.</td>';
                            productosTbody.appendChild(infoRow);
                            
                            // Remover el mensaje después de 3 segundos
                            setTimeout(() => {
                                infoRow.remove();
                            }, 3000);
                        }
                        
                        calcularTotales();
                        toggleSearchButtons(); // Actualizar estado después de cargar productos
                    } else {
                        // No hay productos para este proveedor
                        const noDataRow = document.createElement('tr');
                        noDataRow.innerHTML = '<td colspan="8" class="text-center text-warning"><i class="fas fa-exclamation-triangle"></i> No se encontraron productos asociados a este proveedor.</td>';
                        productosTbody.appendChild(noDataRow);
                        
                        setTimeout(() => {
                            noDataRow.remove();
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    loadingRow.remove();
                    
                    const errorRow = document.createElement('tr');
                    errorRow.innerHTML = '<td colspan="8" class="text-center text-danger"><i class="fas fa-exclamation-circle"></i> Error al cargar productos del proveedor. Intente nuevamente.</td>';
                    productosTbody.appendChild(errorRow);
                    
                    setTimeout(() => {
                        errorRow.remove();
                    }, 3000);
                });
        }

        function abrirModalSeleccionMultiple(proveedorId) {
            console.log('Abriendo modal de selección múltiple para proveedor ID:', proveedorId);
            
            // Limpiar contenido anterior
            multipleProductsTbody.innerHTML = '<tr><td colspan="9" class="text-center text-info"><i class="fas fa-spinner fa-spin"></i> Cargando productos...</td></tr>';
            
            // Mostrar el modal
            const modal = new bootstrap.Modal(multipleProductsModal);
            modal.show();
            
            const url = `../../ajax/buscar_productos.php?proveedor_id=${encodeURIComponent(proveedorId)}`;
            console.log('URL de consulta completa:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Respuesta recibida (modal):', response.status);
                    if (!response.ok) throw new Error('Error al cargar productos');
                    return response.json();
                })
                .then(data => {
                    console.log('Datos recibidos (modal):', data);
                    
                    multipleProductsTbody.innerHTML = '';
                    
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(producto => {
                            const stockActual = parseFloat(producto.stock) || 0;
                            const stockMinimo = parseFloat(producto.stock_minimo) || 0;
                            const necesitaStock = stockActual <= stockMinimo;
                            const cantidadSugerida = necesitaStock ? Math.max(1, stockMinimo - stockActual) : 1;
                            
                            const statusClass = necesitaStock ? 'text-danger' : stockActual <= (stockMinimo * 1.5) ? 'text-warning' : 'text-success';
                            const statusText = necesitaStock ? 'Stock Bajo' : stockActual <= (stockMinimo * 1.5) ? 'Stock Medio' : 'Stock OK';
                            
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>
                                    <input type="checkbox" class="form-check-input product-checkbox" 
                                           data-producto='${JSON.stringify(producto)}' 
                                           ${necesitaStock ? 'checked' : ''}>
                                </td>
                                <td>${producto.codigo || 'N/A'}</td>
                                <td>${producto.nombre}</td>
                                <td>${producto.categoria_nombre || 'N/A'}</td>
                                <td>$${parseFloat(producto.precio_compra || 0).toFixed(2)}</td>
                                <td class="text-end">${stockActual}</td>
                                <td class="text-end">${stockMinimo}</td>
                                <td>
                                    <input type="number" class="form-control form-control-sm cantidad-modal" 
                                           value="${cantidadSugerida}" min="1" style="width: 70px;">
                                </td>
                                <td class="${statusClass}">
                                    <strong>${statusText}</strong>
                                </td>
                            `;
                            multipleProductsTbody.appendChild(row);
                        });
                        
                        // Actualizar contador de seleccionados
                        actualizarContadorSeleccionados();
                        
                    } else {
                        multipleProductsTbody.innerHTML = '<tr><td colspan="9" class="text-center text-warning">No se encontraron productos para este proveedor.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    multipleProductsTbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Error al cargar productos. Intente nuevamente.</td></tr>';
                });
        }

        function actualizarContadorSeleccionados() {
            const checkboxes = multipleProductsTbody.querySelectorAll('.product-checkbox');
            const seleccionados = multipleProductsTbody.querySelectorAll('.product-checkbox:checked');
            
            const btnText = `<i class="fas fa-plus"></i> Agregar Productos Seleccionados (${seleccionados.length}/${checkboxes.length})`;
            addSelectedProductsBtn.innerHTML = btnText;
            addSelectedProductsBtn.disabled = seleccionados.length === 0;
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

        // Validación del formulario antes de enviar
        document.getElementById('form-compra').addEventListener('submit', function(e) {
            const proveedorId = proveedorSelect.value;
            const proveedorIdHidden = document.getElementById('proveedor_id_hidden').value;
            const deposito = document.querySelector('select[name="deposito_id"]').value;
            const condicionPago = document.querySelector('select[name="condicion_pago"]').value;
            const hasProducts = productosTbody.children.length > 0;
            
            let errores = [];
            
            // Validar proveedor: debe estar seleccionado en el campo visible o en el oculto
            if (!proveedorId && !proveedorIdHidden) {
                errores.push('Debe seleccionar un proveedor');
            }
            
            if (!deposito) {
                errores.push('Debe seleccionar un depósito de entrega');
            }
            
            if (!condicionPago) {
                errores.push('Debe seleccionar una condición de pago');
            }
            
            if (!hasProducts) {
                errores.push('Debe agregar al menos un producto a la orden');
            }
            
            if (errores.length > 0) {
                e.preventDefault();
                alert('Errores encontrados:\n\n• ' + errores.join('\n• ') + '\n\nPor favor corrija estos errores antes de continuar.');
                return false;
            }
            
            // Debug: Mostrar información de productos antes de enviar
            const productRows = productosTbody.children.length;
            console.log('Productos a enviar:', productRows);
            
            // Confirmar envío con información de productos
            if (!confirm(`¿Está seguro de que desea guardar esta orden de compra?\n\nProductos: ${productRows}`)) {
                e.preventDefault();
                return false;
            }
        });

        // Inicializar
        toggleSearchButtons();
        calcularTotales();
    });
    </script>
</body>
</html>
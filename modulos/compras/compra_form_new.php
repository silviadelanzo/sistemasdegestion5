<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');
require_once '../../config/navbar_code.php';

$pdo = conectarDB();
$id = $_GET['id'] ?? 0;
$compra = null;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM compras WHERE id = ?");
    $stmt->execute([$id]);
    $compra = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener datos para los selectores
$proveedores = $pdo->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC);
$paises = $pdo->query("SELECT * FROM paises ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$provincias = $pdo->query("SELECT * FROM provincias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$ciudades = $pdo->query("SELECT * FROM ciudades ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$productos = $pdo->query("SELECT * FROM productos WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $compra ? 'Editar' : 'Nueva'; ?> Orden de Compra - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        :root {
            --primary-color: #0074D9;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            margin-top: 20px;
        }

        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .card-custom:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1rem 1.5rem;
        }

        .whatsapp-btn {
            background-color: #25D366;
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .whatsapp-btn:hover {
            background-color: #1da851;
            transform: scale(1.05);
            color: white;
        }

        .btn-nuevo {
            background-color: var(--success-color);
            border: none;
            color: white;
            border-radius: 8px;
            padding: 5px 15px;
            font-size: 0.85rem;
            margin-left: 10px;
        }

        .btn-nuevo:hover {
            background-color: #218838;
            color: white;
        }

        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 5px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 116, 217, 0.25);
        }

        .producto-row {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .producto-row:hover {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-eliminar {
            background-color: var(--danger-color);
            border: none;
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
        }

        .btn-eliminar:hover {
            background-color: #c82333;
            color: white;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .phone-input {
            position: relative;
            display: flex;
            align-items: center;
        }

        .phone-prefix {
            width: 85px;
            height: 38px;
            border: 1px solid #ddd;
            border-right: none;
            border-radius: 4px 0 0 4px;
            background: white;
            font-size: 0.85rem;
            padding: 0 5px;
            flex-shrink: 0;
        }

        .phone-number {
            border-radius: 0 4px 4px 0;
            border-left: none;
            flex: 1;
        }

        .total-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container-fluid main-container">
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="text-primary">
                        <i class="fas fa-shopping-cart me-2"></i>
                        <?php echo $compra ? 'Editar' : 'Nueva'; ?> Orden de Compra
                    </h1>
                    <div>
                        <a href="compras.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>

                <form id="form-compra" method="POST" action="gestionar_compra.php">
                    <input type="hidden" name="action" value="<?php echo $compra ? 'actualizar' : 'crear'; ?>">
                    <input type="hidden" name="id" value="<?php echo $compra['id'] ?? ''; ?>">

                    <!-- Información del Proveedor -->
                    <div class="card card-custom">
                        <div class="card-header card-header-custom">
                            <h5 class="mb-0">
                                <i class="fas fa-truck me-2"></i>
                                Información del Proveedor
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">Proveedor *</label>
                                    <div class="d-flex align-items-center">
                                        <select class="form-select" name="proveedor_id" id="proveedor_id" required style="flex: 1;">
                                            <option value="">-- Seleccionar Proveedor --</option>
                                            <?php foreach ($proveedores as $proveedor): ?>
                                                <option value="<?php echo $proveedor['id']; ?>"
                                                    data-razon="<?php echo htmlspecialchars($proveedor['razon_social']); ?>"
                                                    data-telefono="<?php echo htmlspecialchars($proveedor['telefono']); ?>"
                                                    data-whatsapp="<?php echo htmlspecialchars($proveedor['whatsapp'] ?? ''); ?>"
                                                    data-email="<?php echo htmlspecialchars($proveedor['email']); ?>"
                                                    <?php echo (isset($compra['proveedor_id']) && $compra['proveedor_id'] == $proveedor['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($proveedor['razon_social']); ?>
                                                    <?php if ($proveedor['nombre_comercial']): ?>
                                                        (<?php echo htmlspecialchars($proveedor['nombre_comercial']); ?>)
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="nuevo">+ Nuevo Proveedor</option>
                                        </select>
                                        <a href="new_prov_complete.php?origen=compras" class="btn btn-nuevo ms-2">
                                            <i class="fas fa-plus"></i> Nuevo
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Contacto Rápido</label>
                                    <div id="contacto-proveedor">
                                        <p class="text-muted mb-1">Selecciona un proveedor</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de la Compra -->
                    <div class="card card-custom">
                        <div class="card-header card-header-custom">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Información de la Compra
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Número de Remito</label>
                                        <input type="text" class="form-control" name="numero_remito"
                                            value="<?php echo $compra['numero_remito'] ?? ''; ?>"
                                            placeholder="Ej: REM-001">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Fecha de Compra *</label>
                                        <input type="date" class="form-control" name="fecha_compra"
                                            value="<?php echo $compra['fecha_compra'] ?? date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Fecha Entrega Estimada</label>
                                        <input type="date" class="form-control" name="fecha_entrega_estimada"
                                            value="<?php echo $compra['fecha_entrega_estimada'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Estado</label>
                                        <select class="form-select" name="estado">
                                            <option value="pendiente" <?php echo (isset($compra['estado']) && $compra['estado'] == 'pendiente') ? 'selected' : ''; ?>>
                                                🟡 Pendiente
                                            </option>
                                            <option value="confirmada" <?php echo (isset($compra['estado']) && $compra['estado'] == 'confirmada') ? 'selected' : ''; ?>>
                                                🔵 Confirmada
                                            </option>
                                            <option value="parcial" <?php echo (isset($compra['estado']) && $compra['estado'] == 'parcial') ? 'selected' : ''; ?>>
                                                🟠 Parcial
                                            </option>
                                            <option value="recibida" <?php echo (isset($compra['estado']) && $compra['estado'] == 'recibida') ? 'selected' : ''; ?>>
                                                🟢 Recibida
                                            </option>
                                            <option value="cancelada" <?php echo (isset($compra['estado']) && $compra['estado'] == 'cancelada') ? 'selected' : ''; ?>>
                                                🔴 Cancelada
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Observaciones</label>
                                        <textarea class="form-control" name="observaciones" rows="3"
                                            placeholder="Observaciones adicionales sobre la compra..."><?php echo $compra['observaciones'] ?? ''; ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Productos del Remito -->
                    <div class="card card-custom">
                        <div class="card-header card-header-custom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-boxes me-2"></i>
                                    Productos del Remito
                                </h5>
                                <button type="button" class="btn btn-light btn-sm" onclick="agregarProducto()">
                                    <i class="fas fa-plus me-1"></i> Agregar Producto
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="productos-container">
                                <!-- Los productos se cargarán aquí dinámicamente -->
                            </div>

                            <!-- Resumen Totales -->
                            <div class="total-section">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary">Resumen de la Compra</h6>
                                        <p class="mb-1">Total de productos: <span id="total-productos" class="fw-bold">0</span></p>
                                        <p class="mb-1">Total de unidades: <span id="total-unidades" class="fw-bold">0</span></p>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <h4 class="text-success mb-0">
                                            Total: $<span id="total-compra" class="fw-bold">0.00</span>
                                        </h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="d-flex justify-content-end gap-2 mb-4">
                        <a href="compras.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i>
                            <?php echo $compra ? 'Actualizar' : 'Guardar'; ?> Orden
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        let contadorProductos = 0;
        let productos = <?php echo json_encode($productos); ?>;

        $(document).ready(function() {
            // Inicializar Select2
            $('#proveedor_id').select2({
                placeholder: "Buscar proveedor...",
                allowClear: true
            });

            // Evento cambio de proveedor
            $('#proveedor_id').on('change', function() {
                actualizarContactoProveedor();
            });

            // Agregar producto inicial si es edición
            <?php if ($compra): ?>
                // Cargar productos existentes aquí
            <?php else: ?>
                agregarProducto(); // Agregar un producto por defecto
            <?php endif; ?>
        });

        function actualizarContactoProveedor() {
            const select = document.getElementById('proveedor_id');
            const selectedOption = select.options[select.selectedIndex];
            const contactoDiv = document.getElementById('contacto-proveedor');

            if (selectedOption.value && selectedOption.value !== 'nuevo') {
                const telefono = selectedOption.dataset.telefono;
                const whatsapp = selectedOption.dataset.whatsapp;
                const email = selectedOption.dataset.email;

                let contactoHTML = '';

                if (telefono) {
                    contactoHTML += `<div class="mb-1">
                        <i class="fas fa-phone text-primary"></i> 
                        <a href="tel:${telefono}" class="text-decoration-none">${telefono}</a>
                    </div>`;
                }

                if (whatsapp) {
                    contactoHTML += `<div class="mb-1">
                        <button type="button" class="btn whatsapp-btn btn-sm" onclick="abrirWhatsApp('${whatsapp}')">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </button>
                    </div>`;
                }

                if (email) {
                    contactoHTML += `<div class="mb-1">
                        <i class="fas fa-envelope text-primary"></i> 
                        <a href="mailto:${email}" class="text-decoration-none">${email}</a>
                    </div>`;
                }

                contactoDiv.innerHTML = contactoHTML || '<p class="text-muted mb-1">Sin información de contacto</p>';
            } else {
                contactoDiv.innerHTML = '<p class="text-muted mb-1">Selecciona un proveedor</p>';
            }
        }

        function abrirWhatsApp(numero) {
            const mensaje = encodeURIComponent('Hola, me pongo en contacto desde el Sistema de Gestión para consultar sobre productos.');
            const url = `https://wa.me/${numero.replace(/[^0-9]/g, '')}?text=${mensaje}`;
            window.open(url, '_blank');
        }

        function agregarProducto() {
            contadorProductos++;
            const container = document.getElementById('productos-container');

            const productoHTML = `
                <div class="producto-row" id="producto-${contadorProductos}">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Producto *</label>
                            <select class="form-select" name="productos[${contadorProductos}][producto_id]" required>
                                <option value="">-- Seleccionar Producto --</option>
                                ${productos.map(p => `
                                    <option value="${p.id}" data-precio="${p.precio_compra || 0}">
                                        ${p.nombre} (${p.codigo})
                                    </option>
                                `).join('')}
                                <option value="nuevo">+ Nuevo Producto</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Cantidad *</label>
                            <input type="number" class="form-control" name="productos[${contadorProductos}][cantidad]" 
                                   value="1" min="1" required onchange="calcularSubtotal(${contadorProductos})">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Precio Unitario *</label>
                            <input type="number" class="form-control" name="productos[${contadorProductos}][precio_unitario]" 
                                   step="0.01" min="0" required onchange="calcularSubtotal(${contadorProductos})">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Subtotal</label>
                            <input type="text" class="form-control bg-light" id="subtotal-${contadorProductos}" 
                                   readonly value="$0.00">
                        </div>
                        <div class="col-md-2 text-center">
                            <label class="form-label fw-bold d-block">&nbsp;</label>
                            <button type="button" class="btn btn-eliminar" onclick="eliminarProducto(${contadorProductos})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', productoHTML);
            calcularTotales();
        }

        function eliminarProducto(id) {
            if (document.querySelectorAll('.producto-row').length > 1) {
                document.getElementById(`producto-${id}`).remove();
                calcularTotales();
            } else {
                alert('Debe mantener al menos un producto en la orden.');
            }
        }

        function calcularSubtotal(id) {
            const cantidad = document.querySelector(`input[name="productos[${id}][cantidad]"]`).value || 0;
            const precio = document.querySelector(`input[name="productos[${id}][precio_unitario]"]`).value || 0;
            const subtotal = cantidad * precio;

            document.getElementById(`subtotal-${id}`).value = `$${subtotal.toFixed(2)}`;
            calcularTotales();
        }

        function calcularTotales() {
            let totalProductos = 0;
            let totalUnidades = 0;
            let totalCompra = 0;

            document.querySelectorAll('.producto-row').forEach(row => {
                const cantidad = parseInt(row.querySelector('input[name*="[cantidad]"]').value) || 0;
                const precio = parseFloat(row.querySelector('input[name*="[precio_unitario]"]').value) || 0;

                totalProductos++;
                totalUnidades += cantidad;
                totalCompra += cantidad * precio;
            });

            document.getElementById('total-productos').textContent = totalProductos;
            document.getElementById('total-unidades').textContent = totalUnidades;
            document.getElementById('total-compra').textContent = totalCompra.toFixed(2);
        }

        function nuevoItem(tipo) {
            const nombre = prompt(`Ingrese el nombre del nuevo ${tipo}:`);
            if (nombre) {
                // Aquí puedes agregar lógica para crear nuevos países, provincias, ciudades
                alert(`Funcionalidad para crear nuevo ${tipo} será implementada`);
            }
        }

        // 📞 FUNCIÓN UNIFICADA - MANEJO DE CÓDIGOS DE PAÍS
        function cambiarCodigoPais(tipo) {
            const select = document.getElementById(`${tipo}-pais`);
            const input = document.getElementById(`${tipo}-input`);
            const codigo = select.value;
            
            // Placeholders específicos por país
            const placeholders = {
                '+54': '11 1234-5678',  // Argentina
                '+1': '(555) 123-4567', // USA
                '+55': '11 99999-9999', // Brasil
                '+56': '9 1234 5678',   // Chile
                '+51': '999 999 999',   // Perú
                '+52': '55 1234 5678',  // México
                '+34': '612 34 56 78',  // España
                '+33': '06 12 34 56 78', // Francia
                '+39': '338 123 4567',  // Italia
                '+49': '0151 23456789'  // Alemania
            };
            
            input.placeholder = placeholders[codigo] || 'Número de teléfono';
        }

        // 🌍 UNIFICACIÓN DE CRITERIOS - MANEJO DE PAÍSES
        document.addEventListener('DOMContentLoaded', function() {
            const paisSelect = document.getElementById('pais_id');
            const provinciaSelect = document.getElementById('provincia_id');
            const ciudadSelect = document.getElementById('ciudad_id');

            if (paisSelect) {
                paisSelect.addEventListener('change', function() {
                    const paisId = this.value;
                    const paisTexto = this.options[this.selectedIndex].text;
                    
                    // Limpiar provincias y ciudades
                    provinciaSelect.innerHTML = '<option value="">-- Seleccionar --</option>';
                    ciudadSelect.innerHTML = '<option value="">-- Seleccionar --</option>';
                    
                    // Solo cargar automáticamente si es Argentina
                    if (paisTexto.toLowerCase().includes('argentina')) {
                        // Cargar provincias argentinas
                        fetch(`../../config/get_provincias.php?pais_id=${paisId}`)
                            .then(response => response.json())
                            .then(provincias => {
                                provincias.forEach(provincia => {
                                    const option = new Option(provincia.nombre, provincia.id);
                                    provinciaSelect.add(option);
                                });
                                // Agregar opción para nueva provincia
                                provinciaSelect.add(new Option('+ Nueva Provincia', 'nuevo'));
                            })
                            .catch(error => console.log('No se pudieron cargar las provincias'));
                    } else {
                        // Para otros países, dejar campos manuales
                        provinciaSelect.innerHTML = `
                            <option value="">-- Ingrese manualmente --</option>
                            <option value="manual">Escribir provincia/estado</option>
                            <option value="nuevo">+ Nueva Provincia</option>
                        `;
                        ciudadSelect.innerHTML = `
                            <option value="">-- Ingrese manualmente --</option>
                            <option value="manual">Escribir ciudad</option>
                            <option value="nuevo">+ Nueva Ciudad</option>
                        `;
                    }
                });
            }

            if (provinciaSelect) {
                provinciaSelect.addEventListener('change', function() {
                    const provinciaId = this.value;
                    const paisTexto = paisSelect.options[paisSelect.selectedIndex].text;
                    
                    // Limpiar ciudades
                    ciudadSelect.innerHTML = '<option value="">-- Seleccionar --</option>';
                    
                    // Solo cargar automáticamente si es Argentina y no es opción manual
                    if (paisTexto.toLowerCase().includes('argentina') && provinciaId !== 'manual' && provinciaId !== 'nuevo' && provinciaId !== '') {
                        fetch(`../../config/get_ciudades.php?provincia_id=${provinciaId}`)
                            .then(response => response.json())
                            .then(ciudades => {
                                ciudades.forEach(ciudad => {
                                    const option = new Option(ciudad.nombre, ciudad.id);
                                    ciudadSelect.add(option);
                                });
                                // Agregar opción para nueva ciudad
                                ciudadSelect.add(new Option('+ Nueva Ciudad', 'nuevo'));
                            })
                            .catch(error => console.log('No se pudieron cargar las ciudades'));
                    } else if (provinciaId === 'manual') {
                        // Cambiar a input manual para provincia
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.className = 'form-control';
                        input.name = 'provincia_manual';
                        input.placeholder = 'Escriba la provincia/estado';
                        provinciaSelect.parentNode.replaceChild(input, provinciaSelect);
                    }
                });
            }
        });

        // Validación del formulario
        document.getElementById('form-compra').addEventListener('submit', function(e) {
            e.preventDefault();

            // Validar que hay al menos un producto
            if (document.querySelectorAll('.producto-row').length === 0) {
                alert('Debe agregar al menos un producto a la orden.');
                return;
            }

            // Validar que todos los productos tienen cantidad y precio
            let valido = true;
            document.querySelectorAll('.producto-row').forEach(row => {
                const cantidad = row.querySelector('input[name*="[cantidad]"]').value;
                const precio = row.querySelector('input[name*="[precio_unitario]"]').value;

                if (!cantidad || cantidad <= 0 || !precio || precio <= 0) {
                    valido = false;
                }
            });

            if (!valido) {
                alert('Todos los productos deben tener cantidad y precio válidos.');
                return;
            }

            // Si todo está válido, enviar formulario
            this.submit();
        });
    </script>
</body>

</html>
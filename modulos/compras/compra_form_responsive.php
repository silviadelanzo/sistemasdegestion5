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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0074D9;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --light-bg: #f8f9fa;
            --border-radius: 12px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 0;
        }

        .main-wrapper {
            background: white;
            margin: 15px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .header-section {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .form-section {
            padding: 30px;
        }

        .section-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .section-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        .section-header {
            background: linear-gradient(135deg, var(--light-bg), #e9ecef);
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h6 {
            margin: 0;
            color: #495057;
            font-weight: 600;
        }

        .section-body {
            padding: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 116, 217, 0.25);
        }

        .btn-action {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-nuevo {
            background: var(--success-color);
            color: white;
            font-size: 0.85rem;
            padding: 8px 15px;
        }

        .btn-nuevo:hover {
            background: #218838;
            color: white;
        }

        .whatsapp-btn {
            background: #25D366;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .whatsapp-btn:hover {
            background: #1da851;
            color: white;
            transform: scale(1.05);
        }

        .producto-row {
            background: var(--light-bg);
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }

        .btn-eliminar {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--danger-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .total-section {
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-top: 20px;
        }

        .select2-container--default .select2-selection--single {
            height: 42px !important;
            border: 2px solid #e9ecef !important;
            border-radius: 8px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px !important;
            padding-left: 15px !important;
        }

        .actions-footer {
            background: var(--light-bg);
            padding: 20px 30px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-wrapper {
                margin: 10px;
            }

            .header-section {
                padding: 15px 20px;
                text-align: center;
            }

            .header-title {
                font-size: 1.2rem;
            }

            .form-section {
                padding: 20px 15px;
            }

            .section-body {
                padding: 15px;
            }

            .actions-footer {
                padding: 15px 20px;
                flex-direction: column;
            }

            .producto-row {
                padding: 15px 45px 15px 15px;
            }

            .btn-eliminar {
                top: 5px;
                right: 5px;
                width: 25px;
                height: 25px;
            }
        }

        @media (max-width: 576px) {
            .header-section {
                flex-direction: column;
                text-align: center;
            }

            .section-header {
                padding: 12px 15px;
            }

            .form-control, .form-select {
                font-size: 16px; /* Evita zoom en iOS */
            }
        }

        /* Estados con colores */
        .estado-pendiente { color: #ffc107; }
        .estado-confirmada { color: #0074D9; }
        .estado-parcial { color: #fd7e14; }
        .estado-recibida { color: #28a745; }
        .estado-cancelada { color: #dc3545; }

        /* Contacto compacto */
        .contacto-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            font-size: 0.9rem;
        }

        .contacto-info .fw-bold {
            color: var(--primary-color);
        }
    </style>
</head>

<body>
    <?php include '../../config/navbar_code.php'; ?>

    <div class="main-wrapper">
        <!-- Header -->
        <div class="header-section">
            <h1 class="header-title">
                <i class="fas fa-shopping-cart"></i>
                <?php echo $compra ? 'Editar' : 'Nueva'; ?> Orden de Compra
            </h1>
            <div>
                <a href="compras.php" class="btn btn-light btn-action">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <form id="form-compra" method="POST" action="gestionar_compra.php">
            <input type="hidden" name="action" value="<?php echo $compra ? 'actualizar' : 'crear'; ?>">
            <input type="hidden" name="id" value="<?php echo $compra['id'] ?? ''; ?>">

            <div class="form-section">
                <!-- Información del Proveedor -->
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-truck text-primary"></i>
                        <h6>Información del Proveedor</h6>
                    </div>
                    <div class="section-body">
                        <div class="row">
                            <div class="col-lg-8 col-md-12 mb-3">
                                <label class="form-label">Proveedor *</label>
                                <div class="d-flex align-items-center gap-2">
                                    <select class="form-select flex-grow-1" name="proveedor_id" id="proveedor_id" required>
                                        <option value="">-- Seleccionar Proveedor --</option>
                                        <?php foreach ($proveedores as $proveedor): ?>
                                            <option value="<?php echo $proveedor['id']; ?>"
                                                data-razon="<?php echo htmlspecialchars($proveedor['razon_social']); ?>"
                                                data-telefono="<?php echo htmlspecialchars($proveedor['telefono']); ?>"
                                                data-whatsapp="<?php echo htmlspecialchars($proveedor['whatsapp'] ?? ''); ?>"
                                                data-email="<?php echo htmlspecialchars($proveedor['email']); ?>"
                                                <?php echo (isset($compra['proveedor_id']) && $compra['proveedor_id'] == $proveedor['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($proveedor['razon_social']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <a href="new_prov_complete.php?origen=compras" class="btn btn-nuevo">
                                        <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Nuevo</span>
                                    </a>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-12 mb-3">
                                <label class="form-label">Contacto Rápido</label>
                                <div id="contacto-proveedor" class="contacto-info">
                                    <p class="text-muted mb-0"><i class="fas fa-info-circle me-1"></i>Selecciona un proveedor</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de la Compra -->
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-info-circle text-info"></i>
                        <h6>Información de la Compra</h6>
                    </div>
                    <div class="section-body">
                        <div class="row">
                            <div class="col-lg-3 col-md-6 mb-3">
                                <label class="form-label">Número de Remito</label>
                                <input type="text" class="form-control" name="numero_remito"
                                    value="<?php echo $compra['numero_remito'] ?? ''; ?>"
                                    placeholder="Ej: REM-001">
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <label class="form-label">Fecha de Compra *</label>
                                <input type="date" class="form-control" name="fecha_compra"
                                    value="<?php echo $compra['fecha_compra'] ?? date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <label class="form-label">Fecha Entrega</label>
                                <input type="date" class="form-control" name="fecha_entrega_estimada"
                                    value="<?php echo $compra['fecha_entrega_estimada'] ?? ''; ?>">
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <label class="form-label">Estado</label>
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

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" name="observaciones" rows="3"
                                    placeholder="Notas adicionales sobre la compra..."><?php echo $compra['observaciones'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Productos -->
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-boxes text-warning"></i>
                        <h6>Productos</h6>
                        <div class="ms-auto">
                            <button type="button" class="btn btn-success btn-sm" onclick="agregarProducto()">
                                <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Agregar Producto</span>
                            </button>
                        </div>
                    </div>
                    <div class="section-body">
                        <div id="productos-container">
                            <!-- Los productos se cargarán aquí -->
                        </div>
                        
                        <div class="total-section">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-success mb-3">
                                        <i class="fas fa-calculator me-2"></i>Resumen de Compra
                                    </h6>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <span id="subtotal-display">$0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>IVA:</span>
                                        <span id="iva-display">$0.00</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong>Total:</strong>
                                        <strong id="total-display">$0.00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer con acciones -->
            <div class="actions-footer">
                <div>
                    <button type="button" class="btn btn-secondary btn-action" onclick="window.location.href='compras.php'">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
                <div>
                    <button type="submit" name="accion" value="borrador" class="btn btn-warning btn-action me-2">
                        <i class="fas fa-save"></i> Guardar Borrador
                    </button>
                    <button type="submit" name="accion" value="confirmar" class="btn btn-primary btn-action">
                        <i class="fas fa-check"></i> Confirmar Compra
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Datos de productos desde PHP
        const productos = <?php echo json_encode($productos); ?>;
        let contadorProductos = 0;

        $(document).ready(function() {
            // Inicializar Select2
            $('#proveedor_id').select2({
                placeholder: 'Buscar proveedor...',
                allowClear: true
            });

            // Evento cambio de proveedor
            $('#proveedor_id').on('change', function() {
                mostrarContactoProveedor();
            });

            // Agregar primer producto automáticamente
            agregarProducto();
        });

        function mostrarContactoProveedor() {
            const proveedorSelect = $('#proveedor_id');
            const proveedorOption = proveedorSelect.find('option:selected');
            const contactoDiv = $('#contacto-proveedor');

            if (proveedorOption.val()) {
                const razon = proveedorOption.data('razon') || 'N/A';
                const telefono = proveedorOption.data('telefono') || '';
                const whatsapp = proveedorOption.data('whatsapp') || '';
                const email = proveedorOption.data('email') || '';

                let html = `<div class="fw-bold text-primary mb-2">${razon}</div>`;
                
                if (telefono) {
                    html += `<div class="mb-1"><i class="fas fa-phone me-1"></i> ${telefono}</div>`;
                }
                if (whatsapp) {
                    html += `<div class="mb-1">
                        <a href="https://wa.me/${whatsapp.replace(/\D/g, '')}" class="whatsapp-btn" target="_blank">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>`;
                }
                if (email) {
                    html += `<div class="mb-1"><i class="fas fa-envelope me-1"></i> ${email}</div>`;
                }

                contactoDiv.html(html);
            } else {
                contactoDiv.html('<p class="text-muted mb-0"><i class="fas fa-info-circle me-1"></i>Selecciona un proveedor</p>');
            }
        }

        function agregarProducto() {
            contadorProductos++;
            const productoHtml = `
                <div class="producto-row" id="producto-${contadorProductos}">
                    <button type="button" class="btn-eliminar" onclick="eliminarProducto(${contadorProductos})">
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <div class="row">
                        <div class="col-lg-4 col-md-6 mb-3">
                            <label class="form-label">Producto *</label>
                            <select class="form-select producto-select" name="productos[${contadorProductos}][producto_id]" required>
                                <option value="">-- Seleccionar Producto --</option>
                                ${productos.map(p => `<option value="${p.id}" data-precio="${p.precio_venta || 0}">${p.nombre} - ${p.codigo}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-3 col-6 mb-3">
                            <label class="form-label">Cantidad *</label>
                            <input type="number" class="form-control cantidad-input" name="productos[${contadorProductos}][cantidad]" 
                                   min="1" step="0.01" required onchange="calcularTotales()">
                        </div>
                        <div class="col-lg-2 col-md-3 col-6 mb-3">
                            <label class="form-label">Precio Unit.</label>
                            <input type="number" class="form-control precio-input" name="productos[${contadorProductos}][precio_unitario]" 
                                   min="0" step="0.01" onchange="calcularTotales()">
                        </div>
                        <div class="col-lg-2 col-md-6 mb-3">
                            <label class="form-label">IVA %</label>
                            <select class="form-select iva-select" name="productos[${contadorProductos}][iva]" onchange="calcularTotales()">
                                <option value="0">0%</option>
                                <option value="10.5">10.5%</option>
                                <option value="21" selected>21%</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6 mb-3">
                            <label class="form-label">Subtotal</label>
                            <input type="text" class="form-control subtotal-producto" readonly>
                        </div>
                    </div>
                </div>
            `;

            $('#productos-container').append(productoHtml);
            
            // Inicializar Select2 para el nuevo producto
            $(`#producto-${contadorProductos} .producto-select`).select2({
                placeholder: 'Buscar producto...',
                allowClear: true
            });
        }

        function eliminarProducto(id) {
            $(`#producto-${id}`).remove();
            calcularTotales();
        }

        function calcularTotales() {
            let subtotalGeneral = 0;
            let ivaGeneral = 0;

            $('.producto-row').each(function() {
                const cantidad = parseFloat($(this).find('.cantidad-input').val()) || 0;
                const precio = parseFloat($(this).find('.precio-input').val()) || 0;
                const iva = parseFloat($(this).find('.iva-select').val()) || 0;

                const subtotal = cantidad * precio;
                const ivaProducto = subtotal * (iva / 100);

                $(this).find('.subtotal-producto').val('$' + subtotal.toFixed(2));

                subtotalGeneral += subtotal;
                ivaGeneral += ivaProducto;
            });

            const total = subtotalGeneral + ivaGeneral;

            $('#subtotal-display').text('$' + subtotalGeneral.toFixed(2));
            $('#iva-display').text('$' + ivaGeneral.toFixed(2));
            $('#total-display').text('$' + total.toFixed(2));
        }

        // Inicializar contacto de proveedor si hay uno seleccionado
        $(document).ready(function() {
            if ($('#proveedor_id').val()) {
                mostrarContactoProveedor();
            }
        });
    </script>
</body>
</html>

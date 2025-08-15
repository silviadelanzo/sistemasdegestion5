<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();

// Obtener datos para los selectores
$proveedores = $pdo->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC);
$productos = $pdo->query("SELECT p.*, c.nombre as categoria_nombre, l.nombre as lugar_nombre
                         FROM productos p
                         LEFT JOIN categorias c ON p.categoria_id = c.id
                         LEFT JOIN lugares l ON p.lugar_id = l.id
                         WHERE p.activo = 1 ORDER BY p.nombre")->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías y lugares para los selectores
$categorias = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$lugares = $pdo->query("SELECT * FROM lugares WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Generar próximo código de remito desde la tabla remitos con formato REM-0000001
try {
    $sql_codigo = "SELECT codigo FROM remitos WHERE codigo LIKE 'REM-%' ORDER BY CAST(SUBSTRING(codigo, 5) AS UNSIGNED) DESC LIMIT 1";
    $stmt_codigo = $pdo->query($sql_codigo);
    $ultimo_codigo = $stmt_codigo->fetchColumn();
    if ($ultimo_codigo) {
        $numero = intval(substr($ultimo_codigo, 4)) + 1;
    } else {
        $numero = 1;
    }
    $nuevo_codigo = 'REM-' . str_pad($numero, 7, '0', STR_PAD_LEFT);
} catch (Exception $e) {
    $nuevo_codigo = 'REM-0000001';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📝 Carga Manual de Compras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0d6efd;
            --success: #198754;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #0dcaf0;
            --light: #f8f9fa;
            --dark: #212529;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }

        .container-custom {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 15px;
        }

        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-bar {
            background: linear-gradient(135deg, var(--primary), #0056b3);
            color: white;
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            position: relative;
            overflow: hidden;
        }

        .header-bar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            z-index: 1;
            position: relative;
        }

        .header-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            z-index: 1;
            position: relative;
        }

        .nav-tabs-custom {
            border-bottom: 3px solid #e9ecef;
            background: linear-gradient(90deg, #f8f9fa, #e9ecef);
            padding: 0 25px;
            margin: 0;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            border-radius: 15px 15px 0 0;
            padding: 15px 25px;
            margin-right: 5px;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-tabs-custom .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, transparent, rgba(13, 110, 253, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .nav-tabs-custom .nav-link:hover::before {
            opacity: 1;
        }

        .nav-tabs-custom .nav-link.active {
            background: linear-gradient(135deg, var(--primary), #0056b3);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }

        .nav-tabs-custom .nav-link i {
            margin-right: 8px;
            font-size: 1.1rem;
        }

        .tab-content-area {
            padding: 30px;
            min-height: 600px;
        }

        .form-section {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            background: linear-gradient(135deg, var(--light), #e9ecef);
            padding: 7px 12px;
            border-bottom: 1px solid #e0e0e0;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 1rem;
        }

        .section-content {
            padding: 10px 6px 6px 6px;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-control,
        .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
            transform: translateY(-1px);
        }

        .btn-custom {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .barcode-scanner {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .barcode-scanner::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .barcode-scanner:hover::before {
            left: 100%;
        }

        .scanner-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 50px;
            padding: 15px 30px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .scanner-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.05);
        }

        .producto-item {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid #dee2e6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            position: relative;
            transition: all 0.3s ease;
        }

        .producto-item:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(13, 110, 253, 0.1);
        }

        .btn-remove {
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-remove:hover {
            background: #c82333;
            transform: scale(1.1);
        }

        .totals-box {
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            border: 2px solid #c3e6cb;
        }

        .footer-actions {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 10px 10px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        /* Select2 customization */
        .select2-container--default .select2-selection--single {
            height: 48px !important;
            border: 2px solid #e9ecef !important;
            border-radius: 8px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 44px !important;
            padding-left: 15px !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container-custom {
                margin: 10px auto;
                padding: 0 10px;
            }

            .header-bar {
                padding: 20px 25px;
                flex-direction: column;
                text-align: center;
            }

            .tab-content-area {
                padding: 20px;
            }

            .nav-tabs-custom {
                padding: 0 15px;
            }

            .nav-tabs-custom .nav-link {
                padding: 12px 15px;
                margin-right: 3px;
                font-size: 0.9rem;
            }
        }

        /* Animaciones */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease forwards;
        }
    </style>
</head>

<body>

    <div class="container-custom">
        <div class="main-card">
            <!-- Header -->
            <div class="header-bar">
                <h1 class="header-title">
                    <i class="fas fa-keyboard"></i>
                    Carga Manual de Compras
                </h1>
                <div class="header-badge">
                    <i class="fas fa-barcode me-2"></i>Con Scanner
                </div>
                <a href="compras_form.php" class="btn btn-light btn-custom">
                    <i class="fas fa-arrow-left"></i> Volver al Selector
                </a>
            </div>

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs nav-tabs-custom" id="compraTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                        <i class="fas fa-info-circle"></i>General
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="productos-tab" data-bs-toggle="tab" data-bs-target="#productos" type="button" role="tab">
                        <i class="fas fa-boxes"></i>Productos <span class="badge bg-secondary ms-2" id="productos-count">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="scanner-tab" data-bs-toggle="tab" data-bs-target="#scanner" type="button" role="tab">
                        <i class="fas fa-barcode"></i>Scanner
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="resumen-tab" data-bs-toggle="tab" data-bs-target="#resumen" type="button" role="tab">
                        <i class="fas fa-clipboard-check"></i>Resumen
                    </button>
                </li>
            </ul>

            <!-- Formulario Principal -->
            <form id="formCompraManual" method="POST" action="procesar_compra_manual.php">
                <div class="tab-content tab-content-area" id="compraTabsContent">

                    <!-- Tab 1: General -->
                    <div class="tab-pane fade show active fade-in-up" id="general" role="tabpanel">
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-building text-primary"></i>
                                Información del Proveedor
                            </div>
                            <div class="section-content">
                                <div class="row">
                                    <div class="col-lg-8 mb-3">
                                        <label class="form-label">Proveedor *</label>
                                        <div class="d-flex gap-3">
                                            <select class="form-select flex-grow-1" id="proveedor_id" name="proveedor_id" required>
                                                <option value="">-- Seleccionar Proveedor --</option>
                                                <?php foreach ($proveedores as $proveedor): ?>
                                                    <option value="<?php echo $proveedor['id']; ?>"
                                                        data-razon="<?php echo htmlspecialchars($proveedor['razon_social']); ?>"
                                                        data-telefono="<?php echo htmlspecialchars($proveedor['telefono']); ?>"
                                                        data-whatsapp="<?php echo htmlspecialchars($proveedor['whatsapp'] ?? ''); ?>"
                                                        data-email="<?php echo htmlspecialchars($proveedor['email']); ?>">
                                                        <?php echo htmlspecialchars($proveedor['razon_social']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <a href="../proveedores/new_prov_complete.php?origen=compras" class="btn btn-success btn-custom">
                                                <i class="fas fa-plus"></i> Nuevo
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 mb-3">
                                        <label class="form-label">Contacto</label>
                                        <div id="contacto-proveedor" class="alert alert-info">
                                            <i class="fas fa-info-circle me-1"></i>Selecciona un proveedor
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-file-invoice text-info"></i>
                                Datos del Remito
                            </div>
                            <div class="section-content">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Código Automático</label>
                                        <input type="text" class="form-control" value="<?php echo $nuevo_codigo; ?>" readonly
                                            style="background: #e8f5e8; font-weight: bold; color: #198754;">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">N° Remito Proveedor</label>
                                        <input type="text" class="form-control" name="numero_remito_proveedor" placeholder="Ej: 12345">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Fecha Entrega *</label>
                                        <input type="date" class="form-control" name="fecha_entrega" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Estado</label>
                                        <select class="form-select" name="estado">
                                            <option value="pendiente">⏳ Pendiente</option>
                                            <option value="confirmada" selected>✅ Confirmado</option>
                                            <option value="recibida">📦 Recibido</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Observaciones</label>
                                        <textarea class="form-control" name="observaciones" rows="3"
                                            placeholder="Notas sobre la entrega, estado de la mercadería, condiciones especiales, etc..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Productos -->
                    <div class="tab-pane fade fade-in-up" id="productos" role="tabpanel">
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-boxes text-warning"></i>
                                Gestión de Productos
                                <div class="ms-auto">
                                    <button type="button" class="btn btn-success btn-custom me-2" onclick="agregarProducto()">
                                        <i class="fas fa-plus"></i> Agregar Existente
                                    </button>
                                    <button type="button" class="btn btn-primary btn-custom" onclick="abrirModalNuevoProducto()">
                                        <i class="fas fa-star"></i> Producto Nuevo
                                    </button>
                                </div>
                            </div>
                            <div class="section-content">
                                <div class="alert alert-info">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    <strong>Tip:</strong> Usa la pestaña "Scanner" para cargar productos con código de barras más rápido.
                                </div>
                                <div id="productos-container">
                                    <!-- Productos dinámicos -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Scanner -->
                    <div class="tab-pane fade fade-in-up" id="scanner" role="tabpanel">
                        <div class="barcode-scanner">
                            <h4 class="mb-3">
                                <i class="fas fa-barcode fa-2x mb-3"></i><br>
                                Scanner de Código de Barras
                            </h4>
                            <p class="mb-4">Escanea productos con pistola láser o cámara para carga rápida</p>
                            <button type="button" class="btn scanner-btn" onclick="activarScanner()">
                                <i class="fas fa-camera me-2"></i>Activar Scanner
                            </button>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Código de Barras</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="codigo_barras_input"
                                        placeholder="Escanea o escribe el código..." style="font-size: 1.2rem; height: 50px;">
                                    <button class="btn btn-outline-primary" type="button" onclick="verificarCodigoBarras()">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                                <small class="text-muted">El cursor debe estar aquí para recibir el scanner</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cantidad</label>
                                <input type="number" class="form-control" id="cantidad_scanner" value="1" min="1" step="0.01" style="font-size: 1.2rem; height: 50px;">
                            </div>
                        </div>

                        <div id="resultado-scanner" class="alert alert-secondary d-none">
                            <i class="fas fa-info-circle me-2"></i>Resultado del escaneo aparecerá aquí
                        </div>

                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-history text-info"></i>
                                Productos Escaneados
                            </div>
                            <div class="section-content">
                                <div id="productos-escaneados">
                                    <p class="text-muted text-center">
                                        <i class="fas fa-barcode fa-3x mb-3 d-block"></i>
                                        Los productos escaneados aparecerán aquí
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 4: Resumen -->
                    <div class="tab-pane fade fade-in-up" id="resumen" role="tabpanel">
                        <div class="totals-box">
                            <h4 class="text-primary mb-4">
                                <i class="fas fa-clipboard-check me-2"></i>Resumen de la Compra
                            </h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <strong>Proveedor:</strong>
                                        <span id="resumen-proveedor" class="text-muted">No seleccionado</span>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Fecha:</strong>
                                        <span id="resumen-fecha"><?php echo date('d/m/Y'); ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Código:</strong>
                                        <span class="badge bg-success"><?php echo $nuevo_codigo; ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="border rounded p-3">
                                                <h3 class="text-primary mb-1" id="total-items">0</h3>
                                                <small class="text-muted">Items</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-3">
                                                <h3 class="text-success mb-1" id="total-cantidad">0</h3>
                                                <small class="text-muted">Cantidad</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="border rounded p-3">
                                                <h3 class="text-info mb-1" id="total-escaneados">0</h3>
                                                <small class="text-muted">Escaneados</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-top">
                                <h5 class="mb-3">Lista de Productos:</h5>
                                <div id="lista-resumen" class="text-muted">
                                    No hay productos agregados
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Importante:</strong> Revisa todos los datos antes de confirmar. Una vez guardado, podrás editarlo desde la lista de compras.
                        </div>
                    </div>

                </div>

                <!-- Footer Actions -->
                <div class="footer-actions">
                    <div>
                        <button type="button" class="btn btn-primary btn-custom" id="btnAnterior" onclick="irAnterior()">
                            <i class="fas fa-arrow-left"></i> Anterior
                        </button>
                        <button type="button" class="btn btn-info btn-custom ms-2" onclick="guardarBorrador()">
                            <i class="fas fa-save"></i> Guardar Borrador
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-warning btn-custom me-2" onclick="limpiarFormulario()">
                            <i class="fas fa-broom"></i> Limpiar Todo
                        </button>
                        <button type="button" class="btn btn-primary btn-custom" id="btnSiguiente" onclick="irSiguiente()">
                            <i class="fas fa-arrow-right"></i> Siguiente
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Navegación secuencial de tabs
        const tabOrder = ['general-tab', 'productos-tab', 'scanner-tab', 'resumen-tab'];

        function getActiveTabIndex() {
            for (let i = 0; i < tabOrder.length; i++) {
                const tab = document.getElementById(tabOrder[i]);
                if (tab && tab.classList.contains('active')) {
                    return i;
                }
            }
            return 0;
        }

        function irSiguiente() {
            const idx = getActiveTabIndex();
            if (idx < tabOrder.length - 1) {
                const nextTab = document.getElementById(tabOrder[idx + 1]);
                if (nextTab) nextTab.click();
            }
        }

        function irAnterior() {
            const idx = getActiveTabIndex();
            if (idx > 0) {
                const prevTab = document.getElementById(tabOrder[idx - 1]);
                if (prevTab) prevTab.click();
            }
        }
        // Opcional: deshabilitar botones en extremos
        document.addEventListener('DOMContentLoaded', function() {
            function updateNavButtons() {
                const idx = getActiveTabIndex();
                document.getElementById('btnAnterior').disabled = idx === 0;
                document.getElementById('btnSiguiente').disabled = idx === tabOrder.length - 1;
            }
            const tabIds = tabOrder.map(id => document.getElementById(id));
            tabIds.forEach(tab => {
                if (tab) tab.addEventListener('shown.bs.tab', updateNavButtons);
            });
            updateNavButtons();
        });

        function irAProductos() {
            var productosTab = document.getElementById('productos-tab');
            if (productosTab) {
                productosTab.click();
            }
        }

        function irAScanner() {
            var scannerTab = document.getElementById('scanner-tab');
            if (scannerTab) {
                scannerTab.click();
            }
        }

        function irAGeneral() {
            var generalTab = document.getElementById('general-tab');
            if (generalTab) {
                generalTab.click();
            }
        }
    </script>
    <script>
        const productos = <?php echo json_encode($productos); ?>;
        const categorias = <?php echo json_encode($categorias); ?>;
        const lugares = <?php echo json_encode($lugares); ?>;
        let contadorProductos = 0;
        let productosEscaneados = 0;

        $(document).ready(function() {
            // Inicializar Select2
            $('#proveedor_id').select2({
                placeholder: 'Buscar proveedor...',
                allowClear: true
            });

            // Event listeners
            $('#proveedor_id').on('change', function() {
                mostrarContactoProveedor();
                actualizarResumen();
            });

            // Activar primer input del scanner
            $('#scanner-tab').on('shown.bs.tab', function() {
                $('#codigo_barras_input').focus();
            });

            // Scanner automático al escribir
            $('#codigo_barras_input').on('keypress', function(e) {
                if (e.which === 13) { // Enter
                    verificarCodigoBarras();
                }
            });

            // Actualizar contadores en tiempo real
            $('input, select, textarea').on('change', actualizarResumen);
        });

        function mostrarContactoProveedor() {
            const proveedorOption = $('#proveedor_id').find('option:selected');
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
                        <a href="https://wa.me/${whatsapp.replace(/\D/g, '')}" class="btn btn-success btn-sm" target="_blank">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>`;
                }
                if (email) {
                    html += `<div><i class="fas fa-envelope me-1"></i> ${email}</div>`;
                }

                contactoDiv.removeClass('alert-info').addClass('alert-success').html(html);
            } else {
                contactoDiv.removeClass('alert-success').addClass('alert-info')
                    .html('<i class="fas fa-info-circle me-1"></i>Selecciona un proveedor');
            }
        }

        function agregarProducto() {
            const proveedorId = $('#proveedor_id').val();

            if (!proveedorId) {
                alert('Primero debe seleccionar un proveedor');
                $('#general-tab').tab('show');
                return;
            }

            contadorProductos++;

            // Filtrar productos por proveedor
            const productosDelProveedor = productos.filter(p => p.proveedor_principal_id == proveedorId);

            if (productosDelProveedor.length === 0) {
                alert('Este proveedor no tiene productos asignados. Use "Producto Nuevo" para agregar uno.');
                return;
            }

            const productoHtml = `
                <div class="producto-item fade-in-up" id="producto-${contadorProductos}">
                    <button type="button" class="btn-remove" onclick="eliminarProducto(${contadorProductos})">
                        <i class="fas fa-times"></i>
                    </button>

                    <div class="row">
                        <div class="col-lg-5 col-md-6 mb-3">
                            <label class="form-label">Producto *</label>
                            <select class="form-select producto-select" name="productos[${contadorProductos}][producto_id]" required onchange="cargarDatosProducto(this, ${contadorProductos})">
                                <option value="">-- Seleccionar --</option>
                                ${productosDelProveedor.map(p => `<option value="${p.id}" data-codigo-proveedor="${p.codigo_proveedor || ''}" data-unidad="${p.unidad || 'UN'}">${p.nombre}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-3 col-6 mb-3">
                            <label class="form-label">Código</label>
                            <input type="text" class="form-control codigo-proveedor-input" name="productos[${contadorProductos}][codigo_proveedor]" readonly>
                        </div>
                        <div class="col-lg-2 col-md-3 col-6 mb-3">
                            <label class="form-label">Cantidad *</label>
                            <input type="number" class="form-control cantidad-input" name="productos[${contadorProductos}][cantidad]"
                                   min="1" step="0.01" required onchange="actualizarResumen()">
                        </div>
                        <div class="col-lg-1 col-md-3 col-6 mb-3">
                            <label class="form-label">Unidad</label>
                            <input type="text" class="form-control unidad-input" name="productos[${contadorProductos}][unidad]" readonly>
                        </div>
                        <div class="col-lg-2 col-md-6 mb-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="productos[${contadorProductos}][estado]">
                                <option value="bueno" selected>✅ Bueno</option>
                                <option value="regular">⚠️ Regular</option>
                                <option value="defectuoso">❌ Defectuoso</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="productos[${contadorProductos}][observaciones]"
                                      rows="2" placeholder="Lote, vencimiento, condiciones..."></textarea>
                        </div>
                    </div>
                </div>
            `;

            $('#productos-container').append(productoHtml);

            // Inicializar Select2 para el nuevo producto
            $(`#producto-${contadorProductos} .producto-select`).select2({
                placeholder: 'Buscar...',
                allowClear: true
            });

            actualizarResumen();
        }

        function eliminarProducto(id) {
            $(`#producto-${id}`).fadeOut(300, function() {
                $(this).remove();
                actualizarResumen();
            });
        }

        function cargarDatosProducto(select, index) {
            const option = select.selectedOptions[0];
            if (option && option.value) {
                const codigoProveedor = option.getAttribute('data-codigo-proveedor') || '';
                const unidad = option.getAttribute('data-unidad') || 'UN';

                document.querySelector(`#producto-${index} .codigo-proveedor-input`).value = codigoProveedor;
                document.querySelector(`#producto-${index} .unidad-input`).value = unidad;
            }
            actualizarResumen();
        }

        function activarScanner() {
            $('#codigo_barras_input').focus();
            // Aquí se podría integrar con una librería de cámara web
            alert('Scanner activado. El cursor está listo para recibir códigos de barras.');
        }

        function verificarCodigoBarras() {
            const codigo = $('#codigo_barras_input').val().trim();
            const cantidad = $('#cantidad_scanner').val();

            if (!codigo) {
                alert('Ingrese un código de barras');
                return;
            }

            // Buscar producto por código de barras
            const producto = productos.find(p => p.codigo_barra === codigo);

            if (producto) {
                agregarProductoEscaneado(producto, cantidad);
                $('#codigo_barras_input').val('').focus();
                $('#cantidad_scanner').val('1');
            } else {
                $('#resultado-scanner').removeClass('d-none alert-secondary').addClass('alert-warning')
                    .html(`<i class="fas fa-exclamation-triangle me-2"></i>Producto no encontrado: ${codigo}`);
            }
        }

        function agregarProductoEscaneado(producto, cantidad) {
            productosEscaneados++;

            const itemHtml = `
                <div class="alert alert-success fade-in-up">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${producto.nombre}</strong><br>
                            <small>Código: ${producto.codigo_barra} | Cantidad: ${cantidad} ${producto.unidad}</small>
                        </div>
                        <button class="btn btn-outline-danger btn-sm" onclick="$(this).parent().parent().remove(); productosEscaneados--; actualizarResumen();">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;

            $('#productos-escaneados').prepend(itemHtml);

            $('#resultado-scanner').removeClass('d-none alert-warning').addClass('alert-success')
                .html(`<i class="fas fa-check me-2"></i>Producto agregado: ${producto.nombre}`);

            actualizarResumen();
        }

        function actualizarResumen() {
            let totalItems = $('.producto-item').length;
            let totalCantidad = 0;

            $('.cantidad-input').each(function() {
                const cantidad = parseFloat($(this).val()) || 0;
                totalCantidad += cantidad;
            });

            // Actualizar contadores
            $('#productos-count').text(totalItems);
            $('#total-items').text(totalItems);
            $('#total-cantidad').text(totalCantidad.toFixed(2));
            $('#total-escaneados').text(productosEscaneados);

            // Actualizar resumen del proveedor
            const proveedorTexto = $('#proveedor_id option:selected').text() || 'No seleccionado';
            $('#resumen-proveedor').text(proveedorTexto);

            // Actualizar lista de productos en resumen
            let listaProductos = '';
            $('.producto-item').each(function() {
                const nombre = $(this).find('.producto-select option:selected').text();
                const cantidad = $(this).find('.cantidad-input').val();
                if (nombre && nombre !== '-- Seleccionar --' && cantidad) {
                    listaProductos += `<div class="border-bottom py-2">${nombre} - <strong>${cantidad}</strong></div>`;
                }
            });

            $('#lista-resumen').html(listaProductos || '<div class="text-muted">No hay productos agregados</div>');
        }

        function guardarBorrador() {
            // Implementar guardado como borrador
            alert('Funcionalidad de borrador próximamente');
        }

        function limpiarFormulario() {
            if (confirm('¿Está seguro de limpiar todo el formulario?')) {
                $('#formCompraManual')[0].reset();
                // Limpiar proveedor (select2)
                if ($('#proveedor_id').data('select2')) {
                    $('#proveedor_id').val(null).trigger('change');
                } else {
                    $('#proveedor_id').val('');
                }
                $('#contacto-proveedor').html('<i class="fas fa-info-circle me-1"></i>Selecciona un proveedor');
                $('#productos-container').empty();
                $('#productos-escaneados').html('<p class="text-muted text-center"><i class="fas fa-barcode fa-3x mb-3 d-block"></i>Los productos escaneados aparecerán aquí</p>');
                contadorProductos = 0;
                productosEscaneados = 0;
                actualizarResumen();
                $('#general-tab').tab('show');
            }
        }

        // Auto-focus en campos importantes
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            const target = $(e.target).attr("data-bs-target");
            if (target === '#general') {
                $('#proveedor_id').focus();
            } else if (target === '#scanner') {
                $('#codigo_barras_input').focus();
            }
        });
    </script>
</body>

</html>

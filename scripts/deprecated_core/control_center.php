<?php
// modulos/compras/ocr_remitos/control_center.php
session_start();
require_once '../../../config/config.php';
require_once 'dual_control_processor.php';
require_once 'dual_control_helpers.php';

// Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../../login.php');
    exit;
}

$dual_controller = new DualControlProcessor($conexion);

// Manejar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'process_compra':
                $result = procesarDocumentoCompra();
                break;
            case 'process_inventario':
                $result = procesarDocumentoInventario();
                break;
            case 'approve_control':
                $result = aprobarControl();
                break;
            case 'get_comparison':
                $result = obtenerComparacion();
                break;
            default:
                throw new Exception('Acción no válida');
        }

        echo json_encode(['success' => true, 'data' => $result]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

function procesarDocumentoCompra()
{
    global $dual_controller;

    if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo');
    }

    $proveedor_id = $_POST['proveedor_id'] ?? null;
    if (!$proveedor_id) {
        throw new Exception('Debe seleccionar un proveedor');
    }

    $upload_dir = '../../../assets/uploads/ocr_compras/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = uniqid('compra_') . '_' . $_FILES['documento']['name'];
    $upload_path = $upload_dir . $filename;

    if (!move_uploaded_file($_FILES['documento']['tmp_name'], $upload_path)) {
        throw new Exception('Error al guardar el archivo');
    }

    return $dual_controller->processCompraDocument($upload_path, $proveedor_id);
}

function procesarDocumentoInventario()
{
    global $dual_controller;

    if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo');
    }

    $upload_dir = '../../../assets/uploads/ocr_inventario/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = uniqid('inventario_') . '_' . $_FILES['documento']['name'];
    $upload_path = $upload_dir . $filename;

    if (!move_uploaded_file($_FILES['documento']['tmp_name'], $upload_path)) {
        throw new Exception('Error al guardar el archivo');
    }

    return $dual_controller->processInventarioDocument($upload_path);
}

function aprobarControl()
{
    global $dual_controller;

    $comparison_id = $_POST['comparison_id'] ?? '';
    $operario_id = $_SESSION['usuario_id'];
    $supervisor_id = $_POST['supervisor_id'] ?? $_SESSION['usuario_id'];
    $observaciones = $_POST['observaciones'] ?? '';

    if (!$comparison_id) {
        throw new Exception('ID de comparación requerido');
    }

    return $dual_controller->approveDoubleControl($comparison_id, $operario_id, $supervisor_id, $observaciones);
}

function obtenerComparacion()
{
    global $dual_controller;

    $comparison_id = $_POST['comparison_id'] ?? '';
    if (!$comparison_id) {
        throw new Exception('ID de comparación requerido');
    }

    return $dual_controller->getComparisonData($comparison_id);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Control OCR - Doble Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .upload-zone {
            border: 3px dashed #007bff;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .upload-zone.dragover {
            border-color: #28a745;
            background: #d4edda;
        }

        .comparison-card {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            margin: 10px 0;
            transition: all 0.3s ease;
        }

        .comparison-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .doc-preview {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            background: white;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 4px 8px;
        }

        .process-indicator {
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }

        .step-active {
            background: #007bff;
            color: white;
        }

        .step-completed {
            background: #28a745;
            color: white;
        }

        .step-pending {
            background: #6c757d;
            color: white;
        }

        .product-diff {
            border-left: 4px solid #007bff;
            padding-left: 15px;
            margin: 10px 0;
        }

        .diff-new {
            border-left-color: #28a745;
            background: #d4edda;
        }

        .diff-update {
            border-left-color: #ffc107;
            background: #fff3cd;
        }

        .diff-conflict {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-eye"></i> OCR Control Center</a>
            <span class="navbar-text">
                <i class="fas fa-user"></i> <?php echo $_SESSION['usuario_nombre']; ?>
            </span>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Panel de Control -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-control"></i> Panel de Control</h5>
                    </div>
                    <div class="card-body">
                        <!-- Tabs para Compras e Inventario -->
                        <ul class="nav nav-tabs" id="controlTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="compras-tab" data-bs-toggle="tab" data-bs-target="#compras" type="button">
                                    <i class="fas fa-truck"></i> Compras
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="inventario-tab" data-bs-toggle="tab" data-bs-target="#inventario" type="button">
                                    <i class="fas fa-boxes"></i> Inventario
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content mt-3" id="controlTabsContent">
                            <!-- Tab Compras -->
                            <div class="tab-pane fade show active" id="compras" role="tabpanel">
                                <form id="formCompras" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label">Proveedor</label>
                                        <select class="form-select" name="proveedor_id" required>
                                            <option value="">Seleccionar proveedor...</option>
                                            <?php
                                            $proveedores = $conexion->query("SELECT id, nombre FROM proveedores WHERE activo = 1 ORDER BY nombre");
                                            while ($prov = $proveedores->fetch_assoc()) {
                                                echo "<option value='{$prov['id']}'>{$prov['nombre']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="upload-zone" id="uploadCompras">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                        <h5>Subir Remito/Factura</h5>
                                        <p class="text-muted">Arrastra el archivo aquí o haz clic para seleccionar</p>
                                        <input type="file" name="documento" accept="image/*,.pdf" style="display: none;">
                                        <button type="button" class="btn btn-outline-primary" onclick="document.querySelector('#compras input[type=file]').click()">
                                            Seleccionar Archivo
                                        </button>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 mt-3">
                                        <i class="fas fa-magic"></i> Procesar con OCR
                                    </button>
                                </form>
                            </div>

                            <!-- Tab Inventario -->
                            <div class="tab-pane fade" id="inventario" role="tabpanel">
                                <form id="formInventario" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label">Categoría (Opcional)</label>
                                        <select class="form-select" name="categoria_id">
                                            <option value="">Todas las categorías</option>
                                            <?php
                                            $categorias = $conexion->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre");
                                            while ($cat = $categorias->fetch_assoc()) {
                                                echo "<option value='{$cat['id']}'>{$cat['nombre']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="upload-zone" id="uploadInventario">
                                        <i class="fas fa-clipboard-list fa-3x text-success mb-3"></i>
                                        <h5>Subir Lista de Inventario</h5>
                                        <p class="text-muted">Documentos de stock inicial, inventarios físicos, etc.</p>
                                        <input type="file" name="documento" accept="image/*,.pdf" style="display: none;">
                                        <button type="button" class="btn btn-outline-success" onclick="document.querySelector('#inventario input[type=file]').click()">
                                            Seleccionar Archivo
                                        </button>
                                    </div>

                                    <button type="submit" class="btn btn-success w-100 mt-3">
                                        <i class="fas fa-search"></i> Analizar Inventario
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progreso de Procesamiento -->
                <div class="card mt-3" id="progressCard" style="display: none;">
                    <div class="card-header bg-info text-white">
                        <h6><i class="fas fa-cogs"></i> Procesamiento OCR</h6>
                    </div>
                    <div class="card-body">
                        <div id="processSteps">
                            <div class="d-flex align-items-center mb-2">
                                <div class="process-indicator step-pending" id="step1">1</div>
                                <span>Análisis OCR</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="process-indicator step-pending" id="step2">2</div>
                                <span>Matching de Productos</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="process-indicator step-pending" id="step3">3</div>
                                <span>Generación de Control</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="process-indicator step-pending" id="step4">4</div>
                                <span>Preparación de Documentos</span>
                            </div>
                        </div>
                        <div class="progress mt-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="progressBar"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel de Resultados -->
            <div class="col-lg-8">
                <div id="resultadosPanel" style="display: none;">
                    <!-- Header de Resultados -->
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 id="resultTitle"><i class="fas fa-check-circle text-success"></i> Procesamiento Completado</h5>
                            <div id="resultActions">
                                <button class="btn btn-outline-primary btn-sm" onclick="imprimirDocumentoControl()">
                                    <i class="fas fa-print"></i> Imprimir Control
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="exportarComparacion()">
                                    <i class="fas fa-download"></i> Exportar
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row" id="resultStats">
                                <!-- Stats dinámicos -->
                            </div>
                        </div>
                    </div>

                    <!-- Comparación de Documentos -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="comparison-card">
                                <div class="card-header bg-secondary text-white">
                                    <h6><i class="fas fa-file-alt"></i> Documento Original</h6>
                                </div>
                                <div class="card-body">
                                    <div class="doc-preview" id="documentoOriginal">
                                        <!-- Contenido dinámico -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="comparison-card">
                                <div class="card-header bg-primary text-white">
                                    <h6><i class="fas fa-clipboard-check"></i> Documento de Control</h6>
                                </div>
                                <div class="card-body">
                                    <div class="doc-preview" id="documentoControl">
                                        <!-- Contenido dinámico -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de Aprobación -->
                    <div class="card mt-3">
                        <div class="card-header bg-warning text-dark">
                            <h6><i class="fas fa-exclamation-triangle"></i> Control de Aprobación</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <strong>INSTRUCCIONES DE DOBLE CONTROL:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Compare físicamente cada producto con ambos documentos</li>
                                    <li>Verifique códigos, descripciones, cantidades y precios</li>
                                    <li>Solo apruebe cuando ambos documentos coincidan perfectamente</li>
                                    <li>En caso de discrepancias, consulte con el supervisor</li>
                                </ol>
                            </div>

                            <form id="formAprobacion">
                                <input type="hidden" name="comparison_id" id="comparisonId">

                                <div class="mb-3">
                                    <label class="form-label">Supervisor Responsable</label>
                                    <select class="form-select" name="supervisor_id" required>
                                        <option value="">Seleccionar supervisor...</option>
                                        <?php
                                        $supervisores = $conexion->query("SELECT id, nombre FROM usuarios WHERE rol = 'supervisor' OR rol = 'admin' ORDER BY nombre");
                                        while ($sup = $supervisores->fetch_assoc()) {
                                            echo "<option value='{$sup['id']}'>{$sup['nombre']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Observaciones</label>
                                    <textarea class="form-control" name="observaciones" rows="3" placeholder="Observaciones del proceso de verificación..."></textarea>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="button" class="btn btn-outline-danger" onclick="rechazarControl()">
                                        <i class="fas fa-times"></i> Rechazar
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check-double"></i> Aprobar e Ingresar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentProcessing = null;

        // Event listeners para upload zones
        document.addEventListener('DOMContentLoaded', function() {
            setupUploadZones();
            setupForms();
        });

        function setupUploadZones() {
            ['uploadCompras', 'uploadInventario'].forEach(zoneId => {
                const zone = document.getElementById(zoneId);
                const input = zone.querySelector('input[type="file"]');

                zone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    zone.classList.add('dragover');
                });

                zone.addEventListener('dragleave', () => {
                    zone.classList.remove('dragover');
                });

                zone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    zone.classList.remove('dragover');
                    input.files = e.dataTransfer.files;
                    updateUploadZone(zone, input.files[0]);
                });

                input.addEventListener('change', (e) => {
                    updateUploadZone(zone, e.target.files[0]);
                });
            });
        }

        function updateUploadZone(zone, file) {
            if (file) {
                zone.innerHTML = `
                    <i class="fas fa-file-check fa-2x text-success mb-2"></i>
                    <h6>${file.name}</h6>
                    <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                `;
            }
        }

        function setupForms() {
            // Form Compras
            document.getElementById('formCompras').addEventListener('submit', async function(e) {
                e.preventDefault();
                await procesarDocumento('process_compra', this);
            });

            // Form Inventario
            document.getElementById('formInventario').addEventListener('submit', async function(e) {
                e.preventDefault();
                await procesarDocumento('process_inventario', this);
            });

            // Form Aprobación
            document.getElementById('formAprobacion').addEventListener('submit', async function(e) {
                e.preventDefault();
                await aprobarControl(this);
            });
        }

        async function procesarDocumento(action, form) {
            showProgress();

            const formData = new FormData(form);
            formData.append('action', action);

            try {
                updateProgress(25, 'step1');

                const response = await fetch('control_center.php', {
                    method: 'POST',
                    body: formData
                });

                updateProgress(50, 'step2');

                const result = await response.json();

                updateProgress(75, 'step3');

                if (result.success) {
                    updateProgress(100, 'step4');
                    displayResults(result.data);
                } else {
                    throw new Error(result.error);
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Error: ' + error.message);
                hideProgress();
            }
        }

        function showProgress() {
            document.getElementById('progressCard').style.display = 'block';
            document.getElementById('resultadosPanel').style.display = 'none';
        }

        function hideProgress() {
            document.getElementById('progressCard').style.display = 'none';
        }

        function updateProgress(percent, currentStep) {
            document.getElementById('progressBar').style.width = percent + '%';

            // Actualizar steps
            ['step1', 'step2', 'step3', 'step4'].forEach((stepId, index) => {
                const step = document.getElementById(stepId);
                if (stepId === currentStep) {
                    step.className = 'process-indicator step-active';
                } else if (index < parseInt(currentStep.replace('step', '')) - 1) {
                    step.className = 'process-indicator step-completed';
                    step.innerHTML = '<i class="fas fa-check"></i>';
                }
            });
        }

        function displayResults(data) {
            hideProgress();

            // Mostrar panel de resultados
            document.getElementById('resultadosPanel').style.display = 'block';

            // Actualizar stats
            const stats = document.getElementById('resultStats');
            stats.innerHTML = `
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-primary">${data.documento_proveedor?.productos_detectados || data.documento_original?.productos_detectados || 0}</h4>
                        <small>Productos Detectados</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-success">${Math.round((data.documento_proveedor?.confidence_ocr || data.documento_original?.confidence_ocr || 0))}%</h4>
                        <small>Precisión OCR</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-info">${data.comparison_id}</h4>
                        <small>ID Control</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-warning">${data.status}</h4>
                        <small>Estado</small>
                    </div>
                </div>
            `;

            // Mostrar documentos
            displayDocumentComparison(data);

            // Configurar formulario de aprobación
            document.getElementById('comparisonId').value = data.comparison_id;
        }

        function displayDocumentComparison(data) {
            const original = data.documento_proveedor || data.documento_original;
            const control = data.documento_control;

            // Documento Original
            let originalHTML = `
                <h6>Información del Documento</h6>
                <p><strong>Tipo:</strong> ${original.tipo_documento}</p>
                <p><strong>Archivo:</strong> ${original.archivo_original}</p>
                <p><strong>Productos detectados:</strong> ${original.productos_detectados}</p>
                <hr>
                <h6>Productos</h6>
            `;

            original.productos.forEach(producto => {
                originalHTML += `
                    <div class="product-diff">
                        <strong>${producto.codigo}</strong> - ${producto.descripcion}<br>
                        <small>Cantidad: ${producto.cantidad} | Precio: $${producto.precio || 0}</small>
                    </div>
                `;
            });

            document.getElementById('documentoOriginal').innerHTML = originalHTML;

            // Documento de Control
            let controlHTML = `
                <h6>Control ID: ${control.control_id}</h6>
                <p><strong>Estado:</strong> ${control.estado}</p>
                <p><strong>Fecha:</strong> ${control.fecha_generacion}</p>
                <hr>
                <h6>Productos con Acciones</h6>
            `;

            control.productos_control.forEach(producto => {
                const actionClass = getActionClass(producto.accion_recomendada);
                controlHTML += `
                    <div class="product-diff ${actionClass}">
                        <strong>${producto.codigo_proveedor || producto.codigo_detectado}</strong> - ${producto.descripcion_proveedor || producto.descripcion_detectada}<br>
                        <small>Cantidad: ${producto.cantidad_proveedor || producto.cantidad_detectada} | Acción: <strong>${producto.accion_recomendada}</strong></small>
                        ${producto.confidence_matching ? `<br><small>Coincidencia: ${Math.round(producto.confidence_matching * 100)}%</small>` : ''}
                    </div>
                `;
            });

            document.getElementById('documentoControl').innerHTML = controlHTML;
        }

        function getActionClass(action) {
            switch (action) {
                case 'crear_nuevo':
                    return 'diff-new';
                case 'actualizar_stock':
                case 'ajustar_stock':
                    return 'diff-update';
                case 'revisar_manual':
                case 'revisar_discrepancia':
                    return 'diff-conflict';
                default:
                    return '';
            }
        }

        async function aprobarControl(form) {
            const formData = new FormData(form);
            formData.append('action', 'approve_control');

            try {
                const response = await fetch('control_center.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(`✅ Control aprobado exitosamente!\n\nProductos procesados: ${result.data.productos_procesados}\nValor total: $${result.data.valor_total}`);
                    location.reload();
                } else {
                    throw new Error(result.error);
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Error al aprobar: ' + error.message);
            }
        }

        function imprimirDocumentoControl() {
            // Implementar impresión del documento de control
            window.print();
        }

        function exportarComparacion() {
            // Implementar exportación de la comparación
            alert('Función de exportación en desarrollo');
        }

        function rechazarControl() {
            if (confirm('¿Está seguro que desea rechazar este control?')) {
                alert('Control rechazado. Se requiere revisión manual.');
                location.reload();
            }
        }
    </script>
</body>

</html>
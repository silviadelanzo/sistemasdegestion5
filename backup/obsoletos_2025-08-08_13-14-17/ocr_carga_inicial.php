<?php
// modulos/inventario/ocr_carga_inicial.php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📦 Carga Inicial de Inventario con OCR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .upload-zone {
            border: 3px dashed #007bff;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }

        .upload-zone:hover,
        .upload-zone.dragover {
            border-color: #0056b3;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            transform: translateY(-2px);
        }

        .process-flow {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0;
        }

        .flow-step {
            flex: 1;
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin: 0 5px;
            position: relative;
        }

        .flow-step.active {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        .flow-step.completed {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .flow-step.pending {
            background: #f8f9fa;
            color: #6c757d;
            border: 2px dashed #dee2e6;
        }

        .flow-arrow {
            position: absolute;
            right: -20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #007bff;
        }

        .comparison-table {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .inventory-card {
            border-left: 5px solid #007bff;
            transition: all 0.3s ease;
        }

        .inventory-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .badge-new {
            background: linear-gradient(45deg, #007bff, #0056b3);
        }

        .badge-exists {
            background: linear-gradient(45deg, #28a745, #20c997);
        }

        .badge-conflict {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h1 class="mb-0"><i class="fas fa-boxes"></i> Carga Inicial de Inventario con OCR</h1>
                        <p class="mb-0">Sistema inteligente para carga masiva de productos con doble control</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flujo de Proceso -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="process-flow">
                    <div class="flow-step active" id="step-1">
                        <i class="fas fa-upload fa-2x mb-2"></i>
                        <h6>1. Subir Documentos</h6>
                        <small>Facturas, remitos, inventarios</small>
                        <div class="flow-arrow"><i class="fas fa-arrow-right"></i></div>
                    </div>
                    <div class="flow-step pending" id="step-2">
                        <i class="fas fa-robot fa-2x mb-2"></i>
                        <h6>2. Procesamiento OCR</h6>
                        <small>Extracción automática</small>
                        <div class="flow-arrow"><i class="fas fa-arrow-right"></i></div>
                    </div>
                    <div class="flow-step pending" id="step-3">
                        <i class="fas fa-balance-scale fa-2x mb-2"></i>
                        <h6>3. Comparación</h6>
                        <small>Con inventario actual</small>
                        <div class="flow-arrow"><i class="fas fa-arrow-right"></i></div>
                    </div>
                    <div class="flow-step pending" id="step-4">
                        <i class="fas fa-eye fa-2x mb-2"></i>
                        <h6>4. Revisión Manual</h6>
                        <small>Doble control</small>
                        <div class="flow-arrow"><i class="fas fa-arrow-right"></i></div>
                    </div>
                    <div class="flow-step pending" id="step-5">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h6>5. Aplicar Cambios</h6>
                        <small>Actualización final</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestañas principales -->
        <ul class="nav nav-tabs" id="inventoryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button" role="tab">
                    <i class="fas fa-cloud-upload-alt"></i> Subir Documentos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="processing-tab" data-bs-toggle="tab" data-bs-target="#processing" type="button" role="tab">
                    <i class="fas fa-cogs"></i> Procesamiento <span class="badge bg-warning ms-2" id="processing-count">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="comparison-tab" data-bs-toggle="tab" data-bs-target="#comparison" type="button" role="tab">
                    <i class="fas fa-columns"></i> Comparación <span class="badge bg-info ms-2" id="comparison-count">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="review-tab" data-bs-toggle="tab" data-bs-target="#review" type="button" role="tab">
                    <i class="fas fa-clipboard-check"></i> Revisión Final <span class="badge bg-success ms-2" id="review-count">0</span>
                </button>
            </li>
        </ul>

        <!-- Contenido de las pestañas -->
        <div class="tab-content" id="inventoryTabsContent">
            <!-- Pestaña de Subida -->
            <div class="tab-pane fade show active" id="upload" role="tabpanel">
                <div class="row mt-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-file-upload"></i> Subir Documentos de Inventario</h5>
                            </div>
                            <div class="card-body">
                                <div class="upload-zone" id="uploadZone">
                                    <i class="fas fa-cloud-upload-alt fa-4x mb-3 text-primary"></i>
                                    <h4>Arrastra documentos aquí o haz clic para seleccionar</h4>
                                    <p class="text-muted">Formatos soportados: PDF, JPG, PNG, TIF</p>
                                    <p class="text-muted">Tipos: Facturas, Remitos, Listas de inventario, Catálogos</p>
                                    <input type="file" id="fileInput" multiple accept=".pdf,.jpg,.jpeg,.png,.tif,.tiff" style="display: none;">
                                </div>

                                <div id="uploadProgress" class="mt-3" style="display: none;">
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                                            role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <div id="progressText" class="text-center mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-info-circle"></i> Información del Proceso</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <small>Documentos subidos:</small>
                                    <span class="badge bg-primary" id="uploaded-count">0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <small>En procesamiento:</small>
                                    <span class="badge bg-warning" id="processing-badge">0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <small>Productos detectados:</small>
                                    <span class="badge bg-info" id="products-detected">0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <small>Listos para revisión:</small>
                                    <span class="badge bg-success" id="ready-review">0</span>
                                </div>

                                <hr>

                                <h6 class="text-muted">Configuración</h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="autoDetectCategory" checked>
                                    <label class="form-check-label" for="autoDetectCategory">
                                        Auto-detectar categorías
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="fuzzyMatching" checked>
                                    <label class="form-check-label" for="fuzzyMatching">
                                        Coincidencia inteligente
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="priceComparison">
                                    <label class="form-check-label" for="priceComparison">
                                        Comparar precios
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h6><i class="fas fa-history"></i> Procesamiento Reciente</h6>
                            </div>
                            <div class="card-body">
                                <div id="recentProcessing">
                                    <p class="text-muted text-center">No hay procesamientos recientes</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pestaña de Procesamiento -->
            <div class="tab-pane fade" id="processing" role="tabpanel">
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-cogs"></i> Estado del Procesamiento OCR</h5>
                                <button class="btn btn-outline-primary btn-sm" onclick="refreshProcessing()">
                                    <i class="fas fa-sync"></i> Actualizar
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="processingList">
                                    <!-- Los elementos se cargarán dinámicamente -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pestaña de Comparación -->
            <div class="tab-pane fade" id="comparison" role="tabpanel">
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="comparison-table">
                            <div class="card-header bg-light">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6><i class="fas fa-file-alt"></i> Detectado en Documento</h6>
                                    </div>
                                    <div class="col-md-4">
                                        <h6><i class="fas fa-database"></i> Inventario Actual</h6>
                                    </div>
                                    <div class="col-md-4">
                                        <h6><i class="fas fa-tasks"></i> Acción Recomendada</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body" id="comparisonResults">
                                <!-- Resultados de comparación se cargarán aquí -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pestaña de Revisión Final -->
            <div class="tab-pane fade" id="review" role="tabpanel">
                <div class="row mt-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-clipboard-check"></i> Revisión Final - Doble Control</h5>
                            </div>
                            <div class="card-body">
                                <div id="finalReviewList">
                                    <!-- Lista de productos para revisión final -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-chart-pie"></i> Resumen de Cambios</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <small>Productos nuevos:</small>
                                    <span class="badge badge-new" id="new-products-count">0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <small>Actualizaciones:</small>
                                    <span class="badge badge-exists" id="updates-count">0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <small>Conflictos:</small>
                                    <span class="badge badge-conflict" id="conflicts-count">0</span>
                                </div>

                                <hr>

                                <button class="btn btn-success w-100" id="applyChangesBtn" disabled>
                                    <i class="fas fa-check"></i> Aplicar Todos los Cambios
                                </button>

                                <button class="btn btn-outline-secondary w-100 mt-2" onclick="exportReport()">
                                    <i class="fas fa-file-export"></i> Exportar Reporte
                                </button>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h6><i class="fas fa-user-check"></i> Control de Calidad</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-3">
                                    <label>Supervisor Responsable:</label>
                                    <select class="form-control" id="supervisorSelect">
                                        <option value="">Seleccionar supervisor...</option>
                                        <!-- Opciones se cargarán dinámicamente -->
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Observaciones:</label>
                                    <textarea class="form-control" id="reviewNotes" rows="3"
                                        placeholder="Notas adicionales del proceso..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Conflicto -->
    <div class="modal fade" id="conflictModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Resolver Conflicto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Datos del Documento</h6>
                            <div id="documentData"></div>
                        </div>
                        <div class="col-md-6">
                            <h6>Datos del Sistema</h6>
                            <div id="systemData"></div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Resolución</h6>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-primary" onclick="resolveConflict('document')">
                                    Usar datos del documento
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="resolveConflict('system')">
                                    Mantener datos del sistema
                                </button>
                                <button type="button" class="btn btn-outline-info" onclick="resolveConflict('manual')">
                                    Editar manualmente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        let processingQueue = [];
        let comparisonResults = [];
        let finalReviewData = [];

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            setupUploadZone();
            loadSupervisors();
            initializeCounters();
        });

        function setupUploadZone() {
            const uploadZone = document.getElementById('uploadZone');
            const fileInput = document.getElementById('fileInput');

            uploadZone.addEventListener('click', () => fileInput.click());
            uploadZone.addEventListener('dragover', handleDragOver);
            uploadZone.addEventListener('drop', handleDrop);
            fileInput.addEventListener('change', handleFileSelect);
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.currentTarget.classList.add('dragover');
        }

        function handleDrop(e) {
            e.preventDefault();
            e.currentTarget.classList.remove('dragover');
            const files = e.dataTransfer.files;
            processFiles(files);
        }

        function handleFileSelect(e) {
            const files = e.target.files;
            processFiles(files);
        }

        function processFiles(files) {
            console.log(`Procesando ${files.length} archivos...`);

            // Mostrar progreso
            const progressContainer = document.getElementById('uploadProgress');
            const progressBar = progressContainer.querySelector('.progress-bar');
            const progressText = document.getElementById('progressText');

            progressContainer.style.display = 'block';

            // Simular procesamiento
            let processed = 0;
            const total = files.length;

            Array.from(files).forEach((file, index) => {
                setTimeout(() => {
                    processed++;
                    const percent = (processed / total) * 100;

                    progressBar.style.width = percent + '%';
                    progressText.textContent = `Procesando ${file.name}... (${processed}/${total})`;

                    // Agregar a cola de procesamiento
                    addToProcessingQueue(file);

                    if (processed === total) {
                        setTimeout(() => {
                            progressContainer.style.display = 'none';
                            updateStepStatus(2, 'active');
                            updateTab('processing-tab');
                        }, 1000);
                    }
                }, index * 500);
            });
        }

        function addToProcessingQueue(file) {
            const item = {
                id: Date.now() + Math.random(),
                filename: file.name,
                status: 'processing',
                progress: 0,
                detected_products: 0,
                timestamp: new Date()
            };

            processingQueue.push(item);
            updateProcessingList();
            updateCounters();

            // Simular procesamiento OCR
            simulateOCRProcessing(item);
        }

        function simulateOCRProcessing(item) {
            const interval = setInterval(() => {
                item.progress += Math.random() * 20;

                if (item.progress >= 100) {
                    item.progress = 100;
                    item.status = 'completed';
                    item.detected_products = Math.floor(Math.random() * 50) + 10;

                    clearInterval(interval);

                    // Mover a comparación
                    generateComparisonData(item);
                    updateStepStatus(3, 'active');
                }

                updateProcessingList();
            }, 1000);
        }

        function generateComparisonData(processedItem) {
            // Simular datos de comparación
            for (let i = 0; i < processedItem.detected_products; i++) {
                const comparison = {
                    id: Date.now() + Math.random(),
                    detected: {
                        codigo: 'ABC' + (Math.floor(Math.random() * 1000) + 100),
                        descripcion: 'Producto detectado ' + (i + 1),
                        cantidad: Math.floor(Math.random() * 100) + 1,
                        precio: (Math.random() * 100 + 10).toFixed(2)
                    },
                    existing: Math.random() > 0.3 ? {
                        codigo: 'ABC' + (Math.floor(Math.random() * 1000) + 100),
                        descripcion: 'Producto existente ' + (i + 1),
                        stock: Math.floor(Math.random() * 200),
                        precio: (Math.random() * 100 + 10).toFixed(2)
                    } : null,
                    action: Math.random() > 0.3 ? (Math.random() > 0.5 ? 'update' : 'new') : 'conflict',
                    confidence: Math.random() * 0.3 + 0.7
                };

                comparisonResults.push(comparison);
            }

            updateComparisonView();
            updateCounters();
        }

        function updateProcessingList() {
            const container = document.getElementById('processingList');

            if (processingQueue.length === 0) {
                container.innerHTML = '<p class="text-muted text-center">No hay documentos en procesamiento</p>';
                return;
            }

            container.innerHTML = processingQueue.map(item => `
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${item.filename}</h6>
                                <small class="text-muted">Estado: ${item.status}</small>
                            </div>
                            <div class="text-end">
                                <div class="badge ${item.status === 'completed' ? 'bg-success' : 'bg-warning'}">
                                    ${item.status === 'completed' ? item.detected_products + ' productos' : Math.round(item.progress) + '%'}
                                </div>
                            </div>
                        </div>
                        ${item.status === 'processing' ? `
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar" style="width: ${item.progress}%"></div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }

        function updateComparisonView() {
            const container = document.getElementById('comparisonResults');

            if (comparisonResults.length === 0) {
                container.innerHTML = '<p class="text-muted text-center">No hay datos para comparar</p>';
                return;
            }

            container.innerHTML = comparisonResults.slice(0, 10).map(item => `
                <div class="row mb-3 p-3 border-bottom">
                    <div class="col-md-4">
                        <div class="inventory-card card h-100">
                            <div class="card-body">
                                <h6>${item.detected.codigo}</h6>
                                <p class="small">${item.detected.descripcion}</p>
                                <div class="d-flex justify-content-between">
                                    <small>Cant: ${item.detected.cantidad}</small>
                                    <small>$${item.detected.precio}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        ${item.existing ? `
                            <div class="inventory-card card h-100">
                                <div class="card-body">
                                    <h6>${item.existing.codigo}</h6>
                                    <p class="small">${item.existing.descripcion}</p>
                                    <div class="d-flex justify-content-between">
                                        <small>Stock: ${item.existing.stock}</small>
                                        <small>$${item.existing.precio}</small>
                                    </div>
                                </div>
                            </div>
                        ` : `
                            <div class="card h-100 bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-plus-circle fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">Producto nuevo</p>
                                </div>
                            </div>
                        `}
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <span class="badge ${getActionBadgeClass(item.action)} mb-2">
                                    ${getActionText(item.action)}
                                </span>
                                <br>
                                <small class="text-muted">Confianza: ${Math.round(item.confidence * 100)}%</small>
                                <br>
                                ${item.action === 'conflict' ? `
                                    <button class="btn btn-sm btn-outline-warning mt-2" onclick="showConflictModal('${item.id}')">
                                        Resolver
                                    </button>
                                ` : `
                                    <button class="btn btn-sm btn-outline-success mt-2" onclick="approveItem('${item.id}')">
                                        Aprobar
                                    </button>
                                `}
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function getActionBadgeClass(action) {
            switch (action) {
                case 'new':
                    return 'badge-new';
                case 'update':
                    return 'badge-exists';
                case 'conflict':
                    return 'badge-conflict';
                default:
                    return 'bg-secondary';
            }
        }

        function getActionText(action) {
            switch (action) {
                case 'new':
                    return 'Crear Nuevo';
                case 'update':
                    return 'Actualizar';
                case 'conflict':
                    return 'Conflicto';
                default:
                    return 'Revisar';
            }
        }

        function updateStepStatus(stepNumber, status) {
            // Actualizar estados de los pasos
            for (let i = 1; i <= 5; i++) {
                const step = document.getElementById(`step-${i}`);
                step.className = 'flow-step';

                if (i < stepNumber) {
                    step.classList.add('completed');
                } else if (i === stepNumber) {
                    step.classList.add(status);
                } else {
                    step.classList.add('pending');
                }
            }
        }

        function updateTab(tabId) {
            // Cambiar a la pestaña especificada
            const tab = document.getElementById(tabId);
            if (tab) {
                tab.click();
            }
        }

        function updateCounters() {
            document.getElementById('uploaded-count').textContent = processingQueue.length;
            document.getElementById('processing-badge').textContent = processingQueue.filter(item => item.status === 'processing').length;
            document.getElementById('processing-count').textContent = processingQueue.filter(item => item.status === 'processing').length;
            document.getElementById('products-detected').textContent = processingQueue.reduce((sum, item) => sum + item.detected_products, 0);
            document.getElementById('comparison-count').textContent = comparisonResults.length;

            // Contadores de revisión final
            const newCount = comparisonResults.filter(item => item.action === 'new').length;
            const updateCount = comparisonResults.filter(item => item.action === 'update').length;
            const conflictCount = comparisonResults.filter(item => item.action === 'conflict').length;

            document.getElementById('new-products-count').textContent = newCount;
            document.getElementById('updates-count').textContent = updateCount;
            document.getElementById('conflicts-count').textContent = conflictCount;
            document.getElementById('review-count').textContent = newCount + updateCount + conflictCount;
        }

        function loadSupervisors() {
            // Simular carga de supervisores
            const select = document.getElementById('supervisorSelect');
            const supervisors = ['Juan Pérez', 'María García', 'Carlos López'];

            supervisors.forEach(supervisor => {
                const option = document.createElement('option');
                option.value = supervisor;
                option.textContent = supervisor;
                select.appendChild(option);
            });
        }

        function initializeCounters() {
            updateCounters();
        }

        // Funciones adicionales
        function refreshProcessing() {
            updateProcessingList();
        }

        function showConflictModal(itemId) {
            const item = comparisonResults.find(r => r.id == itemId);
            if (item) {
                // Mostrar modal de conflicto
                new bootstrap.Modal(document.getElementById('conflictModal')).show();
            }
        }

        function approveItem(itemId) {
            console.log('Aprobando item:', itemId);
            // Implementar lógica de aprobación
        }

        function resolveConflict(resolution) {
            console.log('Resolviendo conflicto:', resolution);
            // Implementar lógica de resolución
        }

        function exportReport() {
            console.log('Exportando reporte...');
            // Implementar exportación
        }
    </script>
</body>

</html>
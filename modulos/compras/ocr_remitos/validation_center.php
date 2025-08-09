<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎯 Centro de Validación OCR - Precisión 100%</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .validation-card {
            border-left: 5px solid #007bff;
            transition: all 0.3s ease;
        }

        .validation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .confidence-meter {
            height: 20px;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }

        .confidence-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .confidence-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.5);
            font-size: 0.8em;
        }

        .similarity-badge {
            background: linear-gradient(45deg, #007bff, #28a745);
            color: white;
            border-radius: 15px;
            padding: 2px 8px;
            font-size: 0.7em;
        }

        .critical-item {
            border-left-color: #dc3545 !important;
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }

        .auto-approved {
            border-left-color: #28a745 !important;
            background-color: #f8f9fa;
        }

        .needs-review {
            border-left-color: #ffc107 !important;
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
                        <h1 class="mb-0"><i class="fas fa-bullseye"></i> Centro de Validación OCR - Precisión 100%</h1>
                        <p class="mb-0">Sistema inteligente de validación humana con aprendizaje automático</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas en tiempo real -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-robot fa-2x text-success mb-2"></i>
                        <h5 class="card-title">Auto-Procesados</h5>
                        <h3 class="text-success" id="auto-count">0</h3>
                        <small class="text-muted">Sin intervención humana</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-eye fa-2x text-warning mb-2"></i>
                        <h5 class="card-title">En Validación</h5>
                        <h3 class="text-warning" id="validation-count">0</h3>
                        <small class="text-muted">Requieren revisión</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                        <h5 class="card-title">Críticos</h5>
                        <h3 class="text-danger" id="critical-count">0</h3>
                        <small class="text-muted">Revisión urgente</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                        <h5 class="card-title">Precisión</h5>
                        <h3 class="text-info" id="accuracy-rate">98.5%</h3>
                        <small class="text-muted">Último procesamiento</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pestañas de navegación -->
        <ul class="nav nav-tabs" id="validationTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                    <i class="fas fa-clock"></i> Pendientes de Validación <span class="badge bg-warning ms-2" id="pending-badge">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="critical-tab" data-bs-toggle="tab" data-bs-target="#critical" type="button" role="tab">
                    <i class="fas fa-exclamation-circle"></i> Críticos <span class="badge bg-danger ms-2" id="critical-badge">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="processed-tab" data-bs-toggle="tab" data-bs-target="#processed" type="button" role="tab">
                    <i class="fas fa-check-circle"></i> Auto-Procesados <span class="badge bg-success ms-2" id="processed-badge">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="learning-tab" data-bs-toggle="tab" data-bs-target="#learning" type="button" role="tab">
                    <i class="fas fa-brain"></i> Aprendizaje IA
                </button>
            </li>
        </ul>

        <!-- Contenido de las pestañas -->
        <div class="tab-content" id="validationTabsContent">
            <!-- Pendientes de Validación -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel">
                <div class="row mt-3" id="pending-items">
                    <!-- Los items se cargarán dinámicamente -->
                </div>
            </div>

            <!-- Elementos Críticos -->
            <div class="tab-pane fade" id="critical" role="tabpanel">
                <div class="row mt-3" id="critical-items">
                    <!-- Los items críticos se cargarán dinámicamente -->
                </div>
            </div>

            <!-- Auto-Procesados -->
            <div class="tab-pane fade" id="processed" role="tabpanel">
                <div class="row mt-3" id="processed-items">
                    <!-- Los items procesados se cargarán dinámicamente -->
                </div>
            </div>

            <!-- Panel de Aprendizaje IA -->
            <div class="tab-pane fade" id="learning" role="tabpanel">
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar"></i> Estadísticas de Aprendizaje</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="learningChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-sliders-h"></i> Umbrales de Confianza</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label>Auto-Procesamiento (>= 98%)</label>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-success" style="width: 98%">98%</div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label>Validación Humana (80-97%)</label>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-warning" style="width: 80%">80%</div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label>Revisión Crítica (< 80%)</label>
                                            <div class="progress mb-2">
                                                <div class="progress-bar bg-danger" style="width: 80%">
                                                    < 80%</div>
                                                </div>
                                            </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Validación Detallada -->
        <div class="modal fade" id="validationModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-search"></i> Validación Detallada</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Datos Detectados por OCR</h6>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <p><strong>Código:</strong> <span id="modal-codigo"></span></p>
                                        <p><strong>Descripción:</strong> <span id="modal-descripcion"></span></p>
                                        <p><strong>Cantidad:</strong> <span id="modal-cantidad"></span></p>
                                        <p><strong>Precio:</strong> <span id="modal-precio"></span></p>
                                        <div class="confidence-meter">
                                            <div class="confidence-fill" id="modal-confidence-fill"></div>
                                            <div class="confidence-text" id="modal-confidence-text"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Productos Similares Encontrados</h6>
                                <div id="modal-similar-products"></div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Acciones de Validación</h6>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-success" onclick="validateProduct('approve')">
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                    <button type="button" class="btn btn-warning" onclick="validateProduct('modify')">
                                        <i class="fas fa-edit"></i> Modificar
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="validateProduct('reject')">
                                        <i class="fas fa-times"></i> Rechazar
                                    </button>
                                    <button type="button" class="btn btn-info" onclick="validateProduct('create_new')">
                                        <i class="fas fa-plus"></i> Crear Nuevo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            let currentValidationId = null;

            // Cargar datos iniciales
            document.addEventListener('DOMContentLoaded', function() {
                loadValidationData();
                setupRealTimeUpdates();
                initializeLearningChart();
            });

            function loadValidationData() {
                // Simulación de datos para demostración
                const mockData = {
                    pending: [{
                            id: 1,
                            codigo: 'ABC123',
                            descripcion: 'Tornillo Phillips 3x20mm',
                            cantidad: 100,
                            precio: 2.50,
                            confidence: 0.87,
                            similarProducts: [{
                                    codigo: 'ABC12',
                                    descripcion: 'Tornillo Phillips 3x25mm',
                                    similarity: 0.92
                                },
                                {
                                    codigo: 'ABC124',
                                    descripcion: 'Tornillo Phillips 4x20mm',
                                    similarity: 0.85
                                }
                            ]
                        },
                        {
                            id: 2,
                            codigo: 'DEF456',
                            descripcion: 'Tuerca hexagonal M6',
                            cantidad: 50,
                            precio: 1.75,
                            confidence: 0.91,
                            similarProducts: [{
                                codigo: 'DEF45',
                                descripcion: 'Tuerca hexagonal M8',
                                similarity: 0.88
                            }]
                        }
                    ],
                    critical: [{
                        id: 3,
                        codigo: 'XYZ789',
                        descripcion: 'Producto no identificado claramente',
                        cantidad: 25,
                        precio: 0,
                        confidence: 0.45,
                        reason: 'low_confidence_critical_review'
                    }],
                    processed: [{
                        id: 4,
                        codigo: 'GHI012',
                        descripcion: 'Cable eléctrico 2.5mm',
                        cantidad: 200,
                        precio: 3.20,
                        confidence: 0.99,
                        action: 'stock_updated'
                    }]
                };

                renderPendingItems(mockData.pending);
                renderCriticalItems(mockData.critical);
                renderProcessedItems(mockData.processed);
                updateCounters(mockData);
            }

            function renderPendingItems(items) {
                const container = document.getElementById('pending-items');
                container.innerHTML = '';

                items.forEach(item => {
                    const card = createValidationCard(item, 'needs-review');
                    container.appendChild(card);
                });
            }

            function renderCriticalItems(items) {
                const container = document.getElementById('critical-items');
                container.innerHTML = '';

                items.forEach(item => {
                    const card = createValidationCard(item, 'critical-item');
                    container.appendChild(card);
                });
            }

            function renderProcessedItems(items) {
                const container = document.getElementById('processed-items');
                container.innerHTML = '';

                items.forEach(item => {
                    const card = createValidationCard(item, 'auto-approved');
                    container.appendChild(card);
                });
            }

            function createValidationCard(item, cardClass) {
                const col = document.createElement('div');
                col.className = 'col-md-6 col-lg-4 mb-3';

                const confidenceColor = getConfidenceColor(item.confidence);
                const confidenceWidth = Math.round(item.confidence * 100);

                col.innerHTML = `
                <div class="card validation-card ${cardClass}" onclick="openValidationModal(${item.id})">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title mb-0">${item.codigo}</h6>
                            <span class="similarity-badge">${confidenceWidth}%</span>
                        </div>
                        <p class="card-text small">${item.descripcion}</p>
                        <div class="row small">
                            <div class="col-6">
                                <strong>Cant:</strong> ${item.cantidad}
                            </div>
                            <div class="col-6">
                                <strong>Precio:</strong> $${item.precio}
                            </div>
                        </div>
                        <div class="confidence-meter mt-2">
                            <div class="confidence-fill ${confidenceColor}" style="width: ${confidenceWidth}%"></div>
                            <div class="confidence-text">${confidenceWidth}% confianza</div>
                        </div>
                        ${item.similarProducts ? `
                            <small class="text-muted mt-1">
                                <i class="fas fa-link"></i> ${item.similarProducts.length} productos similares
                            </small>
                        ` : ''}
                    </div>
                </div>
            `;

                return col;
            }

            function getConfidenceColor(confidence) {
                if (confidence >= 0.95) return 'bg-success';
                if (confidence >= 0.80) return 'bg-warning';
                return 'bg-danger';
            }

            function updateCounters(data) {
                document.getElementById('auto-count').textContent = data.processed.length;
                document.getElementById('validation-count').textContent = data.pending.length;
                document.getElementById('critical-count').textContent = data.critical.length;

                document.getElementById('pending-badge').textContent = data.pending.length;
                document.getElementById('critical-badge').textContent = data.critical.length;
                document.getElementById('processed-badge').textContent = data.processed.length;
            }

            function openValidationModal(itemId) {
                currentValidationId = itemId;

                // Simular carga de datos del item
                const mockItem = {
                    codigo: 'ABC123',
                    descripcion: 'Tornillo Phillips 3x20mm',
                    cantidad: 100,
                    precio: 2.50,
                    confidence: 0.87,
                    similarProducts: [{
                            codigo: 'ABC12',
                            descripcion: 'Tornillo Phillips 3x25mm',
                            similarity: 0.92,
                            stock: 50
                        },
                        {
                            codigo: 'ABC124',
                            descripcion: 'Tornillo Phillips 4x20mm',
                            similarity: 0.85,
                            stock: 30
                        }
                    ]
                };

                // Rellenar modal con datos
                document.getElementById('modal-codigo').textContent = mockItem.codigo;
                document.getElementById('modal-descripcion').textContent = mockItem.descripcion;
                document.getElementById('modal-cantidad').textContent = mockItem.cantidad;
                document.getElementById('modal-precio').textContent = '$' + mockItem.precio;

                const confidencePercent = Math.round(mockItem.confidence * 100);
                const confidenceFill = document.getElementById('modal-confidence-fill');
                const confidenceText = document.getElementById('modal-confidence-text');

                confidenceFill.style.width = confidencePercent + '%';
                confidenceFill.className = 'confidence-fill ' + getConfidenceColor(mockItem.confidence);
                confidenceText.textContent = confidencePercent + '%';

                // Mostrar productos similares
                const similarContainer = document.getElementById('modal-similar-products');
                similarContainer.innerHTML = '';

                mockItem.similarProducts.forEach(product => {
                    const similarityPercent = Math.round(product.similarity * 100);
                    similarContainer.innerHTML += `
                    <div class="card mb-2">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between">
                                <small><strong>${product.codigo}</strong></small>
                                <span class="badge bg-primary">${similarityPercent}%</span>
                            </div>
                            <small>${product.descripcion}</small>
                            <br><small class="text-muted">Stock: ${product.stock}</small>
                        </div>
                    </div>
                `;
                });

                new bootstrap.Modal(document.getElementById('validationModal')).show();
            }

            function validateProduct(action) {
                console.log(`Validating product ${currentValidationId} with action: ${action}`);

                // Aquí iría la llamada AJAX al backend
                // fetch('/validate_ocr_product', { ... })

                // Simular validación exitosa
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('validationModal')).hide();
                    loadValidationData(); // Recargar datos

                    // Mostrar notificación
                    showNotification(`Producto ${action === 'approve' ? 'aprobado' : action === 'reject' ? 'rechazado' : 'modificado'} exitosamente`, 'success');
                }, 500);
            }

            function showNotification(message, type) {
                // Implementar sistema de notificaciones
                const alertClass = type === 'success' ? 'alert-success' : 'alert-warning';
                const notification = document.createElement('div');
                notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
                notification.style.top = '20px';
                notification.style.right = '20px';
                notification.style.zIndex = '9999';
                notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }

            function setupRealTimeUpdates() {
                // Actualizar datos cada 30 segundos
                setInterval(loadValidationData, 30000);
            }

            function initializeLearningChart() {
                const ctx = document.getElementById('learningChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Precisión del Sistema (%)',
                            data: [85, 89, 92, 94, 96, 98.5],
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: false,
                                min: 80,
                                max: 100
                            }
                        }
                    }
                });
            }
        </script>
</body>

</html>
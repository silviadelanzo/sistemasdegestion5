<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();

// Obtener datos necesarios
$proveedores = $pdo->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC);
$categorias = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$lugares = $pdo->query("SELECT * FROM lugares WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📄 Importar CSV - Carga Masiva</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }

        .main-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 1400px;
            overflow: hidden;
        }

        .header-csv {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .upload-zone {
            border: 3px dashed #ffc107;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            margin: 30px;
            background: linear-gradient(135deg, #fff8e1, #ffecb3);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .upload-zone:hover {
            border-color: #ff9800;
            background: linear-gradient(135deg, #fff3c4, #ffe082);
            transform: translateY(-2px);
        }

        .upload-zone.dragover {
            border-color: #4caf50;
            background: linear-gradient(135deg, #e8f5e8, #c8e6c8);
        }

        .nav-tabs-csv {
            background: linear-gradient(90deg, #fff8e1, #ffecb3);
            border-bottom: 3px solid #ffc107;
            padding: 0 30px;
        }

        .nav-tabs-csv .nav-link {
            border: none;
            border-radius: 15px 15px 0 0;
            padding: 15px 25px;
            margin-right: 5px;
            font-weight: 600;
            color: #495057;
            transition: all 0.3s ease;
        }

        .nav-tabs-csv .nav-link.active {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }

        .preview-table {
            max-height: 400px;
            overflow-y: auto;
            border: 2px solid #dee2e6;
            border-radius: 10px;
        }

        .mapping-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid #dee2e6;
            border-radius: 12px;
            padding: 20px;
            margin: 10px 0;
            transition: all 0.3s ease;
        }

        .mapping-card:hover {
            border-color: #ffc107;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.2);
        }

        .validation-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }

        .validation-item.error {
            border-color: #dc3545;
            background: #f8d7da;
        }

        .validation-item.warning {
            border-color: #ffc107;
            background: #fff3cd;
        }

        .validation-item.success {
            border-color: #28a745;
            background: #d4edda;
        }

        .progress-step {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }

        .step-circle.active {
            background: #ffc107;
            color: white;
        }

        .step-circle.completed {
            background: #28a745;
            color: white;
        }

        .btn-csv {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            border: none;
            color: white;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-csv:hover {
            background: linear-gradient(135deg, #fd7e14, #dc3545);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }
    </style>
</head>

<body>
    <div class="main-card">
        <!-- Header -->
        <div class="header-csv">
            <h1 class="mb-3">
                <i class="fas fa-file-csv fa-2x"></i><br>
                Importación Masiva CSV
            </h1>
            <p class="lead mb-4">Carga miles de productos desde archivos CSV exportados</p>
            <div class="row text-center">
                <div class="col-md-3">
                    <h3 id="total-filas">0</h3>
                    <small>Filas Detectadas</small>
                </div>
                <div class="col-md-3">
                    <h3 id="total-validas">0</h3>
                    <small>Válidas</small>
                </div>
                <div class="col-md-3">
                    <h3 id="total-errores">0</h3>
                    <small>Con Errores</small>
                </div>
                <div class="col-md-3">
                    <h3 id="progreso-import">0%</h3>
                    <small>Progreso</small>
                </div>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="d-flex justify-content-between">
                        <div class="progress-step">
                            <div class="step-circle active" id="step-1">1</div>
                            <span>Subir Archivo</span>
                        </div>
                        <div class="progress-step">
                            <div class="step-circle" id="step-2">2</div>
                            <span>Mapear Campos</span>
                        </div>
                        <div class="progress-step">
                            <div class="step-circle" id="step-3">3</div>
                            <span>Validar Datos</span>
                        </div>
                        <div class="progress-step">
                            <div class="step-circle" id="step-4">4</div>
                            <span>Importar</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs nav-tabs-csv" id="csvTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button" role="tab">
                    <i class="fas fa-upload"></i> Subir Archivo
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="mapping-tab" data-bs-toggle="tab" data-bs-target="#mapping" type="button" role="tab">
                    <i class="fas fa-map"></i> Mapeo de Campos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="validation-tab" data-bs-toggle="tab" data-bs-target="#validation" type="button" role="tab">
                    <i class="fas fa-check-circle"></i> Validación
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="import-tab" data-bs-toggle="tab" data-bs-target="#import" type="button" role="tab">
                    <i class="fas fa-database"></i> Importar
                </button>
            </li>
        </ul>

        <div class="tab-content p-4">
            <!-- Tab Upload -->
            <div class="tab-pane fade show active" id="upload" role="tabpanel">
                <div class="upload-zone" id="upload-zone" onclick="document.getElementById('csv-file').click()">
                    <i class="fas fa-cloud-upload-alt fa-4x text-warning mb-3"></i>
                    <h4>Arrastra tu archivo CSV aquí</h4>
                    <p class="text-muted mb-4">O haz clic para seleccionar archivo</p>
                    <p class="small text-muted">Formatos soportados: .csv, .txt (máximo 10MB)</p>
                    <input type="file" id="csv-file" accept=".csv,.txt" style="display: none;">
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-info-circle me-2"></i>Formato Requerido</h5>
                            </div>
                            <div class="card-body">
                                <p>Tu archivo CSV debe contener las siguientes columnas:</p>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>Nombre del producto</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Código/SKU</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Cantidad</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Precio (opcional)</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Categoría (opcional)</li>
                                </ul>
                                <a href="#" class="btn btn-outline-warning btn-sm" onclick="descargarPlantilla()">
                                    <i class="fas fa-download me-2"></i>Descargar Plantilla
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-cogs me-2"></i>Configuración</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Separador de Columnas</label>
                                    <select class="form-select" id="separador">
                                        <option value="," selected>Coma (,)</option>
                                        <option value=";">Punto y coma (;)</option>
                                        <option value="\t">Tabulación</option>
                                        <option value="|">Pipe (|)</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Proveedor por Defecto</label>
                                    <select class="form-select" id="proveedor-defecto">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($proveedores as $proveedor): ?>
                                            <option value="<?php echo $proveedor['id']; ?>">
                                                <?php echo htmlspecialchars($proveedor['razon_social']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="primera-fila-headers" checked>
                                    <label class="form-check-label" for="primera-fila-headers">
                                        Primera fila contiene encabezados
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview del archivo -->
                <div id="file-preview" class="mt-4" style="display: none;">
                    <h5><i class="fas fa-eye me-2"></i>Vista Previa del Archivo</h5>
                    <div class="preview-table">
                        <table class="table table-striped" id="preview-table">
                            <!-- Se llenará dinámicamente -->
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <button class="btn btn-csv" onclick="siguientePaso(2)">
                            <i class="fas fa-arrow-right me-2"></i>Continuar al Mapeo
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab Mapping -->
            <div class="tab-pane fade" id="mapping" role="tabpanel">
                <h4 class="mb-4"><i class="fas fa-map me-2"></i>Mapeo de Campos</h4>
                <p class="text-muted mb-4">Relaciona las columnas de tu archivo con los campos del sistema</p>
                
                <div id="mapping-container">
                    <!-- Se generará dinámicamente -->
                </div>

                <div class="alert alert-info mt-4">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Tip:</strong> Los campos marcados con * son obligatorios. Los campos opcionales ayudan a organizar mejor tus productos.
                </div>

                <div class="text-end">
                    <button class="btn btn-secondary me-2" onclick="siguientePaso(1)">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </button>
                    <button class="btn btn-csv" onclick="validarMapeo()">
                        <i class="fas fa-check me-2"></i>Validar Mapeo
                    </button>
                </div>
            </div>

            <!-- Tab Validation -->
            <div class="tab-pane fade" id="validation" role="tabpanel">
                <h4 class="mb-4"><i class="fas fa-check-circle me-2"></i>Validación de Datos</h4>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success" id="count-valid">0</h3>
                                <p class="mb-0">Registros Válidos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning" id="count-warning">0</h3>
                                <p class="mb-0">Con Advertencias</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-danger" id="count-error">0</h3>
                                <p class="mb-0">Con Errores</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info" id="count-duplicate">0</h3>
                                <p class="mb-0">Duplicados</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Filtrar por tipo:</label>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="filter-type" id="filter-all" checked>
                        <label class="btn btn-outline-secondary" for="filter-all">Todos</label>
                        
                        <input type="radio" class="btn-check" name="filter-type" id="filter-errors">
                        <label class="btn btn-outline-danger" for="filter-errors">Solo Errores</label>
                        
                        <input type="radio" class="btn-check" name="filter-type" id="filter-warnings">
                        <label class="btn btn-outline-warning" for="filter-warnings">Solo Advertencias</label>
                    </div>
                </div>

                <div id="validation-results">
                    <!-- Se llenará dinámicamente -->
                </div>

                <div class="text-end mt-4">
                    <button class="btn btn-secondary me-2" onclick="siguientePaso(2)">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Mapeo
                    </button>
                    <button class="btn btn-warning me-2" onclick="corregirErrores()">
                        <i class="fas fa-tools me-2"></i>Corregir Errores
                    </button>
                    <button class="btn btn-csv" onclick="siguientePaso(4)" id="btn-continuar-import" disabled>
                        <i class="fas fa-database me-2"></i>Continuar a Importación
                    </button>
                </div>
            </div>

            <!-- Tab Import -->
            <div class="tab-pane fade" id="import" role="tabpanel">
                <h4 class="mb-4"><i class="fas fa-database me-2"></i>Importación Final</h4>
                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5>Resumen de Importación</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Archivo:</strong> <span id="resumen-archivo">-</span></p>
                                        <p><strong>Total de filas:</strong> <span id="resumen-filas">0</span></p>
                                        <p><strong>Registros válidos:</strong> <span id="resumen-validos">0</span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Proveedor:</strong> <span id="resumen-proveedor">-</span></p>
                                        <p><strong>Productos nuevos:</strong> <span id="resumen-nuevos">0</span></p>
                                        <p><strong>Productos actualizados:</strong> <span id="resumen-actualizados">0</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Opciones de Importación</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="actualizar-existentes" checked>
                                    <label class="form-check-label" for="actualizar-existentes">
                                        Actualizar productos existentes
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="crear-nuevos" checked>
                                    <label class="form-check-label" for="crear-nuevos">
                                        Crear productos nuevos
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="backup-antes">
                                    <label class="form-check-label" for="backup-antes">
                                        Crear backup antes de importar
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4" id="progress-card" style="display: none;">
                    <div class="card-header">
                        <h5>Progreso de Importación</h5>
                    </div>
                    <div class="card-body">
                        <div class="progress mb-3" style="height: 25px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%" id="import-progress-bar">
                                0%
                            </div>
                        </div>
                        <div id="import-status">Preparando importación...</div>
                    </div>
                </div>

                <div class="text-end">
                    <button class="btn btn-secondary me-2" onclick="siguientePaso(3)">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Validación
                    </button>
                    <button class="btn btn-success btn-lg" onclick="iniciarImportacion()" id="btn-iniciar-import">
                        <i class="fas fa-play me-2"></i>Iniciar Importación
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-light p-3 text-center">
            <button class="btn btn-secondary me-3" onclick="window.location.href='compras_form.php'">
                <i class="fas fa-arrow-left me-2"></i>Volver al Selector
            </button>
            <button class="btn btn-info" onclick="mostrarAyuda()">
                <i class="fas fa-question-circle me-2"></i>Ayuda
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>
    
    <script>
        let csvData = [];
        let csvHeaders = [];
        let currentStep = 1;
        let validationResults = [];

        document.addEventListener('DOMContentLoaded', function() {
            setupDragAndDrop();
            setupFileInput();
        });

        function setupDragAndDrop() {
            const uploadZone = document.getElementById('upload-zone');
            
            uploadZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadZone.classList.add('dragover');
            });

            uploadZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadZone.classList.remove('dragover');
            });

            uploadZone.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadZone.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    procesarArchivo(files[0]);
                }
            });
        }

        function setupFileInput() {
            document.getElementById('csv-file').addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    procesarArchivo(e.target.files[0]);
                }
            });
        }

        function procesarArchivo(archivo) {
            if (!archivo.name.toLowerCase().match(/\.(csv|txt)$/)) {
                alert('Por favor selecciona un archivo CSV o TXT');
                return;
            }

            if (archivo.size > 10 * 1024 * 1024) { // 10MB
                alert('El archivo es demasiado grande. Máximo 10MB permitido.');
                return;
            }

            const separador = document.getElementById('separador').value;
            
            Papa.parse(archivo, {
                delimiter: separador,
                header: false,
                skipEmptyLines: true,
                complete: function(results) {
                    csvData = results.data;
                    if (document.getElementById('primera-fila-headers').checked && csvData.length > 0) {
                        csvHeaders = csvData[0];
                        csvData = csvData.slice(1);
                    } else {
                        csvHeaders = csvData[0].map((_, i) => `Columna ${i + 1}`);
                    }
                    
                    mostrarVistaPrevia();
                    actualizarContadores();
                },
                error: function(error) {
                    alert('Error al procesar el archivo: ' + error.message);
                }
            });
        }

        function mostrarVistaPrevia() {
            const previewDiv = document.getElementById('file-preview');
            const table = document.getElementById('preview-table');
            
            let html = '<thead><tr>';
            csvHeaders.forEach(header => {
                html += `<th>${header}</th>`;
            });
            html += '</tr></thead><tbody>';
            
            // Mostrar solo las primeras 10 filas
            const filasPreview = csvData.slice(0, 10);
            filasPreview.forEach(fila => {
                html += '<tr>';
                fila.forEach(celda => {
                    html += `<td>${celda || ''}</td>`;
                });
                html += '</tr>';
            });
            
            if (csvData.length > 10) {
                html += `<tr><td colspan="${csvHeaders.length}" class="text-center text-muted">... y ${csvData.length - 10} filas más</td></tr>`;
            }
            
            html += '</tbody>';
            table.innerHTML = html;
            previewDiv.style.display = 'block';
        }

        function siguientePaso(paso) {
            // Desactivar paso actual
            document.getElementById(`step-${currentStep}`).classList.remove('active');
            
            // Activar nuevo paso
            currentStep = paso;
            document.getElementById(`step-${currentStep}`).classList.add('active');
            
            // Marcar pasos anteriores como completados
            for (let i = 1; i < currentStep; i++) {
                document.getElementById(`step-${i}`).classList.add('completed');
            }
            
            // Activar tab correspondiente
            const tabs = ['', 'upload-tab', 'mapping-tab', 'validation-tab', 'import-tab'];
            document.getElementById(tabs[paso]).click();
            
            if (paso === 2) {
                generarMapeoColumnas();
            }
        }

        function generarMapeoColumnas() {
            const container = document.getElementById('mapping-container');
            const camposObligatorios = [
                { id: 'nombre', label: 'Nombre del Producto *', required: true },
                { id: 'codigo', label: 'Código/SKU *', required: true },
                { id: 'cantidad', label: 'Cantidad *', required: true }
            ];
            
            const camposOpcionales = [
                { id: 'precio', label: 'Precio de Compra', required: false },
                { id: 'categoria', label: 'Categoría', required: false },
                { id: 'lugar', label: 'Ubicación/Lugar', required: false },
                { id: 'descripcion', label: 'Descripción', required: false },
                { id: 'unidad', label: 'Unidad de Medida', required: false }
            ];
            
            let html = '<div class="row">';
            
            [...camposObligatorios, ...camposOpcionales].forEach(campo => {
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="mapping-card">
                            <label class="form-label fw-bold">${campo.label}</label>
                            <select class="form-select" id="map-${campo.id}" ${campo.required ? 'required' : ''}>
                                <option value="">-- No mapear --</option>
                                ${csvHeaders.map((header, index) => 
                                    `<option value="${index}">${header}</option>`
                                ).join('')}
                            </select>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
            
            // Auto-mapeo inteligente
            autoMapearColumnas();
        }

        function autoMapearColumnas() {
            const mapeos = {
                'nombre': ['nombre', 'producto', 'descripcion', 'item', 'articulo'],
                'codigo': ['codigo', 'sku', 'referencia', 'cod', 'code'],
                'cantidad': ['cantidad', 'qty', 'stock', 'unidades'],
                'precio': ['precio', 'cost', 'costo', 'value', 'valor'],
                'categoria': ['categoria', 'category', 'tipo', 'clase'],
                'lugar': ['lugar', 'ubicacion', 'location', 'warehouse', 'deposito']
            };
            
            Object.keys(mapeos).forEach(campo => {
                const select = document.getElementById(`map-${campo}`);
                const palabrasClave = mapeos[campo];
                
                csvHeaders.forEach((header, index) => {
                    const headerLower = header.toLowerCase();
                    if (palabrasClave.some(palabra => headerLower.includes(palabra))) {
                        select.value = index;
                    }
                });
            });
        }

        function validarMapeo() {
            const camposObligatorios = ['nombre', 'codigo', 'cantidad'];
            let valido = true;
            
            camposObligatorios.forEach(campo => {
                const select = document.getElementById(`map-${campo}`);
                if (!select.value) {
                    select.classList.add('is-invalid');
                    valido = false;
                } else {
                    select.classList.remove('is-invalid');
                }
            });
            
            if (!valido) {
                alert('Por favor mapea todos los campos obligatorios (marcados con *)');
                return;
            }
            
            validarDatos();
            siguientePaso(3);
        }

        function validarDatos() {
            validationResults = [];
            let countValid = 0, countWarning = 0, countError = 0, countDuplicate = 0;
            
            const mapeo = {
                nombre: document.getElementById('map-nombre').value,
                codigo: document.getElementById('map-codigo').value,
                cantidad: document.getElementById('map-cantidad').value,
                precio: document.getElementById('map-precio').value
            };
            
            const codigosVistos = new Set();
            
            csvData.forEach((fila, index) => {
                const resultado = {
                    fila: index + 1,
                    tipo: 'success',
                    mensajes: [],
                    datos: {}
                };
                
                // Extraer datos según mapeo
                if (mapeo.nombre) resultado.datos.nombre = fila[mapeo.nombre];
                if (mapeo.codigo) resultado.datos.codigo = fila[mapeo.codigo];
                if (mapeo.cantidad) resultado.datos.cantidad = fila[mapeo.cantidad];
                if (mapeo.precio) resultado.datos.precio = fila[mapeo.precio];
                
                // Validaciones
                if (!resultado.datos.nombre || resultado.datos.nombre.trim() === '') {
                    resultado.tipo = 'error';
                    resultado.mensajes.push('Nombre del producto es obligatorio');
                }
                
                if (!resultado.datos.codigo || resultado.datos.codigo.trim() === '') {
                    resultado.tipo = 'error';
                    resultado.mensajes.push('Código del producto es obligatorio');
                }
                
                if (!resultado.datos.cantidad || isNaN(resultado.datos.cantidad) || parseFloat(resultado.datos.cantidad) <= 0) {
                    resultado.tipo = 'error';
                    resultado.mensajes.push('Cantidad debe ser un número mayor a 0');
                }
                
                // Verificar duplicados
                if (resultado.datos.codigo && codigosVistos.has(resultado.datos.codigo)) {
                    resultado.tipo = resultado.tipo === 'error' ? 'error' : 'warning';
                    resultado.mensajes.push('Código duplicado en el archivo');
                    countDuplicate++;
                } else if (resultado.datos.codigo) {
                    codigosVistos.add(resultado.datos.codigo);
                }
                
                // Contadores
                if (resultado.tipo === 'success') countValid++;
                else if (resultado.tipo === 'warning') countWarning++;
                else if (resultado.tipo === 'error') countError++;
                
                validationResults.push(resultado);
            });
            
            // Actualizar contadores
            document.getElementById('count-valid').textContent = countValid;
            document.getElementById('count-warning').textContent = countWarning;
            document.getElementById('count-error').textContent = countError;
            document.getElementById('count-duplicate').textContent = countDuplicate;
            
            // Habilitar botón de continuar si no hay errores críticos
            document.getElementById('btn-continuar-import').disabled = countError > 0;
            
            mostrarResultadosValidacion();
        }

        function mostrarResultadosValidacion() {
            const container = document.getElementById('validation-results');
            let html = '';
            
            validationResults.forEach(resultado => {
                const iconos = {
                    'success': 'fas fa-check-circle text-success',
                    'warning': 'fas fa-exclamation-triangle text-warning',
                    'error': 'fas fa-times-circle text-danger'
                };
                
                html += `
                    <div class="validation-item ${resultado.tipo}">
                        <div class="d-flex align-items-start">
                            <i class="${iconos[resultado.tipo]} me-3 mt-1"></i>
                            <div class="flex-grow-1">
                                <h6>Fila ${resultado.fila}</h6>
                                <p class="mb-1"><strong>Producto:</strong> ${resultado.datos.nombre || 'Sin nombre'}</p>
                                <p class="mb-1"><strong>Código:</strong> ${resultado.datos.codigo || 'Sin código'}</p>
                                <p class="mb-1"><strong>Cantidad:</strong> ${resultado.datos.cantidad || 'Sin cantidad'}</p>
                                ${resultado.mensajes.length > 0 ? 
                                    `<div class="mt-2"><strong>Problemas:</strong><ul class="mb-0">${resultado.mensajes.map(msg => `<li>${msg}</li>`).join('')}</ul></div>` 
                                    : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function actualizarContadores() {
            document.getElementById('total-filas').textContent = csvData.length;
            document.getElementById('resumen-filas').textContent = csvData.length;
        }

        function descargarPlantilla() {
            const plantilla = [
                ['Nombre', 'Codigo', 'Cantidad', 'Precio', 'Categoria', 'Descripcion'],
                ['Producto Ejemplo 1', 'PROD001', '10', '15.50', 'Categoría A', 'Descripción del producto'],
                ['Producto Ejemplo 2', 'PROD002', '25', '8.75', 'Categoría B', 'Otra descripción']
            ];
            
            const csv = Papa.unparse(plantilla);
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'plantilla_productos.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function iniciarImportacion() {
            if (!confirm('¿Estás seguro de iniciar la importación? Esta acción no se puede deshacer.')) {
                return;
            }
            
            document.getElementById('btn-iniciar-import').disabled = true;
            document.getElementById('progress-card').style.display = 'block';
            
            // Simular progreso de importación
            let progreso = 0;
            const total = validationResults.filter(r => r.tipo !== 'error').length;
            
            const interval = setInterval(() => {
                progreso += Math.random() * 10;
                if (progreso > 100) progreso = 100;
                
                document.getElementById('import-progress-bar').style.width = progreso + '%';
                document.getElementById('import-progress-bar').textContent = Math.floor(progreso) + '%';
                document.getElementById('import-status').textContent = 
                    progreso < 100 ? `Procesando registro ${Math.floor(progreso * total / 100)} de ${total}...` : 'Importación completada!';
                
                if (progreso >= 100) {
                    clearInterval(interval);
                    setTimeout(() => {
                        alert('¡Importación completada exitosamente!');
                        // Aquí se redirigiría a la lista de compras o se resetearía el formulario
                    }, 1000);
                }
            }, 200);
        }

        function mostrarAyuda() {
            alert('Sistema de ayuda próximamente...');
        }

        function corregirErrores() {
            alert('Herramienta de corrección automática próximamente...');
        }
    </script>
</body>
</html>

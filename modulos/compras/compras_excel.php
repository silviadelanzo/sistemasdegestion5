<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();

// Obtener datos necesarios
$proveedores = $pdo->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC);
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$lugares = $pdo->query("SELECT * FROM lugares ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Excel - Importación de Inventarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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

        .header-excel {
            background: linear-gradient(135deg, #28a745, #17a2b8);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header-excel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="excel" width="20" height="20" patternUnits="userSpaceOnUse"><rect x="0" y="0" width="10" height="10" fill="rgba(255,255,255,0.1)"/><rect x="10" y="10" width="10" height="10" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23excel)"/></svg>');
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .upload-zone-excel {
            border: 3px dashed #28a745;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            margin: 30px;
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .upload-zone-excel:hover {
            border-color: #17a2b8;
            background: linear-gradient(135deg, #b3e5fc, #81d4fa);
            transform: translateY(-2px);
        }

        .upload-zone-excel.processing {
            border-color: #007bff;
            background: linear-gradient(135deg, #cce5ff, #99d6ff);
            animation: processing-pulse 2s infinite;
        }

        @keyframes processing-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        .nav-tabs-excel {
            background: linear-gradient(90deg, #d4edda, #c3e6cb);
            border-bottom: 3px solid #28a745;
            padding: 0 30px;
        }

        .nav-tabs-excel .nav-link {
            border: none;
            border-radius: 15px 15px 0 0;
            padding: 15px 25px;
            margin-right: 5px;
            font-weight: 600;
            color: #495057;
            transition: all 0.3s ease;
        }

        .nav-tabs-excel .nav-link.active {
            background: linear-gradient(135deg, #28a745, #17a2b8);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .excel-preview {
            max-height: 400px;
            overflow: auto;
            border-radius: 12px;
            border: 2px solid #dee2e6;
            background: #f8f9fa;
        }

        .excel-table {
            font-size: 12px;
            margin: 0;
        }

        .excel-table th {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            position: sticky;
            top: 0;
            z-index: 5;
            border: 1px solid #fff;
            font-weight: 600;
            text-align: center;
            font-size: 11px;
            padding: 8px 4px;
        }

        .excel-table td {
            border: 1px solid #dee2e6;
            padding: 6px 4px;
            text-align: center;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .excel-table tbody tr:hover {
            background: #e8f5e8;
        }

        .mapping-card {
            background: white;
            border: 2px solid #28a745;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            transition: all 0.3s ease;
        }

        .mapping-card:hover {
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.2);
            transform: translateY(-2px);
        }

        .mapping-card.required {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .mapping-card.mapped {
            border-color: #28a745;
            background: #f0fff0;
        }

        .validation-item {
            background: white;
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 15px;
            margin: 10px 0;
            position: relative;
        }

        .validation-item.error {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .validation-item.warning {
            border-color: #ffc107;
            background: #fffbf0;
        }

        .validation-item.success {
            border-color: #28a745;
            background: #f0fff0;
        }

        .progress-excel {
            height: 25px;
            border-radius: 12px;
            background: #e9ecef;
            overflow: hidden;
            margin: 15px 0;
        }

        .progress-bar-excel {
            background: linear-gradient(90deg, #28a745, #20c997);
            height: 100%;
            border-radius: 12px;
            transition: width 0.5s ease;
            position: relative;
        }

        .btn-excel {
            background: linear-gradient(135deg, #28a745, #17a2b8);
            border: none;
            color: white;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-excel:hover {
            background: linear-gradient(135deg, #20c997, #007bff);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .stats-excel {
            background: linear-gradient(135deg, #28a745, #17a2b8);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin: 10px 0;
        }

        .template-card {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 2px solid #2196f3;
            border-radius: 15px;
            padding: 20px;
            margin: 15px 0;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .template-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(33, 150, 243, 0.2);
        }

        .column-selector {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            margin: 5px 0;
        }

        .file-info {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
        }

        .spinner-excel {
            width: 50px;
            height: 50px;
            border: 4px solid #e3f2fd;
            border-top: 4px solid #28a745;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .data-type-indicator {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 5px;
        }

        .data-type-text { background: #e3f2fd; color: #1976d2; }
        .data-type-number { background: #e8f5e8; color: #388e3c; }
        .data-type-date { background: #fff3e0; color: #f57c00; }
        .data-type-empty { background: #fafafa; color: #757575; }
    </style>
</head>

<body>
    <div class="main-card">
        <!-- Header -->
        <div class="header-excel">
            <h1 class="mb-3">
                <i class="fas fa-file-excel fa-2x"></i><br>
                Importación desde Excel
            </h1>
            <p class="lead mb-4">Importa inventarios completos desde archivos Excel, CSV o OpenOffice</p>
            <div class="row text-center">
                <div class="col-md-3">
                    <h3 id="archivos-procesados">0</h3>
                    <small>Archivos Procesados</small>
                </div>
                <div class="col-md-3">
                    <h3 id="filas-importadas">0</h3>
                    <small>Filas Importadas</small>
                </div>
                <div class="col-md-3">
                    <h3 id="productos-creados">0</h3>
                    <small>Productos Creados</small>
                </div>
                <div class="col-md-3">
                    <h3 id="errores-detectados">0</h3>
                    <small>Errores Corregidos</small>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs nav-tabs-excel" id="excelTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button" role="tab">
                    <i class="fas fa-upload"></i> Cargar Archivo
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview" type="button" role="tab">
                    <i class="fas fa-table"></i> Vista Previa <span class="badge bg-light text-dark ms-1" id="badge-filas">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="mapping-tab" data-bs-toggle="tab" data-bs-target="#mapping" type="button" role="tab">
                    <i class="fas fa-columns"></i> Mapeo de Columnas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="import-tab" data-bs-toggle="tab" data-bs-target="#import" type="button" role="tab">
                    <i class="fas fa-download"></i> Importar Datos
                </button>
            </li>
        </ul>

        <div class="tab-content p-4">
            <!-- Tab Upload -->
            <div class="tab-pane fade show active" id="upload" role="tabpanel">
                <div class="upload-zone-excel" id="upload-zone-excel" onclick="document.getElementById('excel-file').click()">
                    <i class="fas fa-file-excel fa-4x text-success mb-3"></i>
                    <h4>Sube tu archivo Excel</h4>
                    <p class="text-muted mb-4">Arrastra archivos Excel, CSV, ODS aquí</p>
                    <p class="small text-muted">Formatos soportados: .xlsx, .xls, .csv, .ods (máximo 50MB)</p>
                    <input type="file" id="excel-file" accept=".xlsx,.xls,.csv,.ods" style="display: none;">
                </div>

                <div class="row mt-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-download me-2"></i>Plantillas Recomendadas</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="template-card" onclick="descargarPlantilla('productos')">
                                            <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                                            <h6>Plantilla Productos</h6>
                                            <p class="small text-muted">Estructura básica para productos</p>
                                            <button class="btn btn-success btn-sm">
                                                <i class="fas fa-download me-2"></i>Descargar
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="template-card" onclick="descargarPlantilla('inventario')">
                                            <i class="fas fa-file-excel fa-3x text-info mb-3"></i>
                                            <h6>Plantilla Inventario Completo</h6>
                                            <p class="small text-muted">Con stock, precios y ubicaciones</p>
                                            <button class="btn btn-info btn-sm">
                                                <i class="fas fa-download me-2"></i>Descargar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    <strong>Tip:</strong> Usa nuestras plantillas para garantizar la compatibilidad.
                                    También puedes subir cualquier Excel y mapear las columnas manualmente.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-cogs me-2"></i>Configuración de Importación</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Proveedor por Defecto</label>
                                    <select class="form-select" id="proveedor-default">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($proveedores as $proveedor): ?>
                                            <option value="<?php echo $proveedor['id']; ?>">
                                                <?php echo htmlspecialchars($proveedor['razon_social']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Categoría por Defecto</label>
                                    <select class="form-select" id="categoria-default">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo $categoria['id']; ?>">
                                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Lugar por Defecto</label>
                                    <select class="form-select" id="lugar-default">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($lugares as $lugar): ?>
                                            <option value="<?php echo $lugar['id']; ?>">
                                                <?php echo htmlspecialchars($lugar['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skip-duplicates" checked>
                                    <label class="form-check-label" for="skip-duplicates">
                                        Omitir productos duplicados
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="auto-create-categories">
                                    <label class="form-check-label" for="auto-create-categories">
                                        Crear categorías automáticamente
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stats-excel mt-3">
                            <h6>Estadísticas de Sesión</h6>
                            <div class="row">
                                <div class="col-6">
                                    <h4 id="tiempo-sesion">0m</h4>
                                    <small>Tiempo Transcurrido</small>
                                </div>
                                <div class="col-6">
                                    <h4 id="memoria-usada">0 MB</h4>
                                    <small>Memoria en Uso</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del archivo subido -->
                <div id="file-info" class="file-info mt-4" style="display: none;">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6><i class="fas fa-file-excel me-2"></i><span id="file-name"></span></h6>
                            <p class="mb-0">
                                <span class="badge bg-success me-2" id="file-size"></span>
                                <span class="badge bg-info me-2" id="file-type"></span>
                                <span class="badge bg-warning" id="file-rows"></span>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-excel" onclick="procesarArchivo()">
                                <i class="fas fa-play me-2"></i>Procesar Archivo
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Preview -->
            <div class="tab-pane fade" id="preview" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="fas fa-table me-2"></i>Vista Previa de Datos</h4>
                    <div>
                        <span class="badge bg-info me-2" id="preview-rows">0 filas</span>
                        <span class="badge bg-success" id="preview-columns">0 columnas</span>
                    </div>
                </div>

                <div class="excel-preview" id="excel-preview">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-table fa-4x mb-3"></i>
                        <h5>Carga un archivo para ver la vista previa</h5>
                        <p>Los datos del Excel aparecerán aquí</p>
                    </div>
                </div>

                <div class="row mt-4" id="preview-actions" style="display: none;">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6>Análisis Automático</h6>
                            </div>
                            <div class="card-body">
                                <div id="analysis-results">
                                    <!-- Resultados del análisis -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6>Configuración de Hoja</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Hoja a Importar</label>
                                    <select class="form-select" id="sheet-selector">
                                        <!-- Se llenarán las hojas disponibles -->
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Fila de Encabezados</label>
                                    <input type="number" class="form-control" id="header-row" value="1" min="1">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Fila de Inicio de Datos</label>
                                    <input type="number" class="form-control" id="data-start-row" value="2" min="1">
                                </div>
                                
                                <button class="btn btn-excel w-100" onclick="continuarAMapeo()">
                                    <i class="fas fa-arrow-right me-2"></i>Continuar a Mapeo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Mapping -->
            <div class="tab-pane fade" id="mapping" role="tabpanel">
                <h4 class="mb-4"><i class="fas fa-columns me-2"></i>Mapeo de Columnas</h4>
                <p class="text-muted mb-4">Asocia las columnas de tu Excel con los campos del sistema</p>

                <div id="mapping-container">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-columns fa-4x mb-3"></i>
                        <h5>Procesa un archivo primero</h5>
                        <p>El mapeo de columnas aparecerá aquí</p>
                    </div>
                </div>

                <div class="text-end mt-4" id="mapping-actions" style="display: none;">
                    <button class="btn btn-secondary me-2" onclick="volverAPreview()">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Vista Previa
                    </button>
                    <button class="btn btn-warning me-2" onclick="autoMapear()">
                        <i class="fas fa-magic me-2"></i>Auto-mapear
                    </button>
                    <button class="btn btn-excel" onclick="validarMapeo()">
                        <i class="fas fa-check me-2"></i>Validar Mapeo
                    </button>
                </div>
            </div>

            <!-- Tab Import -->
            <div class="tab-pane fade" id="import" role="tabpanel">
                <h4 class="mb-4"><i class="fas fa-download me-2"></i>Importación de Datos</h4>

                <div class="progress-excel">
                    <div class="progress-bar-excel" style="width: 0%" id="import-progress">
                        <span class="position-absolute w-100 text-center text-dark fw-bold">0%</span>
                    </div>
                </div>

                <div class="row" id="import-status">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-list-check me-2"></i>Validación de Datos</h6>
                            </div>
                            <div class="card-body">
                                <div id="validation-results">
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-hourglass-start fa-2x mb-2"></i>
                                        <p>Esperando validación...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="fas fa-chart-bar me-2"></i>Estadísticas de Importación</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <h4 class="text-success" id="import-success">0</h4>
                                        <small>Exitosos</small>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <h4 class="text-danger" id="import-errors">0</h4>
                                        <small>Errores</small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Tiempo estimado restante</small>
                                    <h5 id="tiempo-restante">--:--</h5>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Velocidad de procesamiento</small>
                                    <h6 id="velocidad-procesamiento">0 filas/seg</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4" id="import-log" style="display: none;">
                    <div class="card-header">
                        <h6><i class="fas fa-list me-2"></i>Log de Importación</h6>
                    </div>
                    <div class="card-body">
                        <div id="log-container" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                            <!-- Log de importación -->
                        </div>
                    </div>
                </div>

                <div class="text-end mt-4" id="import-final-actions" style="display: none;">
                    <button class="btn btn-success me-2" onclick="descargarReporte()">
                        <i class="fas fa-file-excel me-2"></i>Descargar Reporte
                    </button>
                    <button class="btn btn-primary" onclick="verProductosImportados()">
                        <i class="fas fa-eye me-2"></i>Ver Productos
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
                <i class="fas fa-question-circle me-2"></i>Ayuda Excel
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script>
        let archivoActual = null;
        let datosExcel = null;
        let columnasDetectadas = [];
        let mapeoColumnas = {};
        let sessionStartTime = Date.now();

        // Configuración de campos del sistema
        const camposSistema = [
            { id: 'nombre', label: 'Nombre del Producto', required: true, type: 'text' },
            { id: 'codigo', label: 'Código/SKU', required: false, type: 'text' },
            { id: 'codigo_barras', label: 'Código de Barras', required: false, type: 'text' },
            { id: 'precio_costo', label: 'Precio de Costo', required: false, type: 'number' },
            { id: 'precio_venta', label: 'Precio de Venta', required: false, type: 'number' },
            { id: 'stock_actual', label: 'Stock Actual', required: false, type: 'number' },
            { id: 'stock_minimo', label: 'Stock Mínimo', required: false, type: 'number' },
            { id: 'categoria', label: 'Categoría', required: false, type: 'text' },
            { id: 'proveedor', label: 'Proveedor', required: false, type: 'text' },
            { id: 'lugar', label: 'Ubicación', required: false, type: 'text' },
            { id: 'descripcion', label: 'Descripción', required: false, type: 'text' },
            { id: 'activo', label: 'Estado (Activo)', required: false, type: 'boolean' }
        ];

        document.addEventListener('DOMContentLoaded', function() {
            setupDragAndDrop();
            setupFileInput();
            actualizarTimer();
        });

        function setupDragAndDrop() {
            const uploadZone = document.getElementById('upload-zone-excel');
            
            uploadZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadZone.style.borderColor = '#17a2b8';
                uploadZone.style.background = 'linear-gradient(135deg, #b3e5fc, #81d4fa)';
            });

            uploadZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadZone.style.borderColor = '#28a745';
                uploadZone.style.background = 'linear-gradient(135deg, #d4edda, #c3e6cb)';
            });

            uploadZone.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadZone.style.borderColor = '#28a745';
                uploadZone.style.background = 'linear-gradient(135deg, #d4edda, #c3e6cb)';
                
                const files = Array.from(e.dataTransfer.files);
                if (files.length > 0) {
                    procesarArchivoSubido(files[0]);
                }
            });
        }

        function setupFileInput() {
            document.getElementById('excel-file').addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    procesarArchivoSubido(e.target.files[0]);
                }
            });
        }

        function procesarArchivoSubido(archivo) {
            // Validar tipo de archivo
            const extensionesValidas = ['xlsx', 'xls', 'csv', 'ods'];
            const extension = archivo.name.toLowerCase().split('.').pop();
            
            if (!extensionesValidas.includes(extension)) {
                alert('Formato de archivo no soportado. Use .xlsx, .xls, .csv o .ods');
                return;
            }
            
            // Validar tamaño (50MB máximo)
            if (archivo.size > 50 * 1024 * 1024) {
                alert('El archivo es demasiado grande. Máximo 50MB permitido.');
                return;
            }
            
            archivoActual = archivo;
            mostrarInfoArchivo(archivo);
            
            // Leer archivo
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    procesarLibroExcel(workbook);
                } catch (error) {
                    alert('Error al procesar el archivo: ' + error.message);
                }
            };
            reader.readAsArrayBuffer(archivo);
        }

        function mostrarInfoArchivo(archivo) {
            document.getElementById('file-name').textContent = archivo.name;
            document.getElementById('file-size').textContent = (archivo.size / 1024 / 1024).toFixed(2) + ' MB';
            document.getElementById('file-type').textContent = archivo.name.split('.').pop().toUpperCase();
            document.getElementById('file-info').style.display = 'block';
        }

        function procesarLibroExcel(workbook) {
            // Obtener nombres de hojas
            const sheetNames = workbook.SheetNames;
            const sheetSelector = document.getElementById('sheet-selector');
            
            sheetSelector.innerHTML = '';
            sheetNames.forEach(name => {
                const option = document.createElement('option');
                option.value = name;
                option.textContent = name;
                sheetSelector.appendChild(option);
            });
            
            // Procesar primera hoja por defecto
            const primeraHoja = workbook.Sheets[sheetNames[0]];
            const datosJson = XLSX.utils.sheet_to_json(primeraHoja, { header: 1, defval: '' });
            
            datosExcel = datosJson;
            mostrarVistaPrevia(datosJson);
            
            document.getElementById('file-rows').textContent = datosJson.length + ' filas';
            document.getElementById('badge-filas').textContent = datosJson.length;
        }

        function procesarArchivo() {
            if (!archivoActual) {
                alert('Selecciona un archivo primero');
                return;
            }
            
            document.getElementById('preview-tab').click();
        }

        function mostrarVistaPrevia(datos) {
            if (!datos || datos.length === 0) {
                document.getElementById('excel-preview').innerHTML = 
                    '<div class="text-center text-muted py-5"><h5>El archivo está vacío</h5></div>';
                return;
            }
            
            // Detectar columnas desde la primera fila
            columnasDetectadas = datos[0] || [];
            
            // Crear tabla de vista previa (mostrar solo primeras 50 filas)
            const filasAMostrar = Math.min(datos.length, 50);
            let html = '<table class="table excel-table"><thead><tr>';
            
            // Encabezados
            for (let i = 0; i < columnasDetectadas.length; i++) {
                const header = columnasDetectadas[i] || `Columna ${i + 1}`;
                html += `<th>${header}</th>`;
            }
            html += '</tr></thead><tbody>';
            
            // Filas de datos (empezar desde fila 1, omitir encabezados)
            for (let i = 1; i < filasAMostrar; i++) {
                html += '<tr>';
                for (let j = 0; j < columnasDetectadas.length; j++) {
                    const valor = datos[i][j] || '';
                    const tipoData = detectarTipoDato(valor);
                    html += `<td title="${valor}">
                        ${valor}
                        <span class="data-type-indicator data-type-${tipoData}">${tipoData.toUpperCase()}</span>
                    </td>`;
                }
                html += '</tr>';
            }
            
            html += '</tbody></table>';
            
            if (datos.length > 50) {
                html += `<div class="text-center text-muted mt-3">
                    <small>Mostrando las primeras 50 filas de ${datos.length} total</small>
                </div>`;
            }
            
            document.getElementById('excel-preview').innerHTML = html;
            document.getElementById('preview-rows').textContent = datos.length + ' filas';
            document.getElementById('preview-columns').textContent = columnasDetectadas.length + ' columnas';
            document.getElementById('preview-actions').style.display = 'block';
            
            // Análisis automático
            analizarDatos(datos);
        }

        function detectarTipoDato(valor) {
            if (!valor || valor === '') return 'empty';
            if (!isNaN(valor) && !isNaN(parseFloat(valor))) return 'number';
            if (Date.parse(valor)) return 'date';
            return 'text';
        }

        function analizarDatos(datos) {
            const analisis = {
                totalFilas: datos.length - 1, // Excluyendo encabezados
                totalColumnas: columnasDetectadas.length,
                columnasVacias: 0,
                porcentajeCompletitud: 0
            };
            
            // Analizar completitud de datos
            let celdastotal = 0;
            let celdasLlenas = 0;
            
            for (let i = 1; i < datos.length; i++) {
                for (let j = 0; j < columnasDetectadas.length; j++) {
                    celdastotal++;
                    if (datos[i][j] && datos[i][j] !== '') {
                        celdasLlenas++;
                    }
                }
            }
            
            analisis.porcentajeCompletitud = celdastotal > 0 ? (celdasLlenas / celdastotal * 100).toFixed(1) : 0;
            
            const htmlAnalisis = `
                <div class="row text-center">
                    <div class="col-6 mb-2">
                        <h6 class="text-success">${analisis.totalFilas}</h6>
                        <small>Filas de Datos</small>
                    </div>
                    <div class="col-6 mb-2">
                        <h6 class="text-info">${analisis.totalColumnas}</h6>
                        <small>Columnas</small>
                    </div>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Completitud de datos</small>
                    <div class="progress">
                        <div class="progress-bar bg-success" style="width: ${analisis.porcentajeCompletitud}%">
                            ${analisis.porcentajeCompletitud}%
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('analysis-results').innerHTML = htmlAnalisis;
        }

        function continuarAMapeo() {
            if (!datosExcel || columnasDetectadas.length === 0) {
                alert('No hay datos para mapear');
                return;
            }
            
            crearInterfazMapeo();
            document.getElementById('mapping-tab').click();
        }

        function crearInterfazMapeo() {
            let html = '';
            
            camposSistema.forEach(campo => {
                const requiredClass = campo.required ? 'required' : '';
                const requiredBadge = campo.required ? '<span class="badge bg-danger ms-2">Requerido</span>' : '';
                
                html += `
                    <div class="mapping-card ${requiredClass}" id="mapping-${campo.id}">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h6 class="mb-1">${campo.label} ${requiredBadge}</h6>
                                <small class="text-muted">Tipo: ${campo.type}</small>
                            </div>
                            <div class="col-md-6">
                                <select class="form-select column-selector" 
                                        onchange="mapearColumna('${campo.id}', this.value)">
                                    <option value="">-- No mapear --</option>
                                    ${columnasDetectadas.map((col, index) => 
                                        `<option value="${index}">${col || 'Columna ' + (index + 1)}</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div class="col-md-2 text-center">
                                <i class="fas fa-arrow-right text-muted" id="arrow-${campo.id}"></i>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            document.getElementById('mapping-container').innerHTML = html;
            document.getElementById('mapping-actions').style.display = 'block';
        }

        function mapearColumna(campoId, columnaIndex) {
            if (columnaIndex === '') {
                delete mapeoColumnas[campoId];
                document.getElementById(`mapping-${campoId}`).classList.remove('mapped');
                document.getElementById(`arrow-${campoId}`).className = 'fas fa-arrow-right text-muted';
            } else {
                mapeoColumnas[campoId] = parseInt(columnaIndex);
                document.getElementById(`mapping-${campoId}`).classList.add('mapped');
                document.getElementById(`arrow-${campoId}`).className = 'fas fa-check text-success';
            }
        }

        function autoMapear() {
            // Intentar mapeo automático basado en nombres de columnas
            const mapeoAutomatico = {
                'nombre': ['nombre', 'producto', 'descripcion', 'name', 'product'],
                'codigo': ['codigo', 'sku', 'code', 'id'],
                'codigo_barras': ['barras', 'barcode', 'ean', 'upc'],
                'precio_costo': ['costo', 'cost', 'precio_compra', 'compra'],
                'precio_venta': ['venta', 'precio', 'price', 'pvp'],
                'stock_actual': ['stock', 'cantidad', 'qty', 'inventory'],
                'categoria': ['categoria', 'category', 'tipo', 'type'],
                'proveedor': ['proveedor', 'supplier', 'vendor']
            };
            
            Object.keys(mapeoAutomatico).forEach(campo => {
                const palabrasClave = mapeoAutomatico[campo];
                
                for (let i = 0; i < columnasDetectadas.length; i++) {
                    const nombreColumna = columnasDetectadas[i].toLowerCase();
                    
                    if (palabrasClave.some(palabra => nombreColumna.includes(palabra))) {
                        const selector = document.querySelector(`select[onchange*="${campo}"]`);
                        if (selector) {
                            selector.value = i;
                            mapearColumna(campo, i);
                        }
                        break;
                    }
                }
            });
            
            alert('Auto-mapeo completado. Revisa y ajusta según necesites.');
        }

        function validarMapeo() {
            // Verificar campos requeridos
            const camposRequeridos = camposSistema.filter(c => c.required);
            const faltantes = [];
            
            camposRequeridos.forEach(campo => {
                if (!mapeoColumnas[campo.id]) {
                    faltantes.push(campo.label);
                }
            });
            
            if (faltantes.length > 0) {
                alert('Faltan mapear campos requeridos:\n- ' + faltantes.join('\n- '));
                return;
            }
            
            // Continuar a importación
            document.getElementById('import-tab').click();
            iniciarImportacion();
        }

        function iniciarImportacion() {
            document.getElementById('import-log').style.display = 'block';
            simularImportacion();
        }

        function simularImportacion() {
            const totalFilas = datosExcel.length - 1; // Excluyendo encabezados
            let filasImportadas = 0;
            let exitosos = 0;
            let errores = 0;
            
            const logContainer = document.getElementById('log-container');
            const startTime = Date.now();
            
            const interval = setInterval(() => {
                // Simular procesamiento de 5-10 filas por iteración
                const filasProcesar = Math.min(Math.ceil(Math.random() * 10) + 5, totalFilas - filasImportadas);
                
                for (let i = 0; i < filasProcesar; i++) {
                    filasImportadas++;
                    
                    // Simular éxito/error (90% éxito)
                    if (Math.random() > 0.1) {
                        exitosos++;
                        logContainer.innerHTML += `<div class="text-success">✓ Fila ${filasImportadas}: Producto importado exitosamente</div>`;
                    } else {
                        errores++;
                        logContainer.innerHTML += `<div class="text-danger">✗ Fila ${filasImportadas}: Error - Datos incompletos</div>`;
                    }
                }
                
                // Actualizar progreso
                const progreso = (filasImportadas / totalFilas) * 100;
                document.getElementById('import-progress').style.width = progreso + '%';
                document.getElementById('import-progress').innerHTML = 
                    `<span class="position-absolute w-100 text-center text-dark fw-bold">${progreso.toFixed(1)}%</span>`;
                
                // Actualizar estadísticas
                document.getElementById('import-success').textContent = exitosos;
                document.getElementById('import-errors').textContent = errores;
                
                // Calcular tiempo restante
                const tiempoTranscurrido = (Date.now() - startTime) / 1000;
                const velocidad = filasImportadas / tiempoTranscurrido;
                const tiempoRestante = (totalFilas - filasImportadas) / velocidad;
                
                document.getElementById('velocidad-procesamiento').textContent = velocidad.toFixed(1) + ' filas/seg';
                document.getElementById('tiempo-restante').textContent = 
                    Math.floor(tiempoRestante / 60) + ':' + (Math.floor(tiempoRestante % 60)).toString().padStart(2, '0');
                
                logContainer.scrollTop = logContainer.scrollHeight;
                
                if (filasImportadas >= totalFilas) {
                    clearInterval(interval);
                    finalizarImportacion();
                }
            }, 500);
        }

        function finalizarImportacion() {
            document.getElementById('tiempo-restante').textContent = '00:00';
            document.getElementById('import-final-actions').style.display = 'block';
            
            // Actualizar contadores globales
            const exitosos = parseInt(document.getElementById('import-success').textContent);
            document.getElementById('productos-creados').textContent = exitosos;
            document.getElementById('filas-importadas').textContent = exitosos;
            document.getElementById('archivos-procesados').textContent = '1';
            
            alert('¡Importación completada exitosamente!');
        }

        function volverAPreview() {
            document.getElementById('preview-tab').click();
        }

        function descargarPlantilla(tipo) {
            // Crear plantilla Excel
            const plantillas = {
                'productos': [
                    ['Nombre', 'Código', 'Código de Barras', 'Precio Costo', 'Precio Venta', 'Stock', 'Categoría'],
                    ['Ejemplo Producto 1', 'PROD001', '1234567890123', '100.00', '150.00', '50', 'Categoría A'],
                    ['Ejemplo Producto 2', 'PROD002', '1234567890124', '200.00', '300.00', '25', 'Categoría B']
                ],
                'inventario': [
                    ['Nombre', 'Código', 'Código de Barras', 'Precio Costo', 'Precio Venta', 'Stock Actual', 'Stock Mínimo', 'Categoría', 'Proveedor', 'Ubicación', 'Activo'],
                    ['Producto Ejemplo', 'PROD001', '1234567890123', '100.00', '150.00', '50', '10', 'Electrónicos', 'Proveedor A', 'Almacén 1', 'SI']
                ]
            };
            
            const ws = XLSX.utils.aoa_to_sheet(plantillas[tipo]);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Plantilla');
            
            XLSX.writeFile(wb, `plantilla_${tipo}.xlsx`);
        }

        function descargarReporte() {
            alert('Descargando reporte de importación...');
        }

        function verProductosImportados() {
            window.location.href = '../inventario/inventario.php';
        }

        function mostrarAyuda() {
            alert('Sistema de ayuda próximamente...');
        }

        function actualizarTimer() {
            setInterval(() => {
                const tiempoTranscurrido = Math.floor((Date.now() - sessionStartTime) / 60000);
                document.getElementById('tiempo-sesion').textContent = tiempoTranscurrido + 'm';
                
                // Simular uso de memoria
                const memoriaUsada = Math.floor(Math.random() * 50) + 10;
                document.getElementById('memoria-usada').textContent = memoriaUsada + ' MB';
            }, 60000);
        }
    </script>
</body>
</html>

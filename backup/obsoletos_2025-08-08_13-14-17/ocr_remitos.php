<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();

// Estadísticas rápidas para el dashboard
try {
    // Contar productos demo
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM productos WHERE codigo LIKE 'DEMO%'");
    $productos_demo = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Contar proveedores demo
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM proveedores WHERE codigo LIKE 'PROV%'");
    $proveedores_demo = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Verificar si existe tabla de procesamiento
    $stmt = $pdo->query("SHOW TABLES LIKE 'scanner_processed_files'");
    $tabla_existe = $stmt->rowCount() > 0;

    $archivos_procesados = 0;
    if ($tabla_existe) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM scanner_processed_files WHERE DATE(processed_at) = CURDATE()");
        $archivos_procesados = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
} catch (Exception $e) {
    $productos_demo = 0;
    $proveedores_demo = 0;
    $archivos_procesados = 0;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCR Remitos - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .ocr-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .ocr-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .btn-ocr {
            border-radius: 25px;
            padding: 10px 30px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>

<body>
    <?php include '../../config/navbar_code.php'; ?>

    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="fas fa-eye text-primary"></i> OCR Remitos</h1>
                        <p class="text-muted mb-0">Sistema de reconocimiento óptico para documentos de compras e inventario</p>
                    </div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../../menu_principal.php">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="compras.php">Compras</a></li>
                            <li class="breadcrumb-item active">OCR Remitos</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card text-center p-3">
                    <h3><?php echo $productos_demo; ?></h3>
                    <small>Productos Demo</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-center p-3">
                    <h3><?php echo $proveedores_demo; ?></h3>
                    <small>Proveedores Demo</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-center p-3">
                    <h3><?php echo $archivos_procesados; ?></h3>
                    <small>Procesados Hoy</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-center p-3">
                    <h3>95%</h3>
                    <small>Precisión OCR</small>
                </div>
            </div>
        </div>

        <!-- Herramientas Principales -->
        <div class="row mb-4">
            <div class="col-lg-4">
                <div class="card ocr-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-upload feature-icon text-primary"></i>
                        <h5>Centro de Control</h5>
                        <p class="text-muted">
                            Sube imágenes de remitos manualmente. Procesamiento en tiempo real con comparación automática.
                        </p>
                        <div class="mt-auto">
                            <a href="ocr_remitos/control_center.php" class="btn btn-primary btn-ocr">
                                <i class="fas fa-eye"></i> Acceder
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card ocr-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-file-plus feature-icon text-success"></i>
                        <h5>Generador de Remitos</h5>
                        <p class="text-muted">
                            Crea remitos de prueba con productos reales para imprimir y escanear. Ideal para testing.
                        </p>
                        <div class="mt-auto">
                            <a href="ocr_remitos/generar_remitos_demo.php" class="btn btn-success btn-ocr">
                                <i class="fas fa-plus-circle"></i> Generar
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card ocr-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-scanner feature-icon text-warning"></i>
                        <h5>Monitor Scanner HP</h5>
                        <p class="text-muted">
                            Monitoreo automático del scanner HP. Procesamiento automático de documentos escaneados.
                        </p>
                        <div class="mt-auto">
                            <a href="ocr_remitos/hp_scanner_monitor.php" class="btn btn-warning btn-ocr">
                                <i class="fas fa-desktop"></i> Monitor
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Características del Sistema -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6><i class="fas fa-cogs"></i> Características del Sistema</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <h6>📊 Procesamiento</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success"></i> OCR automático</li>
                                    <li><i class="fas fa-check text-success"></i> Matching inteligente</li>
                                    <li><i class="fas fa-check text-success"></i> Doble control</li>
                                    <li><i class="fas fa-check text-success"></i> Comparación en tiempo real</li>
                                </ul>
                            </div>
                            <div class="col-6">
                                <h6>🔧 Tecnología</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success"></i> Tesseract OCR</li>
                                    <li><i class="fas fa-check text-success"></i> Scanner HP integrado</li>
                                    <li><i class="fas fa-check text-success"></i> Códigos EAN-13</li>
                                    <li><i class="fas fa-check text-success"></i> Workflow supervisor</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6><i class="fas fa-route"></i> Flujo de Trabajo</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="d-flex mb-3">
                                <div class="badge bg-primary rounded-pill me-3">1</div>
                                <div>
                                    <strong>Generar/Escanear</strong><br>
                                    <small class="text-muted">Crear remito demo o escanear documento real</small>
                                </div>
                            </div>
                            <div class="d-flex mb-3">
                                <div class="badge bg-primary rounded-pill me-3">2</div>
                                <div>
                                    <strong>Procesar OCR</strong><br>
                                    <small class="text-muted">Extracción automática de texto y datos</small>
                                </div>
                            </div>
                            <div class="d-flex mb-3">
                                <div class="badge bg-primary rounded-pill me-3">3</div>
                                <div>
                                    <strong>Matching</strong><br>
                                    <small class="text-muted">Comparación con productos existentes</small>
                                </div>
                            </div>
                            <div class="d-flex">
                                <div class="badge bg-primary rounded-pill me-3">4</div>
                                <div>
                                    <strong>Validación</strong><br>
                                    <small class="text-muted">Revisión y aprobación final</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Links Rápidos -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h6><i class="fas fa-link"></i> Enlaces Rápidos</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="compras.php" class="btn btn-outline-primary w-100 mb-2">
                                    <i class="fas fa-shopping-cart"></i> Ver Compras
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="proveedores.php" class="btn btn-outline-secondary w-100 mb-2">
                                    <i class="fas fa-building"></i> Proveedores
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="../Inventario/productos.php" class="btn btn-outline-info w-100 mb-2">
                                    <i class="fas fa-box"></i> Productos
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="reportes_compras.php" class="btn btn-outline-success w-100 mb-2">
                                    <i class="fas fa-chart-bar"></i> Reportes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
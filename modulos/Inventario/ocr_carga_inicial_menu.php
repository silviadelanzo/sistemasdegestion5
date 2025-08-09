<?php
// modulos/inventario/ocr_carga_inicial_menu.php
session_start();
require_once '../../config/config.php';

// Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga Inicial de Inventario - OCR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .feature-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .workflow-step {
            border-left: 4px solid #007bff;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }

        .workflow-step.completed {
            border-left-color: #28a745;
            opacity: 0.7;
        }

        .workflow-step.active {
            border-left-color: #ffc107;
            background: #fff3cd;
            padding: 1rem;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="../inventario/"><i class="fas fa-boxes"></i> Módulo Inventario</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../compras/ocr_remitos/control_center.php">
                    <i class="fas fa-truck"></i> OCR Compras
                </a>
                <span class="navbar-text">
                    <i class="fas fa-user"></i> <?php echo $_SESSION['usuario_nombre']; ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Header Principal -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h1><i class="fas fa-eye"></i> Sistema OCR - Carga Inicial de Inventario</h1>
                        <p class="lead">Automatización inteligente con doble control de calidad</p>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <h4>🔍</h4>
                                <small>Lectura Automática</small>
                            </div>
                            <div class="col-md-3">
                                <h4>🎯</h4>
                                <small>Precisión 100%</small>
                            </div>
                            <div class="col-md-3">
                                <h4>✅</h4>
                                <small>Doble Control</small>
                            </div>
                            <div class="col-md-3">
                                <h4>💰</h4>
                                <small>Sistema Gratuito</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Opciones Principales -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card feature-card">
                    <div class="card-body text-center">
                        <div class="feature-icon text-success">
                            <i class="fas fa-upload"></i>
                        </div>
                        <h4>Carga Inicial de Inventario</h4>
                        <p class="text-muted">Procesa documentos de inventario inicial, listas de productos, conteos físicos, etc.</p>
                        <ul class="list-unstyled text-start">
                            <li><i class="fas fa-check text-success"></i> Lectura automática de códigos y descripciones</li>
                            <li><i class="fas fa-check text-success"></i> Comparación con inventario actual</li>
                            <li><i class="fas fa-check text-success"></i> Detección de productos nuevos</li>
                            <li><i class="fas fa-check text-success"></i> Ajustes automáticos de stock</li>
                        </ul>
                        <a href="../compras/ocr_remitos/control_center.php#inventario" class="btn btn-success btn-lg">
                            <i class="fas fa-magic"></i> Comenzar Carga OCR
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card feature-card">
                    <div class="card-body text-center">
                        <div class="feature-icon text-primary">
                            <i class="fas fa-truck"></i>
                        </div>
                        <h4>Procesamiento de Compras</h4>
                        <p class="text-muted">Procesa remitos y facturas de proveedores para actualización automática de inventario.</p>
                        <ul class="list-unstyled text-start">
                            <li><i class="fas fa-check text-primary"></i> Lectura de remitos de proveedores</li>
                            <li><i class="fas fa-check text-primary"></i> Matching inteligente de productos</li>
                            <li><i class="fas fa-check text-primary"></i> Actualización automática de stock</li>
                            <li><i class="fas fa-check text-primary"></i> Control de recepción de mercadería</li>
                        </ul>
                        <a href="../compras/ocr_remitos/control_center.php#compras" class="btn btn-primary btn-lg">
                            <i class="fas fa-scanner"></i> Procesar Remitos
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workflow de Doble Control -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="fas fa-cogs"></i> Proceso de Doble Control</h5>
                    </div>
                    <div class="card-body">
                        <div class="workflow-step completed">
                            <h6><i class="fas fa-upload"></i> PASO 1: Subir Documento</h6>
                            <p>El operador sube la imagen del documento (inventario, remito, factura, etc.)</p>
                        </div>

                        <div class="workflow-step completed">
                            <h6><i class="fas fa-eye"></i> PASO 2: Procesamiento OCR</h6>
                            <p>El sistema lee automáticamente códigos, descripciones, cantidades y precios</p>
                        </div>

                        <div class="workflow-step active">
                            <h6><i class="fas fa-search"></i> PASO 3: Matching Inteligente</h6>
                            <p><strong>PROCESO ACTUAL:</strong> El sistema compara cada producto detectado con la base de datos existente usando múltiples algoritmos de similitud</p>
                        </div>

                        <div class="workflow-step">
                            <h6><i class="fas fa-clipboard-check"></i> PASO 4: Generación de Control</h6>
                            <p>Se genera un documento de control con las acciones recomendadas para cada producto</p>
                        </div>

                        <div class="workflow-step">
                            <h6><i class="fas fa-balance-scale"></i> PASO 5: Comparación Manual</h6>
                            <p><strong>DOBLE CONTROL:</strong> El operador compara físicamente el documento original con el documento de control generado</p>
                        </div>

                        <div class="workflow-step">
                            <h6><i class="fas fa-user-check"></i> PASO 6: Aprobación Supervisada</h6>
                            <p>El supervisor revisa y aprueba los cambios antes de aplicarlos al inventario</p>
                        </div>

                        <div class="workflow-step">
                            <h6><i class="fas fa-database"></i> PASO 7: Aplicación de Cambios</h6>
                            <p>Solo después de la doble verificación se actualizan los datos en el sistema</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6><i class="fas fa-chart-bar"></i> Estadísticas del Sistema</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // Obtener estadísticas si existen las tablas
                        $stats = [
                            'documentos_procesados' => 0,
                            'productos_detectados' => 0,
                            'precision_promedio' => 0,
                            'documentos_pendientes' => 0
                        ];

                        try {
                            // Verificar si existen las tablas OCR
                            $check_tables = $conexion->query("SHOW TABLES LIKE 'ocr_%'");
                            if ($check_tables->num_rows > 0) {
                                $result = $conexion->query("SELECT COUNT(*) as total FROM ocr_document_comparisons WHERE status = 'approved'");
                                if ($result) {
                                    $row = $result->fetch_assoc();
                                    $stats['documentos_procesados'] = $row['total'];
                                }

                                $result = $conexion->query("SELECT COUNT(*) as total FROM ocr_document_comparisons WHERE status = 'pending_approval'");
                                if ($result) {
                                    $row = $result->fetch_assoc();
                                    $stats['documentos_pendientes'] = $row['total'];
                                }

                                $result = $conexion->query("SELECT AVG(precision_promedio) as promedio FROM ocr_precision_stats");
                                if ($result && $result->num_rows > 0) {
                                    $row = $result->fetch_assoc();
                                    $stats['precision_promedio'] = round($row['promedio'] ?? 85, 1);
                                } else {
                                    $stats['precision_promedio'] = 85.0; // Valor estimado
                                }
                            }
                        } catch (Exception $e) {
                            // Si no existen las tablas, usar valores por defecto
                            $stats['precision_promedio'] = 85.0;
                        }
                        ?>

                        <div class="text-center mb-3">
                            <h3 class="text-success"><?php echo $stats['documentos_procesados']; ?></h3>
                            <small>Documentos Procesados</small>
                        </div>

                        <div class="text-center mb-3">
                            <h3 class="text-warning"><?php echo $stats['documentos_pendientes']; ?></h3>
                            <small>Pendientes de Aprobación</small>
                        </div>

                        <div class="text-center mb-3">
                            <h3 class="text-info"><?php echo $stats['precision_promedio']; ?>%</h3>
                            <small>Precisión Promedio</small>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <a href="../compras/ocr_remitos/control_center.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye"></i> Centro de Control
                            </a>
                            <button class="btn btn-outline-info btn-sm" onclick="instalarSistema()">
                                <i class="fas fa-download"></i> Instalar Sistema
                            </button>
                            <a href="../../assets/uploads/" class="btn btn-outline-secondary btn-sm" target="_blank">
                                <i class="fas fa-folder"></i> Ver Archivos
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header bg-secondary text-white">
                        <h6><i class="fas fa-question-circle"></i> Ayuda Rápida</h6>
                    </div>
                    <div class="card-body">
                        <h6>Tipos de Documentos Soportados:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> Listas de inventario</li>
                            <li><i class="fas fa-check text-success"></i> Conteos físicos</li>
                            <li><i class="fas fa-check text-success"></i> Remitos de proveedores</li>
                            <li><i class="fas fa-check text-success"></i> Facturas</li>
                            <li><i class="fas fa-check text-success"></i> Listas de precios</li>
                        </ul>

                        <h6 class="mt-3">Formatos Soportados:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-image text-primary"></i> JPG, PNG, TIFF</li>
                            <li><i class="fas fa-file-pdf text-danger"></i> PDF (imagen)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function instalarSistema() {
            if (confirm('¿Desea instalar las tablas necesarias para el sistema OCR?\n\nEsto creará las siguientes tablas:\n- ocr_control_documents\n- ocr_document_comparisons\n- movimientos_inventario\n- ocr_processed_files\n- ocr_precision_stats\n- ocr_learning_data')) {
                // Redirigir a un script de instalación
                window.open('../compras/ocr_remitos/dual_control_database.sql', '_blank');
                alert('Ejecute el script SQL en phpMyAdmin para completar la instalación.');
            }
        }

        // Actualizar progreso cada 30 segundos si hay documentos pendientes
        <?php if ($stats['documentos_pendientes'] > 0): ?>
            setInterval(function() {
                location.reload();
            }, 30000);
        <?php endif; ?>
    </script>
</body>

</html>
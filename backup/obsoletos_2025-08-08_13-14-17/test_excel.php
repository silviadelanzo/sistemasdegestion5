<?php
require_once 'config/config.php';

iniciarSesionSegura();
requireLogin('login.php');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Excel - <?php echo SISTEMA_NOMBRE; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-file-earmark-excel me-2"></i>
                            Prueba de Generación de Excel
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5><i class="bi bi-info-circle me-2"></i>Estado del Sistema</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>PHP:</strong> <?php echo phpversion(); ?></p>
                                    <p><strong>Composer:</strong>
                                        <?php echo file_exists('vendor/autoload.php') ? '✅ Instalado' : '❌ No encontrado'; ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>PhpSpreadsheet:</strong>
                                        <?php
                                        if (file_exists('vendor/autoload.php')) {
                                            require_once 'vendor/autoload.php';
                                            echo class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet') ? '✅ Instalado' : '❌ No encontrado';
                                        } else {
                                            echo '❌ Vendor no encontrado';
                                        }
                                        ?>
                                    </p>
                                    <p><strong>Extensión GD:</strong>
                                        <?php echo extension_loaded('gd') ? '✅ Activa' : '❌ Inactiva'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <?php if (file_exists('vendor/autoload.php') && class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')): ?>
                            <div class="alert alert-success">
                                <h5><i class="bi bi-check-circle me-2"></i>¡Sistema Listo!</h5>
                                <p>PhpSpreadsheet está instalado y listo para generar archivos Excel.</p>
                            </div>

                            <div class="d-grid gap-3">
                                <a href="generar_excel_inventario.php" class="btn btn-success btn-lg">
                                    <i class="bi bi-download me-2"></i>
                                    Descargar Excel de Inventario
                                </a>

                                <a href="verificar_gd.php" class="btn btn-info">
                                    <i class="bi bi-gear me-2"></i>
                                    Verificar Extensiones PHP
                                </a>

                                <a href="verificar_extensiones.php" class="btn btn-secondary">
                                    <i class="bi bi-list-check me-2"></i>
                                    Ver Todas las Extensiones
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h5><i class="bi bi-exclamation-triangle me-2"></i>Configuración Pendiente</h5>

                                <?php if (!file_exists('vendor/autoload.php')): ?>
                                    <p><strong>1. Instalar Composer:</strong> Ejecuta en terminal:</p>
                                    <code>composer require phpoffice/phpspreadsheet</code>
                                <?php endif; ?>

                                <?php if (!extension_loaded('gd')): ?>
                                    <p><strong>2. Activar extensión GD:</strong></p>
                                    <ol>
                                        <li>Abrir <code>C:\xampp\php\php.ini</code></li>
                                        <li>Buscar <code>;extension=gd</code></li>
                                        <li>Quitar el punto y coma: <code>extension=gd</code></li>
                                        <li>Reiniciar Apache</li>
                                    </ol>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4">
                            <h6>Opciones Disponibles:</h6>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><strong>PhpSpreadsheet</strong> - Para archivos Excel reales (.xlsx)</span>
                                    <span class="badge bg-success">Recomendado</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><strong>CSV</strong> - Compatible con TextMaker y todos los programas</span>
                                    <span class="badge bg-info">Ya implementado</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><strong>HTML con headers Excel</strong> - Para visualización web</span>
                                    <span class="badge bg-warning">Básico</span>
                                </li>
                            </ul>
                        </div>

                        <div class="mt-4 text-center">
                            <a href="modulos/Inventario/productos.php" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left me-2"></i>
                                Volver al Inventario
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
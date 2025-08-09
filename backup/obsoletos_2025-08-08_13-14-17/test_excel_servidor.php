<?php
require_once 'config/config.php';

// Verificar login solo si existe la función
if (function_exists('iniciarSesionSegura')) {
    iniciarSesionSegura();
}

// No requerir login para esta prueba en servidor
// requireLogin('login.php');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Excel en Servidor - <?php echo defined('SISTEMA_NOMBRE') ? SISTEMA_NOMBRE : 'Sistema'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-file-earmark-excel me-2"></i>
                            Prueba de Excel en Servidor
                        </h4>
                    </div>
                    <div class="card-body">

                        <!-- Información del Servidor -->
                        <div class="alert alert-info">
                            <h5><i class="bi bi-server me-2"></i>Información del Servidor</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>PHP:</strong> <?php echo phpversion(); ?></p>
                                    <p><strong>Sistema:</strong> <?php echo PHP_OS; ?></p>
                                    <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido'; ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Memoria PHP:</strong> <?php echo ini_get('memory_limit'); ?></p>
                                    <p><strong>Tiempo máximo:</strong> <?php echo ini_get('max_execution_time'); ?>s</p>
                                    <p><strong>Upload máximo:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Timezone:</strong> <?php echo date_default_timezone_get(); ?></p>
                                    <p><strong>Fecha/Hora:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                                    <p><strong>Directorio:</strong> <?php echo __DIR__; ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Verificación de Composer y Dependencias -->
                        <div class="alert alert-<?php echo file_exists('vendor/autoload.php') ? 'success' : 'warning'; ?>">
                            <h5><i class="bi bi-box-seam me-2"></i>Dependencias</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Composer vendor/autoload.php:</strong>
                                        <?php echo file_exists('vendor/autoload.php') ? '✅ Encontrado' : '❌ No encontrado'; ?>
                                    </p>
                                    <?php if (file_exists('vendor/autoload.php')): ?>
                                        <p><strong>PhpSpreadsheet:</strong>
                                            <?php
                                            require_once 'vendor/autoload.php';
                                            echo class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet') ? '✅ Disponible' : '❌ No disponible';
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>composer.json:</strong>
                                        <?php echo file_exists('composer.json') ? '✅ Existe' : '❌ No existe'; ?>
                                    </p>
                                    <p><strong>composer.lock:</strong>
                                        <?php echo file_exists('composer.lock') ? '✅ Existe' : '❌ No existe'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Verificación de Extensiones PHP -->
                        <div class="alert alert-secondary">
                            <h5><i class="bi bi-gear me-2"></i>Extensiones PHP</h5>
                            <div class="row">
                                <?php
                                $extensiones_necesarias = [
                                    'zip' => 'Para archivos comprimidos',
                                    'xml' => 'Para procesamiento XML',
                                    'xmlreader' => 'Para lectura XML',
                                    'xmlwriter' => 'Para escritura XML',
                                    'mbstring' => 'Para cadenas multibyte',
                                    'gd' => 'Para imágenes (opcional)',
                                    'curl' => 'Para conexiones HTTP',
                                    'openssl' => 'Para conexiones seguras'
                                ];

                                $col_count = 0;
                                foreach ($extensiones_necesarias as $ext => $desc) {
                                    if ($col_count % 2 == 0) echo '<div class="col-md-6">';

                                    $estado = extension_loaded($ext) ? '✅' : '❌';
                                    $color = extension_loaded($ext) ? 'success' : 'danger';
                                    echo "<p><span class='text-{$color}'><strong>{$ext}:</strong> {$estado}</span> <small class='text-muted'>({$desc})</small></p>";

                                    $col_count++;
                                    if ($col_count % 2 == 0) echo '</div>';
                                }
                                if ($col_count % 2 != 0) echo '</div>';
                                ?>
                            </div>
                        </div>

                        <!-- Prueba de Conexión a Base de Datos -->
                        <?php
                        $db_status = 'danger';
                        $db_message = 'Error de conexión';
                        $db_details = '';

                        try {
                            if (function_exists('conectarDB')) {
                                $pdo = conectarDB();
                                $db_status = 'success';
                                $db_message = 'Conexión exitosa';

                                // Verificar tabla productos
                                $sql = "SELECT COUNT(*) as total FROM productos WHERE activo = 1";
                                $stmt = $pdo->query($sql);
                                $total_productos = $stmt->fetchColumn();
                                $db_details = "Productos activos: {$total_productos}";
                            } else {
                                $db_status = 'warning';
                                $db_message = 'Función conectarDB no disponible';
                            }
                        } catch (Exception $e) {
                            $db_details = 'Error: ' . $e->getMessage();
                        }
                        ?>

                        <div class="alert alert-<?php echo $db_status; ?>">
                            <h5><i class="bi bi-database me-2"></i>Base de Datos</h5>
                            <p><strong>Estado:</strong> <?php echo $db_message; ?></p>
                            <?php if ($db_details): ?>
                                <p><strong>Detalles:</strong> <?php echo $db_details; ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Acciones de Prueba -->
                        <?php if (file_exists('vendor/autoload.php') && class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')): ?>
                            <div class="alert alert-success">
                                <h5><i class="bi bi-check-circle me-2"></i>¡Sistema Listo para Excel!</h5>
                                <p>Todas las dependencias están disponibles en el servidor.</p>
                            </div>

                            <div class="alert alert-warning">
                                <h5><i class="bi bi-exclamation-circle me-2"></i>PhpSpreadsheet Detectado</h5>
                                <p>Pero puede tener problemas de compatibilidad con PHP <?php echo PHP_VERSION; ?></p>
                                <p><strong>Recomendación:</strong> Usa las opciones sin dependencias para máxima compatibilidad.</p>
                            </div>

                            <div class="d-grid gap-3">
                                <a href="excel_xlsx_nativo.php" class="btn btn-success btn-lg">
                                    <i class="bi bi-download me-2"></i>
                                    Excel XLSX Nativo (Garantizado)
                                </a>

                                <a href="excel_php81.php" class="btn btn-outline-primary">
                                    <i class="bi bi-file-earmark-excel me-2"></i>
                                    Probar PhpSpreadsheet
                                </a>

                                <a href="excel_real_sinvendor.php" class="btn btn-outline-success">
                                    <i class="bi bi-file-earmark-excel me-2"></i>
                                    Excel .XLS Real
                                </a>

                                <a href="test_excel_simple_servidor.php" class="btn btn-info">
                                    <i class="bi bi-file-earmark-excel me-2"></i>
                                    Test Básico PhpSpreadsheet
                                </a>

                                <div class="row">
                                    <div class="col-md-6">
                                        <a href="excel_nativo.php" class="btn btn-outline-success w-100">
                                            <i class="bi bi-file-earmark-excel me-2"></i>
                                            Excel Nativo (Alternativo)
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="modulos/Inventario/reporte_inventario_csv.php" class="btn btn-outline-info w-100">
                                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                                            Reporte CSV Avanzado
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h5><i class="bi bi-exclamation-triangle me-2"></i>Configuración Pendiente</h5>
                                <p>Para generar archivos Excel necesitas:</p>
                                <ol>
                                    <li>Subir la carpeta <code>vendor/</code> completa al servidor</li>
                                    <li>O ejecutar <code>composer install</code> en el servidor</li>
                                    <li>Verificar permisos de escritura</li>
                                </ol>
                            </div>

                            <!-- Opciones alternativas mientras tanto -->
                            <div class="alert alert-info">
                                <h5><i class="bi bi-lightbulb me-2"></i>Opciones Disponibles Sin Vendor</h5>
                                <p>Mientras configuras PhpSpreadsheet, puedes usar estas alternativas:</p>
                            </div>

                            <div class="alert alert-warning">
                                <h5><i class="bi bi-exclamation-triangle me-2"></i>Problema de Compatibilidad PHP</h5>
                                <p>Tu servidor tiene <strong>PHP <?php echo PHP_VERSION; ?></strong> pero PhpSpreadsheet requiere PHP 8.2+</p>
                                <p><strong>Solución:</strong> Usa los generadores sin dependencias que funcionan en cualquier servidor.</p>
                            </div>

                            <div class="d-grid gap-3">
                                <a href="excel_xlsx_nativo.php" class="btn btn-success btn-lg">
                                    <i class="bi bi-file-earmark-excel me-2"></i>
                                    Excel XLSX Nativo (Recomendado PlanMaker)
                                </a>

                                <a href="excel_real_sinvendor.php" class="btn btn-outline-success">
                                    <i class="bi bi-file-earmark-excel me-2"></i>
                                    Excel .XLS Real (BIFF8)
                                </a>

                                <div class="row">
                                    <div class="col-md-6">
                                        <a href="excel_php81.php" class="btn btn-outline-warning w-100">
                                            <i class="bi bi-tools me-2"></i>
                                            Intentar con PhpSpreadsheet
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="instalador_php81.php" class="btn btn-outline-info w-100">
                                            <i class="bi bi-download me-2"></i>
                                            Instalar Versión Compatible
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Información para Subir al Servidor -->
                        <div class="mt-4">
                            <h6><i class="bi bi-cloud-upload me-2"></i>Para subir al servidor necesitas:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><strong>Archivos PHP:</strong> Todos los .php</li>
                                        <li class="list-group-item"><strong>Carpeta vendor/:</strong> Completa con todas las librerías</li>
                                        <li class="list-group-item"><strong>composer.json:</strong> Archivo de dependencias</li>
                                        <li class="list-group-item"><strong>config/:</strong> Archivos de configuración</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><strong>Permisos:</strong> 755 para carpetas, 644 para archivos</li>
                                        <li class="list-group-item"><strong>PHP:</strong> Versión 7.4 o superior</li>
                                        <li class="list-group-item"><strong>MySQL:</strong> Base de datos configurada</li>
                                        <li class="list-group-item"><strong>config.php:</strong> Datos de BD del servidor</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <a href="menu_principal.php" class="btn btn-outline-primary">
                                <i class="bi bi-house me-2"></i>
                                Ir al Menú Principal
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
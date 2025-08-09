<?php
// Verificador de Capacidades del Servidor para PDF y Excel
// Archivo para comprobar qué librerías están disponibles en cPanel

require_once '../../config/config.php';

// Función para verificar si una clase existe y está disponible
function verificarClase($clase, $descripcion)
{
    $disponible = class_exists($clase);
    $estado = $disponible ? '✅ DISPONIBLE' : '❌ NO DISPONIBLE';
    $color = $disponible ? 'success' : 'danger';

    return [
        'clase' => $clase,
        'descripcion' => $descripcion,
        'disponible' => $disponible,
        'estado' => $estado,
        'color' => $color
    ];
}

// Función para verificar si una extensión PHP está cargada
function verificarExtension($extension, $descripcion)
{
    $disponible = extension_loaded($extension);
    $estado = $disponible ? '✅ CARGADA' : '❌ NO CARGADA';
    $color = $disponible ? 'success' : 'danger';

    return [
        'extension' => $extension,
        'descripcion' => $descripcion,
        'disponible' => $disponible,
        'estado' => $estado,
        'color' => $color
    ];
}

// Función para verificar comandos de sistema
function verificarComando($comando, $descripcion)
{
    $disponible = false;
    $ruta = '';

    // Verificar en sistemas Unix/Linux
    if (function_exists('shell_exec')) {
        $ruta = trim(shell_exec("which $comando 2>/dev/null"));
        if (!empty($ruta)) {
            $disponible = true;
        }
    }

    $estado = $disponible ? "✅ DISPONIBLE en: $ruta" : '❌ NO DISPONIBLE';
    $color = $disponible ? 'success' : 'danger';

    return [
        'comando' => $comando,
        'descripcion' => $descripcion,
        'disponible' => $disponible,
        'estado' => $estado,
        'color' => $color,
        'ruta' => $ruta
    ];
}

// Lista de verificaciones
$verificaciones_pdf = [
    verificarClase('TCPDF', 'TCPDF - Generador PDF completo'),
    verificarClase('Dompdf\\Dompdf', 'DomPDF - HTML a PDF'),
    verificarClase('mPDF', 'mPDF - Generador PDF avanzado'),
    verificarClase('FPDF', 'FPDF - PDF básico'),
    verificarComando('wkhtmltopdf', 'wkhtmltopdf - Conversor HTML a PDF'),
    verificarComando('phantomjs', 'PhantomJS - Navegador headless'),
    verificarComando('chromium', 'Chromium - Para PDF con Puppeteer'),
    verificarComando('google-chrome', 'Google Chrome - Para PDF headless')
];

$verificaciones_excel = [
    verificarExtension('zip', 'ZipArchive - Para generar XLSX'),
    verificarClase('PhpOffice\\PhpSpreadsheet\\Spreadsheet', 'PhpSpreadsheet - Excel completo'),
    verificarClase('PHPExcel', 'PHPExcel - Excel (versión antigua)'),
    verificarExtension('simplexml', 'SimpleXML - Para XML de Excel'),
    verificarExtension('dom', 'DOM - Para manipular XML')
];

$verificaciones_sistema = [
    verificarExtension('curl', 'cURL - Para descargas HTTP'),
    verificarExtension('gd', 'GD - Para manipular imágenes'),
    verificarExtension('imagick', 'ImageMagick - Procesamiento avanzado de imágenes'),
    verificarExtension('mbstring', 'Mbstring - Manejo de cadenas UTF-8'),
    verificarExtension('iconv', 'Iconv - Conversión de encoding'),
    verificarExtension('json', 'JSON - Para datos JSON'),
    verificarExtension('xml', 'XML - Para parsing XML')
];

// Información del sistema
$info_sistema = [
    'PHP Version' => PHP_VERSION,
    'Sistema Operativo' => PHP_OS,
    'Arquitectura' => php_uname('m'),
    'Servidor Web' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
    'Límite de Memoria' => ini_get('memory_limit'),
    'Tiempo de Ejecución' => ini_get('max_execution_time') . ' segundos',
    'Subida de Archivos' => ini_get('file_uploads') ? 'Habilitada' : 'Deshabilitada',
    'Tamaño Máximo Upload' => ini_get('upload_max_filesize'),
    'Post Max Size' => ini_get('post_max_size'),
    'Funciones exec()' => function_exists('exec') ? 'Disponible' : 'Bloqueada',
    'Funciones shell_exec()' => function_exists('shell_exec') ? 'Disponible' : 'Bloqueada',
    'Funciones system()' => function_exists('system') ? 'Disponible' : 'Bloqueada'
];

// Verificar conexión a base de datos
try {
    $pdo = conectarDB();
    $bd_status = '✅ CONECTADA';
    $bd_color = 'success';
    $bd_info = 'Conexión exitosa a la base de datos';
} catch (Exception $e) {
    $bd_status = '❌ ERROR';
    $bd_color = 'danger';
    $bd_info = 'Error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificador de Capacidades del Servidor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .header-section {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }

        .verification-card {
            margin-bottom: 20px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .verification-header {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
        }

        .status-badge {
            font-size: 0.9rem;
            padding: 8px 12px;
            border-radius: 20px;
        }

        .info-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .recommendation {
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            border-left: 5px solid #e17055;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .test-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="header-section">
        <div class="container">
            <div class="text-center">
                <h1><i class="bi bi-server me-3"></i>Verificador de Capacidades del Servidor</h1>
                <p class="lead">Comprobación de librerías para PDF, Excel y funcionalidades del sistema</p>
                <small>Ejecutado el: <?php echo date('d/m/Y H:i:s'); ?></small>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Información del Sistema -->
        <div class="verification-card card">
            <div class="verification-header">
                <h4 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información del Sistema</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6">
                        <table class="table table-striped">
                            <?php foreach (array_slice($info_sistema, 0, ceil(count($info_sistema) / 2), true) as $key => $value): ?>
                                <tr>
                                    <th style="width: 50%;"><?php echo $key; ?></th>
                                    <td><code><?php echo $value; ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <div class="col-lg-6">
                        <table class="table table-striped">
                            <?php foreach (array_slice($info_sistema, ceil(count($info_sistema) / 2), null, true) as $key => $value): ?>
                                <tr>
                                    <th style="width: 50%;"><?php echo $key; ?></th>
                                    <td><code><?php echo $value; ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>

                <!-- Estado de Base de Datos -->
                <div class="mt-3">
                    <h6><i class="bi bi-database me-2"></i>Estado de Base de Datos</h6>
                    <div class="alert alert-<?php echo $bd_color; ?>">
                        <strong><?php echo $bd_status; ?></strong> - <?php echo $bd_info; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Verificaciones PDF -->
        <div class="verification-card card">
            <div class="verification-header">
                <h4 class="mb-0"><i class="bi bi-file-earmark-pdf me-2"></i>Capacidades para PDF</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Librería/Comando</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($verificaciones_pdf as $verificacion): ?>
                                <tr>
                                    <td><code><?php echo $verificacion['clase'] ?? $verificacion['comando']; ?></code></td>
                                    <td><?php echo $verificacion['descripcion']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $verificacion['color']; ?> status-badge">
                                            <?php echo $verificacion['estado']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Verificaciones Excel -->
        <div class="verification-card card">
            <div class="verification-header">
                <h4 class="mb-0"><i class="bi bi-file-earmark-excel me-2"></i>Capacidades para Excel</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Extensión/Librería</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($verificaciones_excel as $verificacion): ?>
                                <tr>
                                    <td><code><?php echo $verificacion['extension'] ?? $verificacion['clase']; ?></code></td>
                                    <td><?php echo $verificacion['descripcion']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $verificacion['color']; ?> status-badge">
                                            <?php echo $verificacion['estado']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Extensiones del Sistema -->
        <div class="verification-card card">
            <div class="verification-header">
                <h4 class="mb-0"><i class="bi bi-gear me-2"></i>Extensiones del Sistema</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Extensión</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($verificaciones_sistema as $verificacion): ?>
                                <tr>
                                    <td><code><?php echo $verificacion['extension']; ?></code></td>
                                    <td><?php echo $verificacion['descripcion']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $verificacion['color']; ?> status-badge">
                                            <?php echo $verificacion['estado']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recomendaciones -->
        <div class="test-section">
            <h4><i class="bi bi-lightbulb me-2"></i>Recomendaciones</h4>

            <?php
            $zip_disponible = extension_loaded('zip');
            $pdf_libs = array_filter($verificaciones_pdf, function ($v) {
                return $v['disponible'];
            });
            $excel_libs = array_filter($verificaciones_excel, function ($v) {
                return $v['disponible'];
            });
            ?>

            <?php if ($zip_disponible): ?>
                <div class="alert alert-success">
                    <strong>✅ Excel Nativo:</strong> ZipArchive está disponible. Puedes usar el generador Excel nativo sin dependencias.
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <strong>❌ Excel Limitado:</strong> ZipArchive no está disponible. Solo podrás generar CSV.
                </div>
            <?php endif; ?>

            <?php if (count($pdf_libs) > 0): ?>
                <div class="alert alert-success">
                    <strong>✅ PDF Disponible:</strong> Se encontraron <?php echo count($pdf_libs); ?> librería(s) para PDF.
                    Recomendación: <?php echo $pdf_libs[0]['descripcion']; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <strong>⚠️ PDF Básico:</strong> No se encontraron librerías PDF. Se usará HTML imprimible como alternativa.
                </div>
            <?php endif; ?>

            <div class="recommendation">
                <h6><i class="bi bi-cloud-upload me-2"></i>Archivos Mínimos para Subir al Servidor:</h6>
                <ul class="mb-0">
                    <li><code>excel_xlsx_nativo.php</code> - ✅ Generador Excel sin dependencias</li>
                    <li><code>modulos/Inventario/reporte_completo_excel.php</code> - ✅ Excel de 3 hojas</li>
                    <li><code>modulos/Inventario/reporte_inventario_pdf.php</code> - ✅ Generador PDF adaptable</li>
                    <li><code>modulos/Inventario/verificar_servidor.php</code> - ⚠️ Este archivo (para diagnóstico)</li>
                    <li><code>config/config.php</code> - ✅ Configuración de BD (actualizada para servidor)</li>
                </ul>
            </div>
        </div>

        <!-- Pruebas Rápidas -->
        <div class="test-section">
            <h4><i class="bi bi-play-circle me-2"></i>Pruebas Rápidas</h4>
            <div class="row">
                <div class="col-md-4">
                    <a href="../../excel_xlsx_nativo.php" class="btn btn-success w-100" target="_blank">
                        <i class="bi bi-file-earmark-excel me-2"></i>Probar Excel Nativo
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="reporte_completo_excel.php" class="btn btn-primary w-100" target="_blank">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>Probar Excel 3 Hojas
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="reporte_inventario_pdf.php" class="btn btn-danger w-100" target="_blank">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Probar PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 mb-4">
            <small class="text-muted">
                Sistema de Gestión | Verificación ejecutada el <?php echo date('d/m/Y H:i:s'); ?>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
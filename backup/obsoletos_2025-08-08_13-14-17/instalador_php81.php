<?php
// Instalador de PhpSpreadsheet compatible con PHP 8.1
require_once 'config/config.php';

// Verificar si se está ejecutando la instalación
if (isset($_POST['instalar']) && $_POST['instalar'] === 'si') {

    try {
        // Crear directorio vendor si no existe
        if (!is_dir('vendor')) {
            mkdir('vendor', 0755, true);
        }

        // URL de PhpSpreadsheet compatible con PHP 8.1
        $phpspreadsheet_url = 'https://github.com/PHPOffice/PhpSpreadsheet/archive/refs/tags/1.28.0.zip';
        $temp_file = 'temp_phpspreadsheet.zip';

        echo "<div class='container py-3'>";
        echo "<div class='alert alert-info'>";
        echo "<h5>🔄 Instalando PhpSpreadsheet compatible con PHP 8.1...</h5>";
        echo "<p>Descargando desde GitHub...</p>";
        echo "</div>";

        // Descargar archivo
        $context = stream_context_create([
            'http' => [
                'timeout' => 60,
                'user_agent' => 'Mozilla/5.0 (compatible; PHP)',
                'follow_location' => true
            ]
        ]);

        $data = file_get_contents($phpspreadsheet_url, false, $context);

        if ($data === false) {
            throw new Exception('No se pudo descargar PhpSpreadsheet desde GitHub');
        }

        file_put_contents($temp_file, $data);

        echo "<div class='alert alert-success'>✅ Descarga completada</div>";

        // Extraer archivo
        $zip = new ZipArchive;
        if ($zip->open($temp_file) === TRUE) {
            echo "<div class='alert alert-info'>📦 Extrayendo archivos...</div>";

            // Extraer a directorio temporal
            $zip->extractTo('temp_extract/');
            $zip->close();

            // Mover archivos a vendor
            $source_dir = 'temp_extract/PhpSpreadsheet-1.28.0/src/';
            $dest_dir = 'vendor/phpoffice/phpspreadsheet/src/';

            if (!is_dir('vendor/phpoffice/phpspreadsheet')) {
                mkdir('vendor/phpoffice/phpspreadsheet', 0755, true);
            }

            // Función recursiva para copiar archivos
            function copiarRecursivo($src, $dst)
            {
                $dir = opendir($src);
                if (!is_dir($dst)) {
                    mkdir($dst, 0755, true);
                }

                while (($file = readdir($dir)) !== false) {
                    if ($file != '.' && $file != '..') {
                        if (is_dir($src . '/' . $file)) {
                            copiarRecursivo($src . '/' . $file, $dst . '/' . $file);
                        } else {
                            copy($src . '/' . $file, $dst . '/' . $file);
                        }
                    }
                }
                closedir($dir);
            }

            if (is_dir($source_dir)) {
                copiarRecursivo($source_dir, $dest_dir);
                echo "<div class='alert alert-success'>✅ Archivos copiados a vendor/</div>";
            }

            // Crear autoload.php simplificado
            $autoload_content = '<?php
// Autoload simplificado para PhpSpreadsheet
spl_autoload_register(function ($class) {
    $prefix = "PhpOffice\\\\PhpSpreadsheet\\\\";
    $base_dir = __DIR__ . "/phpoffice/phpspreadsheet/src/PhpSpreadsheet/";
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace("\\\\", "/", $relative_class) . ".php";
    
    if (file_exists($file)) {
        require $file;
    }
});
';

            file_put_contents('vendor/autoload.php', $autoload_content);
            echo "<div class='alert alert-success'>✅ Autoload creado</div>";

            // Limpiar archivos temporales
            unlink($temp_file);

            function eliminarRecursivo($dir)
            {
                if (is_dir($dir)) {
                    $objects = scandir($dir);
                    foreach ($objects as $object) {
                        if ($object != "." && $object != "..") {
                            if (is_dir($dir . "/" . $object)) {
                                eliminarRecursivo($dir . "/" . $object);
                            } else {
                                unlink($dir . "/" . $object);
                            }
                        }
                    }
                    rmdir($dir);
                }
            }

            if (is_dir('temp_extract')) {
                eliminarRecursivo('temp_extract');
            }

            echo "<div class='alert alert-success'>";
            echo "<h5>🎉 ¡Instalación completada!</h5>";
            echo "<p>PhpSpreadsheet compatible con PHP 8.1 ha sido instalado.</p>";
            echo "<a href='excel_php81.php' class='btn btn-success'>Probar Excel</a> ";
            echo "<a href='test_excel_servidor.php' class='btn btn-primary'>Ver Estado</a>";
            echo "</div>";
        } else {
            throw new Exception('No se pudo extraer el archivo ZIP');
        }

        echo "</div>";
        exit;
    } catch (Exception $e) {
        echo "<div class='container py-3'>";
        echo "<div class='alert alert-danger'>";
        echo "<h5>❌ Error en la instalación</h5>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<a href='instalador_php81.php' class='btn btn-warning'>Reintentar</a>";
        echo "</div>";
        echo "</div>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador PhpSpreadsheet PHP 8.1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">
                            <i class="bi bi-tools me-2"></i>
                            Instalador PhpSpreadsheet para PHP 8.1
                        </h4>
                    </div>
                    <div class="card-body">

                        <div class="alert alert-info">
                            <h5><i class="bi bi-info-circle me-2"></i>Problema detectado:</h5>
                            <p>Tu servidor tiene <strong>PHP <?php echo PHP_VERSION; ?></strong> pero la versión actual de PhpSpreadsheet requiere PHP 8.2+</p>
                        </div>

                        <div class="alert alert-success">
                            <h5><i class="bi bi-lightbulb me-2"></i>Solución:</h5>
                            <p>Este instalador descargará <strong>PhpSpreadsheet v1.28.0</strong> que es compatible con PHP 8.1+</p>
                        </div>

                        <?php
                        // Verificar requisitos
                        $requisitos_ok = true;
                        $problemas = [];

                        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                            $requisitos_ok = false;
                            $problemas[] = 'PHP 8.1+ requerido (actual: ' . PHP_VERSION . ')';
                        }

                        if (!extension_loaded('zip')) {
                            $requisitos_ok = false;
                            $problemas[] = 'Extensión ZIP no disponible';
                        }

                        if (!function_exists('file_get_contents')) {
                            $requisitos_ok = false;
                            $problemas[] = 'file_get_contents() no disponible';
                        }

                        if (!is_writable('.')) {
                            $requisitos_ok = false;
                            $problemas[] = 'No hay permisos de escritura en el directorio';
                        }
                        ?>

                        <div class="alert alert-secondary">
                            <h6><i class="bi bi-list-check me-2"></i>Verificación de requisitos:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p>✅ <strong>PHP:</strong> <?php echo PHP_VERSION; ?></p>
                                    <p><?php echo extension_loaded('zip') ? '✅' : '❌'; ?> <strong>ZIP:</strong> <?php echo extension_loaded('zip') ? 'Disponible' : 'No disponible'; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><?php echo function_exists('file_get_contents') ? '✅' : '❌'; ?> <strong>Downloads:</strong> <?php echo function_exists('file_get_contents') ? 'Habilitado' : 'Deshabilitado'; ?></p>
                                    <p><?php echo is_writable('.') ? '✅' : '❌'; ?> <strong>Permisos:</strong> <?php echo is_writable('.') ? 'Escritura OK' : 'Sin permisos'; ?></p>
                                </div>
                            </div>
                        </div>

                        <?php if ($requisitos_ok): ?>
                            <div class="alert alert-primary">
                                <h6><i class="bi bi-download me-2"></i>Lo que se instalará:</h6>
                                <ul class="mb-0">
                                    <li>PhpSpreadsheet v1.28.0 (compatible PHP 8.1+)</li>
                                    <li>Autoload simplificado</li>
                                    <li>Estructura vendor/ básica</li>
                                    <li>Compatibilidad total con tu servidor</li>
                                </ul>
                            </div>

                            <form method="POST" onsubmit="mostrarProgreso()">
                                <input type="hidden" name="instalar" value="si">
                                <div class="text-center">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="bi bi-download me-2"></i>
                                        Instalar PhpSpreadsheet
                                    </button>
                                </div>
                            </form>

                            <div id="progreso" style="display:none;" class="mt-3">
                                <div class="alert alert-info">
                                    <div class="d-flex align-items-center">
                                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                        <span>Instalando... Por favor espera</span>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h6><i class="bi bi-exclamation-triangle me-2"></i>Problemas encontrados:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($problemas as $problema): ?>
                                        <li><?php echo htmlspecialchars($problema); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <h6>Opciones manuales:</h6>
                                <div class="d-grid gap-2">
                                    <a href="excel_nativo.php" class="btn btn-warning">
                                        <i class="bi bi-file-earmark-excel me-2"></i>
                                        Excel Nativo (Sin vendor)
                                    </a>
                                    <a href="modulos/Inventario/reporte_inventario_csv.php" class="btn btn-info">
                                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                                        Reporte CSV
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Herramientas:</h6>
                                <div class="d-grid gap-2">
                                    <a href="test_excel_servidor.php" class="btn btn-outline-primary">
                                        <i class="bi bi-server me-2"></i>
                                        Test del Servidor
                                    </a>
                                    <a href="verificar_permisos.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-shield-check me-2"></i>
                                        Verificar Sistema
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function mostrarProgreso() {
            document.getElementById('progreso').style.display = 'block';
        }
    </script>
</body>

</html>
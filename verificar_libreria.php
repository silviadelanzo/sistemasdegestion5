<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificador de Librería PhpSpreadsheet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 700px; margin-top: 50px; }
        .card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .status-ok { color: #198754; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Verificador de Librería PhpSpreadsheet</h4>
            </div>
            <div class="card-body">
                <h5 class="card-title">Análisis del Entorno del Servidor</h5>
                <ul class="list-group list-group-flush">
                    <?php
                    $autoloader_path = __DIR__ . '/vendor/autoload.php';
                    $libreria_ok = false;
                    ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>1. Buscando el archivo de carga automática...</strong><br>
                            <small class="text-muted">Ruta esperada: <code><?= htmlspecialchars($autoloader_path) ?></code></small>
                        </div>
                        <?php if (file_exists($autoloader_path)): ?>
                            <span class="badge bg-success rounded-pill status-ok">ENCONTRADO</span>
                        <?php else: ?>
                            <span class="badge bg-danger rounded-pill status-error">NO ENCONTRADO</span>
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>2. Verificando la clase principal de la librería...</strong><br>
                            <small class="text-muted">Clase: <code>PhpOffice\PhpSpreadsheet\Spreadsheet</code></small>
                        </div>
                        <?php
                        if (file_exists($autoloader_path)) {
                            require_once $autoloader_path;
                            if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                                echo '<span class="badge bg-success rounded-pill status-ok">DISPONIBLE</span>';
                                $libreria_ok = true;
                            } else {
                                echo '<span class="badge bg-danger rounded-pill status-error">NO DISPONIBLE</span>';
                            }
                        } else {
                            echo '<span class="badge bg-secondary rounded-pill">OMITIDO</span>';
                        }
                        ?>
                    </li>
                </ul>

                <div class="alert mt-4 <?= $libreria_ok ? 'alert-success' : 'alert-danger' ?>">
                    <h5 class="alert-heading"><?= $libreria_ok ? '¡Diagnóstico Exitoso!' : '¡Atención!' ?></h5>
                    <?php if ($libreria_ok): ?>
                        <p>La librería <strong>PhpSpreadsheet</strong> está instalada correctamente y lista para ser utilizada.</p>
                        <p class="mb-0">La funcionalidad de "Exportar a Excel" debería funcionar sin problemas en este servidor.</p>
                    <?php else: ?>
                        <p>La librería <strong>PhpSpreadsheet</strong> no se encuentra o no está instalada correctamente en este servidor.</p>
                        <hr>
                        <p class="mb-0"><strong>Solución:</strong> Sube la carpeta <code>vendor</code> completa al directorio raíz de tu proyecto en el servidor remoto para que la exportación a Excel funcione.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// modulos/compras/ocr_remitos/hp_scanner_monitor.php
session_start();
require_once '../../../config/config.php';
require_once 'dual_control_processor.php';
require_once 'dual_control_helpers.php';

// Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../../login.php');
    exit;
}

class HPScannerMonitor
{
    private $db;
    private $dual_controller;
    private $scanner_folder;
    private $processed_folder;

    public function __construct($database)
    {
        $this->db = $database;
        $this->dual_controller = new DualControlProcessor($database);

        // Configurar carpetas
        $this->scanner_folder = '../../../assets/scanner_input/';
        $this->processed_folder = '../../../assets/scanner_processed/';

        // Crear carpetas si no existen
        $this->createFoldersIfNotExist();
    }

    private function createFoldersIfNotExist()
    {
        if (!is_dir($this->scanner_folder)) {
            mkdir($this->scanner_folder, 0755, true);
        }
        if (!is_dir($this->processed_folder)) {
            mkdir($this->processed_folder, 0755, true);
        }
    }

    public function monitorFolder()
    {
        $files = glob($this->scanner_folder . '*.*');
        $processed_files = [];

        foreach ($files as $file) {
            if (is_file($file)) {
                $result = $this->processScannedFile($file);
                $processed_files[] = $result;
            }
        }

        return $processed_files;
    }

    private function processScannedFile($file_path)
    {
        $filename = basename($file_path);
        $file_info = pathinfo($file_path);
        $timestamp = date('Y-m-d H:i:s');

        echo "🖨️ PROCESANDO ARCHIVO DEL SCANNER HP\n";
        echo "====================================\n";
        echo "📄 Archivo: {$filename}\n";
        echo "⏰ Hora: {$timestamp}\n";

        try {
            // Determinar tipo de documento por nombre o contenido
            $tipo_documento = $this->detectDocumentType($filename, $file_path);
            echo "📋 Tipo detectado: {$tipo_documento}\n";

            // Procesar según el tipo
            if (strpos($tipo_documento, 'remito') !== false || strpos($tipo_documento, 'compra') !== false) {
                $result = $this->processCompraDocument($file_path, $filename);
            } else {
                $result = $this->processInventarioDocument($file_path, $filename);
            }

            // Mover archivo a carpeta de procesados
            $new_path = $this->processed_folder . date('Y-m-d_H-i-s') . '_' . $filename;
            rename($file_path, $new_path);

            // Registrar en base de datos
            $this->logProcessedFile($filename, $new_path, $result, 'success');

            echo "✅ Procesamiento completado exitosamente\n";
            echo "📊 Productos detectados: {$result['productos_detectados']}\n";
            echo "🎯 Precisión OCR: {$result['confidence']}%\n";

            return [
                'status' => 'success',
                'filename' => $filename,
                'result' => $result,
                'timestamp' => $timestamp
            ];
        } catch (Exception $e) {
            echo "❌ Error procesando {$filename}: " . $e->getMessage() . "\n";

            // Mover a carpeta de errores
            $error_folder = $this->processed_folder . 'errors/';
            if (!is_dir($error_folder)) mkdir($error_folder, 0755, true);

            $error_path = $error_folder . date('Y-m-d_H-i-s') . '_ERROR_' . $filename;
            rename($file_path, $error_path);

            // Registrar error
            $this->logProcessedFile($filename, $error_path, null, 'error', $e->getMessage());

            return [
                'status' => 'error',
                'filename' => $filename,
                'error' => $e->getMessage(),
                'timestamp' => $timestamp
            ];
        }
    }

    private function detectDocumentType($filename, $file_path)
    {
        // Detectar por nombre de archivo
        $filename_lower = strtolower($filename);

        if (
            strpos($filename_lower, 'remito') !== false ||
            strpos($filename_lower, 'factura') !== false ||
            strpos($filename_lower, 'compra') !== false
        ) {
            return 'remito_compra';
        }

        if (
            strpos($filename_lower, 'inventario') !== false ||
            strpos($filename_lower, 'stock') !== false ||
            strpos($filename_lower, 'conteo') !== false
        ) {
            return 'inventario_inicial';
        }

        // Si no se puede determinar por nombre, usar OCR básico para detectar
        try {
            $ocr_processor = new OCRProcessor();
            $quick_ocr = $ocr_processor->processImage($file_path);
            $text = strtolower($quick_ocr['text']);

            if (
                strpos($text, 'remito') !== false ||
                strpos($text, 'factura') !== false ||
                strpos($text, 'proveedor') !== false
            ) {
                return 'remito_compra';
            }

            return 'inventario_inicial';
        } catch (Exception $e) {
            // Default a inventario si no se puede determinar
            return 'inventario_inicial';
        }
    }

    private function processCompraDocument($file_path, $filename)
    {
        // Determinar proveedor (por defecto usar el primero disponible)
        $proveedor_query = "SELECT id FROM proveedores WHERE activo = 1 ORDER BY id LIMIT 1";
        $proveedor_result = $this->db->query($proveedor_query);
        $proveedor_id = $proveedor_result->fetch_assoc()['id'] ?? 1;

        return $this->dual_controller->processCompraDocument($file_path, $proveedor_id, 'remito');
    }

    private function processInventarioDocument($file_path, $filename)
    {
        return $this->dual_controller->processInventarioDocument($file_path);
    }

    private function logProcessedFile($filename, $path, $result, $status, $error = null)
    {
        $query = "
            INSERT INTO scanner_processed_files 
            (filename, file_path, result_data, status, error_message, processed_at, usuario_id) 
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ";

        $stmt = $this->db->prepare($query);
        $result_json = $result ? json_encode($result) : null;
        $usuario_id = $_SESSION['usuario_id'] ?? 1;

        $stmt->execute([
            $filename,
            $path,
            $result_json,
            $status,
            $error,
            $usuario_id
        ]);
    }

    public function getRecentProcessedFiles($limit = 10)
    {
        $query = "
            SELECT filename, status, processed_at, error_message,
                   JSON_EXTRACT(result_data, '$.productos_detectados') as productos_detectados,
                   JSON_EXTRACT(result_data, '$.confidence') as confidence
            FROM scanner_processed_files 
            ORDER BY processed_at DESC 
            LIMIT ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStatistics()
    {
        $stats_query = "
            SELECT 
                COUNT(*) as total_files,
                COUNT(CASE WHEN status = 'success' THEN 1 END) as successful,
                COUNT(CASE WHEN status = 'error' THEN 1 END) as errors,
                AVG(CAST(JSON_EXTRACT(result_data, '$.confidence') AS DECIMAL(5,2))) as avg_confidence,
                SUM(CAST(JSON_EXTRACT(result_data, '$.productos_detectados') AS UNSIGNED)) as total_productos
            FROM scanner_processed_files 
            WHERE DATE(processed_at) = CURDATE()
        ";

        $result = $this->db->query($stats_query);
        return $result->fetch_assoc();
    }
}

// Manejar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'] ?? '';
        $monitor = new HPScannerMonitor($conexion);

        switch ($action) {
            case 'scan_folder':
                $result = $monitor->monitorFolder();
                break;
            case 'get_recent':
                $result = $monitor->getRecentProcessedFiles();
                break;
            case 'get_stats':
                $result = $monitor->getStatistics();
                break;
            default:
                throw new Exception('Acción no válida');
        }

        echo json_encode(['success' => true, 'data' => $result]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Crear tabla si no existe
$create_table = "
CREATE TABLE IF NOT EXISTS scanner_processed_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    result_data JSON NULL,
    status ENUM('success', 'error', 'processing') DEFAULT 'processing',
    error_message TEXT NULL,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT NULL,
    INDEX idx_status (status),
    INDEX idx_processed_at (processed_at)
)
";
$conexion->query($create_table);

$monitor = new HPScannerMonitor($conexion);
$recent_files = $monitor->getRecentProcessedFiles(5);
$stats = $monitor->getStatistics();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Scanner HP - OCR Automático</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .scanner-status {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .file-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }

        .file-card.success {
            border-left-color: #28a745;
        }

        .file-card.error {
            border-left-color: #dc3545;
        }

        .monitor-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .monitor-active {
            background: #28a745;
            animation: pulse 2s infinite;
        }

        .monitor-inactive {
            background: #6c757d;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .folder-path {
            background: #f8f9fa;
            border: 1px dashed #dee2e6;
            border-radius: 5px;
            padding: 15px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-scanner"></i> Monitor Scanner HP</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="control_center.php">
                    <i class="fas fa-eye"></i> Centro de Control
                </a>
                <span class="navbar-text">
                    <i class="fas fa-user"></i> <?php echo $_SESSION['usuario_nombre']; ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Estado del Scanner -->
        <div class="scanner-status">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4><i class="fas fa-printer"></i> HP Ink Tank Wireless 410 Series</h4>
                    <p class="mb-0">
                        <span class="monitor-indicator monitor-active"></span>
                        <strong>Estado:</strong> Conectado y monitoreando
                        <br>
                        <i class="fas fa-network-wired"></i> <strong>IP:</strong> 192.168.0.100
                        <br>
                        <i class="fas fa-folder"></i> <strong>Carpeta:</strong> assets/scanner_input/
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-light btn-lg" onclick="scanFolder()">
                        <i class="fas fa-search"></i> Escanear Ahora
                    </button>
                </div>
            </div>
        </div>

        <!-- Estadísticas de Hoy -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-primary"><?php echo $stats['total_files'] ?? 0; ?></h3>
                        <small>Archivos Procesados Hoy</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-success"><?php echo $stats['successful'] ?? 0; ?></h3>
                        <small>Exitosos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-danger"><?php echo $stats['errors'] ?? 0; ?></h3>
                        <small>Errores</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-info"><?php echo round($stats['avg_confidence'] ?? 0, 1); ?>%</h3>
                        <small>Precisión Promedio</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Configuración -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6><i class="fas fa-cog"></i> Configuración del Scanner</h6>
                    </div>
                    <div class="card-body">
                        <h6>Carpeta de Entrada:</h6>
                        <div class="folder-path">
                            C:\xampp\htdocs\sistemadgestion5\assets\scanner_input\
                        </div>

                        <hr>

                        <h6>Configuración en HP Smart:</h6>
                        <ol class="list-group list-group-numbered">
                            <li class="list-group-item">Abrir HP Smart en el ordenador</li>
                            <li class="list-group-item">Ir a "Escanear"</li>
                            <li class="list-group-item">Configurar destino: "Carpeta"</li>
                            <li class="list-group-item">Seleccionar la carpeta de arriba</li>
                            <li class="list-group-item">Guardar configuración</li>
                        </ol>

                        <hr>

                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="openHPSmart()">
                                <i class="fas fa-external-link-alt"></i> Abrir HP Smart
                            </button>
                            <button class="btn btn-outline-success" onclick="testFolder()">
                                <i class="fas fa-folder-open"></i> Probar Carpeta
                            </button>
                            <button class="btn btn-outline-warning" onclick="startAutoMonitor()">
                                <i class="fas fa-play"></i> Monitor Automático
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Archivos Recientes -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6><i class="fas fa-history"></i> Archivos Procesados Recientemente</h6>
                    </div>
                    <div class="card-body">
                        <div id="recentFiles">
                            <?php if (empty($recent_files)): ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>No hay archivos procesados aún.</p>
                                    <p>Escanea un documento con la HP para comenzar.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_files as $file): ?>
                                    <div class="card file-card <?php echo $file['status']; ?> mb-2">
                                        <div class="card-body py-2">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <i class="fas fa-file-alt"></i> <?php echo $file['filename']; ?>
                                                </div>
                                                <div class="col-md-2 text-center">
                                                    <?php if ($file['status'] === 'success'): ?>
                                                        <span class="badge bg-success">✅ Éxito</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">❌ Error</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-2 text-center">
                                                    <?php if ($file['productos_detectados']): ?>
                                                        <small><?php echo $file['productos_detectados']; ?> productos</small>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-2 text-end">
                                                    <small><?php echo date('H:i', strtotime($file['processed_at'])); ?></small>
                                                </div>
                                            </div>
                                            <?php if ($file['error_message']): ?>
                                                <div class="mt-2">
                                                    <small class="text-danger">Error: <?php echo $file['error_message']; ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let monitorInterval = null;

        async function scanFolder() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Escaneando...';
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'scan_folder');

                const response = await fetch('hp_scanner_monitor.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    if (result.data.length > 0) {
                        alert(`✅ Procesados ${result.data.length} archivos del scanner`);
                        location.reload();
                    } else {
                        alert('📂 No hay archivos nuevos en la carpeta del scanner');
                    }
                } else {
                    throw new Error(result.error);
                }

            } catch (error) {
                alert('❌ Error: ' + error.message);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        function openHPSmart() {
            // Intentar abrir HP Smart
            window.open('http://192.168.0.100', '_blank');
        }

        function testFolder() {
            alert('📁 Carpeta de entrada:\nC:\\xampp\\htdocs\\sistemadgestion5\\assets\\scanner_input\\\n\nConfigura tu HP para escanear a esta carpeta.');
        }

        function startAutoMonitor() {
            if (monitorInterval) {
                clearInterval(monitorInterval);
                monitorInterval = null;
                event.target.innerHTML = '<i class="fas fa-play"></i> Monitor Automático';
                event.target.className = 'btn btn-outline-warning';
            } else {
                monitorInterval = setInterval(scanFolder, 30000); // Cada 30 segundos
                event.target.innerHTML = '<i class="fas fa-stop"></i> Detener Monitor';
                event.target.className = 'btn btn-warning';
                alert('🤖 Monitor automático iniciado. Revisará la carpeta cada 30 segundos.');
            }
        }

        // Auto-refresh cada 60 segundos
        setInterval(function() {
            if (!monitorInterval) { // Solo si no está en modo auto
                location.reload();
            }
        }, 60000);
    </script>
</body>

</html>
<?php
// modulos/compras/ocr_remitos/scanner.php
require_once '../../config/config.php';
require_once 'ocr_processor.php';
require_once 'ai_parser.php';
require_once 'product_matcher.php';
require_once 'auto_updater.php';

class RemitoScanner
{
    private $db;
    private $upload_dir;
    private $processed_dir;

    public function __construct()
    {
        $this->db = new PDO(DB_DSN, DB_USER, DB_PASS);
        $this->upload_dir = __DIR__ . '/uploads/';
        $this->processed_dir = __DIR__ . '/processed/';

        // Crear directorios si no existen
        if (!is_dir($this->upload_dir)) mkdir($this->upload_dir, 0755, true);
        if (!is_dir($this->processed_dir)) mkdir($this->processed_dir, 0755, true);
    }

    public function processRemito($file_path, $proveedor_id)
    {
        try {
            // PASO 1: OCR - Extraer texto de la imagen
            $ocr = new OCRProcessor();
            $extracted_text = $ocr->extractText($file_path);

            // PASO 2: AI Parser - Interpretar el texto según plantilla del proveedor
            $parser = new AIParser();
            $template = $this->getProveedorTemplate($proveedor_id);
            $parsed_data = $parser->parseRemito($extracted_text, $template);

            // PASO 3: Matching - Comparar productos con BD
            $matcher = new ProductMatcher($this->db);
            $matching_results = $matcher->matchProducts($parsed_data['productos']);

            // PASO 4: Auto-actualización
            $updater = new AutoUpdater($this->db);
            $update_results = $updater->processMatches($matching_results, $proveedor_id);

            // PASO 5: Generar reporte
            $report = $this->generateReport($parsed_data, $matching_results, $update_results);

            // Mover archivo a procesados
            $this->moveToProcessed($file_path, $proveedor_id);

            return [
                'success' => true,
                'report' => $report,
                'productos_actualizados' => $update_results['updated'],
                'productos_nuevos' => $update_results['new_products'],
                'errores' => $update_results['errors']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $file_path
            ];
        }
    }

    private function getProveedorTemplate($proveedor_id)
    {
        $stmt = $this->db->prepare("SELECT template_config FROM proveedores WHERE id = ?");
        $stmt->execute([$proveedor_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['template_config']) {
            return json_decode($result['template_config'], true);
        }

        // Template genérico si no tiene configuración específica
        return $this->getGenericTemplate();
    }

    private function getGenericTemplate()
    {
        return [
            "tipo" => "generic",
            "campos" => [
                "codigo" => ["regex" => "/\b[A-Z0-9]{4,12}\b/"],
                "descripcion" => ["regex" => "/[A-Za-z0-9\s]{10,100}/"],
                "cantidad" => ["regex" => "/\b\d{1,6}\b/"],
                "precio" => ["regex" => "/\$?\d+[.,]\d{2}/"]
            ]
        ];
    }

    private function generateReport($parsed_data, $matching_results, $update_results)
    {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_productos_detectados' => count($parsed_data['productos']),
            'productos_exactos' => count($matching_results['exact_matches']),
            'productos_similares' => count($matching_results['fuzzy_matches']),
            'productos_nuevos' => count($matching_results['new_products']),
            'actualizaciones_exitosas' => count($update_results['updated']),
            'errores_procesamiento' => count($update_results['errors'])
        ];
    }

    private function moveToProcessed($file_path, $proveedor_id)
    {
        $filename = basename($file_path);
        $new_path = $this->processed_dir . date('Y-m-d_H-i-s') . '_proveedor_' . $proveedor_id . '_' . $filename;
        rename($file_path, $new_path);
        return $new_path;
    }

    public function uploadRemito($proveedor_id)
    {
        if (!isset($_FILES['remito']) || $_FILES['remito']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir el archivo');
        }

        $file = $_FILES['remito'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/pdf'];

        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Tipo de archivo no permitido. Use JPG, PNG o PDF');
        }

        $filename = uniqid('remito_') . '_' . $file['name'];
        $upload_path = $this->upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Error al guardar el archivo');
        }

        return $upload_path;
    }
}

// Interfaz web para subir remitos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_remito'])) {
    try {
        $scanner = new RemitoScanner();
        $file_path = $scanner->uploadRemito($_POST['proveedor_id']);
        $result = $scanner->processRemito($file_path, $_POST['proveedor_id']);

        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤖 Scanner Automático de Remitos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .drop-zone {
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .drop-zone:hover {
            background-color: #f8f9fa;
        }

        .drop-zone.dragover {
            border-color: #28a745;
            background-color: #d4edda;
        }

        .result-card {
            margin-top: 20px;
        }

        .processing {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>🤖 Scanner Automático de Remitos</h4>
                        <p class="mb-0">Sube una imagen o PDF del remito para procesamiento automático</p>
                    </div>
                    <div class="card-body">
                        <form id="remitoForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="proveedor_id" class="form-label">📦 Seleccionar Proveedor</label>
                                <select class="form-select" id="proveedor_id" name="proveedor_id" required>
                                    <option value="">-- Seleccione un proveedor --</option>
                                    <!-- Aquí cargarías los proveedores desde la BD -->
                                    <option value="1">ACME Suministros</option>
                                    <option value="2">TechnoPartes S.A.</option>
                                    <option value="3">Distribuidora Central</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">📄 Subir Remito</label>
                                <div class="drop-zone" id="dropZone">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <h5>Arrastra y suelta el archivo aquí</h5>
                                    <p class="text-muted">O haz clic para seleccionar</p>
                                    <input type="file" id="remito" name="remito" accept="image/*,.pdf" hidden required>
                                </div>
                                <small class="text-muted">Formatos soportados: JPG, PNG, PDF (máx. 10MB)</small>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                🚀 Procesar Remito Automáticamente
                            </button>
                        </form>

                        <div class="processing text-center">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2">Procesando remito con IA... Por favor espera</p>
                        </div>

                        <div id="results" class="result-card"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Drag & Drop functionality
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('remito');

        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            updateDropZoneText();
        });

        fileInput.addEventListener('change', updateDropZoneText);

        function updateDropZoneText() {
            if (fileInput.files.length > 0) {
                dropZone.innerHTML = `
                    <i class="fas fa-file-alt fa-3x text-success mb-3"></i>
                    <h5>Archivo seleccionado: ${fileInput.files[0].name}</h5>
                    <p class="text-success">Listo para procesar</p>
                `;
            }
        }

        // Form submission
        document.getElementById('remitoForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData();
            formData.append('remito', fileInput.files[0]);
            formData.append('proveedor_id', document.getElementById('proveedor_id').value);
            formData.append('process_remito', '1');

            document.querySelector('.processing').style.display = 'block';
            document.getElementById('results').innerHTML = '';

            try {
                const response = await fetch('scanner.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                document.querySelector('.processing').style.display = 'none';
                displayResults(result);

            } catch (error) {
                document.querySelector('.processing').style.display = 'none';
                displayError('Error al procesar el remito: ' + error.message);
            }
        });

        function displayResults(result) {
            const resultsDiv = document.getElementById('results');

            if (result.success) {
                resultsDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h5>✅ Remito procesado exitosamente</h5>
                        
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h3 class="text-success">${result.productos_actualizados.length}</h3>
                                        <p>Productos Actualizados</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <h3 class="text-warning">${result.productos_nuevos.length}</h3>
                                        <p>Productos Nuevos</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <h3 class="text-danger">${result.errores.length}</h3>
                                        <p>Errores</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button class="btn btn-info" onclick="showDetails('updated')">Ver Actualizados</button>
                            <button class="btn btn-warning" onclick="showDetails('new')">Ver Nuevos</button>
                            <button class="btn btn-success" onclick="generateReport()">Generar Reporte</button>
                        </div>
                    </div>
                `;
            } else {
                resultsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h5>❌ Error al procesar el remito</h5>
                        <p>${result.error}</p>
                    </div>
                `;
            }
        }

        function displayError(message) {
            document.getElementById('results').innerHTML = `
                <div class="alert alert-danger">
                    <h5>❌ Error</h5>
                    <p>${message}</p>
                </div>
            `;
        }

        function showDetails(type) {
            // Implementar vista detallada de productos
            alert(`Mostrando detalles de productos ${type}`);
        }

        function generateReport() {
            // Implementar generación de reporte
            alert('Generando reporte detallado...');
        }
    </script>
</body>

</html>
<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');
header('Content-Type: text/html; charset=UTF-8');

$pageTitle = "Escanear Remito - " . SISTEMA_NOMBRE;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .main-container {
            margin: 0 auto;
            max-width: 1000px;
            padding: 20px;
        }

        .upload-zone {
            border: 3px dashed #17a2b8;
            border-radius: 15px;
            padding: 50px;
            text-align: center;
            background: #f0fcff;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-zone:hover {
            background: #e0f7fa;
            border-color: #138496;
        }

        .format-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
        }

        .preview-zone {
            min-height: 300px;
            border: 2px dashed #ced4da;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }
    </style>
</head>

<body>
    <?php include "../../config/navbar_code.php"; ?>

    <div class="main-container">
        <a href="nueva_compra.php" class="btn btn-outline-secondary back-button">
            <i class="bi bi-arrow-left"></i> Volver
        </a>

        <div class="text-center mb-5">
            <h1><i class="bi bi-image text-info"></i> Escanear Remito</h1>
            <p class="lead">Sube una imagen o PDF del remito para procesamiento automático</p>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <h3><i class="bi bi-camera"></i> Subir Imagen/PDF</h3>
                <div class="upload-zone" onclick="document.getElementById('remitoFile').click()">
                    <i class="bi bi-file-image display-4 text-info"></i>
                    <h4>Arrastra tu archivo aquí</h4>
                    <p>JPG, PNG, PDF hasta 10MB</p>
                    <input type="file" id="remitoFile" accept=".jpg,.jpeg,.png,.pdf" style="display: none;">
                    <button class="btn btn-info btn-lg mt-3">
                        <i class="bi bi-camera"></i> Seleccionar Archivo
                    </button>
                </div>

                <div class="mt-4">
                    <h5>Vista Previa:</h5>
                    <div class="preview-zone" id="previewZone">
                        <div class="text-muted">
                            <i class="bi bi-eye-slash display-4"></i>
                            <p>No hay archivo seleccionado</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="format-card">
                    <h5><i class="bi bi-check-circle text-success"></i> Formatos Soportados</h5>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-file-image text-primary"></i> <strong>JPG/JPEG</strong> - Ideal para fotos</li>
                        <li><i class="bi bi-file-image text-primary"></i> <strong>PNG</strong> - Buena calidad</li>
                        <li><i class="bi bi-file-pdf text-danger"></i> <strong>PDF</strong> - Documentos escaneados</li>
                    </ul>
                </div>

                <div class="format-card">
                    <h5><i class="bi bi-lightbulb text-warning"></i> Consejos para mejores resultados</h5>
                    <ul class="small">
                        <li>Asegúrate de que el texto sea legible</li>
                        <li>Evita sombras y reflejos</li>
                        <li>Mantén el documento plano</li>
                        <li>Usa buena iluminación</li>
                        <li>Enfoca bien la imagen</li>
                    </ul>
                </div>

                <div class="format-card">
                    <h5><i class="bi bi-gear text-secondary"></i> Proceso OCR</h5>
                    <ol class="small">
                        <li>Análisis de la imagen</li>
                        <li>Reconocimiento de texto</li>
                        <li>Extracción de productos</li>
                        <li>Detección de precios</li>
                        <li>Generación de vista previa</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <div class="alert alert-info">
                <h5><i class="bi bi-robot"></i> Procesamiento Automático</h5>
                <p>Una vez subida la imagen/PDF:</p>
                <ol>
                    <li>El sistema procesará el documento con OCR</li>
                    <li>Extraerá automáticamente productos, cantidades y precios</li>
                    <li>Te mostrará una vista previa para revisar</li>
                    <li>Podrás corregir cualquier error detectado</li>
                    <li>La compra se creará como "Pendiente de Validación"</li>
                </ol>
            </div>
        </div>

        <!-- Botón de procesamiento (aparece cuando hay archivo) -->
        <div class="text-center mt-4" id="processButton" style="display: none;">
            <button class="btn btn-info btn-lg">
                <i class="bi bi-cpu"></i> Procesar con OCR
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('remitoFile').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const fileName = file.name;
                const fileSize = (file.size / 1024 / 1024).toFixed(2);

                // Mostrar vista previa
                const previewZone = document.getElementById('previewZone');
                const processButton = document.getElementById('processButton');

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewZone.innerHTML = `
                    <div class="w-100">
                        <img src="${e.target.result}" class="img-fluid" style="max-height: 280px;">
                        <p class="text-center mt-2 small text-muted">${fileName} (${fileSize} MB)</p>
                    </div>
                `;
                    };
                    reader.readAsDataURL(file);
                } else if (file.type === 'application/pdf') {
                    previewZone.innerHTML = `
                <div class="text-center">
                    <i class="bi bi-file-pdf display-4 text-danger"></i>
                    <p class="mt-2"><strong>${fileName}</strong></p>
                    <p class="text-muted small">${fileSize} MB</p>
                    <p class="text-info small">PDF listo para procesamiento OCR</p>
                </div>
            `;
                }

                processButton.style.display = 'block';
            }
        });

        // Drag and drop functionality
        const uploadZone = document.querySelector('.upload-zone');

        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.background = '#e0f7fa';
        });

        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.background = '#f0fcff';
        });

        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.background = '#f0fcff';

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('remitoFile').files = files;
                // Trigger change event
                const event = new Event('change');
                document.getElementById('remitoFile').dispatchEvent(event);
            }
        });
    </script>
</body>

</html>
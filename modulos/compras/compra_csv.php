<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');
header('Content-Type: text/html; charset=UTF-8');

$pageTitle = "Carga por CSV - " . SISTEMA_NOMBRE;
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
            border: 3px dashed #007bff;
            border-radius: 15px;
            padding: 50px;
            text-align: center;
            background: #f8f9ff;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-zone:hover {
            background: #e3f2fd;
            border-color: #0056b3;
        }

        .csv-example {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
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
            <h1><i class="bi bi-file-earmark-spreadsheet text-success"></i> Carga por CSV</h1>
            <p class="lead">Sube un archivo CSV con el formato correcto para crear compras masivamente</p>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <h3><i class="bi bi-upload"></i> Subir Archivo CSV</h3>
                <div class="upload-zone" onclick="document.getElementById('csvFile').click()">
                    <i class="bi bi-cloud-upload display-4 text-primary"></i>
                    <h4>Arrastra tu archivo CSV aquí</h4>
                    <p>o haz clic para seleccionarlo</p>
                    <input type="file" id="csvFile" accept=".csv" style="display: none;">
                    <button class="btn btn-primary btn-lg mt-3">
                        <i class="bi bi-folder2-open"></i> Seleccionar Archivo
                    </button>
                </div>
            </div>

            <div class="col-lg-6">
                <h3><i class="bi bi-download"></i> Plantilla CSV</h3>
                <div class="csv-example">
                    <h5>Formato requerido:</h5>
                    <p>El archivo CSV debe tener las siguientes columnas:</p>
                    <table class="table table-sm table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>proveedor_id</th>
                                <th>producto_codigo</th>
                                <th>producto_nombre</th>
                                <th>cantidad</th>
                                <th>precio_unitario</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>PROD001</td>
                                <td>Producto Ejemplo</td>
                                <td>10</td>
                                <td>50.00</td>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>PROD002</td>
                                <td>Otro Producto</td>
                                <td>5</td>
                                <td>75.50</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle"></i> Importante:</h6>
                        <ul class="mb-0 small">
                            <li>Use punto (.) como separador decimal</li>
                            <li>No incluya encabezados en la primera fila</li>
                            <li>El proveedor_id debe existir en el sistema</li>
                            <li>Los códigos de producto pueden ser nuevos</li>
                        </ul>
                    </div>

                    <button class="btn btn-outline-success btn-sm w-100">
                        <i class="bi bi-download"></i> Descargar Plantilla de Ejemplo
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <div class="alert alert-warning">
                <h5><i class="bi bi-exclamation-triangle"></i> Proceso de Validación</h5>
                <p>Una vez subido el archivo CSV:</p>
                <ol>
                    <li>El sistema procesará y validará los datos</li>
                    <li>Se mostrará una vista previa de la compra a crear</li>
                    <li>Podrás revisar y corregir cualquier error</li>
                    <li>La compra quedará como "Pendiente de Validación"</li>
                    <li>Podrás confirmarla desde la lista de compras</li>
                </ol>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('csvFile').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const fileName = e.target.files[0].name;
                alert('Archivo seleccionado: ' + fileName + '\n\nEn la implementación completa, aquí se procesaría el archivo.');
            }
        });

        // Drag and drop functionality
        const uploadZone = document.querySelector('.upload-zone');

        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.background = '#e3f2fd';
        });

        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.background = '#f8f9ff';
        });

        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.background = '#f8f9ff';

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('csvFile').files = files;
                alert('Archivo cargado: ' + files[0].name);
            }
        });
    </script>
</body>

</html>
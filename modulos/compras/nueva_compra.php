<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');
header('Content-Type: text/html; charset=UTF-8');

$pageTitle = "Nueva Compra - Seleccionar Método - " . SISTEMA_NOMBRE;
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

        .method-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
            background: white;
        }

        .method-card:hover {
            border-color: #007bff;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 123, 255, 0.15);
        }

        .method-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #6c757d;
        }

        .method-card:hover .method-icon {
            color: #007bff;
        }

        .method-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: #495057;
        }

        .method-description {
            color: #6c757d;
            margin-bottom: 20px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .method-features {
            list-style: none;
            padding: 0;
            margin-bottom: 25px;
        }

        .method-features li {
            padding: 5px 0;
            color: #28a745;
            font-size: 0.9rem;
        }

        .method-features li::before {
            content: "✓ ";
            font-weight: bold;
            margin-right: 8px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
            padding: 30px 0;
            background: white;
            border-radius: 15px;
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
        <a href="compras.php" class="btn btn-outline-secondary back-button">
            <i class="bi bi-arrow-left"></i> Volver a Compras
        </a>

        <div class="page-header">
            <h1><i class="bi bi-cart-plus text-primary"></i> Nueva Compra</h1>
            <p class="lead">Selecciona el método de carga que prefieras</p>
            <p class="text-muted">Todos los métodos generan una compra pendiente de validación</p>
        </div>

        <div class="row g-4">
            <!-- Método 1: Ingreso Manual -->
            <div class="col-lg-4">
                <div class="method-card" onclick="window.location.href='compra_form.php'">
                    <div class="method-icon">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <h3 class="method-title">Ingreso Manual</h3>
                    <p class="method-description">
                        Carga manual de la compra con formulario paso a paso
                    </p>
                    <ul class="method-features">
                        <li>Código automático COMP-000000X</li>
                        <li>Selección de proveedor</li>
                        <li>Carga múltiple de productos</li>
                        <li>Reutilizar datos del proveedor</li>
                        <li>Validación en tiempo real</li>
                    </ul>
                    <button class="btn btn-primary btn-lg">
                        <i class="bi bi-pencil"></i> Empezar Carga Manual
                    </button>
                </div>
            </div>

            <!-- Método 2: Carga por CSV -->
            <div class="col-lg-4">
                <div class="method-card" onclick="window.location.href='compra_csv.php'">
                    <div class="method-icon">
                        <i class="bi bi-file-earmark-spreadsheet"></i>
                    </div>
                    <h3 class="method-title">Carga por CSV</h3>
                    <p class="method-description">
                        Subir archivo CSV con formato predefinido
                    </p>
                    <ul class="method-features">
                        <li>Carga masiva de productos</li>
                        <li>Plantilla CSV descargable</li>
                        <li>Validación automática</li>
                        <li>Detección de errores</li>
                        <li>Vista previa antes de guardar</li>
                    </ul>
                    <button class="btn btn-success btn-lg">
                        <i class="bi bi-upload"></i> Subir Archivo CSV
                    </button>
                </div>
            </div>

            <!-- Método 3: OCR de Imagen/PDF (redirige a página dedicada) -->
            <div class="col-lg-4">
                <a href="ocr_popup_full.php" style="text-decoration:none;">
                <div class="method-card">
                    <div class="method-icon">
                        <i class="bi bi-image"></i>
                    </div>
                    <h3 class="method-title">Escanear Remito</h3>
                    <p class="method-description">
                        Procesa remitos en PDF/JPG con reconocimiento automático de productos y cantidades.
                    </p>
                    <ul class="method-features">
                        <li>Sube JPG, PNG o PDF</li>
                        <li>Procesamiento OCR automático</li>
                        <li>Extracción de productos</li>
                        <li>Reconocimiento de precios</li>
                        <li>Revisión antes de guardar</li>
                    </ul>
                    <button class="btn btn-info btn-lg">
                        <i class="bi bi-camera"></i> Subir Remito
                    </button>
                </div>
                </a>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12">
                <div class="alert alert-info">
                    <h5><i class="bi bi-info-circle"></i> Importante:</h5>
                    <ul class="mb-0">
                        <li><strong>Todas las compras</strong> quedan inicialmente como "Pendiente de Validación"</li>
                        <li><strong>Puedes revisar y editar</strong> cualquier compra antes de confirmarla</li>
                        <li><strong>Los códigos de compra</strong> se generan automáticamente (COMP-0000001, COMP-0000002, etc.)</li>
                        <li><strong>Los remitos</strong> se asocian automáticamente con un número único</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Agregar efectos de hover
            document.querySelectorAll('.method-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.background = 'linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.background = 'white';
                });
            });
        </script>
</body>

</html>
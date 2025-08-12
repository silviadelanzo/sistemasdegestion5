<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OCR Remito - Página Completa</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc 0%, #c2e9fb 100%);
            min-height: 100vh;
            margin: 0;
        }
        .ocr-iframe-box {
            max-width: 820px;
            margin: 48px auto 0 auto;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            overflow: hidden;
            background: #fff;
        }
        .ocr-iframe {
            width: 100%;
            min-height: 720px;
            border: none;
            background: transparent;
            display: block;
        }
        @media (max-width: 900px) {
            .ocr-iframe-box { max-width: 99vw; }
        }
        @media (max-width: 600px) {
            .ocr-iframe { min-height: 95vh; }
        }
    </style>
</head>
<body>
    <div class="ocr-iframe-box" style="max-width: 980px; min-width: 400px; height: 650px;">
        <iframe class="ocr-iframe" src="ocr_remitos/ocr_lectura_mejorada.php" style="min-height: 640px; height: 100%;"></iframe>
    </div>
</body>
</html>

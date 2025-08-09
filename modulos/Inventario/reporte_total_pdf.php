<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

// Configurar encoding UTF-8
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Obtener totales generales
    $sql_totales = "SELECT 
                        COUNT(*) as total_productos,
                        COALESCE(SUM(stock), 0) as total_stock,
                        COALESCE(SUM(stock * precio_venta), 0) as valor_total,
                        COALESCE(AVG(precio_venta), 0) as precio_promedio,
                        COUNT(DISTINCT categoria_id) as total_categorias,
                        COUNT(DISTINCT lugar_id) as total_lugares
                    FROM productos 
                    WHERE activo = 1";
    $totales = $pdo->query($sql_totales)->fetch(PDO::FETCH_ASSOC);

    // Obtener todos los productos con detalles completos
    $sql_productos = "SELECT 
                        p.codigo, p.nombre as producto, p.stock, 
                        p.precio_venta, p.precio_compra,
                        c.nombre as categoria,
                        l.nombre as lugar,
                        p.fecha_creacion,
                        (p.stock * p.precio_venta) as valor_total,
                        CASE 
                            WHEN p.stock = 0 THEN 'Sin Stock'
                            WHEN p.stock <= 5 THEN 'Bajo'
                            WHEN p.stock >= 100 THEN 'Alto'
                            ELSE 'Normal'
                        END as estado_stock
                      FROM productos p
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      LEFT JOIN lugares l ON p.lugar_id = l.id
                      WHERE p.activo = 1
                      ORDER BY p.nombre";
    $productos = $pdo->query($sql_productos)->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas adicionales
    $sql_stats = "SELECT 
                    COUNT(CASE WHEN stock <= 5 THEN 1 END) as productos_bajo_stock,
                    COUNT(CASE WHEN stock = 0 THEN 1 END) as productos_sin_stock,
                    COUNT(CASE WHEN stock >= 100 THEN 1 END) as productos_alto_stock,
                    MIN(precio_venta) as precio_minimo,
                    MAX(precio_venta) as precio_maximo
                  FROM productos 
                  WHERE activo = 1";
    $estadisticas = $pdo->query($sql_stats)->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

$fecha_reporte = date('d/m/Y H:i:s');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte General de Inventario - PDF</title>
    <style>
        /* Estilos para pantalla */
        @media screen {
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                margin: 20px;
                background-color: #f8f9fa;
                line-height: 1.4;
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            .print-buttons {
                text-align: center;
                margin-bottom: 30px;
                background: #e3f2fd;
                padding: 20px;
                border-radius: 8px;
            }

            .btn {
                padding: 12px 24px;
                margin: 0 10px;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                font-weight: 600;
            }

            .btn-primary {
                background: #007bff;
                color: white;
            }

            .btn-success {
                background: #28a745;
                color: white;
            }

            .btn-secondary {
                background: #6c757d;
                color: white;
            }

            .btn:hover {
                opacity: 0.9;
                transform: translateY(-1px);
                transition: all 0.2s;
            }
        }

        /* Estilos para impresión/PDF */
        @media print {
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 15mm;
                font-size: 10pt;
                line-height: 1.3;
                color: #000;
            }

            .print-buttons {
                display: none !important;
            }

            .container {
                box-shadow: none;
                border-radius: 0;
                padding: 0;
                margin: 0;
            }

            h1 {
                font-size: 16pt;
                margin-bottom: 5mm;
                text-align: center;
                color: #000;
            }

            h2 {
                font-size: 12pt;
                margin: 6mm 0 3mm 0;
                color: #000;
                border-bottom: 1pt solid #000;
                padding-bottom: 2mm;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 6mm;
                font-size: 8pt;
            }

            th,
            td {
                border: 0.5pt solid #000;
                padding: 2mm;
                text-align: left;
            }

            th {
                background-color: #f0f0f0 !important;
                font-weight: bold;
                color: #000;
            }

            .text-right {
                text-align: right;
            }

            .text-center {
                text-align: center;
            }

            .totals-box {
                border: 1pt solid #000;
                padding: 4mm;
                margin: 4mm 0;
                background-color: #f8f8f8 !important;
            }

            .stats-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 5mm;
                margin: 5mm 0;
            }

            /* Forzar colores en impresión */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        /* Estilos generales */
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        h2 {
            color: #34495e;
            margin: 20px 0 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            font-size: 12px;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-box {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            padding: 15px;
            margin: 15px 0;
            border-radius: 6px;
        }

        .totals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .total-item {
            text-align: center;
        }

        .total-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 12px;
        }

        .total-value {
            font-size: 18px;
            font-weight: 700;
            color: #6f42c1;
        }

        .report-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .report-date {
            color: #6c757d;
            font-size: 14px;
        }

        .stock-bajo {
            color: #dc3545;
            font-weight: bold;
        }

        .stock-alto {
            color: #28a745;
            font-weight: bold;
        }

        .stock-normal {
            color: #17a2b8;
        }

        .stock-sin {
            color: #6c757d;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="print-buttons">
            <h3>📊 Reporte General de Inventario - Exportación PDF</h3>
            <p>Este reporte incluye todos los productos con estadísticas completas</p>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Imprimir / Guardar como PDF
            </button>
            <a href="reportes.php" class="btn btn-secondary">
                ← Volver a Reportes
            </a>
            <a href="reporte_completo_excel.php" class="btn btn-success">
                📊 Exportar Excel
            </a>
        </div>

        <div class="report-header">
            <h1>📊 REPORTE GENERAL DE INVENTARIO</h1>
            <p class="report-date">Generado el: <?php echo $fecha_reporte; ?></p>
            <p class="report-date">Sistema: <?php echo htmlspecialchars(SISTEMA_NOMBRE); ?></p>
        </div>

        <?php if (isset($error)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>

            <div class="totals-box">
                <h2>📈 Resumen Ejecutivo</h2>
                <div class="totals-grid">
                    <div class="total-item">
                        <div class="total-label">Total Productos</div>
                        <div class="total-value"><?php echo number_format($totales['total_productos']); ?></div>
                    </div>
                    <div class="total-item">
                        <div class="total-label">Stock Total</div>
                        <div class="total-value"><?php echo number_format($totales['total_stock']); ?></div>
                    </div>
                    <div class="total-item">
                        <div class="total-label">Valor Total</div>
                        <div class="total-value"><?php echo formatCurrency($totales['valor_total']); ?></div>
                    </div>
                    <div class="total-item">
                        <div class="total-label">Precio Promedio</div>
                        <div class="total-value"><?php echo formatCurrency($totales['precio_promedio']); ?></div>
                    </div>
                    <div class="total-item">
                        <div class="total-label">Categorías</div>
                        <div class="total-value"><?php echo number_format($totales['total_categorias']); ?></div>
                    </div>
                    <div class="total-item">
                        <div class="total-label">Ubicaciones</div>
                        <div class="total-value"><?php echo number_format($totales['total_lugares']); ?></div>
                    </div>
                </div>
            </div>

            <?php if (isset($estadisticas)): ?>
                <div class="totals-box">
                    <h2>⚠️ Alertas de Stock</h2>
                    <div class="stats-grid">
                        <div class="total-item">
                            <div class="total-label">Stock Bajo</div>
                            <div class="total-value stock-bajo"><?php echo number_format($estadisticas['productos_bajo_stock']); ?></div>
                        </div>
                        <div class="total-item">
                            <div class="total-label">Sin Stock</div>
                            <div class="total-value stock-bajo"><?php echo number_format($estadisticas['productos_sin_stock']); ?></div>
                        </div>
                        <div class="total-item">
                            <div class="total-label">Stock Alto</div>
                            <div class="total-value stock-alto"><?php echo number_format($estadisticas['productos_alto_stock']); ?></div>
                        </div>
                        <div class="total-item">
                            <div class="total-label">Rango Precios</div>
                            <div class="total-value" style="font-size: 14px;">
                                <?php echo formatCurrency($estadisticas['precio_minimo']); ?> -
                                <?php echo formatCurrency($estadisticas['precio_maximo']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <h2>📋 Inventario Completo</h2>

            <?php if (!empty($productos)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Ubicación</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Estado</th>
                            <th class="text-right">P. Compra</th>
                            <th class="text-right">P. Venta</th>
                            <th class="text-right">Valor Total</th>
                            <th class="text-center">Fecha Alta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($producto['codigo'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($producto['producto']); ?></td>
                                <td><?php echo htmlspecialchars($producto['categoria'] ?: 'Sin categoría'); ?></td>
                                <td><?php echo htmlspecialchars($producto['lugar'] ?: 'Sin ubicación'); ?></td>
                                <td class="text-center"><?php echo number_format($producto['stock']); ?></td>
                                <td class="text-center">
                                    <span class="stock-<?php echo strtolower($producto['estado_stock']); ?>">
                                        <?php echo $producto['estado_stock']; ?>
                                    </span>
                                </td>
                                <td class="text-right"><?php echo formatCurrency($producto['precio_compra']); ?></td>
                                <td class="text-right"><?php echo formatCurrency($producto['precio_venta']); ?></td>
                                <td class="text-right"><?php echo formatCurrency($producto['valor_total']); ?></td>
                                <td class="text-center"><?php echo date('d/m/Y', strtotime($producto['fecha_creacion'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #6c757d; font-style: italic; padding: 40px;">
                    No hay productos registrados en el inventario.
                </p>
            <?php endif; ?>

        <?php endif; ?>

        <div style="margin-top: 30px; text-align: center; color: #6c757d; font-size: 12px;">
            <p>Reporte generado automáticamente por <?php echo htmlspecialchars(SISTEMA_NOMBRE); ?></p>
            <p>Fecha: <?php echo $fecha_reporte; ?> | Total de registros: <?php echo count($productos); ?></p>
        </div>
    </div>

    <script>
        // Función para optimizar la impresión
        function optimizarImpresion() {
            // Remover elementos no necesarios para impresión
            const printButtons = document.querySelector('.print-buttons');
            if (printButtons) {
                printButtons.style.display = 'none';
            }

            // Ajustar estilos para PDF
            document.body.style.margin = '0';
            document.body.style.padding = '15mm';
        }

        // Detectar cuando se va a imprimir
        window.addEventListener('beforeprint', optimizarImpresion);

        // Restaurar después de imprimir
        window.addEventListener('afterprint', function() {
            location.reload(); // Recargar para restaurar estilos
        });
    </script>
</body>

</html>
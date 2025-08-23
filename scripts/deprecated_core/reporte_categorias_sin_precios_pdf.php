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

    // Obtener datos del inventario por categorías (sin precios)
    $sql_totales = "SELECT 
                        COUNT(DISTINCT c.id) as total_categorias, 
                        COUNT(p.id) as total_productos, 
                        COALESCE(SUM(p.stock), 0) as total_stock
                    FROM categorias c 
                    LEFT JOIN productos p ON c.id = p.categoria_id AND p.activo = 1";
    $totales = $pdo->query($sql_totales)->fetch(PDO::FETCH_ASSOC);

    // Obtener productos por categoría (sin precios)
    $sql_productos = "SELECT 
                        c.nombre as categoria,
                        p.codigo, p.nombre as producto, p.stock,
                        l.nombre as lugar,
                        p.fecha_creacion
                      FROM categorias c 
                      LEFT JOIN productos p ON c.id = p.categoria_id AND p.activo = 1
                      LEFT JOIN lugares l ON p.lugar_id = l.id
                      WHERE p.id IS NOT NULL
                      ORDER BY c.nombre, p.nombre";
    $productos = $pdo->query($sql_productos)->fetchAll(PDO::FETCH_ASSOC);

    // Productos sin categoría (sin precios)
    $sql_sin_categoria = "SELECT 'Sin Categoría' as categoria,
                                 p.codigo, p.nombre as producto, p.stock,
                                 l.nombre as lugar,
                                 p.fecha_creacion
                          FROM productos p
                          LEFT JOIN lugares l ON p.lugar_id = l.id
                          WHERE p.categoria_id IS NULL AND p.activo = 1
                          ORDER BY p.nombre";
    $sin_categoria = $pdo->query($sql_sin_categoria)->fetchAll(PDO::FETCH_ASSOC);

    // Combinar todos los productos
    $todos_productos = array_merge($productos, $sin_categoria);
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
    <title>Reporte por Categorías Sin Precios - PDF</title>
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
                font-size: 11pt;
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
                font-size: 18pt;
                margin-bottom: 5mm;
                text-align: center;
                color: #000;
            }

            h2 {
                font-size: 14pt;
                margin: 8mm 0 4mm 0;
                color: #000;
                border-bottom: 1pt solid #000;
                padding-bottom: 2mm;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 8mm;
                font-size: 9pt;
            }

            th,
            td {
                border: 0.5pt solid #000;
                padding: 3mm;
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
                padding: 5mm;
                margin: 5mm 0;
                background-color: #f8f8f8 !important;
            }

            /* Forzar colores en impresión */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
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
            padding: 8px;
            text-align: left;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .total-item {
            text-align: center;
        }

        .total-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 14px;
        }

        .total-value {
            font-size: 20px;
            font-weight: 700;
            color: #007bff;
        }

        .report-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .report-date {
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="print-buttons">
            <h3>📦 Reporte por Categorías Sin Precios - Exportación PDF</h3>
            <p>Este reporte muestra la distribución de productos por categoría sin información de precios</p>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Imprimir / Guardar como PDF
            </button>
            <a href="reportes.php" class="btn btn-secondary">
                ← Volver a Reportes
            </a>
        </div>

        <div class="report-header">
            <h1>📦 REPORTE POR CATEGORÍAS - SIN PRECIOS</h1>
            <p class="report-date">Generado el: <?php echo $fecha_reporte; ?></p>
            <p class="report-date">Sistema: <?php echo htmlspecialchars(SISTEMA_NOMBRE); ?></p>
        </div>

        <?php if (isset($error)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>

            <div class="totals-box">
                <h2>📊 Resumen General</h2>
                <div class="totals-grid">
                    <div class="total-item">
                        <div class="total-label">Categorías</div>
                        <div class="total-value"><?php echo number_format($totales['total_categorias']); ?></div>
                    </div>
                    <div class="total-item">
                        <div class="total-label">Total Productos</div>
                        <div class="total-value"><?php echo number_format($totales['total_productos']); ?></div>
                    </div>
                    <div class="total-item">
                        <div class="total-label">Stock Total</div>
                        <div class="total-value"><?php echo number_format($totales['total_stock']); ?></div>
                    </div>
                </div>
            </div>

            <h2>📋 Detalle de Productos por Categoría</h2>

            <?php if (!empty($todos_productos)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Ubicación</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Fecha Alta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $categoria_actual = '';
                        $total_categoria_stock = 0;
                        $productos_categoria = 0;

                        foreach ($todos_productos as $index => $producto):
                            // Si cambia la categoría, mostrar totales de la anterior
                            if ($categoria_actual != '' && $categoria_actual != $producto['categoria']) {
                                echo "<tr style='background-color: #e9ecef; font-weight: bold;'>";
                                echo "<td colspan='4'>TOTAL " . htmlspecialchars($categoria_actual) . " ({$productos_categoria} productos)</td>";
                                echo "<td class='text-center'>" . number_format($total_categoria_stock) . "</td>";
                                echo "<td></td>";
                                echo "</tr>";

                                $total_categoria_stock = 0;
                                $productos_categoria = 0;
                            }

                            $categoria_actual = $producto['categoria'];
                            $total_categoria_stock += $producto['stock'];
                            $productos_categoria++;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
                                <td><?php echo htmlspecialchars($producto['codigo'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($producto['producto']); ?></td>
                                <td><?php echo htmlspecialchars($producto['lugar'] ?: 'Sin ubicación'); ?></td>
                                <td class="text-center"><?php echo number_format($producto['stock']); ?></td>
                                <td class="text-center"><?php echo date('d/m/Y', strtotime($producto['fecha_creacion'])); ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if ($categoria_actual != ''): ?>
                            <tr style='background-color: #e9ecef; font-weight: bold;'>
                                <td colspan='4'>TOTAL <?php echo htmlspecialchars($categoria_actual); ?> (<?php echo $productos_categoria; ?> productos)</td>
                                <td class='text-center'><?php echo number_format($total_categoria_stock); ?></td>
                                <td></td>
                            </tr>
                        <?php endif; ?>
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
            <p>Fecha: <?php echo $fecha_reporte; ?> | Total de registros: <?php echo count($todos_productos); ?></p>
            <p><strong>Nota:</strong> Este reporte no incluye información de precios por políticas de confidencialidad</p>
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
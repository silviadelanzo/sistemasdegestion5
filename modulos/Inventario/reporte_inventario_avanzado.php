<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

// Configurar charset UTF-8
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="reporte_inventario_completo_' . date('Y-m-d_H-i-s') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // 1. ESTADÍSTICAS GENERALES
    $sql_stats = "
        SELECT 
            COUNT(*) as total_productos,
            SUM(stock) as stock_total,
            SUM(stock * precio_venta) as valor_total_venta,
            SUM(stock * precio_compra) as valor_total_compra,
            AVG(precio_venta) as precio_promedio_venta,
            AVG(precio_compra) as precio_promedio_compra,
            COUNT(CASE WHEN stock <= stock_minimo THEN 1 END) as productos_bajo_stock,
            COUNT(CASE WHEN stock = 0 THEN 1 END) as productos_sin_stock
        FROM productos 
        WHERE activo = 1
    ";
    $stats_generales = $pdo->query($sql_stats)->fetch(PDO::FETCH_ASSOC);

    // 2. ANÁLISIS POR CATEGORÍAS
    $sql_categorias = "
        SELECT 
            COALESCE(c.nombre, 'Sin Categoría') as categoria,
            COUNT(p.id) as cantidad_productos,
            SUM(p.stock) as stock_total,
            SUM(p.stock * p.precio_venta) as valor_total_venta,
            SUM(p.stock * p.precio_compra) as valor_total_compra,
            AVG(p.precio_venta) as precio_promedio,
            COUNT(CASE WHEN p.stock <= p.stock_minimo THEN 1 END) as productos_bajo_stock
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE p.activo = 1
        GROUP BY p.categoria_id, c.nombre
        ORDER BY valor_total_venta DESC
    ";
    $analisis_categorias = $pdo->query($sql_categorias)->fetchAll(PDO::FETCH_ASSOC);

    // 3. ANÁLISIS POR LUGARES
    $sql_lugares = "
        SELECT 
            COALESCE(l.nombre, 'Sin Ubicación') as lugar,
            COUNT(p.id) as cantidad_productos,
            SUM(p.stock) as stock_total,
            SUM(p.stock * p.precio_venta) as valor_total_venta,
            SUM(p.stock * p.precio_compra) as valor_total_compra,
            AVG(p.precio_venta) as precio_promedio,
            COUNT(CASE WHEN p.stock <= p.stock_minimo THEN 1 END) as productos_bajo_stock
        FROM productos p
        LEFT JOIN lugares l ON p.lugar_id = l.id
        WHERE p.activo = 1
        GROUP BY p.lugar_id, l.nombre
        ORDER BY valor_total_venta DESC
    ";
    $analisis_lugares = $pdo->query($sql_lugares)->fetchAll(PDO::FETCH_ASSOC);

    // 4. LISTADO COMPLETO DE PRODUCTOS
    $sql_productos = "
        SELECT 
            p.codigo,
            p.nombre,
            p.descripcion,
            COALESCE(c.nombre, 'Sin Categoría') as categoria,
            COALESCE(l.nombre, 'Sin Ubicación') as lugar,
            p.stock,
            p.stock_minimo,
            p.precio_venta,
            p.precio_compra,
            (p.stock * p.precio_venta) as valor_stock_venta,
            (p.stock * p.precio_compra) as valor_stock_compra,
            CASE 
                WHEN p.precio_compra > 0 THEN ((p.precio_venta - p.precio_compra) / p.precio_compra) * 100
                ELSE 0 
            END as margen_ganancia,
            p.fecha_creacion,
            CASE 
                WHEN p.stock = 0 THEN 'Sin Stock'
                WHEN p.stock <= p.stock_minimo THEN 'Stock Bajo'
                WHEN p.stock <= (p.stock_minimo * 1.5) THEN 'Stock Medio'
                ELSE 'Stock Normal'
            END as estado_stock
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        LEFT JOIN lugares l ON p.lugar_id = l.id
        WHERE p.activo = 1
        ORDER BY c.nombre, l.nombre, p.nombre
    ";
    $productos = $pdo->query($sql_productos)->fetchAll(PDO::FETCH_ASSOC);

    // 5. TOP 10 PRODUCTOS MÁS VALIOSOS
    $sql_top_productos = "
        SELECT 
            p.codigo,
            p.nombre,
            COALESCE(c.nombre, 'Sin Categoría') as categoria,
            p.stock,
            p.precio_venta,
            (p.stock * p.precio_venta) as valor_total
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE p.activo = 1 AND p.stock > 0
        ORDER BY valor_total DESC
        LIMIT 10
    ";
    $top_productos = $pdo->query($sql_top_productos)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "Error al generar reporte: " . $e->getMessage();
    exit;
}

// Función para formatear moneda
function formatCurrencyExcel($amount)
{
    return number_format($amount, 2, '.', ',');
}

// Función para formatear porcentaje
function formatPercentage($value)
{
    return number_format($value, 1) . '%';
}

// Generar contenido Excel
echo "\xEF\xBB\xBF"; // BOM para UTF-8
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="ProgId" content="Excel.Sheet">
    <meta name="Generator" content="Microsoft Excel 15">
    <style>
        .header-principal {
            background-color: #1f4e79;
            color: white;
            font-weight: bold;
            text-align: center;
            font-size: 16pt;
            padding: 12px;
        }

        .header-seccion {
            background-color: #4472c4;
            color: white;
            font-weight: bold;
            text-align: center;
            font-size: 14pt;
            padding: 8px;
        }

        .subheader {
            background-color: #d9e2f3;
            font-weight: bold;
            text-align: center;
            font-size: 11pt;
            padding: 6px;
        }

        .data {
            text-align: left;
            font-size: 10pt;
            padding: 4px;
        }

        .data-center {
            text-align: center;
            font-size: 10pt;
            padding: 4px;
        }

        .currency {
            text-align: right;
            font-size: 10pt;
            padding: 4px;
        }

        .total {
            background-color: #e2efda;
            font-weight: bold;
            font-size: 11pt;
            padding: 6px;
        }

        .total-currency {
            background-color: #e2efda;
            font-weight: bold;
            font-size: 11pt;
            text-align: right;
            padding: 6px;
        }

        .stock-sin {
            background-color: #ffcdd2;
            color: #d32f2f;
            font-weight: bold;
        }

        .stock-bajo {
            background-color: #fff3cd;
            color: #856404;
            font-weight: bold;
        }

        .stock-medio {
            background-color: #cce5ff;
            color: #0066cc;
        }

        .stock-normal {
            background-color: #d4edda;
            color: #155724;
        }

        .categoria-header {
            background-color: #ffc107;
            font-weight: bold;
            font-size: 12pt;
            padding: 6px;
        }

        .lugar-header {
            background-color: #17a2b8;
            color: white;
            font-weight: bold;
            font-size: 12pt;
            padding: 6px;
        }
    </style>
</head>

<body>
    <table border="1" cellpadding="2" cellspacing="0">

        <!-- ========================================= -->
        <!-- ENCABEZADO PRINCIPAL -->
        <!-- ========================================= -->
        <tr>
            <td colspan="15" class="header-principal">📊 REPORTE COMPLETO DE INVENTARIO - INVENTPRO</td>
        </tr>
        <tr>
            <td colspan="15" class="subheader">Generado el <?php echo date('d/m/Y H:i:s'); ?> por <?php echo $_SESSION['nombre_usuario'] ?? 'Sistema'; ?></td>
        </tr>
        <tr>
            <td colspan="15" style="height: 20px;"></td>
        </tr>

        <!-- ========================================= -->
        <!-- RESUMEN EJECUTIVO -->
        <!-- ========================================= -->
        <tr>
            <td colspan="15" class="header-seccion">📈 RESUMEN EJECUTIVO</td>
        </tr>
        <tr>
            <td colspan="15" style="height: 10px;"></td>
        </tr>

        <tr>
            <td class="total">Total de Productos:</td>
            <td class="data-center"><?php echo number_format($stats_generales['total_productos']); ?></td>
            <td style="width: 20px;"></td>
            <td class="total">Stock Total:</td>
            <td class="data-center"><?php echo number_format($stats_generales['stock_total']); ?> unidades</td>
            <td style="width: 20px;"></td>
            <td class="total">Valor Total (Venta):</td>
            <td class="total-currency">$ <?php echo formatCurrencyExcel($stats_generales['valor_total_venta']); ?></td>
            <td style="width: 20px;"></td>
            <td class="total">Valor Total (Compra):</td>
            <td class="total-currency">$ <?php echo formatCurrencyExcel($stats_generales['valor_total_compra']); ?></td>
            <td style="width: 20px;"></td>
            <td class="total">Margen Global:</td>
            <td class="total-currency">$ <?php echo formatCurrencyExcel($stats_generales['valor_total_venta'] - $stats_generales['valor_total_compra']); ?></td>
            <td class="data-center"><?php echo formatPercentage((($stats_generales['valor_total_venta'] - $stats_generales['valor_total_compra']) / $stats_generales['valor_total_compra']) * 100); ?></td>
        </tr>

        <tr>
            <td class="total">Productos Bajo Stock:</td>
            <td class="data-center stock-bajo"><?php echo number_format($stats_generales['productos_bajo_stock']); ?></td>
            <td style="width: 20px;"></td>
            <td class="total">Productos Sin Stock:</td>
            <td class="data-center stock-sin"><?php echo number_format($stats_generales['productos_sin_stock']); ?></td>
            <td style="width: 20px;"></td>
            <td class="total">Precio Promedio Venta:</td>
            <td class="total-currency">$ <?php echo formatCurrencyExcel($stats_generales['precio_promedio_venta']); ?></td>
            <td style="width: 20px;"></td>
            <td class="total">Precio Promedio Compra:</td>
            <td class="total-currency">$ <?php echo formatCurrencyExcel($stats_generales['precio_promedio_compra']); ?></td>
            <td colspan="4"></td>
        </tr>

        <tr>
            <td colspan="15" style="height: 30px;"></td>
        </tr>

        <!-- ========================================= -->
        <!-- ANÁLISIS POR CATEGORÍAS -->
        <!-- ========================================= -->
        <tr>
            <td colspan="15" class="header-seccion">📂 ANÁLISIS POR CATEGORÍAS</td>
        </tr>
        <tr>
            <td colspan="15" style="height: 10px;"></td>
        </tr>

        <tr>
            <td class="subheader">Categoría</td>
            <td class="subheader">Productos</td>
            <td class="subheader">Stock Total</td>
            <td class="subheader">Valor Venta</td>
            <td class="subheader">Valor Compra</td>
            <td class="subheader">Margen $</td>
            <td class="subheader">Margen %</td>
            <td class="subheader">Precio Promedio</td>
            <td class="subheader">Bajo Stock</td>
            <td class="subheader">% del Total</td>
            <td colspan="5"></td>
        </tr>

        <?php foreach ($analisis_categorias as $cat): ?>
            <?php
            $margen_valor = $cat['valor_total_venta'] - $cat['valor_total_compra'];
            $margen_porcentaje = $cat['valor_total_compra'] > 0 ? ($margen_valor / $cat['valor_total_compra']) * 100 : 0;
            $porcentaje_total = $stats_generales['valor_total_venta'] > 0 ? ($cat['valor_total_venta'] / $stats_generales['valor_total_venta']) * 100 : 0;
            ?>
            <tr>
                <td class="categoria-header"><?php echo htmlspecialchars($cat['categoria']); ?></td>
                <td class="data-center"><?php echo number_format($cat['cantidad_productos']); ?></td>
                <td class="data-center"><?php echo number_format($cat['stock_total']); ?></td>
                <td class="currency">$ <?php echo formatCurrencyExcel($cat['valor_total_venta']); ?></td>
                <td class="currency">$ <?php echo formatCurrencyExcel($cat['valor_total_compra']); ?></td>
                <td class="currency">$ <?php echo formatCurrencyExcel($margen_valor); ?></td>
                <td class="data-center"><?php echo formatPercentage($margen_porcentaje); ?></td>
                <td class="currency">$ <?php echo formatCurrencyExcel($cat['precio_promedio']); ?></td>
                <td class="data-center <?php echo $cat['productos_bajo_stock'] > 0 ? 'stock-bajo' : 'stock-normal'; ?>"><?php echo number_format($cat['productos_bajo_stock']); ?></td>
                <td class="data-center"><?php echo formatPercentage($porcentaje_total); ?></td>
                <td colspan="5"></td>
            </tr>
        <?php endforeach; ?>

        <tr>
            <td colspan="15" style="height: 30px;"></td>
        </tr>

        <!-- ========================================= -->
        <!-- ANÁLISIS POR UBICACIONES -->
        <!-- ========================================= -->
        <tr>
            <td colspan="15" class="header-seccion">📍 ANÁLISIS POR UBICACIONES</td>
        </tr>
        <tr>
            <td colspan="15" style="height: 10px;"></td>
        </tr>

        <tr>
            <td class="subheader">Ubicación</td>
            <td class="subheader">Productos</td>
            <td class="subheader">Stock Total</td>
            <td class="subheader">Valor Venta</td>
            <td class="subheader">Valor Compra</td>
            <td class="subheader">Margen $</td>
            <td class="subheader">Margen %</td>
            <td class="subheader">Precio Promedio</td>
            <td class="subheader">Bajo Stock</td>
            <td class="subheader">% del Total</td>
            <td colspan="5"></td>
        </tr>

        <?php foreach ($analisis_lugares as $lugar): ?>
            <?php
            $margen_valor = $lugar['valor_total_venta'] - $lugar['valor_total_compra'];
            $margen_porcentaje = $lugar['valor_total_compra'] > 0 ? ($margen_valor / $lugar['valor_total_compra']) * 100 : 0;
            $porcentaje_total = $stats_generales['valor_total_venta'] > 0 ? ($lugar['valor_total_venta'] / $stats_generales['valor_total_venta']) * 100 : 0;
            ?>
            <tr>
                <td class="lugar-header"><?php echo htmlspecialchars($lugar['lugar']); ?></td>
                <td class="data-center"><?php echo number_format($lugar['cantidad_productos']); ?></td>
                <td class="data-center"><?php echo number_format($lugar['stock_total']); ?></td>
                <td class="currency">$ <?php echo formatCurrencyExcel($lugar['valor_total_venta']); ?></td>
                <td class="currency">$ <?php echo formatCurrencyExcel($lugar['valor_total_compra']); ?></td>
                <td class="currency">$ <?php echo formatCurrencyExcel($margen_valor); ?></td>
                <td class="data-center"><?php echo formatPercentage($margen_porcentaje); ?></td>
                <td class="currency">$ <?php echo formatCurrencyExcel($lugar['precio_promedio']); ?></td>
                <td class="data-center <?php echo $lugar['productos_bajo_stock'] > 0 ? 'stock-bajo' : 'stock-normal'; ?>"><?php echo number_format($lugar['productos_bajo_stock']); ?></td>
                <td class="data-center"><?php echo formatPercentage($porcentaje_total); ?></td>
                <td colspan="5"></td>
            </tr>
        <?php endforeach; ?>

        <tr>
            <td colspan="15" style="height: 30px;"></td>
        </tr>

        <!-- ========================================= -->
        <!-- TOP 10 PRODUCTOS MÁS VALIOSOS -->
        <!-- ========================================= -->
        <tr>
            <td colspan="15" class="header-seccion">🏆 TOP 10 PRODUCTOS MÁS VALIOSOS</td>
        </tr>
        <tr>
            <td colspan="15" style="height: 10px;"></td>
        </tr>

        <tr>
            <td class="subheader">Ranking</td>
            <td class="subheader">Código</td>
            <td class="subheader">Producto</td>
            <td class="subheader">Categoría</td>
            <td class="subheader">Stock</td>
            <td class="subheader">Precio Unitario</td>
            <td class="subheader">Valor Total</td>
            <td colspan="8"></td>
        </tr>

        <?php foreach ($top_productos as $index => $prod): ?>
            <tr>
                <td class="data-center"><?php echo ($index + 1); ?>°</td>
                <td class="data"><?php echo htmlspecialchars($prod['codigo']); ?></td>
                <td class="data"><?php echo htmlspecialchars($prod['nombre']); ?></td>
                <td class="data"><?php echo htmlspecialchars($prod['categoria']); ?></td>
                <td class="data-center"><?php echo number_format($prod['stock']); ?></td>
                <td class="currency">$ <?php echo formatCurrencyExcel($prod['precio_venta']); ?></td>
                <td class="currency">$ <?php echo formatCurrencyExcel($prod['valor_total']); ?></td>
                <td colspan="8"></td>
            </tr>
        <?php endforeach; ?>

        <tr>
            <td colspan="15" style="height: 30px;"></td>
        </tr>

        <!-- ========================================= -->
        <!-- LISTADO DETALLADO DE PRODUCTOS -->
        <!-- ========================================= -->
        <tr>
            <td colspan="15" class="header-seccion">📋 LISTADO DETALLADO DE PRODUCTOS</td>
        </tr>
        <tr>
            <td colspan="15" style="height: 10px;"></td>
        </tr>

        <tr>
            <td class="subheader">Código</td>
            <td class="subheader">Producto</td>
            <td class="subheader">Categoría</td>
            <td class="subheader">Ubicación</td>
            <td class="subheader">Stock</td>
            <td class="subheader">Stock Mín.</td>
            <td class="subheader">Estado</td>
            <td class="subheader">P. Venta</td>
            <td class="subheader">P. Compra</td>
            <td class="subheader">Margen %</td>
            <td class="subheader">Valor Stock Venta</td>
            <td class="subheader">Valor Stock Compra</td>
            <td class="subheader">Ganancia Potencial</td>
            <td class="subheader">Fecha Alta</td>
            <td class="subheader">Descripción</td>
        </tr>

        <?php foreach ($productos as $producto): ?>
            <?php
            $estado_class = match ($producto['estado_stock']) {
                'Sin Stock' => 'stock-sin',
                'Stock Bajo' => 'stock-bajo',
                'Stock Medio' => 'stock-medio',
                default => 'stock-normal'
            };
            ?>
            <tr>
                <td class="data"><?php echo htmlspecialchars($producto['codigo']); ?></td>
                <td class="data"><?php echo htmlspecialchars($producto['nombre']); ?></td>
                <td class="data"><?php echo htmlspecialchars($producto['categoria']); ?></td>
                <td class="data"><?php echo htmlspecialchars($producto['lugar']); ?></td>
                <td class="data-center <?php echo $estado_class; ?>"><?php echo number_format($producto['stock']); ?></td>
                <td class="data-center"><?php echo number_format($producto['stock_minimo']); ?></td>
                <td class="data-center <?php echo $estado_class; ?>"><?php echo $producto['estado_stock']; ?></td>
                <td class="currency">$ <?php echo formatCurrencyExcel($producto['precio_venta']); ?></td>
                <td class="currency">$ <?php echo formatCurrencyExcel($producto['precio_compra']); ?></td>
                <td class="data-center"><?php echo formatPercentage($producto['margen_ganancia']); ?></td>
                <td class="currency">$ <?php echo formatCurrencyExcel($producto['valor_stock_venta']); ?></td>
                <td class="currency">$ <?php echo formatCurrencyExcel($producto['valor_stock_compra']); ?></td>
                <td class="currency">$ <?php echo formatCurrencyExcel($producto['valor_stock_venta'] - $producto['valor_stock_compra']); ?></td>
                <td class="data-center"><?php echo date('d/m/Y', strtotime($producto['fecha_creacion'])); ?></td>
                <td class="data"><?php echo htmlspecialchars(substr($producto['descripcion'] ?? '', 0, 50)); ?></td>
            </tr>
        <?php endforeach; ?>

        <!-- ========================================= -->
        <!-- TOTALES FINALES -->
        <!-- ========================================= -->
        <tr>
            <td colspan="15" style="height: 20px;"></td>
        </tr>
        <tr>
            <td colspan="10" class="total">TOTALES GENERALES</td>
            <td class="total-currency">$ <?php echo formatCurrencyExcel(array_sum(array_column($productos, 'valor_stock_venta'))); ?></td>
            <td class="total-currency">$ <?php echo formatCurrencyExcel(array_sum(array_column($productos, 'valor_stock_compra'))); ?></td>
            <td class="total-currency">$ <?php echo formatCurrencyExcel(array_sum(array_column($productos, 'valor_stock_venta')) - array_sum(array_column($productos, 'valor_stock_compra'))); ?></td>
            <td colspan="2"></td>
        </tr>

        <!-- ========================================= -->
        <!-- PIE DEL REPORTE -->
        <!-- ========================================= -->
        <tr>
            <td colspan="15" style="height: 30px;"></td>
        </tr>
        <tr>
            <td colspan="15" class="subheader">📊 Reporte generado por InventPro - Sistema de Gestión de Inventario | www.inventpro.com</td>
        </tr>
        <tr>
            <td colspan="15" class="data-center">Este reporte contiene información confidencial de la empresa. Distribución restringida.</td>
        </tr>

    </table>
</body>

</html>
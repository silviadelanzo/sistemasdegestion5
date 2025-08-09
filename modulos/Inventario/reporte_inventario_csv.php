<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

// Generar archivo CSV compatible con TextMaker y Excel
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="reporte_inventario_completo_' . date('Y-m-d_H-i-s') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// BOM para UTF-8 (compatibilidad con TextMaker)
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Función para escapar valores CSV
    function csvEscape($value)
    {
        if (is_numeric($value)) {
            return $value;
        }
        return '"' . str_replace('"', '""', $value) . '"';
    }

    // ENCABEZADO DEL REPORTE
    fputcsv($output, ['REPORTE COMPLETO DE INVENTARIO - INVENTPRO'], ';');
    fputcsv($output, ['Generado el ' . date('d/m/Y H:i:s') . ' por ' . ($_SESSION['nombre_usuario'] ?? 'Sistema')], ';');
    fputcsv($output, [], ';');

    // ===== ESTADÍSTICAS GENERALES =====
    fputcsv($output, ['=== RESUMEN EJECUTIVO ==='], ';');
    fputcsv($output, [], ';');

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
    $stats = $pdo->query($sql_stats)->fetch(PDO::FETCH_ASSOC);

    $margen_global = $stats['valor_total_venta'] - $stats['valor_total_compra'];
    $porcentaje_margen = $stats['valor_total_compra'] > 0 ? ($margen_global / $stats['valor_total_compra']) * 100 : 0;

    fputcsv($output, ['Métrica', 'Valor', 'Observaciones'], ';');
    fputcsv($output, ['Total de Productos', number_format($stats['total_productos']), 'Productos activos en el sistema'], ';');
    fputcsv($output, ['Stock Total', number_format($stats['stock_total']) . ' unidades', 'Suma de todos los stocks'], ';');
    fputcsv($output, ['Valor Total (Venta)', '$' . number_format($stats['valor_total_venta'], 2), 'Valor del inventario a precio de venta'], ';');
    fputcsv($output, ['Valor Total (Compra)', '$' . number_format($stats['valor_total_compra'], 2), 'Valor del inventario a precio de compra'], ';');
    fputcsv($output, ['Margen Global', '$' . number_format($margen_global, 2), 'Diferencia entre valor venta y compra'], ';');
    fputcsv($output, ['Porcentaje Margen', number_format($porcentaje_margen, 1) . '%', 'Margen de ganancia promedio'], ';');
    fputcsv($output, ['Productos Bajo Stock', $stats['productos_bajo_stock'], 'Productos que necesitan reposición'], ';');
    fputcsv($output, ['Productos Sin Stock', $stats['productos_sin_stock'], 'Productos agotados'], ';');
    fputcsv($output, [], ';');

    // ===== ANÁLISIS POR CATEGORÍAS =====
    fputcsv($output, ['=== ANÁLISIS POR CATEGORÍAS ==='], ';');
    fputcsv($output, [], ';');

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
    $categorias = $pdo->query($sql_categorias)->fetchAll(PDO::FETCH_ASSOC);

    fputcsv($output, [
        'Categoría',
        'Cantidad Productos',
        'Stock Total',
        'Valor Venta',
        'Valor Compra',
        'Margen ($)',
        'Margen (%)',
        'Precio Promedio',
        'Bajo Stock',
        '% del Total'
    ], ';');

    foreach ($categorias as $cat) {
        $margen_valor = $cat['valor_total_venta'] - $cat['valor_total_compra'];
        $margen_porcentaje = $cat['valor_total_compra'] > 0 ? ($margen_valor / $cat['valor_total_compra']) * 100 : 0;
        $porcentaje_total = $stats['valor_total_venta'] > 0 ? ($cat['valor_total_venta'] / $stats['valor_total_venta']) * 100 : 0;

        fputcsv($output, [
            $cat['categoria'],
            number_format($cat['cantidad_productos']),
            number_format($cat['stock_total']),
            '$' . number_format($cat['valor_total_venta'], 2),
            '$' . number_format($cat['valor_total_compra'], 2),
            '$' . number_format($margen_valor, 2),
            number_format($margen_porcentaje, 1) . '%',
            '$' . number_format($cat['precio_promedio'], 2),
            $cat['productos_bajo_stock'],
            number_format($porcentaje_total, 1) . '%'
        ], ';');
    }
    fputcsv($output, [], ';');

    // ===== ANÁLISIS POR UBICACIONES =====
    fputcsv($output, ['=== ANÁLISIS POR UBICACIONES ==='], ';');
    fputcsv($output, [], ';');

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
    $lugares = $pdo->query($sql_lugares)->fetchAll(PDO::FETCH_ASSOC);

    fputcsv($output, [
        'Ubicación',
        'Cantidad Productos',
        'Stock Total',
        'Valor Venta',
        'Valor Compra',
        'Margen ($)',
        'Margen (%)',
        'Precio Promedio',
        'Bajo Stock',
        '% del Total'
    ], ';');

    foreach ($lugares as $lugar) {
        $margen_valor = $lugar['valor_total_venta'] - $lugar['valor_total_compra'];
        $margen_porcentaje = $lugar['valor_total_compra'] > 0 ? ($margen_valor / $lugar['valor_total_compra']) * 100 : 0;
        $porcentaje_total = $stats['valor_total_venta'] > 0 ? ($lugar['valor_total_venta'] / $stats['valor_total_venta']) * 100 : 0;

        fputcsv($output, [
            $lugar['lugar'],
            number_format($lugar['cantidad_productos']),
            number_format($lugar['stock_total']),
            '$' . number_format($lugar['valor_total_venta'], 2),
            '$' . number_format($lugar['valor_total_compra'], 2),
            '$' . number_format($margen_valor, 2),
            number_format($margen_porcentaje, 1) . '%',
            '$' . number_format($lugar['precio_promedio'], 2),
            $lugar['productos_bajo_stock'],
            number_format($porcentaje_total, 1) . '%'
        ], ';');
    }
    fputcsv($output, [], ';');

    // ===== TOP 10 PRODUCTOS MÁS VALIOSOS =====
    fputcsv($output, ['=== TOP 10 PRODUCTOS MÁS VALIOSOS ==='], ';');
    fputcsv($output, [], ';');

    $sql_top = "
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
    $top_productos = $pdo->query($sql_top)->fetchAll(PDO::FETCH_ASSOC);

    fputcsv($output, ['Ranking', 'Código', 'Producto', 'Categoría', 'Stock', 'Precio Unitario', 'Valor Total'], ';');

    foreach ($top_productos as $index => $prod) {
        fputcsv($output, [
            ($index + 1) . '°',
            $prod['codigo'],
            $prod['nombre'],
            $prod['categoria'],
            number_format($prod['stock']),
            '$' . number_format($prod['precio_venta'], 2),
            '$' . number_format($prod['valor_total'], 2)
        ], ';');
    }
    fputcsv($output, [], ';');

    // ===== PRODUCTOS CON STOCK CRÍTICO =====
    fputcsv($output, ['=== PRODUCTOS CON STOCK CRÍTICO ==='], ';');
    fputcsv($output, [], ';');

    $sql_criticos = "
        SELECT 
            p.codigo,
            p.nombre,
            COALESCE(c.nombre, 'Sin Categoría') as categoria,
            COALESCE(l.nombre, 'Sin Ubicación') as lugar,
            p.stock,
            p.stock_minimo,
            CASE 
                WHEN p.stock = 0 THEN 'SIN STOCK'
                WHEN p.stock <= p.stock_minimo THEN 'CRÍTICO'
                ELSE 'BAJO'
            END as estado
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        LEFT JOIN lugares l ON p.lugar_id = l.id
        WHERE p.activo = 1 AND p.stock <= (p.stock_minimo * 1.2)
        ORDER BY p.stock ASC, p.stock_minimo DESC
        LIMIT 20
    ";
    $criticos = $pdo->query($sql_criticos)->fetchAll(PDO::FETCH_ASSOC);

    if (count($criticos) > 0) {
        fputcsv($output, ['Código', 'Producto', 'Categoría', 'Ubicación', 'Stock Actual', 'Stock Mínimo', 'Estado'], ';');

        foreach ($criticos as $critico) {
            fputcsv($output, [
                $critico['codigo'],
                $critico['nombre'],
                $critico['categoria'],
                $critico['lugar'],
                number_format($critico['stock']),
                number_format($critico['stock_minimo']),
                $critico['estado']
            ], ';');
        }
    } else {
        fputcsv($output, ['¡Excelente! No hay productos con stock crítico'], ';');
    }
    fputcsv($output, [], ';');

    // ===== LISTADO DETALLADO DE PRODUCTOS =====
    fputcsv($output, ['=== LISTADO DETALLADO DE PRODUCTOS ==='], ';');
    fputcsv($output, [], ';');

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

    fputcsv($output, [
        'Código',
        'Producto',
        'Categoría',
        'Ubicación',
        'Stock',
        'Stock Mín.',
        'Estado Stock',
        'Precio Venta',
        'Precio Compra',
        'Margen %',
        'Valor Stock Venta',
        'Valor Stock Compra',
        'Ganancia Potencial',
        'Fecha Alta',
        'Descripción'
    ], ';');

    foreach ($productos as $producto) {
        fputcsv($output, [
            $producto['codigo'],
            $producto['nombre'],
            $producto['categoria'],
            $producto['lugar'],
            number_format($producto['stock']),
            number_format($producto['stock_minimo']),
            $producto['estado_stock'],
            '$' . number_format($producto['precio_venta'], 2),
            '$' . number_format($producto['precio_compra'], 2),
            number_format($producto['margen_ganancia'], 1) . '%',
            '$' . number_format($producto['valor_stock_venta'], 2),
            '$' . number_format($producto['valor_stock_compra'], 2),
            '$' . number_format($producto['valor_stock_venta'] - $producto['valor_stock_compra'], 2),
            date('d/m/Y', strtotime($producto['fecha_creacion'])),
            substr($producto['descripcion'] ?? '', 0, 100)
        ], ';');
    }

    // ===== TOTALES FINALES =====
    fputcsv($output, [], ';');
    fputcsv($output, ['=== TOTALES GENERALES ==='], ';');
    fputcsv($output, [], ';');

    $total_venta = array_sum(array_column($productos, 'valor_stock_venta'));
    $total_compra = array_sum(array_column($productos, 'valor_stock_compra'));
    $ganancia_total = $total_venta - $total_compra;

    fputcsv($output, ['Concepto', 'Valor'], ';');
    fputcsv($output, ['Total Valor Inventario (Venta)', '$' . number_format($total_venta, 2)], ';');
    fputcsv($output, ['Total Valor Inventario (Compra)', '$' . number_format($total_compra, 2)], ';');
    fputcsv($output, ['Ganancia Potencial Total', '$' . number_format($ganancia_total, 2)], ';');
    fputcsv($output, ['Margen Promedio Global', number_format($total_compra > 0 ? ($ganancia_total / $total_compra) * 100 : 0, 1) . '%'], ';');
    fputcsv($output, [], ';');

    // ===== PIE DEL REPORTE =====
    fputcsv($output, ['=== INFORMACIÓN DEL REPORTE ==='], ';');
    fputcsv($output, [], ';');
    fputcsv($output, ['Sistema', 'InventPro - Sistema de Gestión de Inventario'], ';');
    fputcsv($output, ['Fecha de Generación', date('d/m/Y H:i:s')], ';');
    fputcsv($output, ['Usuario', $_SESSION['nombre_usuario'] ?? 'Sistema'], ';');
    fputcsv($output, ['Total de Registros', count($productos)], ';');
    fputcsv($output, ['Estado', 'Reporte Completo Generado Exitosamente'], ';');
    fputcsv($output, [], ';');
    fputcsv($output, ['NOTA', 'Este reporte contiene información confidencial de la empresa'], ';');
    fputcsv($output, ['RESTRICCIÓN', 'Distribución limitada - Solo personal autorizado'], ';');
} catch (Exception $e) {
    fputcsv($output, ['ERROR', 'Error al generar reporte: ' . $e->getMessage()], ';');
} finally {
    fclose($output);
}

<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    $term = isset($_GET['term']) ? trim($_GET['term']) : '';
    $proveedor_id = isset($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : 0;
    $debug = isset($_GET['debug']) ? (bool)$_GET['debug'] : false;

    if ($proveedor_id <= 0) {
        echo json_encode([]);
        exit;
    }

    // Detectar posibles nombres de tabla pivote
    $pivotCandidates = ['producto_proveedor', 'productos_proveedor', 'producto_proveedores', 'productos_proveedores'];
    $pivotTable = null;
    foreach ($pivotCandidates as $candidate) {
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$candidate]);
            if ($stmt->fetchColumn()) { $pivotTable = $candidate; break; }
        } catch (Exception $e) { /* ignorar */ }
    }

    // Si hay tabla pivote, detectar dinámicamente las columnas relevantes
    $productCol = null;
    $providerCols = [];
    if ($pivotTable) {
        try {
            $desc = $pdo->query("DESCRIBE {$pivotTable}")->fetchAll(PDO::FETCH_COLUMN, 0);
            // Columna de producto
            $productColCandidates = ['producto_id','product_id','id_producto','id_product','producto','product'];
            foreach ($productColCandidates as $c) { if (in_array($c, $desc, true)) { $productCol = $c; break; } }
            // Columnas posibles de proveedor (principal y alternativos)
            $providerColCandidates = [
                'proveedor_principal',
                'proveedor_alternativo01','proveedor_alternativo02','proveedor_alternativo03','proveedor_alternativo04',
                'proveedor_id','provider_id','id_proveedor','id_provider','proveedor','provider'
            ];
            foreach ($providerColCandidates as $c) { if (in_array($c, $desc, true)) { $providerCols[] = $c; } }
            // Validar
            if (!$productCol || count($providerCols) === 0) {
                $pivotTable = null; // deshabilitar si no es utilizable
            }
        } catch (Exception $e) {
            $pivotTable = null;
        }
    }

    // Construir WHERE de búsqueda flexible
    $whereSearch = '';
    $params = [
        ':proveedor_id' => $proveedor_id,
    ];

    if ($term !== '') {
        $whereSearch = " AND (p.nombre LIKE :term OR p.codigo LIKE :term OR p.codigo_barra LIKE :term OR p.descripcion LIKE :term)";
        $params[':term'] = "%{$term}%";
        $params[':term_exact'] = $term;
    } else {
        $params[':term'] = '%%';
        $params[':term_exact'] = '';
    }

    // Subconsulta: última compra del mismo proveedor para ese producto
    $ultimaCompraSelect = "(
        SELECT cd.precio_unitario
        FROM compra_detalles cd
        JOIN compras co ON co.id = cd.compra_id
        WHERE co.proveedor_id = :proveedor_id2 AND cd.producto_id = p.id
        ORDER BY co.fecha_compra DESC, cd.id DESC
        LIMIT 1
    ) AS precio_ultima_compra";

    $params[':proveedor_id2'] = $proveedor_id;

    if ($pivotTable) {
        // Construir condición OR para columnas de proveedor
        $providerOr = [];
        foreach ($providerCols as $idx => $col) {
            // evitar conflictos de parámetros: usamos siempre el mismo :proveedor_id en la condición
            $providerOr[] = "pp.{$col} = :proveedor_id";
        }
        $providerCond = '(' . implode(' OR ', $providerOr) . ')';

        // Determinar si el proveedor principal está en la lista de columnas de la tabla pivote
        $principalCol = 'proveedor_principal';
        $hasPrincipalCol = in_array($principalCol, $providerCols, true);
        $tipoProveedorSelect = $hasPrincipalCol ? "CASE WHEN pp.{$principalCol} = :proveedor_id THEN 'P' ELSE 'A' END" : "'A'";

        $sql = "
            SELECT
                p.id,
                p.nombre,
                p.codigo,
                p.codigo_barra,
                p.descripcion,
                p.stock,
                p.stock_minimo,
                p.precio_compra, -- fallback
                c.nombre AS categoria_nombre,
                $ultimaCompraSelect,
                {$tipoProveedorSelect} AS proveedor_tipo
            FROM productos p
            INNER JOIN {$pivotTable} pp ON pp.{$productCol} = p.id AND {$providerCond}
            LEFT JOIN categorias c ON c.id = p.categoria_id
            WHERE p.activo = 1
            $whereSearch
            ORDER BY
                (CASE WHEN {$tipoProveedorSelect} = 'P' THEN 0 ELSE 1 END) ASC,
                CASE
                    WHEN p.codigo_barra = :term_exact THEN 0
                    WHEN p.codigo = :term_exact THEN 1
                    WHEN p.nombre LIKE :term THEN 2
                    ELSE 3
                END,
                p.nombre
            LIMIT 30
        ";
    } else {
        // Fallback si no existe tabla pivote: usar proveedor_principal_id
        $sql = "
            SELECT
                p.id,
                p.nombre,
                p.codigo,
                p.codigo_barra,
                p.descripcion,
                p.stock,
                p.stock_minimo,
                p.precio_compra, -- fallback
                c.nombre AS categoria_nombre,
                $ultimaCompraSelect,
                'P' AS proveedor_tipo
            FROM productos p
            LEFT JOIN categorias c ON c.id = p.categoria_id
            WHERE p.activo = 1 AND p.proveedor_principal_id = :proveedor_id
            $whereSearch
            ORDER BY
                CASE
                    WHEN p.codigo_barra = :term_exact THEN 0
                    WHEN p.codigo = :term_exact THEN 1
                    WHEN p.nombre LIKE :term THEN 2
                    ELSE 3
                END,
                p.nombre
            LIMIT 30
        ";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = array_map(function ($r) {
        $precio = $r['precio_ultima_compra'] !== null ? (float)$r['precio_ultima_compra'] : (float)$r['precio_compra'];
        return [
            'id' => (int)$r['id'],
            'nombre' => $r['nombre'],
            'codigo' => $r['codigo'],
            'codigo_barra' => $r['codigo_barra'],
            'stock' => (int)$r['stock'],
            'stock_minimo' => (int)$r['stock_minimo'],
            'categoria_nombre' => $r['categoria_nombre'] ?? null,
            'precio_compra' => number_format($precio, 2, '.', ''),
            'descripcion' => $r['descripcion'] ?? null,
            'proveedor_tipo' => $r['proveedor_tipo'] ?? 'A',
        ];
    }, $rows);

    if ($debug) {
        echo json_encode([
            'data' => $result,
            'debug' => [
                'pivotTable' => $pivotTable,
                'productCol' => $productCol,
                'providerCols' => $providerCols,
                'term' => $term,
                'proveedor_id' => $proveedor_id,
                'sql_used' => $sql,
            ]
        ]);
    } else {
        echo json_encode($result);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Error en búsqueda de productos', 'detail' => $e->getMessage()]);
}

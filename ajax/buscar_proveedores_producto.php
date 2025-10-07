<?php
require_once '../config/config.php';
iniciarSesionSegura();
requireLogin('../login.php');

header('Content-Type: application/json; charset=utf-8');

$pdo = conectarDB();
$producto_id = $_GET['producto_id'] ?? 0;

try {
    if (!$producto_id) {
        throw new Exception('ID de producto requerido');
    }
    
    // Obtener información del producto y sus proveedores
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.nombre as producto_nombre,
            p.codigo,
            pp.proveedor_principal,
            pp.proveedor_alternativo01,
            pp.proveedor_alternativo02,
            pp.proveedor_alternativo03,
            pp.proveedor_alternativo04
        FROM productos p
        JOIN productos_proveedores pp ON p.id = pp.producto_id
        WHERE p.id = ?
    ");
    
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        throw new Exception('Producto no encontrado');
    }
    
    $proveedores = [];
    
    // Recopilar todos los IDs de proveedores
    $proveedor_ids = [];
    if ($producto['proveedor_principal']) $proveedor_ids[] = $producto['proveedor_principal'];
    if ($producto['proveedor_alternativo01']) $proveedor_ids[] = $producto['proveedor_alternativo01'];
    if ($producto['proveedor_alternativo02']) $proveedor_ids[] = $producto['proveedor_alternativo02'];
    if ($producto['proveedor_alternativo03']) $proveedor_ids[] = $producto['proveedor_alternativo03'];
    if ($producto['proveedor_alternativo04']) $proveedor_ids[] = $producto['proveedor_alternativo04'];
    
    if (!empty($proveedor_ids)) {
        $placeholders = str_repeat('?,', count($proveedor_ids) - 1) . '?';
        
        $stmt_proveedores = $pdo->prepare("
            SELECT 
                prov.id,
                prov.razon_social,
                prov.condiciones_pago,
                prov.tiempo_entrega_dias,
                prov.telefono,
                prov.email,
                p.precio_compra,
                NULL as fecha_ultima_compra,
                CASE 
                    WHEN prov.id = ? THEN 'Principal'
                    ELSE 'Alternativo'
                END as tipo_proveedor
            FROM proveedores prov
            LEFT JOIN productos p ON p.id = ?
            WHERE prov.id IN ($placeholders)
            AND prov.activo = 1
            ORDER BY 
                CASE WHEN prov.id = ? THEN 0 ELSE 1 END,
                prov.razon_social
        ");
        
        $params = [$producto['proveedor_principal'], $producto_id];
        $params = array_merge($params, $proveedor_ids);
        $params[] = $producto['proveedor_principal'];
        
        $stmt_proveedores->execute($params);
        $proveedores = $stmt_proveedores->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'status' => 'success',
        'producto' => $producto,
        'proveedores' => $proveedores
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
<?php
require_once '../../config/config.php';

// Crear conexión
$pdo = conectarDB();

session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['accion']) || $_POST['accion'] !== 'crear_producto') {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Validar datos obligatorios
    $nombre = trim($_POST['nombre']);
    $codigo_proveedor = trim($_POST['codigo_proveedor']);
    $proveedor_id = (int)$_POST['proveedor_id'];
    $unidad = trim($_POST['unidad']);
    
    if (empty($nombre) || empty($codigo_proveedor) || empty($proveedor_id)) {
        throw new Exception('Faltan datos obligatorios');
    }
    
    // Manejar categoría
    $categoria_id = null;
    if (!empty($_POST['categoria_id'])) {
        $categoria_id = (int)$_POST['categoria_id'];
    } elseif (!empty($_POST['nueva_categoria'])) {
        // Crear nueva categoría
        $nueva_categoria = trim($_POST['nueva_categoria']);
        $stmt = $pdo->prepare("INSERT INTO categorias (nombre, activo, fecha_creacion) VALUES (?, 1, NOW())");
        $stmt->execute([$nueva_categoria]);
        $categoria_id = $pdo->lastInsertId();
    }
    
    // Manejar lugar
    $lugar_id = null;
    if (!empty($_POST['lugar_id'])) {
        $lugar_id = (int)$_POST['lugar_id'];
    } elseif (!empty($_POST['nuevo_lugar'])) {
        // Crear nuevo lugar
        $nuevo_lugar = trim($_POST['nuevo_lugar']);
        $stmt = $pdo->prepare("INSERT INTO lugares (nombre, activo, fecha_creacion) VALUES (?, 1, NOW())");
        $stmt->execute([$nuevo_lugar]);
        $lugar_id = $pdo->lastInsertId();
    }
    
    // Generar código único para el producto
    $stmt = $pdo->prepare("SELECT codigo FROM productos WHERE codigo LIKE 'PROD-%' ORDER BY CAST(SUBSTRING(codigo, 6) AS UNSIGNED) DESC LIMIT 1");
    $stmt->execute();
    $ultimo_codigo = $stmt->fetchColumn();
    
    if ($ultimo_codigo) {
        $numero = (int)substr($ultimo_codigo, 5) + 1;
    } else {
        $numero = 1;
    }
    $codigo_producto = 'PROD-' . str_pad($numero, 7, '0', STR_PAD_LEFT);
    
    // Preparar datos opcionales
    $precio_compra = !empty($_POST['precio_compra']) ? (float)$_POST['precio_compra'] : null;
    $stock = !empty($_POST['stock']) ? (int)$_POST['stock'] : 0;
    $descripcion = !empty($_POST['descripcion']) ? trim($_POST['descripcion']) : null;
    $usuario_id = $_SESSION['usuario_id'] ?? 1;
    
    // Insertar producto
    $sql = "INSERT INTO productos (
        codigo, 
        nombre, 
        descripcion, 
        categoria_id, 
        lugar_id, 
        precio_compra, 
        stock, 
        activo, 
        fecha_creacion, 
        creado_por, 
        proveedor_principal_id, 
        codigo_proveedor, 
        unidad
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $codigo_producto,
        $nombre,
        $descripcion,
        $categoria_id,
        $lugar_id,
        $precio_compra,
        $stock,
        $usuario_id,
        $proveedor_id,
        $codigo_proveedor,
        $unidad
    ]);
    
    $producto_id = $pdo->lastInsertId();
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Producto creado exitosamente',
        'producto_id' => $producto_id,
        'codigo' => $codigo_producto
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false, 
        'message' => 'Error al crear el producto: ' . $e->getMessage()
    ]);
}
?>

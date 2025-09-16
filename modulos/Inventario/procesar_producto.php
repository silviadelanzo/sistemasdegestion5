<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit;
}

require_once '../../config/config.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: productos.php');
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Recoger y validar cuenta_id de la sesión
    if (!isset($_SESSION['cuenta_id']) || empty($_SESSION['cuenta_id'])) {
        throw new Exception("La sesión no tiene una cuenta asociada. Por favor, inicie sesión de nuevo.");
    }
    $cuenta_id = (int)$_SESSION['cuenta_id'];
    if ($cuenta_id <= 0) {
        throw new Exception("ID de cuenta inválido en la sesión.");
    }

    // Recoger datos del formulario
    $es_edicion = !empty($_POST['id']);
    $producto_id = $es_edicion ? intval($_POST['id']) : null;
    
    // Datos básicos
    $codigo_barras = trim($_POST['codigo_barras']);
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria_id = intval($_POST['categoria_id']);
    $lugar_id = intval($_POST['lugar_id']);
    
    // Datos fiscales y monetarios
    $moneda_id = !empty($_POST['moneda_id']) ? intval($_POST['moneda_id']) : null;
    $impuesto_id = !empty($_POST['impuesto_id']) ? intval($_POST['impuesto_id']) : null;
    $redondeo_decimales = intval($_POST['redondeo_decimales'] ?? 2);
    $tipo_redondeo = $_POST['tipo_redondeo'] ?? 'centavo';
    
    // Precios
    $precio_compra = floatval($_POST['precio_compra']);
    $precio_minorista = floatval($_POST['precio_minorista']);
    $precio_mayorista = floatval($_POST['precio_mayorista']);
    $utilidad_minorista = floatval($_POST['utilidad_minorista'] ?? 0);
    $utilidad_mayorista = floatval($_POST['utilidad_mayorista'] ?? 0);
    
    // Stock
    $usar_control_stock = isset($_POST['usar_control_stock']) ? 1 : 0;
    $stock = $usar_control_stock ? intval($_POST['stock']) : 0;
    $stock_minimo = intval($_POST['stock_minimo'] ?? 0);
    $stock_maximo = intval($_POST['stock_maximo'] ?? 9999);
    
    // Vencimientos
    $usar_alerta_vencimiento = isset($_POST['usar_alerta_vencimiento']) ? 1 : 0;
    $fecha_vencimiento = $usar_alerta_vencimiento && !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
    $alerta_vencimiento_dias = intval($_POST['alerta_vencimiento_dias'] ?? 30);
    
    // Validaciones básicas
    if (empty($codigo_barras)) {
        throw new Exception('El código de barras es obligatorio');
    }
    
    if (empty($nombre)) {
        throw new Exception('El nombre del producto es obligatorio');
    }
    
    if ($categoria_id <= 0) {
        throw new Exception('Debe seleccionar una categoría válida');
    }
    
    if ($precio_compra <= 0) {
        throw new Exception('El precio de compra debe ser mayor a 0');
    }
    
    // Verificar código único dentro de la misma cuenta
    $sql_check = "SELECT id FROM productos WHERE codigo_barras = ? AND cuenta_id = ?";
    $params_check = [$codigo_barras, $cuenta_id];
    
    if ($es_edicion) {
        $sql_check .= " AND id != ?";
        $params_check[] = $producto_id;
    }
    
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute($params_check);
    
    if ($stmt_check->fetch()) {
        throw new Exception('El código de barras ya existe en otro producto de esta cuenta.');
    }
    
    // Manejo de imagen
    $imagen_url = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/img/productos/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_info = pathinfo($_FILES['imagen']['name']);
        $extension = strtolower($file_info['extension']);
        
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $extensiones_permitidas)) {
            throw new Exception('Formato de imagen no permitido. Use: ' . implode(', ', $extensiones_permitidas));
        }
        
        $nombre_archivo = 'producto_' . $codigo_barras . '_' . time() . '.' . $extension;
        $ruta_completa = $upload_dir . $nombre_archivo;
        
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_completa)) {
            $imagen_url = 'assets/img/productos/' . $nombre_archivo;
        }
    }
    
    // Preparar consulta SQL
    if ($es_edicion) {
        // Actualizar producto existente
        $sql = "UPDATE productos SET 
                codigo_barras = ?, nombre = ?, descripcion = ?, categoria_id = ?, lugar_id = ?,
                moneda_id = ?, impuesto_id = ?, redondeo_decimales = ?, tipo_redondeo = ?,
                precio_compra = ?, precio_minorista = ?, precio_mayorista = ?,
                utilidad_minorista = ?, utilidad_mayorista = ?,
                usar_control_stock = ?, stock = ?, stock_minimo = ?, stock_maximo = ?,
                usar_alerta_vencimiento = ?, fecha_vencimiento = ?, alerta_vencimiento_dias = ?,
                fecha_actualizacion = NOW()";
        
        $params = [
            $codigo_barras, $nombre, $descripcion, $categoria_id, $lugar_id,
            $moneda_id, $impuesto_id, $redondeo_decimales, $tipo_redondeo,
            $precio_compra, $precio_minorista, $precio_mayorista,
            $utilidad_minorista, $utilidad_mayorista,
            $usar_control_stock, $stock, $stock_minimo, $stock_maximo,
            $usar_alerta_vencimiento, $fecha_vencimiento, $alerta_vencimiento_dias
        ];
        
        if (!empty($imagen_url)) {
            $sql .= ", imagen_url = ?";
            $params[] = $imagen_url;
        }
        
        $sql .= " WHERE id = ? AND cuenta_id = ?";
        $params[] = $producto_id;
        $params[] = $cuenta_id;
        
    } else {
        // Crear nuevo producto
        $sql = "INSERT INTO productos (
                cuenta_id, codigo_barras, nombre, descripcion, categoria_id, lugar_id,
                moneda_id, impuesto_id, redondeo_decimales, tipo_redondeo,
                precio_compra, precio_minorista, precio_mayorista,
                utilidad_minorista, utilidad_mayorista,
                usar_control_stock, stock, stock_minimo, stock_maximo,
                usar_alerta_vencimiento, fecha_vencimiento, alerta_vencimiento_dias,
                imagen_url, usuario_creacion, fecha_creacion, fecha_actualizacion
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, NOW(), NOW()
            )";
        
        $params = [
            $cuenta_id, $codigo_barras, $nombre, $descripcion, $categoria_id, $lugar_id,
            $moneda_id, $impuesto_id, $redondeo_decimales, $tipo_redondeo,
            $precio_compra, $precio_minorista, $precio_mayorista,
            $utilidad_minorista, $utilidad_mayorista,
            $usar_control_stock, $stock, $stock_minimo, $stock_maximo,
            $usar_alerta_vencimiento, $fecha_vencimiento, $alerta_vencimiento_dias,
            $imagen_url, $_SESSION['usuario_id']
        ];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    if ($es_edicion && $stmt->rowCount() === 0) {
        throw new Exception("No se pudo actualizar el producto. Verifique que el producto pertenezca a su cuenta.");
    }

    // Obtener ID del producto (para nuevos productos)
    if (!$es_edicion) {
        $producto_id = $pdo->lastInsertId();
    }
    
    // Registrar en log de actividades
    $accion = $es_edicion ? 'actualizar_producto' : 'crear_producto';
    $log_sql = "INSERT INTO logs_sistema (usuario_id, cuenta_id, accion, tabla_afectada, registro_id, detalles, fecha) 
                VALUES (?, ?, ?, 'productos', ?, ?, NOW())";
    $log_stmt = $pdo->prepare($log_sql);
    $log_stmt->execute([
        $_SESSION['usuario_id'],
        $cuenta_id,
        $accion,
        $producto_id,
        json_encode([
            'codigo' => $codigo_barras,
            'nombre' => $nombre,
            'precio_compra' => $precio_compra,
            'stock' => $stock
        ])
    ]);
    
    $pdo->commit();
    
    // Redirigir con mensaje de éxito
    $mensaje = $es_edicion ? 'Producto actualizado exitosamente' : 'Producto creado exitosamente';
    header("Location: productos.php?success=" . urlencode($mensaje));
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    
    // Log del error
    error_log("Error al procesar producto: " . $e->getMessage());
    
    // Redirigir con mensaje de error
    $redirect_url = $es_edicion ? "producto_form_nuevo.php?id=$producto_id" : "producto_form_nuevo.php";
    header("Location: $redirect_url&error=" . urlencode($e->getMessage()));
    exit;
}
?>

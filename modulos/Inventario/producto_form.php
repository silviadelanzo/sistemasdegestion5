<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

$producto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$es_edicion = $producto_id > 0;

// Datos iniciales del producto
$producto = [
    'codigo_interno' => '',
    'codigo_barras' => '',
    'nombre' => '',
    'descripcion' => '',
    'categoria_id' => '',
    'lugar_id' => '',
    'unidad_medida' => 'UN',
    'factor_conversion' => 1.00,
    'precio_compra' => 0.00,
    'precio_minorista' => 0.00,
    'precio_mayorista' => 0.00,
    'utilidad_minorista' => 30.00,
    'utilidad_mayorista' => 15.00,
    'moneda_id' => 1,
    'impuesto_id' => 1,
    'stock' => 0,
    'stock_minimo' => 1,
    'stock_maximo' => 1000,
    'usar_control_stock' => 1,
    'usa_vencimiento' => 0,
    'fecha_vencimiento' => '',
    'alerta_vencimiento_dias' => 30,
    'publicar_web' => 0,
    'imagen' => ''
];

$categorias = [];
$lugares = [];
$monedas = [];
$impuestos = [];
$errores = [];
$mensaje_exito = '';

try {
    $pdo = conectarDB();
    
    // Generar código automático para productos nuevos
    if (!$es_edicion) {
        $stmt = $pdo->query("SELECT MAX(id) as ultimo_id FROM productos");
        $resultado = $stmt->fetch();
        $ultimo_id = $resultado['ultimo_id'] ?? 0;
        $siguiente_id = $ultimo_id + 1;
        $producto['codigo_interno'] = 'PROD-' . str_pad($siguiente_id, 7, '0', STR_PAD_LEFT);
    }
    
    // Cargar datos necesarios
    $stmt = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT id, nombre FROM lugares ORDER BY nombre");
    $lugares = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT id, nombre, simbolo FROM monedas ORDER BY nombre");
    $monedas = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT id, nombre, porcentaje FROM impuestos ORDER BY nombre");
    $impuestos = $stmt->fetchAll();
    
    // Cargar producto si es edición
    if ($es_edicion) {
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto_db = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($producto_db) {
            $producto = array_merge($producto, $producto_db);
        }
    }
    
} catch (PDOException $e) {
    $errores[] = "Error de conexión: " . $e->getMessage();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validaciones por pestaña
        $nombre = trim($_POST['nombre'] ?? '');
        $codigo_interno = trim($_POST['codigo_interno'] ?? '');
        $codigo_barras = trim($_POST['codigo_barras'] ?? '');
        
        // Validaciones obligatorias
        if (empty($nombre)) $errores[] = "El nombre del producto es obligatorio";
        if (empty($codigo_interno)) $errores[] = "El código interno es obligatorio";
        if (empty($codigo_barras)) $errores[] = "El código de barras es obligatorio";
        if (empty($_POST['moneda_id'])) $errores[] = "La moneda es obligatoria";
        if (empty($_POST['impuesto_id'])) $errores[] = "El tipo de impuesto es obligatorio";
        if (empty($_POST['precio_compra']) || $_POST['precio_compra'] <= 0) $errores[] = "El precio de compra es obligatorio y debe ser mayor a 0";
        
        // Validar vencimiento si está activado
        $usa_vencimiento = isset($_POST['usa_vencimiento']) ? 1 : 0;
        if ($usa_vencimiento && empty($_POST['fecha_vencimiento'])) {
            $errores[] = "Si usa vencimiento, debe especificar la fecha";
        }
        
        // Verificar código único
        if (!empty($codigo_interno)) {
            $stmt = $pdo->prepare("SELECT id FROM productos WHERE codigo_interno = ? AND id != ?");
            $stmt->execute([$codigo_interno, $producto_id]);
            if ($stmt->fetch()) {
                $errores[] = "El código interno ya existe";
            }
        }
        
        if (!empty($codigo_barras)) {
            $stmt = $pdo->prepare("SELECT id FROM productos WHERE codigo_barras = ? AND id != ?");
            $stmt->execute([$codigo_barras, $producto_id]);
            if ($stmt->fetch()) {
                $errores[] = "El código de barras ya existe";
            }
        }
        
        if (empty($errores)) {
            $datos = [
                'codigo_interno' => $codigo_interno,
                'codigo_barras' => $codigo_barras,
                'nombre' => $nombre,
                'descripcion' => $_POST['descripcion'] ?? '',
                'categoria_id' => $_POST['categoria_id'] ?: null,
                'lugar_id' => $_POST['lugar_id'] ?: null,
                'unidad_medida' => $_POST['unidad_medida'] ?? 'UN',
                'factor_conversion' => floatval($_POST['factor_conversion'] ?? 1.00),
                'precio_compra' => floatval($_POST['precio_compra']),
                'precio_minorista' => floatval($_POST['precio_minorista'] ?? 0),
                'precio_mayorista' => floatval($_POST['precio_mayorista'] ?? 0),
                'utilidad_minorista' => floatval($_POST['utilidad_minorista'] ?? 30),
                'utilidad_mayorista' => floatval($_POST['utilidad_mayorista'] ?? 15),
                'moneda_id' => intval($_POST['moneda_id']),
                'impuesto_id' => intval($_POST['impuesto_id']),
                'stock' => intval($_POST['stock'] ?? 0),
                'stock_minimo' => intval($_POST['stock_minimo'] ?? 1),
                'stock_maximo' => intval($_POST['stock_maximo'] ?? 1000),
                'usar_control_stock' => isset($_POST['usar_control_stock']) ? 1 : 0,
                'usa_vencimiento' => $usa_vencimiento,
                'fecha_vencimiento' => $usa_vencimiento ? $_POST['fecha_vencimiento'] : null,
                'alerta_vencimiento_dias' => intval($_POST['alerta_vencimiento_dias'] ?? 30),
                'publicar_web' => isset($_POST['publicar_web']) ? 1 : 0
            ];
            
            if ($es_edicion) {
                $sql = "UPDATE productos SET codigo_interno=?, codigo_barras=?, nombre=?, descripcion=?, categoria_id=?, lugar_id=?, unidad_medida=?, factor_conversion=?, precio_compra=?, precio_minorista=?, precio_mayorista=?, utilidad_minorista=?, utilidad_mayorista=?, moneda_id=?, impuesto_id=?, stock=?, stock_minimo=?, stock_maximo=?, usar_control_stock=?, usa_vencimiento=?, fecha_vencimiento=?, alerta_vencimiento_dias=?, publicar_web=? WHERE id=?";
                $params = array_values($datos);
                $params[] = $producto_id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $mensaje_exito = "Producto actualizado exitosamente";
            } else {
                $sql = "INSERT INTO productos (codigo_interno, codigo_barras, nombre, descripcion, categoria_id, lugar_id, unidad_medida, factor_conversion, precio_compra, precio_minorista, precio_mayorista, utilidad_minorista, utilidad_mayorista, moneda_id, impuesto_id, stock, stock_minimo, stock_maximo, usar_control_stock, usa_vencimiento, fecha_vencimiento, alerta_vencimiento_dias, publicar_web) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($datos));
                $mensaje_exito = "Producto creado exitosamente";
            }
        }
    } catch (PDOException $e) {
        $errores[] = "Error de base de datos: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $es_edicion ? 'Editar' : 'Nuevo'; ?> Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0d6efd;
            --success: #198754;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .container-form {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        .form-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .form-header {
            background: linear-gradient(135deg, var(--primary), #0056b3);
            color: white;
            padding: 25px 30px;
        }
        .nav-tabs-custom {
            background: #f8f9fa;
            padding: 0 20px;
            border-bottom: none;
        }
        .nav-tabs-custom .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 15px 20px;
            position: relative;
        }
        .nav-tabs-custom .nav-link.active {
            background: var(--primary);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .nav-tabs-custom .nav-link.completed::after {
            content: '✓';
            position: absolute;
            top: 5px;
            right: 5px;
            background: var(--success);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .tab-content {
            padding: 30px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        .btn-navigation {
            min-width: 120px;
        }
        .step-indicator {
            text-align: center;
            margin-bottom: 20px;
        }
        .step-indicator .step {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            line-height: 30px;
            margin: 0 5px;
            font-weight: bold;
        }
        .step-indicator .step.active {
            background: var(--primary);
            color: white;
        }
        .step-indicator .step.completed {
            background: var(--success);
            color: white;
        }
        .required-field {
            border-left: 4px solid var(--danger);
            padding-left: 10px;
        }
        
        /* Estilos para Modal de Escáner */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        #reader {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .metodo-captura {
            transition: all 0.3s ease;
            border-radius: 10px;
            padding: 15px;
            margin: 5px;
            border: 2px solid transparent;
        }
        
        .metodo-captura:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-color: currentColor;
        }
        
        .area-captura {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
            display: none;
        }
        
        .qr-code-container {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 2px dashed #28a745;
        }
        
        .pantalla-inicial {
            text-align: center;
            padding: 20px;
        }
        
        .icono-metodo {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: block;
        }
        
        .card-metodo {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            border-radius: 10px;
            padding: 15px;
            height: 100%;
        }
        
        .card-metodo:hover {
            border-color: #0d6efd;
            transform: translateY(-5px);
        }
        
        .estado-cargando {
            text-align: center;
            padding: 20px;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
        
        .url-container {
            background: #e9ecef;
            padding: 10px;
            border-radius: 8px;
            font-family: monospace;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container-form">
        <div class="form-card">
            <!-- Header -->
            <div class="form-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">
                            <i class="bi bi-<?php echo $es_edicion ? 'pencil-square' : 'plus-circle'; ?> me-2"></i>
                            <?php echo $es_edicion ? 'Editar' : 'Nuevo'; ?> Producto
                        </h2>
                        <p class="mb-0 opacity-75">Gestión completa de inventario</p>
                    </div>
                    <a href="productos.php" class="btn btn-light">
                        <i class="bi bi-arrow-left me-1"></i>Volver al Listado
                    </a>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger mx-4 mt-3">
                    <h6><i class="bi bi-exclamation-triangle me-2"></i>Errores Encontrados:</h6>
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensaje_exito)): ?>
                <div class="alert alert-success mx-4 mt-3">
                    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($mensaje_exito); ?>
                </div>
            <?php endif; ?>

            <!-- Indicador de pasos -->
            <div class="step-indicator mt-3">
                <span class="step active" data-step="1">1</span>
                <span class="step" data-step="2">2</span>
                <span class="step" data-step="3">3</span>
                <span class="step" data-step="4">4</span>
                <span class="step" data-step="5">5</span>
                <span class="step" data-step="6">6</span>
            </div>

            <form method="POST" id="formProducto" enctype="multipart/form-data">
                <!-- Pestañas -->
                <ul class="nav nav-tabs nav-tabs-custom" id="productTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                            <i class="bi bi-info-circle me-2"></i>General
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="impuestos-tab" data-bs-toggle="tab" data-bs-target="#impuestos" type="button" role="tab">
                            <i class="bi bi-currency-dollar me-2"></i>Impuestos/Moneda
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="precios-tab" data-bs-toggle="tab" data-bs-target="#precios" type="button" role="tab">
                            <i class="bi bi-calculator me-2"></i>Precios
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stock" type="button" role="tab">
                            <i class="bi bi-boxes me-2"></i>Stock
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="vencimientos-tab" data-bs-toggle="tab" data-bs-target="#vencimientos" type="button" role="tab">
                            <i class="bi bi-calendar-event me-2"></i>Vencimientos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="proveedores-tab" data-bs-toggle="tab" data-bs-target="#proveedores" type="button" role="tab">
                            <i class="bi bi-building me-2"></i>Proveedores
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="productTabContent">
                    <!-- PESTAÑA 1: GENERAL -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel">
                        <h4 class="mb-4"><i class="bi bi-info-circle text-primary me-2"></i>Información General</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3 required-field">
                                    <label class="form-label"><strong>Código Interno *</strong></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="codigo_interno" 
                                               value="<?php echo htmlspecialchars($producto['codigo_interno']); ?>" required>
                                        <?php if (!$es_edicion): ?>
                                        <button type="button" class="btn btn-outline-primary" onclick="generarCodigo()">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 required-field">
                                    <label class="form-label"><strong>Código de Barras *</strong></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="codigo_barras" id="codigoBarras"
                                               value="<?php echo htmlspecialchars($producto['codigo_barras']); ?>" required>
                                        <button type="button" class="btn btn-outline-primary" onclick="activarEscaner()" title="Escanear con celular">
                                            <i class="bi bi-camera"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Puedes escanear el código con tu celular</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 required-field">
                            <label class="form-label"><strong>Nombre del Producto *</strong></label>
                            <input type="text" class="form-control" name="nombre" 
                                   value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="3"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Categoría</label>
                                    <div class="input-group">
                                        <select class="form-select" name="categoria_id">
                                            <option value="">Seleccionar categoría</option>
                                            <?php foreach ($categorias as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" 
                                                        <?php echo ($producto['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalCategoria">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Lugar</label>
                                    <div class="input-group">
                                        <select class="form-select" name="lugar_id">
                                            <option value="">Seleccionar lugar</option>
                                            <?php foreach ($lugares as $lugar): ?>
                                                <option value="<?php echo $lugar['id']; ?>" 
                                                        <?php echo ($producto['lugar_id'] == $lugar['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($lugar['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalLugar">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Unidad de Medida</label>
                                    <select class="form-select" name="unidad_medida">
                                        <option value="UN" <?php echo ($producto['unidad_medida'] == 'UN') ? 'selected' : ''; ?>>Unidad</option>
                                        <option value="KG" <?php echo ($producto['unidad_medida'] == 'KG') ? 'selected' : ''; ?>>Kilogramo</option>
                                        <option value="LT" <?php echo ($producto['unidad_medida'] == 'LT') ? 'selected' : ''; ?>>Litro</option>
                                        <option value="MT" <?php echo ($producto['unidad_medida'] == 'MT') ? 'selected' : ''; ?>>Metro</option>
                                        <option value="M2" <?php echo ($producto['unidad_medida'] == 'M2') ? 'selected' : ''; ?>>Metro Cuadrado</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="publicar_web" 
                                       <?php echo $producto['publicar_web'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">
                                    <strong>Publicar en tienda web</strong>
                                </label>
                                <div class="form-text">Marque para mostrar este producto en la tienda online</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-primary btn-navigation" onclick="siguientePestana()">
                                Siguiente <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- PESTAÑA 2: IMPUESTOS/MONEDA -->
                    <div class="tab-pane fade" id="impuestos" role="tabpanel">
                        <h4 class="mb-4"><i class="bi bi-currency-dollar text-primary me-2"></i>Configuración Fiscal</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3 required-field">
                                    <label class="form-label"><strong>Moneda *</strong></label>
                                    <select class="form-select" name="moneda_id" required>
                                        <option value="">Seleccionar moneda</option>
                                        <?php foreach ($monedas as $moneda): ?>
                                            <option value="<?php echo $moneda['id']; ?>" 
                                                    <?php echo ($producto['moneda_id'] == $moneda['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($moneda['simbolo'] . ' - ' . $moneda['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 required-field">
                                    <label class="form-label"><strong>Tipo de Impuesto *</strong></label>
                                    <div class="input-group">
                                        <select class="form-select" name="impuesto_id" required>
                                            <option value="">Seleccionar impuesto</option>
                                            <?php foreach ($impuestos as $impuesto): ?>
                                                <option value="<?php echo $impuesto['id']; ?>" 
                                                        <?php echo ($producto['impuesto_id'] == $impuesto['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($impuesto['nombre'] . ' (' . $impuesto['porcentaje'] . '%)'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalImpuesto">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-secondary btn-navigation" onclick="anteriorPestana()">
                                <i class="bi bi-arrow-left me-2"></i>Anterior
                            </button>
                            <button type="button" class="btn btn-primary btn-navigation" onclick="siguientePestana()">
                                Siguiente <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- PESTAÑA 3: PRECIOS -->
                    <div class="tab-pane fade" id="precios" role="tabpanel">
                        <h4 class="mb-4"><i class="bi bi-calculator text-primary me-2"></i>Gestión de Precios</h4>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3 required-field">
                                    <label class="form-label"><strong>Precio de Compra *</strong></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="precio_compra" 
                                               value="<?php echo $producto['precio_compra']; ?>" step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Precio Minorista</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="precio_minorista" 
                                               value="<?php echo $producto['precio_minorista']; ?>" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Precio Mayorista</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="precio_mayorista" 
                                               value="<?php echo $producto['precio_mayorista']; ?>" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Utilidad Minorista (%)</label>
                                    <input type="number" class="form-control" name="utilidad_minorista" 
                                           value="<?php echo $producto['utilidad_minorista']; ?>" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Utilidad Mayorista (%)</label>
                                    <input type="number" class="form-control" name="utilidad_mayorista" 
                                           value="<?php echo $producto['utilidad_mayorista']; ?>" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-secondary btn-navigation" onclick="anteriorPestana()">
                                <i class="bi bi-arrow-left me-2"></i>Anterior
                            </button>
                            <button type="button" class="btn btn-primary btn-navigation" onclick="siguientePestana()">
                                Siguiente <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- PESTAÑA 4: STOCK -->
                    <div class="tab-pane fade" id="stock" role="tabpanel">
                        <h4 class="mb-4"><i class="bi bi-boxes text-primary me-2"></i>Control de Stock</h4>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Stock Actual</label>
                                    <input type="number" class="form-control" name="stock" 
                                           value="<?php echo $producto['stock']; ?>" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Stock Mínimo</label>
                                    <input type="number" class="form-control" name="stock_minimo" 
                                           value="<?php echo $producto['stock_minimo']; ?>" min="1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Stock Máximo</label>
                                    <input type="number" class="form-control" name="stock_maximo" 
                                           value="<?php echo $producto['stock_maximo']; ?>" min="1">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="usar_control_stock" 
                                       <?php echo $producto['usar_control_stock'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">
                                    Usar control de stock automático
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-secondary btn-navigation" onclick="anteriorPestana()">
                                <i class="bi bi-arrow-left me-2"></i>Anterior
                            </button>
                            <button type="button" class="btn btn-primary btn-navigation" onclick="siguientePestana()">
                                Siguiente <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- PESTAÑA 5: VENCIMIENTOS -->
                    <div class="tab-pane fade" id="vencimientos" role="tabpanel">
                        <h4 class="mb-4"><i class="bi bi-calendar-event text-primary me-2"></i>Control de Vencimientos</h4>
                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="usa_vencimiento" id="usaVencimiento"
                                       <?php echo $producto['usa_vencimiento'] ? 'checked' : ''; ?> onchange="toggleVencimiento()">
                                <label class="form-check-label" for="usaVencimiento">
                                    <strong>Este producto usa vencimiento</strong>
                                </label>
                                <div class="form-text">Marque esta opción solo si el producto tiene fecha de vencimiento</div>
                            </div>
                        </div>

                        <div id="camposVencimiento" style="<?php echo $producto['usa_vencimiento'] ? '' : 'display: none;'; ?>">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fecha de Vencimiento</label>
                                        <input type="date" class="form-control" name="fecha_vencimiento" 
                                               value="<?php echo $producto['fecha_vencimiento']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Alerta de Vencimiento (días)</label>
                                        <input type="number" class="form-control" name="alerta_vencimiento_dias" 
                                               value="<?php echo $producto['alerta_vencimiento_dias']; ?>" min="1">
                                        <div class="form-text">Días antes del vencimiento para mostrar alerta</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-secondary btn-navigation" onclick="anteriorPestana()">
                                <i class="bi bi-arrow-left me-2"></i>Anterior
                            </button>
                            <button type="button" class="btn btn-primary btn-navigation" onclick="siguientePestana()">
                                Siguiente <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- PESTAÑA 6: PROVEEDORES -->
                    <div class="tab-pane fade" id="proveedores" role="tabpanel">
                        <h4 class="mb-4"><i class="bi bi-building text-primary me-2"></i>Información de Proveedores</h4>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            La gestión de proveedores se configurará en una versión posterior del sistema.
                            Por ahora, puede guardar el producto con la información básica.
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-secondary btn-navigation" onclick="anteriorPestana()">
                                <i class="bi bi-arrow-left me-2"></i>Anterior
                            </button>
                            <button type="submit" class="btn btn-success btn-navigation">
                                <i class="bi bi-save me-2"></i>Guardar Producto
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Nueva Categoría -->
    <div class="modal fade" id="modalCategoria" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre de la Categoría</label>
                        <input type="text" class="form-control" id="nombreCategoria">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarCategoria()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Lugar -->
    <div class="modal fade" id="modalLugar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Lugar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Lugar</label>
                        <input type="text" class="form-control" id="nombreLugar">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarLugar()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Impuesto -->
    <div class="modal fade" id="modalImpuesto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Tipo de Impuesto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Impuesto</label>
                        <input type="text" class="form-control" id="nombreImpuesto" placeholder="Ej: IVA 21%">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Porcentaje (%)</label>
                        <input type="number" class="form-control" id="porcentajeImpuesto" step="0.01" min="0" max="100" placeholder="21.00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarImpuesto()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Escáner de Códigos -->
    <div class="modal fade" id="modalEscaner" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-upc-scan me-2"></i>Capturar Código de Barras
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Selector de método de captura -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="mb-3">
                                <i class="bi bi-gear me-2"></i>Selecciona el método de captura:
                            </h6>
                            <div class="row g-3">
                                <!-- Webcam PC -->
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-outline-primary w-100 h-100 metodo-captura" data-metodo="webcam">
                                        <div class="text-center">
                                            <i class="bi bi-camera-video display-6"></i>
                                            <div class="mt-2">
                                                <strong>Webcam PC</strong>
                                                <small class="d-block text-muted">Cámara de la computadora</small>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                                
                                <!-- Celular WiFi -->
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-outline-success w-100 h-100 metodo-captura" data-metodo="celular">
                                        <div class="text-center">
                                            <i class="bi bi-phone display-6"></i>
                                            <div class="mt-2">
                                                <strong>Celular WiFi</strong>
                                                <small class="d-block text-muted">Android/iPhone</small>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                                
                                <!-- Lector Físico -->
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-outline-warning w-100 h-100 metodo-captura" data-metodo="lector">
                                        <div class="text-center">
                                            <i class="bi bi-upc display-6"></i>
                                            <div class="mt-2">
                                                <strong>Lector USB</strong>
                                                <small class="d-block text-muted">Escáner físico</small>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                                
                                <!-- Entrada Manual -->
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-outline-secondary w-100 h-100 metodo-captura" data-metodo="manual">
                                        <div class="text-center">
                                            <i class="bi bi-keyboard display-6"></i>
                                            <div class="mt-2">
                                                <strong>Manual</strong>
                                                <small class="d-block text-muted">Escribir código</small>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Área de contenido dinámico -->
                    <div id="areaCaptura">
                        <!-- Pantalla inicial -->
                        <div id="pantallaInicial" class="text-center">
                            <i class="bi bi-arrow-up-circle display-1 text-muted"></i>
                            <h5 class="mt-3 text-muted">Selecciona un método de captura arriba</h5>
                            <p class="text-muted">Elige la opción que prefieras para capturar el código de barras</p>
                        </div>
                        
                        <!-- Webcam -->
                        <div id="areaWebcam" style="display: none;">
                            <div class="row">
                                <div class="col-md-8">
                                    <div id="selectorCamara" class="mb-3" style="display: none;">
                                        <label class="form-label">
                                            <i class="bi bi-camera-video me-2"></i>Cámara:
                                        </label>
                                        <select class="form-select" id="listaCamaras">
                                            <option value="">Detectando cámaras...</option>
                                        </select>
                                    </div>
                                    <div id="reader" style="width: 100%; max-width: 400px; margin: 0 auto;"></div>
                                    <div id="estadoEscaner" class="mt-3 text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Iniciando cámara...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Iniciando escáner...</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="alert alert-info">
                                        <h6><i class="bi bi-info-circle me-2"></i>Instrucciones:</h6>
                                        <ul class="mb-0 small">
                                            <li>📏 Mantén el código a <strong>15-25 cm</strong></li>
                                            <li>💡 Asegúrate de tener <strong>buena luz</strong></li>
                                            <li>🎯 <strong>Centra el código</strong> en el área</li>
                                            <li>⚡ La detección es <strong>automática</strong></li>
                                        </ul>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary w-100 mb-2" onclick="cambiarCamara()">
                                        <i class="bi bi-arrow-repeat me-2"></i>Cambiar Cámara
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Celular WiFi -->
                        <div id="areaCelular" style="display: none;">
                            <div class="alert alert-primary">
                                <h6><i class="bi bi-phone me-2"></i>Usar Celular via WiFi:</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>📱 En tu celular:</strong>
                                        <ol class="mb-0 small">
                                            <li>Conecta a la misma WiFi que tu PC</li>
                                            <li>Abre Chrome/Safari</li>
                                            <li>Ve a: <code id="urlCelular">http://192.168.0.103/sistemadgestion5/modulos/Inventario/producto_form.php</code></li>
                                            <li>Busca este mismo formulario</li>
                                            <li>Usa el botón de cámara 📷</li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>🔗 QR para acceso rápido:</strong>
                                        <div id="qrCodeCelular" class="text-center mt-2">
                                            <div style="width: 150px; height: 150px; border: 2px dashed #ccc; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                                                <span class="small text-muted">QR Code aquí</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Lector Físico -->
                        <div id="areaLector" style="display: none;">
                            <div class="alert alert-warning">
                                <h6><i class="bi bi-upc me-2"></i>Usar Lector Físico USB/Bluetooth:</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>🔌 Configuración:</strong>
                                        <ol class="mb-0 small">
                                            <li>Conecta tu lector USB o Bluetooth</li>
                                            <li>Asegúrate que esté configurado como teclado</li>
                                            <li>Haz clic en el campo de abajo</li>
                                            <li>Escanea el código directamente</li>
                                            <li>El código aparecerá automáticamente</li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>📝 Campo de captura:</strong>
                                        <div class="input-group mt-2">
                                            <span class="input-group-text"><i class="bi bi-upc"></i></span>
                                            <input type="text" class="form-control" id="campoLector" placeholder="Escanea aquí con tu lector...">
                                            <button type="button" class="btn btn-success" onclick="usarCodigoLector()">
                                                <i class="bi bi-check2"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">El lector escribirá automáticamente en este campo</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Entrada Manual -->
                        <div id="areaManual" style="display: none;">
                            <div class="alert alert-secondary">
                                <h6><i class="bi bi-keyboard me-2"></i>Entrada Manual:</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>✍️ Escribe el código:</strong>
                                        <div class="input-group mt-2">
                                            <span class="input-group-text"><i class="bi bi-123"></i></span>
                                            <input type="text" class="form-control" id="codigoManual" placeholder="Ejemplo: 7794900001234">
                                            <button type="button" class="btn btn-primary" onclick="usarCodigoManual()">
                                                <i class="bi bi-check2"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Presiona Enter o clic en ✓ para usar</small>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>📋 Tipos de código comunes:</strong>
                                        <ul class="mb-0 small">
                                            <li><strong>EAN-13:</strong> 13 dígitos (ej: 7794900001234)</li>
                                            <li><strong>EAN-8:</strong> 8 dígitos (ej: 12345678)</li>
                                            <li><strong>UPC-A:</strong> 12 dígitos (ej: 123456789012)</li>
                                            <li><strong>Code 128:</strong> Letras y números</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cerrar
                    </button>
                    <button type="button" class="btn btn-info" onclick="mostrarAyuda()">
                        <i class="bi bi-question-circle me-2"></i>Ayuda
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Múltiples CDN para máxima disponibilidad -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/minified/html5-qrcode.min.js" onerror="cargarCDNAlternativo()"></script>
    <script>
        let pestanaActual = 1;
        let html5QrcodeScanner = null;
        const totalPestanas = 6;
        let libreriaQRCargada = false;

        // Lista de CDN alternativos
        const cdnAlternativos = [
            'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/minified/html5-qrcode.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js',
            'https://cdn.skypack.dev/html5-qrcode@2.3.8/minified/html5-qrcode.min.js'
        ];
        let cdnActual = 0;

        // Cargar CDN alternativo
        function cargarCDNAlternativo() {
            if (cdnActual < cdnAlternativos.length) {
                console.log(`Intentando CDN alternativo ${cdnActual + 1}...`);
                const script = document.createElement('script');
                script.src = cdnAlternativos[cdnActual];
                script.onload = () => {
                    libreriaQRCargada = true;
                    console.log(`CDN alternativo ${cdnActual + 1} cargado exitosamente`);
                };
                script.onerror = () => {
                    cdnActual++;
                    if (cdnActual < cdnAlternativos.length) {
                        cargarCDNAlternativo();
                    } else {
                        console.error('Todos los CDN fallaron, usando modo offline');
                        mostrarModoOffline();
                    }
                };
                document.head.appendChild(script);
                cdnActual++;
            }
        }

        // Mostrar opciones cuando no hay conexión
        function mostrarModoOffline() {
            const modal = document.getElementById('modalEscaner');
            if (modal) {
                modal.querySelector('.modal-body').innerHTML = `
                    <div class="alert alert-warning">
                        <h5><i class="bi bi-wifi-off me-2"></i>Sin Conexión a Internet</h5>
                        <p>No se pudo cargar la librería de escáner desde internet. Puedes usar estas alternativas:</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-warning" onclick="mostrarAreaLector()">
                                <i class="bi bi-upc-scan me-2"></i>Usar Lector USB/Bluetooth
                            </button>
                            <button class="btn btn-secondary" onclick="mostrarAreaManual()">
                                <i class="bi bi-keyboard me-2"></i>Ingresar Código Manualmente
                            </button>
                            <button class="btn btn-info" onclick="reintentar()">
                                <i class="bi bi-arrow-clockwise me-2"></i>Reintentar Conexión
                            </button>
                        </div>
                        <hr>
                        <small class="text-muted">
                            <strong>Tip:</strong> Los lectores USB funcionan sin conexión a internet
                        </small>
                    </div>
                `;
            }
        }

        // Reintentar carga
        function reintentar() {
            cdnActual = 0;
            libreriaQRCargada = false;
            cargarCDNAlternativo();
            mostrarNotificacion('Reintentando conexión...', 'info');
        }

        // Verificar que la librería esté cargada
        function verificarLibreriaQR() {
            return new Promise((resolve, reject) => {
                // Si ya está cargada, resolver inmediatamente
                if (typeof Html5QrcodeScanner !== 'undefined') {
                    libreriaQRCargada = true;
                    resolve();
                    return;
                }

                // Si ya sabemos que falló, rechazar inmediatamente
                if (cdnActual >= cdnAlternativos.length && !libreriaQRCargada) {
                    reject(new Error('No hay conexión a internet y la librería no está disponible'));
                    return;
                }

                // Esperar a que se cargue
                let attempts = 0;
                const checkInterval = setInterval(() => {
                    attempts++;
                    
                    if (typeof Html5QrcodeScanner !== 'undefined') {
                        clearInterval(checkInterval);
                        libreriaQRCargada = true;
                        resolve();
                    } else if (attempts > 100) { // 10 segundos máximo
                        clearInterval(checkInterval);
                        
                        // Si no se cargó y no hemos agotado los CDN, intentar siguiente
                        if (cdnActual < cdnAlternativos.length) {
                            cargarCDNAlternativo();
                            // Reiniciar verificación
                            verificarLibreriaQR().then(resolve).catch(reject);
                        } else {
                            reject(new Error('No se pudo cargar la librería de escáner. Verifica tu conexión a internet.'));
                        }
                    }
                }, 100);
            });
        }

        // Eliminar función cargarLibreriaAlternativa (ya no es necesaria)
        // function cargarLibreriaAlternativa() { ... }

        function generarCodigo() {
            fetch('../../obtener_ultimo_codigo.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('input[name="codigo_interno"]').value = data.codigo;
                        mostrarNotificacion('Código generado: ' + data.codigo, 'success');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarNotificacion('Error al generar código', 'error');
                });
        }

        function siguientePestana() {
            if (validarPestanaActual()) {
                if (pestanaActual < totalPestanas) {
                    marcarPestanaCompletada(pestanaActual);
                    pestanaActual++;
                    cambiarPestana(pestanaActual);
                    actualizarIndicador();
                }
            }
        }

        function anteriorPestana() {
            if (pestanaActual > 1) {
                pestanaActual--;
                cambiarPestana(pestanaActual);
                actualizarIndicador();
            }
        }

        function cambiarPestana(numero) {
            const pestanas = ['general', 'impuestos', 'precios', 'stock', 'vencimientos', 'proveedores'];
            const tab = new bootstrap.Tab(document.getElementById(pestanas[numero - 1] + '-tab'));
            tab.show();
        }

        function validarPestanaActual() {
            let valido = true;
            let campos = [];

            switch (pestanaActual) {
                case 1: // General
                    campos = ['codigo_interno', 'codigo_barras', 'nombre'];
                    break;
                case 2: // Impuestos
                    campos = ['moneda_id', 'impuesto_id'];
                    break;
                case 3: // Precios
                    campos = ['precio_compra'];
                    break;
                case 5: // Vencimientos
                    if (document.getElementById('usaVencimiento').checked) {
                        campos = ['fecha_vencimiento'];
                    }
                    break;
            }

            campos.forEach(campo => {
                const input = document.querySelector(`[name="${campo}"]`);
                if (input && (!input.value || input.value.trim() === '')) {
                    input.classList.add('is-invalid');
                    valido = false;
                } else if (input) {
                    input.classList.remove('is-invalid');
                }
            });

            if (!valido) {
                mostrarNotificacion('Complete todos los campos obligatorios antes de continuar', 'error');
            }

            return valido;
        }

        function marcarPestanaCompletada(numero) {
            const pestanas = ['general', 'impuestos', 'precios', 'stock', 'vencimientos', 'proveedores'];
            const tab = document.getElementById(pestanas[numero - 1] + '-tab');
            tab.classList.add('completed');
        }

        function actualizarIndicador() {
            document.querySelectorAll('.step').forEach((step, index) => {
                const numero = index + 1;
                step.classList.remove('active', 'completed');
                
                if (numero < pestanaActual) {
                    step.classList.add('completed');
                } else if (numero === pestanaActual) {
                    step.classList.add('active');
                }
            });
        }

        function toggleVencimiento() {
            const campos = document.getElementById('camposVencimiento');
            const checkbox = document.getElementById('usaVencimiento');
            campos.style.display = checkbox.checked ? 'block' : 'none';
            
            if (!checkbox.checked) {
                // Limpiar campos si se desactiva
                document.querySelector('[name="fecha_vencimiento"]').value = '';
            }
        }

        function mostrarNotificacion(mensaje, tipo) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${tipo === 'error' ? 'danger' : 'success'} position-fixed`;
            alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alert.innerHTML = `
                <i class="bi bi-${tipo === 'error' ? 'exclamation-triangle' : 'check-circle'} me-2"></i>
                ${mensaje}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
        }

        function guardarCategoria() {
            const nombre = document.getElementById('nombreCategoria').value.trim();
            if (!nombre) {
                mostrarNotificacion('Ingrese un nombre para la categoría', 'error');
                return;
            }

            const data = {
                nombre: nombre
            };

            fetch('../../ajax/guardar_categoria.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.querySelector('select[name="categoria_id"]');
                    const option = new Option(nombre, data.id, true, true);
                    select.add(option);
                    
                    bootstrap.Modal.getInstance(document.getElementById('modalCategoria')).hide();
                    document.getElementById('nombreCategoria').value = '';
                    mostrarNotificacion('Categoría creada exitosamente', 'success');
                } else {
                    mostrarNotificacion('Error: ' + data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('Error al guardar categoría', 'error');
            });
        }

        function guardarImpuesto() {
            const nombre = document.getElementById('nombreImpuesto').value.trim();
            const porcentaje = document.getElementById('porcentajeImpuesto').value;
            
            if (!nombre) {
                mostrarNotificacion('Ingrese un nombre para el impuesto', 'error');
                return;
            }
            
            if (!porcentaje || porcentaje < 0 || porcentaje > 100) {
                mostrarNotificacion('Ingrese un porcentaje válido (0-100)', 'error');
                return;
            }

            const data = {
                nombre: nombre,
                porcentaje: parseFloat(porcentaje)
            };

            fetch('../../ajax/guardar_impuesto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.querySelector('select[name="impuesto_id"]');
                    const option = new Option(`${nombre} (${porcentaje}%)`, data.id, true, true);
                    select.add(option);
                    
                    bootstrap.Modal.getInstance(document.getElementById('modalImpuesto')).hide();
                    document.getElementById('nombreImpuesto').value = '';
                    document.getElementById('porcentajeImpuesto').value = '';
                    mostrarNotificacion('Impuesto creado exitosamente', 'success');
                } else {
                    mostrarNotificacion('Error: ' + data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('Error al guardar impuesto', 'error');
            });
        }

        function activarEscaner() {
            const modal = new bootstrap.Modal(document.getElementById('modalEscaner'));
            modal.show();
            
            // Resetear estado
            mostrarPantallaInicial();
            
            // Detectar estado de conexión y ajustar interfaz
            actualizarEstadoConexion();
            
            // Configurar event listeners para los métodos
            document.querySelectorAll('.metodo-captura').forEach(btn => {
                btn.addEventListener('click', function() {
                    const metodo = this.dataset.metodo;
                    seleccionarMetodoCaptura(metodo);
                });
            });
            
            // Generar URL para celular
            const ip = window.location.hostname === 'localhost' ? '192.168.0.103' : window.location.hostname;
            document.getElementById('urlCelular').textContent = `http://${ip}${window.location.pathname}`;
        }

        function actualizarEstadoConexion() {
            const sinInternet = !navigator.onLine || !libreriaQRCargada;
            
            if (sinInternet) {
                // Deshabilitar opciones que requieren internet
                const webcamBtn = document.querySelector('[data-metodo="webcam"]');
                const celularBtn = document.querySelector('[data-metodo="celular"]');
                
                if (webcamBtn) {
                    webcamBtn.classList.add('disabled');
                    webcamBtn.innerHTML = `
                        <i class="icono-metodo bi bi-camera-video-off"></i>
                        <div>
                            <strong>Webcam PC</strong><br>
                            <small class="text-muted">Sin conexión</small>
                        </div>
                    `;
                }
                
                if (celularBtn) {
                    celularBtn.classList.add('disabled');
                    celularBtn.innerHTML = `
                        <i class="icono-metodo bi bi-phone"></i>
                        <div>
                            <strong>Celular WiFi</strong><br>
                            <small class="text-muted">Sin conexión</small>
                        </div>
                    `;
                }
                
                // Destacar opciones offline
                const lectorBtn = document.querySelector('[data-metodo="lector"]');
                const manualBtn = document.querySelector('[data-metodo="manual"]');
                
                if (lectorBtn) {
                    lectorBtn.classList.add('border-warning', 'border-2');
                    lectorBtn.innerHTML = `
                        <i class="icono-metodo bi bi-upc-scan text-warning"></i>
                        <div>
                            <strong>Lector USB</strong><br>
                            <small class="text-success">✅ Recomendado</small>
                        </div>
                    `;
                }
                
                if (manualBtn) {
                    manualBtn.classList.add('border-success', 'border-2');
                    manualBtn.innerHTML = `
                        <i class="icono-metodo bi bi-keyboard text-success"></i>
                        <div>
                            <strong>Manual</strong><br>
                            <small class="text-success">✅ Disponible</small>
                        </div>
                    `;
                }
                
                // Mostrar mensaje de estado
                const pantallaInicial = document.getElementById('pantallaInicial');
                if (pantallaInicial) {
                    const alerta = document.createElement('div');
                    alerta.className = 'alert alert-info mb-3';
                    alerta.innerHTML = `
                        <i class="bi bi-wifi-off me-2"></i>
                        <strong>Modo Sin Conexión:</strong> Usa el lector USB o entrada manual
                    `;
                    pantallaInicial.insertBefore(alerta, pantallaInicial.firstChild);
                }
            }
        }

        function mostrarPantallaInicial() {
            // Ocultar todas las áreas
            document.getElementById('areaWebcam').style.display = 'none';
            document.getElementById('areaCelular').style.display = 'none';
            document.getElementById('areaLector').style.display = 'none';
            document.getElementById('areaManual').style.display = 'none';
            
            // Mostrar pantalla inicial
            document.getElementById('pantallaInicial').style.display = 'block';
            
            // Resetear botones
            document.querySelectorAll('.metodo-captura').forEach(btn => {
                btn.classList.remove('btn-primary', 'btn-success', 'btn-warning', 'btn-secondary');
                btn.classList.add('btn-outline-primary', 'btn-outline-success', 'btn-outline-warning', 'btn-outline-secondary');
            });
            
            // Limpiar escáner si existe
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
            }
        }

        function seleccionarMetodoCaptura(metodo) {
            // Ocultar pantalla inicial
            document.getElementById('pantallaInicial').style.display = 'none';
            
            // Resetear todas las áreas
            document.getElementById('areaWebcam').style.display = 'none';
            document.getElementById('areaCelular').style.display = 'none';
            document.getElementById('areaLector').style.display = 'none';
            document.getElementById('areaManual').style.display = 'none';
            
            // Resetear botones
            document.querySelectorAll('.metodo-captura').forEach(btn => {
                btn.classList.remove('btn-primary', 'btn-success', 'btn-warning', 'btn-secondary');
                if (btn.dataset.metodo === 'webcam') btn.classList.add('btn-outline-primary');
                if (btn.dataset.metodo === 'celular') btn.classList.add('btn-outline-success');
                if (btn.dataset.metodo === 'lector') btn.classList.add('btn-outline-warning');
                if (btn.dataset.metodo === 'manual') btn.classList.add('btn-outline-secondary');
            });
            
            // Activar botón seleccionado
            const btnSeleccionado = document.querySelector(`[data-metodo="${metodo}"]`);
            btnSeleccionado.classList.remove('btn-outline-primary', 'btn-outline-success', 'btn-outline-warning', 'btn-outline-secondary');
            if (metodo === 'webcam') btnSeleccionado.classList.add('btn-primary');
            if (metodo === 'celular') btnSeleccionado.classList.add('btn-success');
            if (metodo === 'lector') btnSeleccionado.classList.add('btn-warning');
            if (metodo === 'manual') btnSeleccionado.classList.add('btn-secondary');
            
            // Mostrar área correspondiente
            switch (metodo) {
                case 'webcam':
                    mostrarAreaWebcam();
                    break;
                case 'celular':
                    mostrarAreaCelular();
                    break;
                case 'lector':
                    mostrarAreaLector();
                    break;
                case 'manual':
                    mostrarAreaManual();
                    break;
            }
        }

        function mostrarAreaWebcam() {
            document.getElementById('areaWebcam').style.display = 'block';
            const estadoEscaner = document.getElementById('estadoEscaner');
            estadoEscaner.style.display = 'block';
            estadoEscaner.innerHTML = `
                <div class="estado-cargando">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2 mb-0">Inicializando escáner...</p>
                    <small class="text-muted">Verificando librería y permisos de cámara</small>
                </div>
            `;
            setTimeout(() => {
                detectarCamaras();
            }, 500);
        }

        function mostrarAreaCelular() {
            document.getElementById('areaCelular').style.display = 'block';
            
            // Verificar si hay conexión
            if (!navigator.onLine) {
                document.getElementById('areaCelular').innerHTML = `
                    <div class="alert alert-warning">
                        <h6><i class="bi bi-wifi-off me-2"></i>Sin Conexión</h6>
                        <p>Para usar la cámara del celular necesitas conexión a internet.</p>
                        <button class="btn btn-warning" onclick="seleccionarMetodoCaptura('lector')">
                            <i class="bi bi-upc-scan me-2"></i>Usar Lector USB en su lugar
                        </button>
                    </div>
                `;
                return;
            }
            
            // Generar URL para celular
            const ip = window.location.hostname === 'localhost' ? '192.168.0.103' : window.location.hostname;
            const urlCelular = `http://${ip}${window.location.pathname}`;
            
            document.getElementById('areaCelular').innerHTML = `
                <div class="qr-code-container">
                    <h6><i class="bi bi-phone me-2"></i>Acceso desde Celular</h6>
                    <div class="url-container mb-3">
                        <small>Abre esta URL en tu celular:</small><br>
                        <span id="urlCelular">${urlCelular}</span>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" onclick="copiarURL()">
                            <i class="bi bi-clipboard me-2"></i>Copiar URL
                        </button>
                        <button class="btn btn-info" onclick="abrirEnCelular()">
                            <i class="bi bi-qr-code me-2"></i>Generar QR Code
                        </button>
                    </div>
                </div>
            `;
            
            mostrarNotificacion('Abre la URL en tu celular para usar la cámara', 'info');
        }

        function copiarURL() {
            const urlElement = document.getElementById('urlCelular');
            if (urlElement) {
                navigator.clipboard.writeText(urlElement.textContent).then(() => {
                    mostrarNotificacion('URL copiada al portapapeles', 'success');
                }).catch(() => {
                    mostrarNotificacion('No se pudo copiar la URL', 'error');
                });
            }
        }

        function abrirEnCelular() {
            mostrarNotificacion('Funcionalidad de QR code próximamente disponible', 'info');
        }

        function mostrarAreaLector() {
            document.getElementById('areaLector').style.display = 'block';
            
            // Mejorar la interfaz del lector USB
            document.getElementById('areaLector').innerHTML = `
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="bi bi-upc-scan me-2"></i>Lector USB/Bluetooth</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Conecta tu lector de códigos de barras y escanea directamente
                        </p>
                        <div class="mb-3">
                            <label class="form-label">Código escaneado:</label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="campoLector" 
                                   placeholder="Haz clic aquí y escanea con tu lector..."
                                   autocomplete="off">
                        </div>
                        <div class="d-grid gap-2 d-md-flex">
                            <button class="btn btn-warning flex-fill" onclick="usarCodigoLector()">
                                <i class="bi bi-check-circle me-2"></i>Usar Este Código
                            </button>
                            <button class="btn btn-outline-secondary" onclick="document.getElementById('campoLector').value = ''">
                                <i class="bi bi-x-circle me-2"></i>Limpiar
                            </button>
                        </div>
                        <hr>
                        <small class="text-muted">
                            <strong>Lectores compatibles:</strong> USB, Bluetooth, PS/2<br>
                            <strong>Funciona sin internet:</strong> ✅ Sí
                        </small>
                    </div>
                </div>
            `;
            
            // Enfocar el campo después de que se renderice
            setTimeout(() => {
                const campo = document.getElementById('campoLector');
                if (campo) {
                    campo.focus();
                    
                    // Agregar evento para detectar cuando se escanea
                    campo.addEventListener('input', function() {
                        if (this.value.length > 8) { // Códigos de barras típicos
                            setTimeout(() => {
                                usarCodigoLector();
                            }, 500); // Dar tiempo para que termine de escribir
                        }
                    });
                }
            }, 100);
            
            mostrarNotificacion('Haz clic en el campo y escanea con tu lector físico', 'info');
        }

        function mostrarAreaManual() {
            document.getElementById('areaManual').style.display = 'block';
            
            // Mejorar la interfaz manual
            document.getElementById('areaManual').innerHTML = `
                <div class="card border-secondary">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bi bi-keyboard me-2"></i>Entrada Manual</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            <i class="bi bi-pencil me-1"></i>
                            Escribe o pega el código de barras manualmente
                        </p>
                        <div class="mb-3">
                            <label class="form-label">Código de barras:</label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="codigoManual" 
                                   placeholder="Ej: 1234567890123"
                                   autocomplete="off">
                        </div>
                        <div class="d-grid gap-2 d-md-flex">
                            <button class="btn btn-secondary flex-fill" onclick="usarCodigoManual()">
                                <i class="bi bi-check-circle me-2"></i>Usar Este Código
                            </button>
                            <button class="btn btn-outline-secondary" onclick="document.getElementById('codigoManual').value = ''">
                                <i class="bi bi-x-circle me-2"></i>Limpiar
                            </button>
                        </div>
                        <hr>
                        <small class="text-muted">
                            <strong>Formatos válidos:</strong> EAN-13, UPC-A, Code 128<br>
                            <strong>Funciona sin internet:</strong> ✅ Sí
                        </small>
                    </div>
                </div>
            `;
            
            // Enfocar el campo
            setTimeout(() => {
                const campo = document.getElementById('codigoManual');
                if (campo) {
                    campo.focus();
                    
                    // Agregar evento Enter
                    campo.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            usarCodigoManual();
                        }
                    });
                }
            }, 100);
        }

        async function detectarCamaras() {
            try {
                // Verificar que la librería esté cargada
                await verificarLibreriaQR();
                
                // Solicitar permisos de cámara
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                stream.getTracks().forEach(track => track.stop()); // Cerrar stream temporal
                
                // Obtener lista de dispositivos
                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoDevices = devices.filter(device => device.kind === 'videoinput');
                
                if (videoDevices.length === 0) {
                    mostrarErrorCamara('No se encontraron cámaras disponibles. Usa las opciones alternativas.');
                    return;
                }
                
                // Mostrar selector si hay múltiples cámaras
                if (videoDevices.length > 1) {
                    mostrarSelectorCamaras(videoDevices);
                }
                
                // Iniciar escáner con la primera cámara
                await iniciarEscaner(videoDevices[0].deviceId);
                
            } catch (error) {
                console.error('Error al detectar cámaras:', error);
                
                if (error.message && error.message.includes('conexión a internet')) {
                    mostrarErrorSinInternet();
                } else if (error.message && error.message.includes('librería')) {
                    mostrarErrorSinInternet();
                } else if (error.name === 'NotAllowedError') {
                    mostrarErrorCamara('Permisos de cámara denegados. Por favor, permite el acceso a la cámara.');
                } else if (error.name === 'NotFoundError') {
                    mostrarErrorCamara('No se encontró ninguna cámara. Verifica que esté conectada.');
                } else {
                    mostrarErrorCamara('Error al acceder a la cámara: ' + error.message);
                }
            }
        }

        function mostrarErrorSinInternet() {
            document.getElementById('estadoEscaner').innerHTML = `
                <div class="alert alert-warning">
                    <h6><i class="bi bi-wifi-off me-2"></i>Sin Conexión a Internet</h6>
                    <p>No se puede usar la cámara sin conexión. Usa estas alternativas:</p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <button type="button" class="btn btn-warning" onclick="seleccionarMetodoCaptura('lector')">
                            <i class="bi bi-upc-scan me-2"></i>Lector USB
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="seleccionarMetodoCaptura('manual')">
                            <i class="bi bi-keyboard me-2"></i>Manual
                        </button>
                        <button type="button" class="btn btn-info btn-sm" onclick="reintentar(); detectarCamaras();">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reintentar
                        </button>
                    </div>
                </div>
            `;
        }

        function mostrarSelectorCamaras(devices) {
            const selector = document.getElementById('listaCamaras');
            const contenedor = document.getElementById('selectorCamara');
            
            selector.innerHTML = '';
            devices.forEach((device, index) => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.textContent = device.label || `Cámara ${index + 1}`;
                selector.appendChild(option);
            });
            
            contenedor.style.display = 'block';
            
            // Event listener para cambio de cámara
            selector.addEventListener('change', function() {
                if (this.value) {
                    iniciarEscaner(this.value);
                }
            });
        }

        function mostrarErrorCamara(mensaje) {
            document.getElementById('estadoEscaner').innerHTML = `
                <div class="alert alert-danger">
                    <h6><i class="bi bi-exclamation-triangle me-2"></i>Error de Cámara</h6>
                    <p>${mensaje}</p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <button type="button" class="btn btn-warning" onclick="seleccionarMetodoCaptura('lector')">
                            <i class="bi bi-upc-scan me-2"></i>Usar Lector USB
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="seleccionarMetodoCaptura('manual')">
                            <i class="bi bi-keyboard me-2"></i>Entrada Manual
                        </button>
                        <button type="button" class="btn btn-success" onclick="seleccionarMetodoCaptura('celular')">
                            <i class="bi bi-phone me-2"></i>Usar Celular
                        </button>
                    </div>
                    <hr>
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Los lectores USB y la entrada manual funcionan sin cámara ni internet
                    </small>
                </div>
            `;
        }

        async function iniciarEscaner(cameraId = null) {
            try {
                // Verificar que la librería esté disponible
                await verificarLibreriaQR();
                
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.clear().catch(() => {});
                }

                const config = { 
                    fps: 10, 
                    qrbox: { width: 250, height: 250 },
                    experimentalFeatures: {
                        useBarCodeDetectorIfSupported: true
                    },
                    rememberLastUsedCamera: true
                };

                // Si se especifica una cámara, configurarla
                if (cameraId) {
                    config.cameraIdOrConfig = cameraId;
                }

                html5QrcodeScanner = new Html5QrcodeScanner("reader", config, false);

                function onScanSuccess(decodedText, decodedResult) {
                    console.log(`Código escaneado: ${decodedText}`);
                    
                    // Colocar el código en el campo
                    document.getElementById('codigoBarras').value = decodedText;
                    
                    // Cerrar modal
                    html5QrcodeScanner.clear().catch(() => {});
                    bootstrap.Modal.getInstance(document.getElementById('modalEscaner')).hide();
                    
                    mostrarNotificacion(`Código de barras capturado: ${decodedText}`, 'success');
                }

                function onScanFailure(error) {
                    // No mostrar errores constantemente
                    console.log(`Error de escaneo: ${error}`);
                }

                html5QrcodeScanner.render(onScanSuccess, onScanFailure);
                
                // Ocultar estado de carga
                setTimeout(() => {
                    const estadoEscaner = document.getElementById('estadoEscaner');
                    if (estadoEscaner) {
                        estadoEscaner.style.display = 'none';
                    }
                }, 2000);
                
            } catch (error) {
                console.error('Error al iniciar escáner:', error);
                mostrarErrorCamara('Error al inicializar el escáner: ' + error.message);
            }
        }

        async function cambiarCamara() {
            const selector = document.getElementById('selectorCamara');
            if (selector.style.display === 'none') {
                await detectarCamaras();
            } else {
                const cameraId = document.getElementById('listaCamaras').value;
                if (cameraId) {
                    await iniciarEscaner(cameraId);
                }
            }
        }

        function usarCodigoLector() {
            const codigo = document.getElementById('campoLector').value.trim();
            if (codigo) {
                document.getElementById('codigoBarras').value = codigo;
                bootstrap.Modal.getInstance(document.getElementById('modalEscaner')).hide();
                mostrarNotificacion(`Código capturado con lector: ${codigo}`, 'success');
            } else {
                mostrarNotificacion('Por favor, escanea un código con tu lector', 'error');
            }
        }

        function usarCodigoManual() {
            const codigo = document.getElementById('codigoManual').value.trim();
            if (codigo) {
                document.getElementById('codigoBarras').value = codigo;
                bootstrap.Modal.getInstance(document.getElementById('modalEscaner')).hide();
                mostrarNotificacion(`Código ingresado manualmente: ${codigo}`, 'success');
            } else {
                mostrarNotificacion('Por favor, ingrese un código válido', 'error');
            }
        }

        function mostrarAyuda() {
            mostrarNotificacion('Consulta la documentación para más información sobre cada método de captura', 'info');
        }

        // Limpiar escáner al cerrar modal
        document.getElementById('modalEscaner').addEventListener('hidden.bs.modal', function () {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
            }
            // Resetear estado del modal
            document.getElementById('estadoEscaner').style.display = 'block';
            document.getElementById('instrucciones').style.display = 'none';
            document.getElementById('selectorCamara').style.display = 'none';
            document.getElementById('entradaManual').style.display = 'none';
            document.getElementById('codigoManual').value = '';
        });

        function guardarLugar() {
            const nombre = document.getElementById('nombreLugar').value.trim();
            if (!nombre) {
                mostrarNotificacion('Ingrese un nombre para el lugar', 'error');
                return;
            }

            const data = {
                nombre: nombre
            };

            fetch('../../ajax/guardar_lugar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.querySelector('select[name="lugar_id"]');
                    const option = new Option(nombre, data.id, true, true);
                    select.add(option);
                    
                    bootstrap.Modal.getInstance(document.getElementById('modalLugar')).hide();
                    document.getElementById('nombreLugar').value = '';
                    mostrarNotificacion('Lugar creado exitosamente', 'success');
                } else {
                    mostrarNotificacion('Error: ' + data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('Error al guardar lugar', 'error');
            });
        }

        // Event listeners para cambio de pestañas
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-generar código si es nuevo producto
            <?php if (!$es_edicion): ?>
            generarCodigo();
            <?php endif; ?>

            // Detectar cambio de pestaña manual
            document.querySelectorAll('#productTabs button[data-bs-toggle="tab"]').forEach((tab, index) => {
                tab.addEventListener('shown.bs.tab', function() {
                    pestanaActual = index + 1;
                    actualizarIndicador();
                });
            });

            actualizarIndicador();
        });

        // Validación en tiempo real
        document.querySelectorAll('input[required], select[required]').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
        });

        // Soporte para Enter en entrada manual
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && document.getElementById('entradaManual').style.display === 'block') {
                const codigoManual = document.getElementById('codigoManual');
                if (document.activeElement === codigoManual) {
                    event.preventDefault();
                    usarCodigoManual();
                }
            }
        });
    </script>
</body>
</html>

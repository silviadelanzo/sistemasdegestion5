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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-camera me-2"></i>Escanear Código de Barras
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="reader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
                    <div class="mt-3">
                        <p class="text-muted">Apunta la cámara de tu celular al código de barras</p>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>¿No funciona?</strong> También puedes ingresar el código manualmente
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/minified/html5-qrcode.min.js"></script>
    <script>
        let pestanaActual = 1;
        let html5QrcodeScanner = null;
        const totalPestanas = 6;

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
            
            // Inicializar escáner después de que el modal se muestre
            setTimeout(() => {
                iniciarEscaner();
            }, 500);
        }

        function iniciarEscaner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
            }

            html5QrcodeScanner = new Html5QrcodeScanner(
                "reader", 
                { 
                    fps: 10, 
                    qrbox: { width: 250, height: 250 },
                    experimentalFeatures: {
                        useBarCodeDetectorIfSupported: true
                    },
                    supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
                    rememberLastUsedCamera: true
                },
                false
            );

            function onScanSuccess(decodedText, decodedResult) {
                console.log(`Código escaneado: ${decodedText}`);
                
                // Colocar el código en el campo
                document.getElementById('codigoBarras').value = decodedText;
                
                // Cerrar modal
                html5QrcodeScanner.clear();
                bootstrap.Modal.getInstance(document.getElementById('modalEscaner')).hide();
                
                mostrarNotificacion(`Código de barras capturado: ${decodedText}`, 'success');
            }

            function onScanFailure(error) {
                // No mostrar errores constantemente
                console.log(`Error de escaneo: ${error}`);
            }

            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }

        // Limpiar escáner al cerrar modal
        document.getElementById('modalEscaner').addEventListener('hidden.bs.modal', function () {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
            }
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
    </script>
</body>
</html>

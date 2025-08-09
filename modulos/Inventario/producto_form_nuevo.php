<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

// Configurar charset UTF-8
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// --- INICIO: Lógica específica del formulario de productos ---
$producto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$es_edicion = $producto_id > 0;

$producto = [
    'codigo_interno' => '',
    'codigo_producto' => '',
    'nombre' => '',
    'descripcion' => '',
    'categoria_id' => '',
    'lugar_id' => '',
    'unidad_medida' => 'UN',
    'factor_conversion' => 1.00,
    'en_oferta' => 0,
    'publicar_web' => 0,
    'stock' => 0,
    'stock_minimo' => 1,
    'stock_maximo' => 1000,
    'usar_control_stock' => 1,
    'precio_compra' => 0.00,
    'precio_minorista' => 0.00,
    'precio_mayorista' => 0.00,
    'utilidad_minorista' => 30.00,
    'utilidad_mayorista' => 15.00,
    'moneda_id' => '',
    'impuesto_id' => '',
    'fecha_vencimiento' => '',
    'alerta_vencimiento_dias' => 15,
    'usar_alerta_vencimiento' => 0,
    'imagen' => ''
];

$categorias = [];
$lugares = [];
$paises = [];
$monedas = [];
$impuestos = [];
$errores = [];
$mensaje_exito = '';

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Cargar datos del producto si es edición
    if ($es_edicion) {
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto_db = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($producto_db) {
            $producto = array_merge($producto, $producto_db);
        } else {
            $errores[] = 'Producto no encontrado.';
        }
    }

    // Procesar formulario
    if ($_POST) {
        // Validaciones básicas
        $campos_requeridos = ['nombre', 'codigo_producto'];
        foreach ($campos_requeridos as $campo) {
            if (empty($_POST[$campo])) {
                $errores[] = "El campo " . ucfirst(str_replace('_', ' ', $campo)) . " es obligatorio.";
            }
        }

        // Verificar código único
        if (!empty($_POST['codigo_producto'])) {
            $stmt = $pdo->prepare("SELECT id FROM productos WHERE codigo_producto = ? AND id != ?");
            $stmt->execute([$_POST['codigo_producto'], $producto_id]);
            if ($stmt->fetch()) {
                $errores[] = 'El código del producto ya existe. Debe ser único.';
            }
        }

        if (empty($errores)) {
            // Preparar datos para guardar
            $datos = [
                'codigo_producto' => $_POST['codigo_producto'],
                'nombre' => $_POST['nombre'],
                'descripcion' => $_POST['descripcion'] ?? '',
                'categoria_id' => !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null,
                'lugar_id' => !empty($_POST['lugar_id']) ? $_POST['lugar_id'] : null,
                'unidad_medida' => $_POST['unidad_medida'] ?? 'UN',
                'factor_conversion' => floatval($_POST['factor_conversion'] ?? 1.00),
                'en_oferta' => isset($_POST['en_oferta']) ? 1 : 0,
                'publicar_web' => isset($_POST['publicar_web']) ? 1 : 0,
                'stock' => intval($_POST['stock'] ?? 0),
                'stock_minimo' => intval($_POST['stock_minimo'] ?? 1),
                'stock_maximo' => intval($_POST['stock_maximo'] ?? 1000),
                'usar_control_stock' => isset($_POST['usar_control_stock']) ? 1 : 0,
                'precio_compra' => floatval($_POST['precio_compra'] ?? 0),
                'utilidad_minorista' => floatval($_POST['utilidad_minorista'] ?? 30),
                'utilidad_mayorista' => floatval($_POST['utilidad_mayorista'] ?? 15),
                'moneda_id' => !empty($_POST['moneda_id']) ? $_POST['moneda_id'] : null,
                'impuesto_id' => !empty($_POST['impuesto_id']) ? $_POST['impuesto_id'] : null,
                'fecha_vencimiento' => !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null,
                'alerta_vencimiento_dias' => intval($_POST['alerta_vencimiento_dias'] ?? 15),
                'usar_alerta_vencimiento' => isset($_POST['usar_alerta_vencimiento']) ? 1 : 0
            ];

            if ($es_edicion) {
                // Actualizar producto existente
                $sql = "UPDATE productos SET 
                        codigo_producto = ?, nombre = ?, descripcion = ?, categoria_id = ?, lugar_id = ?,
                        unidad_medida = ?, factor_conversion = ?, en_oferta = ?, publicar_web = ?,
                        stock = ?, stock_minimo = ?, stock_maximo = ?, usar_control_stock = ?,
                        precio_compra = ?, utilidad_minorista = ?, utilidad_mayorista = ?,
                        moneda_id = ?, impuesto_id = ?, fecha_vencimiento = ?,
                        alerta_vencimiento_dias = ?, usar_alerta_vencimiento = ?
                        WHERE id = ?";
                
                $params = array_values($datos);
                $params[] = $producto_id;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                $mensaje_exito = 'Producto actualizado exitosamente.';
            } else {
                // Crear nuevo producto
                $campos = implode(', ', array_keys($datos));
                $placeholders = str_repeat('?,', count($datos) - 1) . '?';
                
                $sql = "INSERT INTO productos ($campos) VALUES ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($datos));
                
                $new_producto_id = $pdo->lastInsertId();
                $mensaje_exito = 'Producto creado exitosamente.';
            }
        }
    }

    // Cargar datos para selects
    $stmt_cat = $pdo->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    $stmt_lug = $pdo->query("SELECT id, nombre FROM lugares WHERE activo = 1 ORDER BY nombre");
    $lugares = $stmt_lug->fetchAll(PDO::FETCH_ASSOC);

    $stmt_paises = $pdo->query("SELECT id, nombre FROM paises WHERE activo = 1 ORDER BY nombre");
    $paises = $stmt_paises->fetchAll(PDO::FETCH_ASSOC);

    // Cargar monedas por país
    $stmt_monedas = $pdo->query("
        SELECT m.*, p.nombre as pais_nombre 
        FROM monedas m 
        JOIN paises p ON m.pais_id = p.id 
        WHERE m.activo = 1 
        ORDER BY p.nombre, m.nombre
    ");
    $monedas = $stmt_monedas->fetchAll(PDO::FETCH_ASSOC);

    // Cargar impuestos por país
    $stmt_impuestos = $pdo->query("
        SELECT i.*, p.nombre as pais_nombre 
        FROM impuestos i 
        JOIN paises p ON i.pais_id = p.id 
        WHERE i.activo = 1 
        ORDER BY p.nombre, i.porcentaje
    ");
    $impuestos = $stmt_impuestos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errores[] = 'Error de base de datos: ' . $e->getMessage();
    error_log('PDOException en producto_form.php: ' . $e->getMessage());
} catch (Exception $e) {
    $errores[] = 'Error del sistema: ' . $e->getMessage();
    error_log('Exception en producto_form.php: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $es_edicion ? 'Editar' : 'Nuevo'; ?> Producto - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding-top: 80px;
        }

        .container-form {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 15px 15px 0 0;
        }

        .form-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 1.8rem;
        }

        .form-body {
            padding: 30px;
        }

        .nav-tabs-custom {
            border-bottom: 3px solid #667eea;
            margin-bottom: 30px;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            color: #666;
            font-weight: 500;
            padding: 15px 25px;
            transition: all 0.3s ease;
            border-radius: 10px 10px 0 0;
            margin-right: 5px;
        }

        .nav-tabs-custom .nav-link:hover {
            background-color: #f8f9fa;
            color: #667eea;
        }

        .nav-tabs-custom .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }

        .tab-content {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .scanner-section {
            background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .price-calculator {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
        }

        .price-result {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .image-preview-container {
            text-align: center;
            margin-bottom: 15px;
        }

        .image-preview {
            width: 200px;
            height: 200px;
            border: 3px dashed #667eea;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .image-preview:hover {
            border-color: #764ba2;
            background: #e9ecef;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            border-radius: 8px;
        }

        .image-preview .placeholder {
            color: #667eea;
            text-align: center;
        }

        .section-divider {
            border-top: 2px solid #e9ecef;
            margin: 25px 0;
            padding-top: 25px;
        }

        .info-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include '../../config/navbar_code.php'; ?>

    <div class="container-form">
        <div class="form-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-<?php echo $es_edicion ? 'pencil-square' : 'plus-circle'; ?> me-2"></i><?php echo $es_edicion ? 'Editar' : 'Nuevo'; ?> Producto</h2>
                <a href="productos.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Volver al Listado
                </a>
            </div>
        </div>

        <div class="form-body">
            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Errores Encontrados:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensaje_exito)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($mensaje_exito); ?>
                    <?php if (!$es_edicion && isset($new_producto_id)): ?>
                        <a href="producto_form.php?id=<?php echo $new_producto_id; ?>" class="alert-link ms-2">Ver/Editar producto creado</a>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="formProducto">
                <!-- PESTAÑAS DE NAVEGACIÓN -->
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

                <!-- CONTENIDO DE LAS PESTAÑAS -->
                <div class="tab-content" id="productTabContent">
                    <!-- TAB 1: GENERAL -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <h5 class="mb-4"><i class="bi bi-info-circle text-primary me-2"></i>Información General</h5>
                        
                        <div class="row g-3">
                            <!-- Códigos -->
                            <div class="col-md-4">
                                <label for="codigo_interno" class="form-label">Código Interno</label>
                                <input type="text" class="form-control" id="codigo_interno" name="codigo_interno" 
                                       value="<?php echo htmlspecialchars($producto['codigo_interno']); ?>" readonly>
                                <small class="text-muted">Se genera automáticamente (PROD-0000XXX)</small>
                            </div>
                            
                            <div class="col-md-8">
                                <label for="codigo_producto" class="form-label">Código del Producto *</label>
                                <div class="scanner-section">
                                    <div class="d-flex align-items-center">
                                        <input type="text" class="form-control me-3" id="codigo_producto" name="codigo_producto" 
                                               value="<?php echo htmlspecialchars($producto['codigo_producto']); ?>" required 
                                               placeholder="Escanear o ingresar código único">
                                        <button type="button" class="btn btn-light" onclick="activarScanner()">
                                            <i class="bi bi-upc-scan"></i> Scanner
                                        </button>
                                    </div>
                                    <small class="d-block mt-2">Código único del producto (código de barras, SKU, etc.)</small>
                                </div>
                            </div>

                            <!-- Información básica -->
                            <div class="col-md-8">
                                <label for="nombre" class="form-label">Nombre del Producto *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                            </div>

                            <div class="col-md-4">
                                <label for="unidad_medida" class="form-label">Unidad de Medida</label>
                                <select class="form-select" id="unidad_medida" name="unidad_medida">
                                    <option value="UN" <?php echo $producto['unidad_medida'] === 'UN' ? 'selected' : ''; ?>>Unidades (UN)</option>
                                    <option value="KG" <?php echo $producto['unidad_medida'] === 'KG' ? 'selected' : ''; ?>>Kilogramos (KG)</option>
                                    <option value="LT" <?php echo $producto['unidad_medida'] === 'LT' ? 'selected' : ''; ?>>Litros (LT)</option>
                                    <option value="M2" <?php echo $producto['unidad_medida'] === 'M2' ? 'selected' : ''; ?>>Metros² (M²)</option>
                                    <option value="CAJA" <?php echo $producto['unidad_medida'] === 'CAJA' ? 'selected' : ''; ?>>Cajas</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                            </div>

                            <!-- Categoría y Ubicación -->
                            <div class="col-md-6">
                                <label for="categoria_id" class="form-label">Categoría (Rubro)</label>
                                <div class="input-group">
                                    <select class="form-select" id="categoria_id" name="categoria_id">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php if ($producto['categoria_id'] == $cat['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($cat['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" onclick="abrirModalCategoria()">+</button>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="lugar_id" class="form-label">Ubicación</label>
                                <div class="input-group">
                                    <select class="form-select" id="lugar_id" name="lugar_id">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($lugares as $lug): ?>
                                            <option value="<?php echo $lug['id']; ?>" <?php if ($producto['lugar_id'] == $lug['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($lug['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" onclick="abrirModalLugar()">+</button>
                                </div>
                            </div>

                            <!-- Opciones especiales -->
                            <div class="col-md-6">
                                <div class="form-check form-check-lg">
                                    <input class="form-check-input" type="checkbox" id="en_oferta" name="en_oferta" 
                                           <?php echo $producto['en_oferta'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="en_oferta">
                                        <i class="bi bi-tag text-warning me-2"></i>Producto en Oferta
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-check-lg">
                                    <input class="form-check-input" type="checkbox" id="publicar_web" name="publicar_web" 
                                           <?php echo $producto['publicar_web'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="publicar_web">
                                        <i class="bi bi-globe text-info me-2"></i>Publicar en Web
                                    </label>
                                </div>
                            </div>

                            <!-- Factor de conversión -->
                            <div class="col-md-4">
                                <label for="factor_conversion" class="form-label">Factor de Conversión</label>
                                <input type="number" class="form-control" id="factor_conversion" name="factor_conversion" 
                                       value="<?php echo htmlspecialchars($producto['factor_conversion']); ?>" step="0.01" min="0.01">
                                <small class="text-muted">Ej: 1 caja = 12 unidades</small>
                            </div>

                            <!-- Imagen del producto -->
                            <div class="col-12">
                                <label for="inputImagen" class="form-label">Imagen del Producto</label>
                                <div class="image-preview-container">
                                    <div class="image-preview" onclick="document.getElementById('inputImagen').click();" id="imagePreviewDiv">
                                        <?php if (!empty($producto['imagen']) && file_exists('../../' . $producto['imagen'])): ?>
                                            <img src="../../<?php echo htmlspecialchars($producto['imagen']); ?>?t=<?php echo time(); ?>" alt="Vista previa">
                                        <?php else: ?>
                                            <span class="placeholder">
                                                <i class="bi bi-image fs-1 d-block"></i>
                                                <small>Click para subir imagen</small>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <input class="form-control" type="file" id="inputImagen" name="imagen" 
                                       accept="image/jpeg, image/png, image/gif, image/webp" onchange="previsualizarImagen(event)" style="display: none;">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: IMPUESTOS/MONEDA -->
                    <div class="tab-pane fade" id="impuestos" role="tabpanel" aria-labelledby="impuestos-tab">
                        <h5 class="mb-4"><i class="bi bi-currency-dollar text-success me-2"></i>Configuración Fiscal y Monetaria</h5>
                        
                        <div class="row g-4">
                            <!-- Configuración de País y Moneda -->
                            <div class="col-12">
                                <div class="card border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="bi bi-globe me-2"></i>Configuración Geográfica</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="pais_fiscal" class="form-label text-white">País Fiscal</label>
                                                <select class="form-select" id="pais_fiscal" onchange="cargarMonedasImpuestos()">
                                                    <option value="">-- Seleccionar País --</option>
                                                    <?php foreach ($paises as $pais): ?>
                                                        <option value="<?php echo $pais['id']; ?>"><?php echo htmlspecialchars($pais['nombre']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="moneda_id" class="form-label text-white">Moneda Base</label>
                                                <div class="input-group">
                                                    <select class="form-select" id="moneda_id" name="moneda_id">
                                                        <option value="">-- Seleccionar Moneda --</option>
                                                        <?php foreach ($monedas as $moneda): ?>
                                                            <option value="<?php echo $moneda['id']; ?>" 
                                                                    data-pais="<?php echo $moneda['pais_id']; ?>"
                                                                    data-simbolo="<?php echo htmlspecialchars($moneda['simbolo']); ?>"
                                                                    <?php if ($producto['moneda_id'] == $moneda['id']) echo 'selected'; ?>>
                                                                <?php echo htmlspecialchars($moneda['pais_nombre'] . ' - ' . $moneda['nombre'] . ' (' . $moneda['simbolo'] . ')'); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="button" class="btn btn-light" onclick="abrirModalMoneda()">
                                                        <i class="bi bi-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Configuración de Impuestos -->
                            <div class="col-12">
                                <div class="card border-0" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="bi bi-receipt me-2"></i>Impuestos Aplicables</h6>
                                        <div class="row" id="impuestos-container">
                                            <?php foreach ($impuestos as $impuesto): ?>
                                                <div class="col-md-4 mb-3 impuesto-option" data-pais="<?php echo $impuesto['pais_id']; ?>" style="display: none;">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="impuesto_id" 
                                                               value="<?php echo $impuesto['id']; ?>" id="impuesto_<?php echo $impuesto['id']; ?>"
                                                               data-porcentaje="<?php echo $impuesto['porcentaje']; ?>"
                                                               <?php if ($producto['impuesto_id'] == $impuesto['id']) echo 'checked'; ?>>
                                                        <label class="form-check-label text-white" for="impuesto_<?php echo $impuesto['id']; ?>">
                                                            <strong><?php echo htmlspecialchars($impuesto['nombre']); ?></strong><br>
                                                            <small><?php echo $impuesto['porcentaje']; ?>% - <?php echo htmlspecialchars($impuesto['pais_nombre']); ?></small>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="text-end mt-3">
                                            <button type="button" class="btn btn-light btn-sm" onclick="abrirModalImpuesto()">
                                                <i class="bi bi-plus me-1"></i>Nuevo Impuesto
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Configuración Avanzada -->
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="bi bi-gear text-primary me-2"></i>Configuración Avanzada</h6>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="redondeo_decimales" class="form-label">Decimales de Redondeo</label>
                                                <select class="form-select" id="redondeo_decimales" name="redondeo_decimales">
                                                    <option value="0">0 decimales</option>
                                                    <option value="1">1 decimal</option>
                                                    <option value="2" selected>2 decimales</option>
                                                    <option value="3">3 decimales</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="tipo_redondeo" class="form-label">Tipo de Redondeo</label>
                                                <select class="form-select" id="tipo_redondeo" name="tipo_redondeo">
                                                    <option value="centavo" selected>Al centavo más cercano</option>
                                                    <option value="peso">Al peso más cercano</option>
                                                    <option value="cinco_pesos">A 5 pesos más cercano</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mt-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="aplicar_impuesto_venta" checked>
                                                        <label class="form-check-label" for="aplicar_impuesto_venta">
                                                            Aplicar impuesto en precio de venta
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: PRECIOS -->
                    <div class="tab-pane fade" id="precios" role="tabpanel" aria-labelledby="precios-tab">
                        <h5 class="mb-4"><i class="bi bi-calculator text-warning me-2"></i>Calculadora de Precios</h5>
                        
                        <div class="price-calculator">
                            <div class="row g-4">
                                <!-- Precio Base -->
                                <div class="col-12">
                                    <h6 class="text-white mb-3"><i class="bi bi-currency-exchange me-2"></i>Precio Base</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="precio_compra" class="form-label text-white">Precio de Costo *</label>
                                            <div class="input-group">
                                                <span class="input-group-text" id="simbolo-moneda">$</span>
                                                <input type="number" class="form-control" id="precio_compra" name="precio_compra" 
                                                       value="<?php echo htmlspecialchars($producto['precio_compra']); ?>" 
                                                       step="0.01" min="0" required onchange="calcularPrecios()">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-white">Moneda e Impuesto Seleccionados</label>
                                            <div class="info-badge" id="info-fiscal">
                                                <span id="info-moneda">Seleccionar en pestaña anterior</span> | 
                                                <span id="info-impuesto">Sin impuesto</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Precios Calculados -->
                                <div class="col-md-6">
                                    <div class="price-result">
                                        <h6 class="text-white"><i class="bi bi-person me-2"></i>Precio Minorista</h6>
                                        <div class="mb-3">
                                            <label for="utilidad_minorista" class="form-label text-white">Utilidad (%)</label>
                                            <input type="number" class="form-control" id="utilidad_minorista" name="utilidad_minorista" 
                                                   value="<?php echo htmlspecialchars($producto['utilidad_minorista']); ?>" 
                                                   step="0.01" min="0" max="1000" onchange="calcularPrecios()">
                                        </div>
                                        <div class="d-flex justify-content-between text-white">
                                            <span>Precio sin impuesto:</span>
                                            <span id="precio-min-sin-impuesto">$0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between text-white">
                                            <span>Impuesto:</span>
                                            <span id="impuesto-minorista">$0.00</span>
                                        </div>
                                        <hr class="bg-white">
                                        <div class="d-flex justify-content-between text-white fs-5 fw-bold">
                                            <span>PRECIO FINAL:</span>
                                            <span id="precio-minorista-final">$0.00</span>
                                        </div>
                                        <input type="hidden" id="precio_minorista" name="precio_minorista" value="<?php echo htmlspecialchars($producto['precio_minorista']); ?>">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="price-result">
                                        <h6 class="text-white"><i class="bi bi-building me-2"></i>Precio Mayorista</h6>
                                        <div class="mb-3">
                                            <label for="utilidad_mayorista" class="form-label text-white">Utilidad (%)</label>
                                            <input type="number" class="form-control" id="utilidad_mayorista" name="utilidad_mayorista" 
                                                   value="<?php echo htmlspecialchars($producto['utilidad_mayorista']); ?>" 
                                                   step="0.01" min="0" max="1000" onchange="calcularPrecios()">
                                        </div>
                                        <div class="d-flex justify-content-between text-white">
                                            <span>Precio sin impuesto:</span>
                                            <span id="precio-may-sin-impuesto">$0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between text-white">
                                            <span>Impuesto:</span>
                                            <span id="impuesto-mayorista">$0.00</span>
                                        </div>
                                        <hr class="bg-white">
                                        <div class="d-flex justify-content-between text-white fs-5 fw-bold">
                                            <span>PRECIO FINAL:</span>
                                            <span id="precio-mayorista-final">$0.00</span>
                                        </div>
                                        <input type="hidden" id="precio_mayorista" name="precio_mayorista" value="<?php echo htmlspecialchars($producto['precio_mayorista']); ?>">
                                    </div>
                                </div>

                                <div class="col-12 text-center">
                                    <button type="button" class="btn btn-light btn-lg" onclick="calcularPrecios()">
                                        <i class="bi bi-arrow-clockwise me-2"></i>Recalcular Precios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: STOCK -->
                    <div class="tab-pane fade" id="stock" role="tabpanel" aria-labelledby="stock-tab">
                        <h5 class="mb-4"><i class="bi bi-boxes text-info me-2"></i>Control de Inventario</h5>
                        
                        <div class="row g-4">
                            <!-- Control de Stock -->
                            <div class="col-12">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="usar_control_stock" name="usar_control_stock" 
                                                   <?php echo $producto['usar_control_stock'] ? 'checked' : ''; ?> onchange="toggleControlStock()">
                                            <label class="form-check-label text-white fw-bold" for="usar_control_stock">
                                                <i class="bi bi-toggles me-2"></i>Usar Control de Stock
                                            </label>
                                        </div>
                                    </div>
                                    <div class="card-body" id="stock-controls">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="stock" class="form-label">Stock Actual/Inicial *</label>
                                                <input type="number" class="form-control form-control-lg" id="stock" name="stock" 
                                                       value="<?php echo htmlspecialchars($producto['stock']); ?>" min="0" required>
                                                <small class="text-muted">Cantidad actual en inventario</small>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="stock_minimo" class="form-label">Stock Mínimo</label>
                                                <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" 
                                                       value="<?php echo htmlspecialchars($producto['stock_minimo']); ?>" min="0">
                                                <small class="text-muted">Alerta cuando llegue a este nivel</small>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="stock_maximo" class="form-label">Stock Máximo</label>
                                                <input type="number" class="form-control" id="stock_maximo" name="stock_maximo" 
                                                       value="<?php echo htmlspecialchars($producto['stock_maximo']); ?>" min="1">
                                                <small class="text-muted">Capacidad máxima de almacenamiento</small>
                                            </div>
                                        </div>

                                        <div class="section-divider">
                                            <h6><i class="bi bi-bar-chart me-2"></i>Indicadores de Stock</h6>
                                            <div class="row text-center">
                                                <div class="col-md-4">
                                                    <div class="card bg-success text-white">
                                                        <div class="card-body">
                                                            <h5 class="card-title" id="indicador-actual">0</h5>
                                                            <p class="card-text">Stock Actual</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card bg-warning text-dark">
                                                        <div class="card-body">
                                                            <h5 class="card-title" id="indicador-disponible">0</h5>
                                                            <p class="card-text">Disponible para Venta</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card bg-info text-white">
                                                        <div class="card-body">
                                                            <h5 class="card-title" id="indicador-nivel">Normal</h5>
                                                            <p class="card-text">Nivel de Stock</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: VENCIMIENTOS -->
                    <div class="tab-pane fade" id="vencimientos" role="tabpanel" aria-labelledby="vencimientos-tab">
                        <h5 class="mb-4"><i class="bi bi-calendar-event text-danger me-2"></i>Control de Vencimientos</h5>
                        
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning text-dark">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="usar_alerta_vencimiento" name="usar_alerta_vencimiento" 
                                                   <?php echo $producto['usar_alerta_vencimiento'] ? 'checked' : ''; ?> onchange="toggleVencimientos()">
                                            <label class="form-check-label fw-bold" for="usar_alerta_vencimiento">
                                                <i class="bi bi-bell me-2"></i>Usar Alertas de Vencimiento
                                            </label>
                                        </div>
                                    </div>
                                    <div class="card-body" id="vencimiento-controls">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                                                <input type="date" class="form-control form-control-lg" id="fecha_vencimiento" name="fecha_vencimiento" 
                                                       value="<?php echo htmlspecialchars($producto['fecha_vencimiento']); ?>">
                                                <small class="text-muted">Fecha límite de consumo/uso del producto</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="alerta_vencimiento_dias" class="form-label">Días de Alerta Anticipada</label>
                                                <input type="number" class="form-control" id="alerta_vencimiento_dias" name="alerta_vencimiento_dias" 
                                                       value="<?php echo htmlspecialchars($producto['alerta_vencimiento_dias']); ?>" min="1" max="365">
                                                <small class="text-muted">Días antes del vencimiento para mostrar alerta</small>
                                            </div>
                                        </div>

                                        <div class="section-divider">
                                            <h6><i class="bi bi-clock-history me-2"></i>Estado del Vencimiento</h6>
                                            <div id="estado-vencimiento" class="alert alert-info">
                                                <i class="bi bi-info-circle me-2"></i>Selecciona una fecha de vencimiento para ver el estado.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 6: PROVEEDORES -->
                    <div class="tab-pane fade" id="proveedores" role="tabpanel" aria-labelledby="proveedores-tab">
                        <h5 class="mb-4"><i class="bi bi-building text-secondary me-2"></i>Gestión de Proveedores</h5>
                        
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Proveedores Asociados</h6>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="abrirModalProveedor()">
                                            <i class="bi bi-plus me-1"></i>Agregar Proveedor
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div id="lista-proveedores">
                                            <div class="text-center text-muted py-4">
                                                <i class="bi bi-building fs-1"></i>
                                                <p>No hay proveedores asociados aún.</p>
                                                <small>Agrega proveedores para gestionar códigos y precios específicos.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BOTONES DE ACCIÓN -->
                <div class="section-divider">
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg px-5 me-3">
                            <i class="bi bi-save me-2"></i><?php echo $es_edicion ? 'Actualizar' : 'Guardar'; ?> Producto
                        </button>
                        <a href="productos.php" class="btn btn-outline-secondary btn-lg px-4">
                            <i class="bi bi-x-circle me-2"></i>Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // VARIABLES GLOBALES
        let monedaSimboloActual = '$';
        let impuestoPorcentajeActual = 0;
        let monedasDisponibles = <?php echo json_encode($monedas); ?>;
        let impuestosDisponibles = <?php echo json_encode($impuestos); ?>;

        // INICIALIZACIÓN
        document.addEventListener('DOMContentLoaded', function() {
            inicializarFormulario();
            calcularPrecios();
            actualizarIndicadoresStock();
            verificarVencimiento();
        });

        // FUNCIÓN PRINCIPAL DE INICIALIZACIÓN
        function inicializarFormulario() {
            // Configurar evento del scanner
            document.getElementById('btn-scanner').addEventListener('click', activarScanner);
            
            // Configurar eventos de stock y vencimiento
            document.getElementById('stock').addEventListener('input', actualizarIndicadoresStock);
            document.getElementById('fecha_vencimiento').addEventListener('change', verificarVencimiento);
            
            // Cargar configuración inicial si existe
            const monedaSeleccionada = document.getElementById('moneda_id').value;
            if (monedaSeleccionada) {
                actualizarSimboloMoneda();
            }
            
            // Configurar estado inicial de controles
            toggleControlStock();
            toggleVencimientos();
        }

        // SCANNER DE CÓDIGOS DE BARRAS
        function activarScanner() {
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                const video = document.createElement('video');
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                
                // Crear modal para el scanner
                const scannerModal = `
                    <div class="modal fade" id="scannerModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Scanner de Códigos de Barras</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <video id="video-scanner" width="100%" height="300" autoplay></video>
                                    <div class="mt-3">
                                        <button class="btn btn-success" onclick="capturarCodigo()">
                                            <i class="bi bi-camera me-2"></i>Capturar
                                        </button>
                                        <button class="btn btn-secondary" onclick="detenerScanner()">
                                            <i class="bi bi-stop me-2"></i>Detener
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                
                document.body.insertAdjacentHTML('beforeend', scannerModal);
                const modal = new bootstrap.Modal(document.getElementById('scannerModal'));
                modal.show();
                
                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                    .then(function(stream) {
                        video.srcObject = stream;
                        video.play();
                    })
                    .catch(function(err) {
                        console.error('Error al acceder a la cámara:', err);
                        alert('No se pudo acceder a la cámara. Usa entrada manual.');
                    });
            } else {
                // Fallback para entrada manual
                const codigo = prompt('Ingresa el código de barras manualmente:');
                if (codigo) {
                    document.getElementById('codigo_barras').value = codigo;
                    verificarCodigoExistente(codigo);
                }
            }
        }

        function capturarCodigo() {
            // Simulación de captura (en producción aquí iría la lógica de OCR)
            const codigoEjemplo = 'PROD-' + String(Math.floor(Math.random() * 999999)).padStart(6, '0');
            document.getElementById('codigo_barras').value = codigoEjemplo;
            verificarCodigoExistente(codigoEjemplo);
            detenerScanner();
        }

        function detenerScanner() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('scannerModal'));
            modal.hide();
            document.getElementById('scannerModal').remove();
        }

        // VERIFICACIÓN DE CÓDIGO EXISTENTE
        function verificarCodigoExistente(codigo) {
            if (codigo.length > 3) {
                fetch('verificar_codigo_producto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ codigo: codigo })
                })
                .then(response => response.json())
                .then(data => {
                    const alertDiv = document.getElementById('alerta-codigo');
                    if (data.existe) {
                        alertDiv.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Código existente:</strong> Ya existe un producto con este código.
                                <a href="producto_form.php?id=${data.producto_id}" class="alert-link">Ver producto</a>
                            </div>`;
                    } else {
                        alertDiv.innerHTML = `
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                <strong>Código disponible:</strong> Puedes usar este código.
                            </div>`;
                    }
                });
            }
        }

        // GESTIÓN DE MONEDAS E IMPUESTOS
        function cargarMonedasImpuestos() {
            const paisId = document.getElementById('pais_fiscal').value;
            
            // Mostrar/ocultar monedas según país
            const selectMoneda = document.getElementById('moneda_id');
            const opciones = selectMoneda.querySelectorAll('option');
            opciones.forEach(opcion => {
                if (opcion.value === '' || opcion.dataset.pais === paisId) {
                    opcion.style.display = 'block';
                } else {
                    opcion.style.display = 'none';
                }
            });
            
            // Mostrar/ocultar impuestos según país
            const impuestosContainer = document.getElementById('impuestos-container');
            const opcionesImpuesto = impuestosContainer.querySelectorAll('.impuesto-option');
            opcionesImpuesto.forEach(opcion => {
                if (opcion.dataset.pais === paisId) {
                    opcion.style.display = 'block';
                } else {
                    opcion.style.display = 'none';
                }
            });
            
            // Actualizar información fiscal
            actualizarInfoFiscal();
        }

        function actualizarSimboloMoneda() {
            const selectMoneda = document.getElementById('moneda_id');
            const opcionSeleccionada = selectMoneda.options[selectMoneda.selectedIndex];
            
            if (opcionSeleccionada && opcionSeleccionada.dataset.simbolo) {
                monedaSimboloActual = opcionSeleccionada.dataset.simbolo;
                document.getElementById('simbolo-moneda').textContent = monedaSimboloActual;
            }
            
            actualizarInfoFiscal();
            calcularPrecios();
        }

        function actualizarInfoFiscal() {
            const selectMoneda = document.getElementById('moneda_id');
            const impuestoSeleccionado = document.querySelector('input[name="impuesto_id"]:checked');
            
            let infoMoneda = 'Sin moneda';
            let infoImpuesto = 'Sin impuesto';
            
            if (selectMoneda.value) {
                const opcion = selectMoneda.options[selectMoneda.selectedIndex];
                infoMoneda = opcion.text;
                monedaSimboloActual = opcion.dataset.simbolo || '$';
            }
            
            if (impuestoSeleccionado) {
                impuestoPorcentajeActual = parseFloat(impuestoSeleccionado.dataset.porcentaje);
                infoImpuesto = impuestoPorcentajeActual + '%';
            }
            
            document.getElementById('info-moneda').textContent = infoMoneda;
            document.getElementById('info-impuesto').textContent = infoImpuesto;
        }

        // CALCULADORA DE PRECIOS
        function calcularPrecios() {
            const precioCompra = parseFloat(document.getElementById('precio_compra').value) || 0;
            const utilidadMinorista = parseFloat(document.getElementById('utilidad_minorista').value) || 0;
            const utilidadMayorista = parseFloat(document.getElementById('utilidad_mayorista').value) || 0;
            const aplicarImpuesto = document.getElementById('aplicar_impuesto_venta').checked;
            
            if (precioCompra <= 0) {
                resetearPrecios();
                return;
            }
            
            // Calcular precios base con utilidad
            const precioMinSinImpuesto = precioCompra * (1 + utilidadMinorista / 100);
            const precioMaySinImpuesto = precioCompra * (1 + utilidadMayorista / 100);
            
            // Calcular impuestos
            let impuestoMin = 0;
            let impuestoMay = 0;
            
            if (aplicarImpuesto && impuestoPorcentajeActual > 0) {
                impuestoMin = precioMinSinImpuesto * (impuestoPorcentajeActual / 100);
                impuestoMay = precioMaySinImpuesto * (impuestoPorcentajeActual / 100);
            }
            
            // Calcular precios finales
            const precioMinFinal = precioMinSinImpuesto + impuestoMin;
            const precioMayFinal = precioMaySinImpuesto + impuestoMay;
            
            // Aplicar redondeo
            const decimales = parseInt(document.getElementById('redondeo_decimales').value);
            
            // Actualizar interfaz
            document.getElementById('precio-min-sin-impuesto').textContent = monedaSimboloActual + precioMinSinImpuesto.toFixed(decimales);
            document.getElementById('impuesto-minorista').textContent = monedaSimboloActual + impuestoMin.toFixed(decimales);
            document.getElementById('precio-minorista-final').textContent = monedaSimboloActual + precioMinFinal.toFixed(decimales);
            
            document.getElementById('precio-may-sin-impuesto').textContent = monedaSimboloActual + precioMaySinImpuesto.toFixed(decimales);
            document.getElementById('impuesto-mayorista').textContent = monedaSimboloActual + impuestoMay.toFixed(decimales);
            document.getElementById('precio-mayorista-final').textContent = monedaSimboloActual + precioMayFinal.toFixed(decimales);
            
            // Actualizar campos ocultos
            document.getElementById('precio_minorista').value = precioMinFinal.toFixed(decimales);
            document.getElementById('precio_mayorista').value = precioMayFinal.toFixed(decimales);
        }

        function resetearPrecios() {
            document.getElementById('precio-min-sin-impuesto').textContent = monedaSimboloActual + '0.00';
            document.getElementById('impuesto-minorista').textContent = monedaSimboloActual + '0.00';
            document.getElementById('precio-minorista-final').textContent = monedaSimboloActual + '0.00';
            document.getElementById('precio-may-sin-impuesto').textContent = monedaSimboloActual + '0.00';
            document.getElementById('impuesto-mayorista').textContent = monedaSimboloActual + '0.00';
            document.getElementById('precio-mayorista-final').textContent = monedaSimboloActual + '0.00';
        }

        // CONTROL DE STOCK
        function toggleControlStock() {
            const usarControl = document.getElementById('usar_control_stock').checked;
            const controles = document.getElementById('stock-controls');
            
            if (usarControl) {
                controles.style.display = 'block';
                document.getElementById('stock').required = true;
            } else {
                controles.style.display = 'none';
                document.getElementById('stock').required = false;
            }
        }

        function actualizarIndicadoresStock() {
            const stock = parseInt(document.getElementById('stock').value) || 0;
            const stockMin = parseInt(document.getElementById('stock_minimo').value) || 0;
            const stockMax = parseInt(document.getElementById('stock_maximo').value) || 9999;
            
            document.getElementById('indicador-actual').textContent = stock;
            document.getElementById('indicador-disponible').textContent = Math.max(0, stock);
            
            let nivel = 'Normal';
            let colorClase = 'bg-info';
            
            if (stock <= stockMin) {
                nivel = 'Crítico';
                colorClase = 'bg-danger';
            } else if (stock <= stockMin * 1.5) {
                nivel = 'Bajo';
                colorClase = 'bg-warning';
            } else if (stock >= stockMax) {
                nivel = 'Exceso';
                colorClase = 'bg-secondary';
            }
            
            const indicadorNivel = document.getElementById('indicador-nivel');
            indicadorNivel.textContent = nivel;
            indicadorNivel.parentElement.className = `card ${colorClase} text-white`;
        }

        // CONTROL DE VENCIMIENTOS
        function toggleVencimientos() {
            const usarAlerta = document.getElementById('usar_alerta_vencimiento').checked;
            const controles = document.getElementById('vencimiento-controls');
            
            controles.style.display = usarAlerta ? 'block' : 'none';
        }

        function verificarVencimiento() {
            const fechaVencimiento = document.getElementById('fecha_vencimiento').value;
            const diasAlerta = parseInt(document.getElementById('alerta_vencimiento_dias').value) || 30;
            
            if (!fechaVencimiento) {
                return;
            }
            
            const hoy = new Date();
            const fechaVenc = new Date(fechaVencimiento);
            const diferenciaDias = Math.ceil((fechaVenc - hoy) / (1000 * 60 * 60 * 24));
            
            const estadoDiv = document.getElementById('estado-vencimiento');
            
            if (diferenciaDias < 0) {
                estadoDiv.className = 'alert alert-danger';
                estadoDiv.innerHTML = `<i class="bi bi-x-circle me-2"></i><strong>VENCIDO:</strong> El producto venció hace ${Math.abs(diferenciaDias)} días.`;
            } else if (diferenciaDias <= diasAlerta) {
                estadoDiv.className = 'alert alert-warning';
                estadoDiv.innerHTML = `<i class="bi bi-exclamation-triangle me-2"></i><strong>PRÓXIMO A VENCER:</strong> Quedan ${diferenciaDias} días.`;
            } else {
                estadoDiv.className = 'alert alert-success';
                estadoDiv.innerHTML = `<i class="bi bi-check-circle me-2"></i><strong>VIGENTE:</strong> Quedan ${diferenciaDias} días para el vencimiento.`;
            }
        }

        // MODALES Y FUNCIONES ADICIONALES
        function abrirModalMoneda() {
            alert('Función para agregar nueva moneda - A desarrollar');
        }

        function abrirModalImpuesto() {
            alert('Función para agregar nuevo impuesto - A desarrollar');
        }

        function abrirModalProveedor() {
            alert('Función para agregar nuevo proveedor - A desarrollar');
        }

        // EVENT LISTENERS ADICIONALES
        document.addEventListener('change', function(e) {
            if (e.target.name === 'impuesto_id') {
                impuestoPorcentajeActual = parseFloat(e.target.dataset.porcentaje) || 0;
                actualizarInfoFiscal();
                calcularPrecios();
            }
            
            if (e.target.id === 'moneda_id') {
                actualizarSimboloMoneda();
            }
        });

        // GENERACIÓN AUTOMÁTICA DE CÓDIGO
        function generarCodigoAutomatico() {
            const timestamp = Date.now().toString().slice(-6);
            const codigo = 'PROD-' + timestamp;
            document.getElementById('codigo_barras').value = codigo;
            verificarCodigoExistente(codigo);
        }

        // VALIDACIÓN DEL FORMULARIO
        function validarFormulario() {
            let errores = [];
            
            // Validaciones básicas
            if (!document.getElementById('codigo_barras').value.trim()) {
                errores.push('El código de barras es obligatorio');
            }
            
            if (!document.getElementById('nombre').value.trim()) {
                errores.push('El nombre del producto es obligatorio');
            }
            
            if (!document.getElementById('categoria_id').value) {
                errores.push('Debe seleccionar una categoría');
            }
            
            // Validaciones de precios
            const precioCompra = parseFloat(document.getElementById('precio_compra').value);
            if (isNaN(precioCompra) || precioCompra <= 0) {
                errores.push('El precio de compra debe ser mayor a 0');
            }
            
            // Validaciones de stock
            if (document.getElementById('usar_control_stock').checked) {
                const stock = parseInt(document.getElementById('stock').value);
                if (isNaN(stock) || stock < 0) {
                    errores.push('El stock debe ser un número válido mayor o igual a 0');
                }
            }
            
            if (errores.length > 0) {
                alert('Errores encontrados:\n- ' + errores.join('\n- '));
                return false;
            }
            
            return true;
        }

        // Agregar validación al formulario
        document.getElementById('producto-form').addEventListener('submit', function(e) {
            if (!validarFormulario()) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
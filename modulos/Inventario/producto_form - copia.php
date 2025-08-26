<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

$errores = [];
$mensaje_exito = '';
$es_edicion = false;

$producto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$step = isset($_GET['step']) ? $_GET['step'] : 'basico';

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    die('Error de conexión: ' . htmlspecialchars($e->getMessage()));
}

// Cargar datos existentes si es edición
$producto = null;
if ($producto_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($producto) {
    $es_edicion = true;
    $codigo = $producto['codigo'];
    $codigo_barra = $producto['codigo_barra'];
    $nombre = $producto['nombre'];
    $descripcion = $producto['descripcion'];
    $categoria_id = $producto['categoria_id'];
    $lugar_id = $producto['lugar_id'];
    $unidad_medida = $producto['unidad_medida'];
    $precio_compra = $producto['precio_compra'];
    $precio_minorista = $producto['precio_minorista'];
    $precio_mayorista = $producto['precio_mayorista'];
    $precio_venta = $producto['precio_venta'];
    $utilidad_minorista = $producto['utilidad_minorista'];
    $utilidad_mayorista = $producto['utilidad_mayorista'];
    $stock = $producto['stock'];
    $stock_minimo = $producto['stock_minimo'];
    $stock_maximo = $producto['stock_maximo'];
    $usar_control_stock = $producto['usar_control_stock'];
    $usar_alerta_vencimiento = $producto['usar_alerta_vencimiento'];
    $fecha_vencimiento = $producto['fecha_vencimiento'];
    $dias_alerta_vencimiento = $producto['dias_alerta_vencimiento'];
    $moneda_id = $producto['moneda_id'];
    $impuesto_id = $producto['impuesto_id'];
    $publicar_web = $producto['publicar_web'];
    // Proveedores (para selección previa en Paso 2)
    $stmt2 = $pdo->prepare("SELECT * FROM productos_proveedores WHERE producto_id = ?");
    $stmt2->execute([$producto_id]);
    $pp = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];
    $proveedor_principal = $pp['proveedor_principal'] ?? '';
    $proveedor_alternativo01 = $pp['proveedor_alternativo01'] ?? '';
    $proveedor_alternativo02 = $pp['proveedor_alternativo02'] ?? '';
    $proveedor_alternativo03 = $pp['proveedor_alternativo03'] ?? '';
    $proveedor_alternativo04 = $pp['proveedor_alternativo04'] ?? '';
    } else {
    $errores[] = 'Producto no encontrado.';
    $es_edicion = false;
    $producto_id = 0;
    }
}

// Si es ALTA, generar código correlativo y valores por defecto
if (!$es_edicion) {
    try {
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(codigo, 6) AS UNSIGNED)) as max_correlativo FROM productos WHERE codigo LIKE 'PROD-%'");
    $max_correlativo = $stmt->fetchColumn() ?? 0;
    $siguiente_correlativo = $max_correlativo + 1;
    $codigo = 'PROD-' . str_pad($siguiente_correlativo, 7, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
    $errores[] = 'No se pudo generar el código: ' . $e->getMessage();
    $codigo = 'PROD-ERROR';
    }
    $codigo_barra = $nombre = $descripcion = '';
    $categoria_id = $lugar_id = '';
    $unidad_medida = 'UN';
    $precio_compra = $precio_minorista = $precio_mayorista = $precio_venta = 0;
    $utilidad_minorista = $utilidad_mayorista = 0;
    $stock = $stock_minimo = $stock_maximo = 0;
    $usar_control_stock = 0;
    $usar_alerta_vencimiento = 0;
    $fecha_vencimiento = null;
    $dias_alerta_vencimiento = 7;
    $moneda_id = 1;
    $impuesto_id = 1;
    $publicar_web = 0;
    $proveedor_principal = $proveedor_alternativo01 = $proveedor_alternativo02 = $proveedor_alternativo03 = $proveedor_alternativo04 = '';
}

// Acciones POST (modular)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar_basico') {
    // Helper numérico (acepta "100,50")
    $num = function($v) { return (float)str_replace(',', '.', (string)$v); };

    // Paso 1: datos básicos
    $nombre = trim($_POST['nombre'] ?? '');
    $codigo_barra = trim($_POST['codigo_barra'] ?? '');
    $categoria_id = $_POST['categoria_id'] ?? null;
    $lugar_id = $_POST['lugar_id'] ?? null;
    $unidad_medida = $_POST['unidad_medida'] ?? 'UN';

    $precio_compra = $num($_POST['precio_compra'] ?? 0);
    $utilidad_minorista = $num($_POST['utilidad_minorista'] ?? 0);
    $utilidad_mayorista = $num($_POST['utilidad_mayorista'] ?? 0);

    // Se recalculan precios abajo
    $usar_alerta_vencimiento = isset($_POST['usar_alerta_vencimiento']) ? 1 : 0;
    $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? null;
    $dias_alerta_vencimiento = isset($_POST['dias_alerta_vencimiento']) ? intval($_POST['dias_alerta_vencimiento']) : null;
    $stock = intval($_POST['stock'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 0);
    $stock_maximo = intval($_POST['stock_maximo'] ?? 0);
    $usar_control_stock = isset($_POST['usar_control_stock']) ? 1 : 0;
    $moneda_id = intval($_POST['moneda_id'] ?? 1);
    $impuesto_id = intval($_POST['impuesto_id'] ?? 1);
    $descripcion = $_POST['descripcion'] ?? '';

    // Validaciones mínimas
    if ($nombre === '') $errores[] = 'El nombre es obligatorio.';
    if ($codigo_barra === '') $errores[] = 'El código de barras es obligatorio.';
    if ($precio_compra <= 0) $errores[] = 'El precio de compra debe ser mayor a 0.';

    // Unicidad código de barras
    if (empty($errores)) {
    try {
    if ($es_edicion) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE codigo_barra = ? AND id != ?");
    $stmt->execute([$codigo_barra, $producto_id]);
    } else {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE codigo_barra = ?");
    $stmt->execute([$codigo_barra]);
    }
    if (intval($stmt->fetchColumn()) > 0) {
    $errores[] = 'El código de barras ya existe en otro producto.';
    }
    } catch (PDOException $e) {
    $errores[] = 'Error al validar código de barras: ' . $e->getMessage();
    }
    }

    // Recalcular precios con % real del impuesto
    if (empty($errores)) {
    try {
    $stmt = $pdo->prepare("SELECT porcentaje FROM impuestos WHERE id = ?");
    $stmt->execute([$impuesto_id]);
    $porcentaje = (float)($stmt->fetchColumn() ?: 0.0);

    $factorImpuesto = 1 + ($porcentaje / 100.0);
    $precio_minorista = round($precio_compra * $factorImpuesto * (1 + ($utilidad_minorista/100.0)), 2);
    $precio_mayorista = round($precio_compra * $factorImpuesto * (1 + ($utilidad_mayorista/100.0)), 2);
    $precio_venta = $precio_minorista; // por defecto
    } catch (PDOException $e) {
    $errores[] = 'No se pudo obtener el impuesto: ' . $e->getMessage();
    }
    }

    if (empty($errores)) {
    try {
    if ($es_edicion) {
    $sql = "UPDATE productos
    SET codigo_barra=?, nombre=?, descripcion=?, categoria_id=?, lugar_id=?, unidad_medida=?,
    precio_compra=?, utilidad_minorista=?, utilidad_mayorista=?,
    precio_minorista=?, precio_mayorista=?, precio_venta=?,
    stock=?, stock_minimo=?, stock_maximo=?,
    usar_control_stock=?, usar_alerta_vencimiento=?, fecha_vencimiento=?, dias_alerta_vencimiento=?,
    moneda_id=?, impuesto_id=?
    WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
    $codigo_barra, $nombre, $descripcion, $categoria_id, $lugar_id, $unidad_medida,
    $precio_compra, $utilidad_minorista, $utilidad_mayorista,
    $precio_minorista, $precio_mayorista, $precio_venta,
    $stock, $stock_minimo, $stock_maximo,
    $usar_control_stock, $usar_alerta_vencimiento,
    $usar_alerta_vencimiento ? $fecha_vencimiento : null,
    $usar_alerta_vencimiento ? $dias_alerta_vencimiento : null,
    $moneda_id, $impuesto_id,
    $producto_id
    ]);
    } else {
    $sql = "INSERT INTO productos
    (codigo, codigo_barra, nombre, descripcion, categoria_id, lugar_id, unidad_medida,
    precio_compra, utilidad_minorista, utilidad_mayorista,
    precio_minorista, precio_mayorista, precio_venta,
    stock, stock_minimo, stock_maximo, usar_control_stock, usar_alerta_vencimiento,
    fecha_vencimiento, dias_alerta_vencimiento, moneda_id, impuesto_id, publicar_web)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
    $codigo, $codigo_barra, $nombre, $descripcion, $categoria_id, $lugar_id, $unidad_medida,
    $precio_compra, $utilidad_minorista, $utilidad_mayorista,
    $precio_minorista, $precio_mayorista, $precio_venta,
    $stock, $stock_minimo, $stock_maximo, $usar_control_stock,
    $usar_alerta_vencimiento, $usar_alerta_vencimiento ? $fecha_vencimiento : null,
    $usar_alerta_vencimiento ? $dias_alerta_vencimiento : null,
    $moneda_id, $impuesto_id, 0
    ]);
    $producto_id = intval($pdo->lastInsertId());
    $es_edicion = true;
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $producto_id . '&step=proveedores&ok=1');
    exit;
    } catch (PDOException $e) {
    $errores[] = 'Error al guardar datos básicos: ' . $e->getMessage();
    }
    }
    $step = 'basico';
    }

    if ($accion === 'guardar_proveedores') {
    if ($producto_id <= 0) {
    $errores[] = 'ID de producto inválido.';
    } else {
    $publicar_web = isset($_POST['publicar_web']) ? 1 : 0;
    $proveedor_principal = $_POST['proveedor_principal'] ?? null;
    $proveedor_alternativo01 = $_POST['proveedor_alternativo01'] ?? null;
    $proveedor_alternativo02 = $_POST['proveedor_alternativo02'] ?? null;
    $proveedor_alternativo03 = $_POST['proveedor_alternativo03'] ?? null;
    $proveedor_alternativo04 = $_POST['proveedor_alternativo04'] ?? null;

    if (!is_numeric($proveedor_principal) || intval($proveedor_principal) <= 0) {
    $errores[] = 'El proveedor principal es obligatorio.';
    }

    if (empty($errores)) {
    try {
    $proveedor_principal    = (is_numeric($proveedor_principal) && $proveedor_principal > 0) ? intval($proveedor_principal) : null;
    $proveedor_alternativo01 = (is_numeric($proveedor_alternativo01) && $proveedor_alternativo01 > 0) ? intval($proveedor_alternativo01) : null;
    $proveedor_alternativo02 = (is_numeric($proveedor_alternativo02) && $proveedor_alternativo02 > 0) ? intval($proveedor_alternativo02) : null;
    $proveedor_alternativo03 = (is_numeric($proveedor_alternativo03) && $proveedor_alternativo03 > 0) ? intval($proveedor_alternativo03) : null;
    $proveedor_alternativo04 = (is_numeric($proveedor_alternativo04) && $proveedor_alternativo04 > 0) ? intval($proveedor_alternativo04) : null;

    $check = $pdo->prepare("SELECT COUNT(*) FROM productos_proveedores WHERE producto_id = ?");
    $check->execute([$producto_id]);

    if (intval($check->fetchColumn()) > 0) {
    $sql_pp = "UPDATE productos_proveedores
    SET proveedor_principal=?, proveedor_alternativo01=?, proveedor_alternativo02=?, proveedor_alternativo03=?, proveedor_alternativo04=?
    WHERE producto_id=?";
    $stmt_pp = $pdo->prepare($sql_pp);
    $stmt_pp->bindValue(1, $proveedor_principal, is_null($proveedor_principal) ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt_pp->bindValue(2, $proveedor_alternativo01, is_null($proveedor_alternativo01) ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt_pp->bindValue(3, $proveedor_alternativo02, is_null($proveedor_alternativo02) ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt_pp->bindValue(4, $proveedor_alternativo03, is_null($proveedor_alternativo03) ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt_pp->bindValue(5, $proveedor_alternativo04, is_null($proveedor_alternativo04) ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt_pp->bindValue(6, $producto_id, PDO::PARAM_INT);
    $stmt_pp->execute();
    } else {
    $sql_pp = "INSERT INTO productos_proveedores (producto_id, proveedor_principal, proveedor_alternativo01, proveedor_alternativo02, proveedor_alternativo03, proveedor_alternativo04)
    VALUES (?,?,?,?,?,?)";
    $stmt_pp = $pdo->prepare($sql_pp);
    $stmt_pp->bindValue(1, $producto_id, PDO::PARAM_INT);
    $stmt_pp->bindValue(2, $proveedor_principal, is_null($proveedor_principal) ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt_pp->bindValue(3, $proveedor_alternativo01, is_null($proveedor_alternativo01) ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt_pp->bindValue(4, $proveedor_alternativo02, is_null($proveedor_alternativo02) ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt_pp->bindValue(5, $proveedor_alternativo03, is_null($proveedor_alternativo03) ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt_pp->bindValue(6, $proveedor_alternativo04, is_null($proveedor_alternativo04) ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt_pp->execute();
    }

    // Subida de imagen (opcional)
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    if (!$es_edicion) {
    $stmt = $pdo->prepare("SELECT codigo FROM productos WHERE id=?");
    $stmt->execute([$producto_id]);
    $codigo = $stmt->fetchColumn();
    }
    $img_dir = __DIR__ . '/../../assets/img/productos/';
    if (!is_dir($img_dir)) @mkdir($img_dir, 0777, true);
    $destino = $img_dir . $codigo . '.jpg';
    $info = getimagesize($_FILES['foto']['tmp_name']);
    if ($info && ($info[2] === IMAGETYPE_JPEG || $info[2] === IMAGETYPE_PNG)) {
    if ($info[2] === IMAGETYPE_PNG) {
    $img = imagecreatefrompng($_FILES['foto']['tmp_name']);
    imagejpeg($img, $destino, 90);
    imagedestroy($img);
    } else {
    move_uploaded_file($_FILES['foto']['tmp_name'], $destino);
    }
    }
    }

    $upd = $pdo->prepare("UPDATE productos SET publicar_web=? WHERE id=?");
    $upd->execute([$publicar_web, $producto_id]);

    $mensaje_exito = 'Proveedores e imagen guardados correctamente.';
    } catch (PDOException $e) {
    $errores[] = 'Error al guardar proveedores: ' . $e->getMessage();
    }
    }
    }
    $step = 'proveedores';
    }
}

// Catálogos
$categorias = $lugares = $monedas = $impuestos = $proveedores_list = [];
try {
    $categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $lugares = $pdo->query("SELECT id, nombre FROM lugares ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $monedas = $pdo->query("SELECT id, nombre FROM monedas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    // Traer también porcentaje para cálculo en vivo
    $impuestos = $pdo->query("SELECT id, nombre, porcentaje FROM impuestos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $proveedores_list = $pdo->query("SELECT id, razon_social FROM proveedores ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errores[] = 'No se pudieron cargar los catálogos: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alta / Edición de producto (Modular)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    /* Barra superior */
    .hero-blue {
    background: linear-gradient(90deg, #0d6efd, #0a58ca);
    color: #fff;
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 18px 0 10px;
    }
    .hero-blue h3 { margin: 0; font-weight: 600; }

    /* Pestañas */
    .nav-tabs { border-bottom: 0; }
    .nav-tabs .nav-link {
    border: 0;
    color: #0d6efd;
    background-color: #e9f2ff;
    margin-right: 6px;
    border-radius: 8px 8px 0 0;
    padding: .5rem .9rem;
    font-weight: 600;
    }
    .nav-tabs .nav-link.active {
    background-color: #0d6efd;
    color: #fff;
    }
    .nav-tabs .nav-link.disabled {
    opacity: .6;
    background-color: #e9f2ff;
    color: #6c9cff;
    pointer-events: none;
    }

    /* Tablas compactas y responsive */
    .table-responsive { border: 1px solid #e9ecef; border-radius: 6px; }
    .table > :not(caption) > * > * { padding: .35rem .5rem; }
    .form-control, .form-select { padding: .35rem .5rem; font-size: .95rem; }
    textarea.form-control { min-height: 42px; }

    /* Checkboxes azules */
    .form-check-input { width: 1.1rem; height: 1.1rem; }
    .form-check-input:checked { background-color: #0d6efd; border-color: #0d6efd; }

    /* Ocultar cualquier badge de código antiguo si quedara */
    .badge.text-bg-secondary, .badge.text-bg-info { display: none !important; }

    @media (max-width: 992px) {
    .card.p-4 { padding: 1rem !important; }
    }
    </style>
</head>
<body class="bg-light">
<?php include "../../config/navbar_code.php"; ?>
<div class="container">

    <div class="hero-blue">
    <h3>Datos del Producto</h3>
    </div>

    <ul class="nav nav-tabs mb-3">
    <li class="nav-item">
    <a class="nav-link <?php echo $step==='basico' ? 'active' : ''; ?>" href="<?php echo $_SERVER['PHP_SELF'] . ($es_edicion ? '?id='.$producto_id.'&step=basico' : ''); ?>">Datos básicos</a>
    </li>
    <li class="nav-item">
    <a class="nav-link <?php echo $step==='proveedores' ? 'active' : ''; ?> <?php echo !$es_edicion ? 'disabled' : ''; ?>" href="<?php echo $es_edicion ? $_SERVER['PHP_SELF'].'?id='.$producto_id.'&step=proveedores' : '#'; ?>">Proveedores e imagen</a>
    </li>
    </ul>

    <?php if ($errores): ?>
    <div class="alert alert-danger">
    <?php foreach ($errores as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?>
    </div>
    <?php endif; ?>

    <?php if ($mensaje_exito): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($mensaje_exito); ?></div>
    <?php endif; ?>

    <?php if ($step === 'basico'): ?>
    <form method="post" class="card p-4 bg-white">
    <input type="hidden" name="accion" value="guardar_basico">
    <div class="table-responsive">
    <table class="table table-bordered align-middle mb-0">
    <tr><td colspan="4"><b>Información General</b></td></tr>
    <tr>
    <td><label class="form-label">Código Interno *</label></td>
    <td><input type="text" class="form-control" value="<?php echo htmlspecialchars($codigo); ?>" readonly></td>
    <td><label class="form-label">Código de Barras *</label></td>
    <td><input type="text" name="codigo_barra" class="form-control" required value="<?php echo htmlspecialchars($codigo_barra ?? ''); ?>"></td>
    </tr>
    <tr>
    <td><label class="form-label">Nombre del Producto *</label></td>
    <td><input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($nombre ?? ''); ?>"></td>
    <td><label class="form-label">Descripción</label></td>
    <td><textarea name="descripcion" class="form-control"><?php echo htmlspecialchars($descripcion ?? ''); ?></textarea></td>
    </tr>
    <tr>
    <td><label class="form-label">Categoría</label></td>
    <td>
    <select name="categoria_id" class="form-select">
    <option value="">-- Seleccionar --</option>
    <?php foreach ($categorias as $cat): ?>
    <option value="<?php echo $cat['id']; ?>" <?php if (($categoria_id ?? '') == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['nombre']); ?></option>
    <?php endforeach; ?>
    </select>
    </td>
    <td><label class="form-label">Lugar</label></td>
    <td>
    <select name="lugar_id" class="form-select">
    <option value="">-- Seleccionar --</option>
    <?php foreach ($lugares as $lug): ?>
    <option value="<?php echo $lug['id']; ?>" <?php if (($lugar_id ?? '') == $lug['id']) echo 'selected'; ?>><?php echo htmlspecialchars($lug['nombre']); ?></option>
    <?php endforeach; ?>
    </select>
    </td>
    </tr>
    <tr>
    <td><label class="form-label">Unidad de Medida</label></td>
    <td colspan="3">
    <select name="unidad_medida" class="form-select w-25">
    <option value="UN"  <?php if (($unidad_medida ?? '') == 'UN')  echo 'selected'; ?>>Unidades</option>
    <option value="KG"  <?php if (($unidad_medida ?? '') == 'KG')  echo 'selected'; ?>>Kilogramos</option>
    <option value="LT"  <?php if (($unidad_medida ?? '') == 'LT')  echo 'selected'; ?>>Litros</option>
    <option value="M2"  <?php if (($unidad_medida ?? '') == 'M2')  echo 'selected'; ?>>Metros cuadrados</option>
    <option value="CAJA"<?php if (($unidad_medida ?? '') == 'CAJA')echo 'selected'; ?>>Caja</option>
    </select>
    </td>
    </tr>
    <tr><td colspan="4"><b>Impuestos/Moneda</b></td></tr>
    <tr>
    <td><label class="form-label">Moneda *</label></td>
    <td>
    <select name="moneda_id" class="form-select">
    <?php foreach ($monedas as $m): ?>
    <option value="<?php echo $m['id']; ?>" <?php if (($moneda_id ?? '') == $m['id']) echo 'selected'; ?>><?php echo htmlspecialchars($m['nombre']); ?></option>
    <?php endforeach; ?>
    </select>
    </td>
    <td><label class="form-label">Tipo de Impuesto *</label></td>
    <td>
    <select id="impuesto_id" name="impuesto_id" class="form-select" required>
    <?php foreach ($impuestos as $imp): ?>
    <option value="<?php echo $imp['id']; ?>"
    data-porcentaje="<?php echo (float)$imp['porcentaje']; ?>"
    <?php if (($impuesto_id ?? '') == $imp['id']) echo 'selected'; ?>>
    <?php echo htmlspecialchars($imp['nombre']); ?>
    </option>
    <?php endforeach; ?>
    </select>
    </td>
    </tr>

    <tr><td colspan="4"><b>Precios</b></td></tr>
    <tr>
    <td><label class="form-label">Precio de Compra (Costo) *</label></td>
    <td>
    <input type="number" class="form-control" id="precio_compra" name="precio_compra"
    step="0.01" min="0" required value="<?php echo htmlspecialchars($precio_compra ?? 0); ?>">
    </td>
    <td><label class="form-label">% Utilidad Minorista</label></td>
    <td>
    <input type="number" class="form-control" id="utilidad_minorista" name="utilidad_minorista"
    step="0.01" min="0" value="<?php echo htmlspecialchars($utilidad_minorista ?? 0); ?>">
    </td>
    </tr>
    <tr>
    <td><label class="form-label">Precio Minorista (calculado)</label></td>
    <td>
    <input type="number" class="form-control" id="precio_minorista" name="precio_minorista"
    step="0.01" min="0" value="<?php echo htmlspecialchars($precio_minorista ?? 0); ?>" readonly>
    </td>
    <td><label class="form-label">% Utilidad Mayorista</label></td>
    <td>
    <input type="number" class="form-control" id="utilidad_mayorista" name="utilidad_mayorista"
    step="0.01" min="0" value="<?php echo htmlspecialchars($utilidad_mayorista ?? 0); ?>">
    </td>
    </tr>
    <tr>
    <td><label class="form-label">Precio Mayorista (calculado)</label></td>
    <td>
    <input type="number" class="form-control" id="precio_mayorista" name="precio_mayorista"
    step="0.01" min="0" value="<?php echo htmlspecialchars($precio_mayorista ?? 0); ?>" readonly>
    </td>
    <td><label class="form-label">Precio de Venta (usa minorista)</label></td>
    <td>
    <input type="number" class="form-control" id="precio_venta" name="precio_venta"
    step="0.01" min="0" value="<?php echo htmlspecialchars($precio_venta ?? 0); ?>" readonly>
    </td>
    </tr>

    <tr><td colspan="4"><b>Stock</b></td></tr>
    <tr>
    <td><label class="form-label">Stock Actual</label></td>
    <td><input type="number" name="stock" class="form-control" min="0" value="<?php echo htmlspecialchars($stock ?? 0); ?>"></td>
    <td><label class="form-label">Stock Mínimo</label></td>
    <td><input type="number" name="stock_minimo" class="form-control" min="0" value="<?php echo htmlspecialchars($stock_minimo ?? 0); ?>"></td>
    </tr>
    <tr>
    <td><label class="form-label">Stock Máximo</label></td>
    <td><input type="number" name="stock_maximo" class="form-control" min="0" value="<?php echo htmlspecialchars($stock_maximo ?? 0); ?>"></td>
    <td colspan="2">
    <div class="form-check">
    <input class="form-check-input" type="checkbox" name="usar_control_stock" value="1" <?php if (!empty($usar_control_stock)) echo 'checked'; ?>>
    <label class="form-check-label">Usar control de stock automático</label>
    </div>
    </td>
    </tr>
    <tr><td colspan="4"><b>Vencimiento</b></td></tr>
    <tr>
    <td colspan="4">
    <div class="form-check">
    <input class="form-check-input" type="checkbox" name="usar_alerta_vencimiento" id="usar_alerta_vencimiento" value="1" onchange="document.getElementById('vencimiento_fields').style.display = this.checked ? 'table-row' : 'none';" <?php if (!empty($usar_alerta_vencimiento)) echo 'checked'; ?>>
    <label class="form-check-label fw-bold" for="usar_alerta_vencimiento">Este producto usa vencimiento</label>
    </div>
    </td>
    </tr>
    <tr id="vencimiento_fields" style="display: <?php echo !empty($usar_alerta_vencimiento) ? 'table-row' : 'none'; ?>;">
    <td><label class="form-label">Fecha de Vencimiento</label></td>
    <td><input type="date" name="fecha_vencimiento" class="form-control" value="<?php echo htmlspecialchars($fecha_vencimiento ?? ''); ?>"></td>
    <td><label class="form-label">Alerta de Vencimiento (días)</label></td>
    <td><input type="number" name="dias_alerta_vencimiento" class="form-control" min="1" value="<?php echo htmlspecialchars($dias_alerta_vencimiento ?? '7'); ?>"></td>
    </tr>

    <tr>
    <td colspan="4" class="text-center">
    <button type="submit" class="btn btn-primary">Guardar datos básicos</button>
    <?php if ($es_edicion): ?>
    <a class="btn btn-outline-secondary ms-2" href="<?php echo $_SERVER['PHP_SELF'].'?id='.$producto_id.'&step=proveedores'; ?>">Continuar a proveedores</a>
    <?php endif; ?>
    </td>
    </tr>
    </table>
    </div>
    </form>
    <?php endif; ?>

    <?php if ($step === 'proveedores'): ?>
    <?php if (!$es_edicion): ?>
    <div class="alert alert-info">Primero guardá los datos básicos del producto.</div>
    <?php else: ?>
    <form method="post" enctype="multipart/form-data" class="card p-4 bg-white">
    <input type="hidden" name="accion" value="guardar_proveedores">
    <div class="table-responsive">
    <table class="table table-bordered align-middle mb-0">
    <tr><td colspan="4"><b>Proveedores</b></td></tr>
    <tr>
    <td><label class="form-label">Proveedor Principal *</label></td>
    <td colspan="3">
    <select name="proveedor_principal" class="form-select" required>
    <option value="">-- Seleccionar --</option>
    <?php foreach ($proveedores_list as $prov): ?>
    <option value="<?php echo $prov['id']; ?>" <?php if (($proveedor_principal ?? '') == $prov['id']) echo 'selected'; ?>><?php echo htmlspecialchars($prov['razon_social']); ?></option>
    <?php endforeach; ?>
    </select>
    </td>
    </tr>
    <?php for ($i=1; $i<=4; $i++): ?>
    <tr>
    <td><label class="form-label">Proveedor Alternativo <?php echo str_pad($i,2,'0',STR_PAD_LEFT); ?></label></td>
    <td colspan="3">
    <select name="proveedor_alternativo<?php echo str_pad($i,2,'0',STR_PAD_LEFT); ?>" class="form-select">
    <option value="">-- Ninguno --</option>
    <?php foreach ($proveedores_list as $prov): ?>
    <option value="<?php echo $prov['id']; ?>" <?php if ((${'proveedor_alternativo0'.$i} ?? '') == $prov['id']) echo 'selected'; ?>>
    <?php echo htmlspecialchars($prov['razon_social']); ?>
    </option>
    <?php endforeach; ?>
    </select>
    </td>
    </tr>
    <?php endfor; ?>

    <tr><td colspan="4"><b>Foto del producto</b></td></tr>
    <tr>
    <td><label class="form-label">Imagen (JPG o PNG)</label></td>
    <td colspan="3">
    <input type="file" name="foto" accept="image/jpeg,image/png" class="form-control">
    <?php if (!empty($codigo) && file_exists(__DIR__ . '/../../assets/img/productos/' . $codigo . '.jpg')): ?>
    <div class="mt-2">
    <img src="../../assets/img/productos/<?php echo $codigo; ?>.jpg?<?php echo time(); ?>" alt="Imagen actual" style="max-width:120px;max-height:120px;border:1px solid #ccc;">
    <span class="text-muted ms-2">Imagen actual</span>
    </div>
    <?php endif; ?>
    </td>
    </tr>

    <tr>
    <td colspan="4">
    <div class="form-check">
    <input class="form-check-input" type="checkbox" name="publicar_web" value="1" <?php if (!empty($publicar_web)) echo 'checked'; ?>>
    <label class="form-check-label fw-bold">Publicar en Web</label>
    </div>
    </td>
    </tr>

    <tr>
    <td colspan="4" class="text-center">
    <button type="submit" class="btn btn-primary">Guardar proveedores</button>
    <a href="http://localhost/sistemadgestion5/modulos/Inventario/productos.php" class="btn btn-secondary ms-2">Volver</a>
    <a class="btn btn-outline-secondary ms-2" href="<?php echo $_SERVER['PHP_SELF'].'?id='.$producto_id.'&step=basico'; ?>">Volver a datos básicos</a>
    </td>
    </tr>
    </table>
    </div>
    </form>
    <?php endif; ?>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Cálculo en vivo: Precio = Costo × (1 + impuesto/100) × (1 + utilidad/100)
// Precio de Venta = Precio Minorista.
(function(){
  const selImpuesto = document.getElementById('impuesto_id');
  const costo = document.getElementById('precio_compra');
  const utilMin = document.getElementById('utilidad_minorista');
  const utilMay = document.getElementById('utilidad_mayorista');
  const pMin = document.getElementById('precio_minorista');
  const pMay = document.getElementById('precio_mayorista');
  const pVenta = document.getElementById('precio_venta');

  if (!selImpuesto) return;

  function porcImpuesto() {
    const opt = selImpuesto.options[selImpuesto.selectedIndex];
    return opt ? parseFloat(opt.getAttribute('data-porcentaje') || '0') : 0;
  }
  function calc() {
    const c = parseFloat(costo.value || '0') || 0;
    const iva = porcImpuesto();
    const uMin = parseFloat(utilMin.value || '0') || 0;
    const uMay = parseFloat(utilMay.value || '0') || 0;

    const fIVA = 1 + (iva / 100.0);
    const fMin = 1 + (uMin / 100.0);
    const fMay = 1 + (uMay / 100.0);

    const vMin = (c * fIVA * fMin);
    const vMay = (c * fIVA * fMay);

    pMin.value = vMin.toFixed(2);
    pMay.value = vMay.toFixed(2);
    pVenta.value = pMin.value;
  }

  ['change','keyup','blur'].forEach(evt => {
    [selImpuesto, costo, utilMin, utilMay].forEach(el => el && el.addEventListener(evt, calc));
  });

  window.addEventListener('DOMContentLoaded', calc);
})();
</script>
</body>
</html>
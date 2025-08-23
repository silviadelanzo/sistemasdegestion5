<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

// Configurar charset UTF-8
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// --- INICIO: Lógica unificada para el Navbar ---
$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
$usuario_rol = $_SESSION['rol_usuario'] ?? 'inventario';
$es_administrador = ($usuario_rol === 'admin' || $usuario_rol === 'administrador');

$compras_pendientes = 0;
$facturas_pendientes = 0;
// --- FIN: Lógica unificada para el Navbar ---


// --- INICIO: Lógica específica del formulario de productos ---
$producto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$es_edicion = $producto_id > 0;

$producto = [
    'codigo' => '',
    'nombre' => '',
    'descripcion' => '',
    'categoria_id' => '',
    'lugar_id' => '',
    'stock' => 0,
    'stock_minimo' => 1,
    'precio_venta' => 0.00,
    'precio_compra' => 0.00,
    'imagen' => ''
];
$categorias = [];
$lugares = [];
$errores = [];
$mensaje_exito = '';

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // --- Lógica para obtener datos para el menú ---
    if (in_array('compras', $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN))) {
        $stmt_compras_pend = $pdo->query("SELECT COUNT(*) as pendientes FROM compras WHERE estado IN ('pendiente', 'confirmada')");
        if ($stmt_compras_pend) $compras_pendientes = $stmt_compras_pend->fetch()['pendientes'] ?? 0;
    }
    if (in_array('facturas', $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN))) {
        $stmt_fact_pend = $pdo->query("SELECT COUNT(*) as pendientes FROM facturas WHERE estado = 'pendiente'");
        if ($stmt_fact_pend) $facturas_pendientes = $stmt_fact_pend->fetch()['pendientes'] ?? 0;
    }
    // --- Fin lógica para el menú ---

    if ($es_edicion) {
        $sql_prod = "SELECT * FROM productos WHERE id = :id";
        $stmt_prod = $pdo->prepare($sql_prod);
        $stmt_prod->bindParam(':id', $producto_id, PDO::PARAM_INT);
        $stmt_prod->execute();
        $producto_db = $stmt_prod->fetch(PDO::FETCH_ASSOC);
        if (!$producto_db) throw new Exception('Producto no encontrado.');
        $producto = $producto_db;
    } else {
        $sql_code = "SELECT codigo FROM productos WHERE codigo LIKE 'PROD-%' ORDER BY CAST(SUBSTRING(codigo, 6) AS UNSIGNED) DESC, codigo DESC LIMIT 1";
        $stmt_code = $pdo->query($sql_code);
        $ultimo_codigo = $stmt_code->fetchColumn();
        $numero = $ultimo_codigo ? intval(substr($ultimo_codigo, 5)) + 1 : 1;
        $producto['codigo'] = 'PROD-' . str_pad($numero, 7, '0', STR_PAD_LEFT);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $producto['codigo'] = trim($_POST['codigo'] ?? $producto['codigo']);
        $producto['nombre'] = trim($_POST['nombre'] ?? '');
        $producto['descripcion'] = trim($_POST['descripcion'] ?? '');
        $producto['categoria_id'] = !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null;
        $producto['lugar_id'] = !empty($_POST['lugar_id']) ? intval($_POST['lugar_id']) : null;
        $producto['stock'] = max(0, intval($_POST['stock'] ?? 0));
        $producto['stock_minimo'] = max(0, intval($_POST['stock_minimo'] ?? 0));
        $producto['precio_venta'] = max(0.00, floatval(str_replace(',', '.', $_POST['precio_venta'] ?? 0)));
        $producto['precio_compra'] = max(0.00, floatval(str_replace(',', '.', $_POST['precio_compra'] ?? 0)));

        if (empty($producto['codigo'])) $errores[] = 'El código es obligatorio.';
        if (empty($producto['nombre'])) $errores[] = 'El nombre es obligatorio.';
        if ($producto['precio_venta'] <= 0 && !$es_edicion) {
            if (isset($_POST['precio_venta']) && floatval(str_replace(',', '.', $_POST['precio_venta'])) <= 0) {
                $errores[] = 'El precio de venta debe ser mayor a 0.';
            }
        }

        $imagen_actual = $producto['imagen'];
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['imagen'];
            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024;

            if (!in_array(strtolower($file['type']), array_map('strtolower', $tipos_permitidos))) $errores[] = 'Tipo de imagen no permitido (solo JPG, PNG, GIF, WEBP).';
            if ($file['size'] > $max_size) $errores[] = 'La imagen es demasiado grande (máx 5MB).';

            if (empty($errores)) {
                $upload_dir_rel = 'assets/img/productos/';
                $upload_dir_abs = '../../' . $upload_dir_rel;
                if (!is_dir($upload_dir_abs)) mkdir($upload_dir_abs, 0775, true);

                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $nombre_archivo_nuevo = 'prod_' . uniqid() . '_' . time() . '.' . $extension;
                $ruta_completa_abs = $upload_dir_abs . $nombre_archivo_nuevo;

                if (move_uploaded_file($file['tmp_name'], $ruta_completa_abs)) {
                    if (!empty($imagen_actual) && $imagen_actual !== $upload_dir_rel . $nombre_archivo_nuevo && file_exists('../../' . $imagen_actual)) {
                        @unlink('../../' . $imagen_actual);
                    }
                    $producto['imagen'] = $upload_dir_rel . $nombre_archivo_nuevo;
                } else {
                    $errores[] = 'Error al mover la imagen subida. Verifique permisos.';
                }
            }
        } elseif (isset($_POST['eliminar_imagen']) && $_POST['eliminar_imagen'] == '1') {
            if (!empty($producto['imagen']) && file_exists('../../' . $producto['imagen'])) {
                @unlink('../../' . $producto['imagen']);
            }
            $producto['imagen'] = '';
        }

        if (empty($errores)) {
            if ($es_edicion) {
                $sql_save = "UPDATE productos SET codigo = :codigo, nombre = :nombre, descripcion = :descripcion, categoria_id = :categoria_id, lugar_id = :lugar_id, stock = :stock, stock_minimo = :stock_minimo, precio_venta = :precio_venta, precio_compra = :precio_compra, imagen = :imagen, fecha_modificacion = NOW() WHERE id = :id";
            } else {
                $sql_save = "INSERT INTO productos (codigo, nombre, descripcion, categoria_id, lugar_id, stock, stock_minimo, precio_venta, precio_compra, imagen, activo, fecha_creacion) VALUES (:codigo, :nombre, :descripcion, :categoria_id, :lugar_id, :stock, :stock_minimo, :precio_venta, :precio_compra, :imagen, 1, NOW())";
            }
            $stmt_save = $pdo->prepare($sql_save);
            $params_save = [
                ':codigo' => $producto['codigo'],
                ':nombre' => $producto['nombre'],
                ':descripcion' => $producto['descripcion'],
                ':categoria_id' => $producto['categoria_id'],
                ':lugar_id' => $producto['lugar_id'],
                ':stock' => $producto['stock'],
                ':stock_minimo' => $producto['stock_minimo'],
                ':precio_venta' => $producto['precio_venta'],
                ':precio_compra' => $producto['precio_compra'],
                ':imagen' => $producto['imagen']
            ];
            if ($es_edicion) $params_save[':id'] = $producto_id;

            $stmt_save->execute($params_save);
            $mensaje_exito = 'Producto ' . ($es_edicion ? 'actualizado' : 'creado') . ' correctamente.';
            if (!$es_edicion) {
                $new_producto_id = $pdo->lastInsertId();
            }
        }
    }

    $sql_categorias = "SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre";
    $stmt_cat = $pdo->query($sql_categorias);
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    $sql_lugares = "SELECT id, nombre FROM lugares WHERE activo = 1 ORDER BY nombre";
    $stmt_lug = $pdo->query($sql_lugares);
    $lugares = $stmt_lug->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = 'Error de base de datos: ' . $e->getMessage() . ' (Código: ' . $e->getCode() . ')';
    error_log('PDOException en producto_form.php: ' . $e->getMessage());
} catch (Exception $e) {
    $errores[] = 'Error del sistema: ' . $e->getMessage();
    error_log('Exception en producto_form.php: ' . $e->getMessage());
}
// --- FIN LÓGICA específica del formulario de productos ---
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $es_edicion ? 'Editar' : 'Nuevo'; ?> Producto - <?php echo htmlspecialchars(SISTEMA_NOMBRE); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 70px;
        }

        .navbar-custom {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar-custom .navbar-brand {
            font-weight: bold;
            color: white !important;
            font-size: 1.1rem;
        }

        .navbar-custom .navbar-nav .nav-link {
            color: white !important;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 2px;
            border-radius: 5px;
            padding: 8px 12px !important;
            font-size: 0.95rem;
        }

        .navbar-custom .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        .navbar-custom .navbar-nav .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }

        .navbar-custom .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .navbar-custom .dropdown-item {
            padding: 8px 16px;
            transition: all 0.2s ease;
        }

        .navbar-custom .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        /* --- INICIO: CÓDIGO CSS CORREGIDO --- */
        .navbar-custom .dropdown-item.active,
        .navbar-custom .dropdown-item:active {
            background-color: #007bff;
            /* Fondo azul primario */
            color: white !important;
            /* Texto blanco */
        }

        /* --- FIN: CÓDIGO CSS CORREGIDO --- */

        .form-container {
            max-width: 900px;
            margin: 30px auto;
            background-color: #fff;
            padding: 0;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .form-header {
            background-color: #0d6efd;
            color: white;
            padding: 20px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        .form-header h2 {
            margin: 0;
            font-size: 1.75rem;
        }

        .form-body {
            padding: 25px;
        }

        .form-label {
            font-weight: 600;
        }

        .image-preview-container {
            text-align: center;
            margin-bottom: 15px;
        }

        .image-preview {
            width: 200px;
            height: 200px;
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            display: block;
            object-fit: contain;
        }

        .image-preview .placeholder {
            font-size: 4rem;
            color: #adb5bd;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }

        .image-preview .placeholder small {
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .btn-add-related {
            font-size: 0.8em;
            padding: 0.25rem 0.5rem;
        }

        .btn-guardar-producto {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .btn-guardar-producto:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
    </style>
</head>

<body>
    <!-- NAVBAR UNIFICADO -->
    <?php include "../../config/navbar_code.php"; ?>

    <div class="container form-container">
        <a class="navbar-brand" href="../../menu_principal.php">
            <i class="bi bi-speedometer2"></i> Gestión Administrativa
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon" style="background-image: url('data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 30 30\'%3e%3cpath stroke=\'rgba%28255, 255, 255, 0.75%29\' stroke-linecap=\'round\' stroke-miterlimit=\'10\' stroke-width=\'2\' d=\'M4 7h22M4 15h22M4 23h22\'/%3e%3c/svg%3e');"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../../menu_principal.php">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                </li>

                <!-- Menú Compras -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-truck"></i> Compras
                        <?php if (isset($compras_pendientes) && $compras_pendientes > 0): ?>
                            <span class="badge bg-info text-dark ms-1"><?php echo $compras_pendientes; ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../../modulos/compras/compras.php"><i class="bi bi-list-ul"></i> Ver Compras</a></li>
                        <li><a class="dropdown-item" href="../../modulos/compras/compra_form.php"><i class="bi bi-truck"></i> Nueva Compra</a></li>
                        <li><a class="dropdown-item" href="../../modulos/compras/proveedores.php"><i class="bi bi-building"></i> Proveedores</a></li>
                        <li><a class="dropdown-item" href="../../modulos/compras/recepcion_mercaderia.php"><i class="bi bi-box-arrow-in-down"></i> Recepción</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="../../modulos/compras/reportes_compras.php"><i class="bi bi-graph-up"></i> Reportes</a></li>
                    </ul>
                </li>

                <!-- Menú Productos -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-box-seam"></i> Productos
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="productos.php"><i class="bi bi-list-ul"></i> Listado de Productos</a></li>
                        <li><a class="dropdown-item active" href="producto_form.php"><i class="bi bi-plus-circle"></i> Nuevo Producto</a></li>
                        <li><a class="dropdown-item" href="productos_por_categoria.php"><i class="bi bi-tag"></i> Por Categoria</a></li>
                        <li><a class="dropdown-item" href="productos_por_lugar.php"><i class="bi bi-geo-alt"></i> Por Ubicación</a></li>
                        <li><a class="dropdown-item" href="productos_inactivos.php"><i class="bi bi-archive"></i> Productos Inactivos</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="reportes.php"><i class="bi bi-graph-up"></i> Reportes</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-people"></i> Clientes
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../../modulos/clientes/clientes.php"><i class="bi bi-list-ul"></i> Ver Clientes</a></li>
                        <li><a class="dropdown-item" href="../../modulos/clientes/cliente_form.php"><i class="bi bi-person-plus"></i> Nuevo Cliente</a></li>
                        <li><a class="dropdown-item" href="../../modulos/clientes/clientes_inactivos.php"><i class="bi bi-person-x"></i> Clientes Inactivos</a></li>
                        <li><a class="dropdown-item" href="../../modulos/clientes/papelera_clientes.php"><i class="bi bi-trash"></i> Papelera</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-cart"></i> Pedidos
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../../modulos/pedidos/pedidos.php"><i class="bi bi-list-ul"></i> Ver Pedidos</a></li>
                        <li><a class="dropdown-item" href="../../modulos/pedidos/pedido_form.php"><i class="bi bi-cart-plus"></i> Nuevo Pedido</a></li>
                        <li><a class="dropdown-item" href="../../modulos/pedidos/pedidos_pendientes.php"><i class="bi bi-clock"></i> Pedidos Pendientes</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="../../modulos/pedidos/reportes_pedidos.php"><i class="bi bi-graph-up"></i> Reportes</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-receipt"></i> Facturación
                        <?php if (isset($facturas_pendientes) && $facturas_pendientes > 0): ?>
                            <span class="badge bg-danger ms-1"><?php echo $facturas_pendientes; ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../../modulos/facturas/facturas.php"><i class="bi bi-list-ul"></i> Ver Facturas</a></li>
                        <li><a class="dropdown-item" href="../../modulos/facturas/factura_form.php"><i class="bi bi-receipt"></i> Nueva Factura</a></li>
                        <li><a class="dropdown-item" href="../../modulos/facturas/facturas_pendientes.php"><i class="bi bi-clock"></i> Facturas Pendientes</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="../../modulos/facturas/reportes_facturas.php"><i class="bi bi-graph-up"></i> Reportes</a></li>
                    </ul>
                </li>
            </ul>

            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($usuario_nombre); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <h6 class="dropdown-header">Rol: <?php echo ucfirst(htmlspecialchars($usuario_rol)); ?></h6>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <?php if ($es_administrador): ?>
                            <li>
                                <h6 class="dropdown-header text-danger"><i class="bi bi-shield-check"></i> Administración</h6>
                            </li>
                            <li><a class="dropdown-item" href="../../modulos/admin/admin_dashboard.php"><i class="bi bi-speedometer2"></i> Panel Admin</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                        <?php endif; ?>

                        <li><a class="dropdown-item" href="../../logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
    </nav>
    <!-- FIN: Navbar Superior Estandarizado -->

    <div class="container form-container">
        <div class="form-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-<?php echo $es_edicion ? 'pencil-square' : 'plus-circle'; ?> me-2"></i><?php echo $es_edicion ? 'Editar' : 'Nuevo'; ?> Producto</h2>
                <a href="productos.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Volver al Listado</a>
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
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensaje_exito)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($mensaje_exito); ?>
                    <?php if (!$es_edicion && isset($new_producto_id)): ?>
                        <a href="producto_form.php?id=<?php echo $new_producto_id; ?>" class="alert-link ms-2">Ver/Editar producto creado</a>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="formProducto">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="codigo" class="form-label">Código *</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" value="<?php echo htmlspecialchars($producto['codigo']); ?>" <?php echo $es_edicion ? '' : 'readonly'; ?> required>
                    </div>
                    <div class="col-md-8">
                        <label for="nombre" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                    </div>

                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label for="categoria_id" class="form-label">Categoría</label>
                        <div class="input-group">
                            <select class="form-select" id="categoria_id" name="categoria_id">
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php if ($producto['categoria_id'] == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-secondary btn-add-related" onclick="abrirModalCategoria()">+</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="lugar_id" class="form-label">Ubicación</label>
                        <div class="input-group">
                            <select class="form-select" id="lugar_id" name="lugar_id">
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($lugares as $lug): ?>
                                    <option value="<?php echo $lug['id']; ?>" <?php if ($producto['lugar_id'] == $lug['id']) echo 'selected'; ?>><?php echo htmlspecialchars($lug['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-secondary btn-add-related" onclick="abrirModalLugar()">+</button>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="stock" class="form-label">Stock Actual *</label>
                        <input type="number" class="form-control" id="stock" name="stock" value="<?php echo htmlspecialchars($producto['stock']); ?>" min="0" required>
                    </div>
                    <div class="col-md-3">
                        <label for="stock_minimo" class="form-label">Stock Mínimo</label>
                        <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" value="<?php echo htmlspecialchars($producto['stock_minimo']); ?>" min="0">
                    </div>
                    <div class="col-md-3">
                        <label for="precio_compra" class="form-label">Precio Compra</label>
                        <div class="input-group"><span class="input-group-text">$</span><input type="number" class="form-control" id="precio_compra" name="precio_compra" value="<?php echo htmlspecialchars(number_format(floatval($producto['precio_compra']), 2, '.', '')); ?>" step="0.01" min="0"></div>
                    </div>
                    <div class="col-md-3">
                        <label for="precio_venta" class="form-label">Precio Venta *</label>
                        <div class="input-group"><span class="input-group-text">$</span><input type="number" class="form-control" id="precio_venta" name="precio_venta" value="<?php echo htmlspecialchars(number_format(floatval($producto['precio_venta']), 2, '.', '')); ?>" step="0.01" min="0.01" required></div>
                    </div>

                    <div class="col-12">
                        <label for="inputImagen" class="form-label">Imagen del Producto</label>
                        <div class="image-preview-container mb-2">
                            <div class="image-preview" onclick="document.getElementById('inputImagen').click();" id="imagePreviewDiv">
                                <?php if (!empty($producto['imagen']) && file_exists('../../' . $producto['imagen'])): ?>
                                    <img src="../../<?php echo htmlspecialchars($producto['imagen']); ?>?t=<?php echo time(); ?>" alt="Vista previa">
                                <?php else: ?>
                                    <span class="placeholder"><i class="bi bi-image"></i><small>Subir imagen</small></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <input class="form-control" type="file" id="inputImagen" name="imagen" accept="image/jpeg, image/png, image/gif, image/webp" onchange="previsualizarImagen(event)">
                        <?php if (!empty($producto['imagen']) && $es_edicion): ?>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" id="eliminar_imagen" name="eliminar_imagen">
                                <label class="form-check-label" for="eliminar_imagen">
                                    Eliminar imagen actual al guardar
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="my-4">
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5 btn-guardar-producto"><i class="bi bi-save me-2"></i><?php echo $es_edicion ? 'Actualizar' : 'Guardar'; ?> Producto</button>
                    <a href="productos.php" class="btn btn-outline-secondary btn-lg px-4 ms-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Modales -->
    <div class="modal fade" id="modalNuevaCategoria" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Categoría</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"><input type="text" class="form-control" id="nombreNuevaCategoria" placeholder="Nombre de la categoría"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" onclick="guardarNuevaCategoria()">Guardar</button></div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalNuevoLugar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Ubicación</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"><input type="text" class="form-control" id="nombreNuevoLugar" placeholder="Nombre de la ubicación"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" onclick="guardarNuevoLugar()">Guardar</button></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let modalCategoriaInstance, modalLugarInstance;
        document.addEventListener('DOMContentLoaded', function() {
            const elCat = document.getElementById('modalNuevaCategoria');
            if (elCat) modalCategoriaInstance = new bootstrap.Modal(elCat);
            const elLug = document.getElementById('modalNuevoLugar');
            if (elLug) modalLugarInstance = new bootstrap.Modal(elLug);
        });

        function previsualizarImagen(event) {
            const reader = new FileReader();
            const imagePreviewDiv = document.getElementById('imagePreviewDiv');
            reader.onload = function() {
                imagePreviewDiv.innerHTML = '<img src="' + reader.result + '" alt="Vista previa">';
            }
            if (event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            } else {
                imagePreviewDiv.innerHTML = '<span class="placeholder"><i class="bi bi-image"></i><small>Subir imagen</small></span>';
            }
        }

        function abrirModalCategoria() {
            if (modalCategoriaInstance) modalCategoriaInstance.show();
        }

        function abrirModalLugar() {
            if (modalLugarInstance) modalLugarInstance.show();
        }

        function guardarNuevaCategoria() {
            const nombre = document.getElementById('nombreNuevaCategoria').value.trim();
            if (!nombre) {
                alert('Ingrese nombre para la categoría.');
                return;
            }
            // CORRECCIÓN: Ruta y formato de datos
            fetch('../../ajax/ajax_categorias.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ accion: 'crear_simple', nombre_categoria: nombre })
                })
                .then(r => r.json()).then(data => {
                    if (data.success) {
                        const sel = document.getElementById('categoria_id');
                        sel.add(new Option(data.categoria.nombre, data.categoria.id, true, true));
                        if (modalCategoriaInstance) modalCategoriaInstance.hide();
                        document.getElementById('nombreNuevaCategoria').value = '';
                        alert('Categoría creada: ' + data.categoria.nombre);
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo crear'));
                    }
                }).catch(e => {
                    console.error(e);
                    alert('Error de conexión al crear categoría.');
                });
        }

        function guardarNuevoLugar() {
            const nombre = document.getElementById('nombreNuevoLugar').value.trim();
            if (!nombre) {
                alert('Ingrese nombre para la ubicación.');
                return;
            }
            // CORRECCIÓN: Ruta y formato de datos
            fetch('../../ajax/ajax_lugares.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ accion: 'crear_simple', nombre_lugar: nombre })
                })
                .then(r => r.json()).then(data => {
                    if (data.success) {
                        const sel = document.getElementById('lugar_id');
                        sel.add(new Option(data.lugar.nombre, data.lugar.id, true, true));
                        if (modalLugarInstance) modalLugarInstance.hide();
                        document.getElementById('nombreNuevoLugar').value = '';
                        alert('Ubicación creada: ' + data.lugar.nombre);
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo crear'));
                    }
                }).catch(e => {
                    console.error(e);
                    alert('Error de conexión al crear ubicación.');
                });
        }
    </script>
</body>

</html>
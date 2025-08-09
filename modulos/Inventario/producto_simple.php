<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

// Variables para el formulario
$producto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$es_edicion = $producto_id > 0;
$errores = [];
$mensaje_exito = '';

// Inicializar datos del producto
$producto = [
    'codigo_interno' => '',
    'nombre' => '',
    'descripcion' => '',
    'categoria_id' => '',
    'lugar_id' => '',
    'unidad_medida' => 'UN',
    'stock' => 0,
    'stock_minimo' => 1,
    'precio_compra' => 0.00,
    'precio_minorista' => 0.00,
    'precio_mayorista' => 0.00,
    'moneda_id' => 1,
    'impuesto_id' => 1
];

try {
    $pdo = conectarDB();
    
    // Cargar categorías
    $stmt = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll();
    
    // Cargar lugares
    $stmt = $pdo->query("SELECT id, nombre FROM lugares ORDER BY nombre");
    $lugares = $stmt->fetchAll();
    
    // Cargar monedas
    $stmt = $pdo->query("SELECT id, codigo, nombre FROM monedas ORDER BY nombre");
    $monedas = $stmt->fetchAll();
    
    // Cargar impuestos
    $stmt = $pdo->query("SELECT id, nombre, porcentaje FROM impuestos ORDER BY nombre");
    $impuestos = $stmt->fetchAll();
    
    // Si es edición, cargar datos del producto
    if ($es_edicion) {
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto_db = $stmt->fetch();
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
        // Validaciones básicas
        $nombre = trim($_POST['nombre'] ?? '');
        $codigo_interno = trim($_POST['codigo_interno'] ?? '');
        
        if (empty($nombre)) {
            $errores[] = "El nombre del producto es obligatorio";
        }
        if (empty($codigo_interno)) {
            $errores[] = "El código interno es obligatorio";
        }
        
        // Si no hay errores, procesar
        if (empty($errores)) {
            if ($es_edicion) {
                // Actualizar producto existente
                $sql = "UPDATE productos SET 
                        codigo_interno = ?, nombre = ?, descripcion = ?, categoria_id = ?, lugar_id = ?,
                        unidad_medida = ?, stock = ?, stock_minimo = ?, precio_compra = ?,
                        precio_minorista = ?, precio_mayorista = ?, moneda_id = ?, impuesto_id = ?
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_POST['codigo_interno'], $_POST['nombre'], $_POST['descripcion'],
                    $_POST['categoria_id'] ?: null, $_POST['lugar_id'] ?: null,
                    $_POST['unidad_medida'], $_POST['stock'], $_POST['stock_minimo'],
                    $_POST['precio_compra'], $_POST['precio_minorista'], $_POST['precio_mayorista'],
                    $_POST['moneda_id'] ?: null, $_POST['impuesto_id'] ?: null, $producto_id
                ]);
                $mensaje_exito = "Producto actualizado exitosamente";
            } else {
                // Crear nuevo producto
                $sql = "INSERT INTO productos (codigo_interno, nombre, descripcion, categoria_id, lugar_id,
                        unidad_medida, stock, stock_minimo, precio_compra, precio_minorista, precio_mayorista,
                        moneda_id, impuesto_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_POST['codigo_interno'], $_POST['nombre'], $_POST['descripcion'],
                    $_POST['categoria_id'] ?: null, $_POST['lugar_id'] ?: null,
                    $_POST['unidad_medida'], $_POST['stock'], $_POST['stock_minimo'],
                    $_POST['precio_compra'], $_POST['precio_minorista'], $_POST['precio_mayorista'],
                    $_POST['moneda_id'] ?: null, $_POST['impuesto_id'] ?: null
                ]);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0d6efd;
            --success: #198754;
            --danger: #dc3545;
        }

        body {
            background: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .container-custom {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }

        .main-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header-bar {
            background: linear-gradient(135deg, var(--primary), #0056b3);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-section {
            padding: 25px;
        }

        .section-title {
            color: var(--primary);
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .btn-generate {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
        }

        .btn-generate:hover {
            background: linear-gradient(45deg, #218838, #1bab87);
            color: white;
        }

        .nav-tabs-custom {
            border-bottom: 2px solid #e9ecef;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 12px 20px;
        }

        .nav-tabs-custom .nav-link.active {
            background: linear-gradient(45deg, var(--primary), #0056b3);
            color: white;
            border-radius: 8px 8px 0 0;
        }

        .tab-content {
            padding: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container-custom">
        <div class="main-card">
            <!-- Header -->
            <div class="header-bar">
                <div>
                    <h4 class="mb-0">
                        <i class="fas fa-<?php echo $es_edicion ? 'edit' : 'plus'; ?> me-2"></i>
                        <?php echo $es_edicion ? 'Editar' : 'Nuevo'; ?> Producto
                    </h4>
                    <small class="opacity-75">Gestión de inventario</small>
                </div>
                <div>
                    <a href="productos.php" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger mx-4 mt-3">
                    <strong>Errores Encontrados:</strong>
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensaje_exito)): ?>
                <div class="alert alert-success mx-4 mt-3">
                    <strong>¡Éxito!</strong> <?php echo htmlspecialchars($mensaje_exito); ?>
                </div>
            <?php endif; ?>

            <!-- Formulario -->
            <form method="POST" class="form-section">
                <!-- Pestañas -->
                <ul class="nav nav-tabs nav-tabs-custom mb-4" id="productTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" 
                                data-bs-target="#general" type="button" role="tab">
                            <i class="fas fa-info-circle me-2"></i>General
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="impuestos-tab" data-bs-toggle="tab" 
                                data-bs-target="#impuestos" type="button" role="tab">
                            <i class="fas fa-dollar-sign me-2"></i>Impuestos/Moneda
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="precios-tab" data-bs-toggle="tab" 
                                data-bs-target="#precios" type="button" role="tab">
                            <i class="fas fa-calculator me-2"></i>Precios
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="stock-tab" data-bs-toggle="tab" 
                                data-bs-target="#stock" type="button" role="tab">
                            <i class="fas fa-boxes me-2"></i>Stock
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="productTabContent">
                    <!-- Pestaña General -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel">
                        <h5 class="section-title">Información General</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Código Interno *</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="codigo_interno" 
                                               value="<?php echo htmlspecialchars($producto['codigo_interno']); ?>" required>
                                        <?php if (!$es_edicion): ?>
                                        <button type="button" class="btn btn-generate" onclick="generarCodigo()">
                                            <i class="fas fa-magic"></i> Generar
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre del Producto *</label>
                                    <input type="text" class="form-control" name="nombre" 
                                           value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                                </div>
                            </div>
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
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCategoria">
                                            <i class="fas fa-plus"></i>
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
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalLugar">
                                            <i class="fas fa-plus"></i>
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
                    </div>

                    <!-- Pestaña Impuestos/Moneda -->
                    <div class="tab-pane fade" id="impuestos" role="tabpanel">
                        <h5 class="section-title">Configuración Fiscal</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Moneda</label>
                                    <select class="form-select" name="moneda_id">
                                        <?php foreach ($monedas as $moneda): ?>
                                            <option value="<?php echo $moneda['id']; ?>" 
                                                    <?php echo ($producto['moneda_id'] == $moneda['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($moneda['codigo'] . ' - ' . $moneda['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Impuesto</label>
                                    <select class="form-select" name="impuesto_id">
                                        <?php foreach ($impuestos as $impuesto): ?>
                                            <option value="<?php echo $impuesto['id']; ?>" 
                                                    <?php echo ($producto['impuesto_id'] == $impuesto['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($impuesto['nombre'] . ' (' . $impuesto['porcentaje'] . '%)'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestaña Precios -->
                    <div class="tab-pane fade" id="precios" role="tabpanel">
                        <h5 class="section-title">Gestión de Precios</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Precio de Compra</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="precio_compra" 
                                               value="<?php echo $producto['precio_compra']; ?>" step="0.01" min="0">
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
                    </div>

                    <!-- Pestaña Stock -->
                    <div class="tab-pane fade" id="stock" role="tabpanel">
                        <h5 class="section-title">Control de Stock</h5>
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
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="productos.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i><?php echo $es_edicion ? 'Actualizar' : 'Guardar'; ?> Producto
                    </button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function generarCodigo() {
            fetch('../../obtener_ultimo_codigo.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('input[name="codigo_interno"]').value = data.codigo;
                        
                        // Mostrar notificación
                        const toast = document.createElement('div');
                        toast.className = 'alert alert-success position-fixed';
                        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
                        toast.innerHTML = `<strong>Código generado:</strong> ${data.codigo}`;
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al generar código');
                });
        }

        function guardarCategoria() {
            const nombre = document.getElementById('nombreCategoria').value;
            if (!nombre.trim()) {
                alert('Ingrese un nombre para la categoría');
                return;
            }

            fetch('../../ajax/guardar_categoria.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nombre: nombre })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Agregar nueva opción al select
                    const select = document.querySelector('select[name="categoria_id"]');
                    const option = new Option(nombre, data.id, true, true);
                    select.add(option);
                    
                    // Cerrar modal
                    bootstrap.Modal.getInstance(document.getElementById('modalCategoria')).hide();
                    document.getElementById('nombreCategoria').value = '';
                    
                    alert('Categoría creada exitosamente');
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar categoría');
            });
        }

        function guardarLugar() {
            const nombre = document.getElementById('nombreLugar').value;
            if (!nombre.trim()) {
                alert('Ingrese un nombre para el lugar');
                return;
            }

            fetch('../../ajax/guardar_lugar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nombre: nombre })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Agregar nueva opción al select
                    const select = document.querySelector('select[name="lugar_id"]');
                    const option = new Option(nombre, data.id, true, true);
                    select.add(option);
                    
                    // Cerrar modal
                    bootstrap.Modal.getInstance(document.getElementById('modalLugar')).hide();
                    document.getElementById('nombreLugar').value = '';
                    
                    alert('Lugar creado exitosamente');
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar lugar');
            });
        }

        // Auto-generar código al cargar si es nuevo producto
        <?php if (!$es_edicion): ?>
        document.addEventListener('DOMContentLoaded', function() {
            if (!document.querySelector('input[name="codigo_interno"]').value) {
                generarCodigo();
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>

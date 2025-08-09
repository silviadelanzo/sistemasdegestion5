<?php
// modulos/compras/ocr_remitos/productos_simulator.php
session_start();
require_once '../../../config/config.php';

// Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../../login.php');
    exit;
}

// Generar productos simulados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        $action = $_POST['action'];

        switch ($action) {
            case 'generate_products':
                $result = generateProductosSimulados();
                break;
            case 'generate_barcodes':
                $result = generateCodigosBarras();
                break;
            case 'clean_simulation':
                $result = limpiarSimulacion();
                break;
            default:
                throw new Exception('Acción no válida');
        }

        echo json_encode(['success' => true, 'data' => $result]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

function generateProductosSimulados()
{
    global $conexion;

    // Productos simulados realistas
    $productos_base = [
        // Almacén
        ['Aceite de Girasol 900ml', 'Aceite', 850, 120, 'ALM'],
        ['Arroz Largo Fino 1kg', 'Cereales', 1200, 80, 'ALM'],
        ['Azúcar Común 1kg', 'Azúcar', 950, 60, 'ALM'],
        ['Fideos Guiseros 500g', 'Pastas', 420, 150, 'ALM'],
        ['Leche Entera 1L', 'Lácteos', 890, 200, 'LAC'],
        ['Pan Lactal 700g', 'Panificados', 650, 100, 'PAN'],
        ['Yerba Mate 1kg', 'Infusiones', 1850, 75, 'INF'],
        ['Café Molido 250g', 'Infusiones', 1320, 90, 'INF'],
        ['Mermelada Durazno 390g', 'Conservas', 780, 50, 'CON'],
        ['Atún en Aceite 170g', 'Conservas', 950, 120, 'CON'],

        // Limpieza
        ['Detergente Líquido 500ml', 'Limpieza', 1150, 80, 'LIM'],
        ['Jabón en Polvo 800g', 'Limpieza', 980, 60, 'LIM'],
        ['Lavandina 1L', 'Limpieza', 650, 90, 'LIM'],
        ['Papel Higiénico x4', 'Higiene', 1250, 100, 'HIG'],
        ['Shampoo 400ml', 'Higiene', 1580, 45, 'HIG'],

        // Bebidas
        ['Gaseosa Cola 2.25L', 'Bebidas', 1450, 150, 'BEB'],
        ['Agua Mineral 1.5L', 'Bebidas', 580, 200, 'BEB'],
        ['Jugo Naranja 1L', 'Bebidas', 890, 80, 'BEB'],
        ['Cerveza Lata 473ml', 'Bebidas', 780, 180, 'BEB'],
        ['Vino Tinto 750ml', 'Bebidas', 2850, 30, 'BEB'],

        // Snacks
        ['Galletitas Dulces 300g', 'Snacks', 650, 120, 'SNK'],
        ['Papas Fritas 150g', 'Snacks', 480, 90, 'SNK'],
        ['Chocolate con Leche 100g', 'Golosinas', 780, 70, 'GOL'],
        ['Caramelos Masticables x50', 'Golosinas', 320, 150, 'GOL'],
        ['Chicles Menta x20', 'Golosinas', 280, 200, 'GOL'],

        // Carnes y Fiambres
        ['Jamón Cocido x100g', 'Fiambres', 890, 50, 'FIA'],
        ['Queso Cremoso x100g', 'Fiambres', 950, 40, 'FIA'],
        ['Salame Milano x100g', 'Fiambres', 1250, 30, 'FIA'],
        ['Mortadela x100g', 'Fiambres', 680, 60, 'FIA'],
        ['Paleta Cocida x100g', 'Fiambres', 780, 45, 'FIA'],

        // Frutas y Verduras
        ['Banana x kg', 'Frutas', 850, 80, 'FRU'],
        ['Manzana Roja x kg', 'Frutas', 1200, 60, 'FRU'],
        ['Naranja x kg', 'Frutas', 680, 100, 'FRU'],
        ['Papa x kg', 'Verduras', 450, 150, 'VER'],
        ['Cebolla x kg', 'Verduras', 320, 120, 'VER'],
        ['Tomate x kg', 'Verduras', 890, 90, 'VER'],
        ['Lechuga x unidad', 'Verduras', 380, 40, 'VER'],
        ['Zanahoria x kg', 'Verduras', 520, 80, 'VER'],

        // Productos de Kiosco
        ['Cigarrillos Comunes x20', 'Cigarrillos', 2850, 200, 'CIG'],
        ['Encendedor Común', 'Accesorios', 380, 150, 'ACC'],
        ['Pilas AA x2', 'Accesorios', 680, 100, 'ACC'],
        ['Cargador USB', 'Accesorios', 1580, 25, 'ACC'],
        ['Auriculares Básicos', 'Accesorios', 2250, 30, 'ACC'],

        // Productos de Farmacia
        ['Alcohol en Gel 250ml', 'Farmacia', 580, 120, 'FAR'],
        ['Vendas Elásticas x2', 'Farmacia', 450, 60, 'FAR'],
        ['Algodón 100g', 'Farmacia', 320, 80, 'FAR'],
        ['Termómetro Digital', 'Farmacia', 1850, 15, 'FAR'],
        ['Barbijos x10', 'Farmacia', 650, 100, 'FAR'],

        // Productos Especiales
        ['Helado 1L Chocolate', 'Helados', 2580, 20, 'HEL'],
        ['Pizza Congelada 500g', 'Congelados', 1850, 35, 'CON'],
        ['Hamburguesas x4', 'Congelados', 2250, 40, 'CON'],
        ['Milanesas x6', 'Congelados', 1950, 30, 'CON'],
        ['Empanadas x12', 'Congelados', 1650, 25, 'CON']
    ];

    $productos_creados = 0;
    $productos_actualizados = 0;

    foreach ($productos_base as $index => $producto_data) {
        $descripcion = $producto_data[0];
        $categoria = $producto_data[1];
        $precio = $producto_data[2] / 100; // Convertir centavos a pesos
        $stock = $producto_data[3];
        $prefijo = $producto_data[4];

        // Generar código único
        $codigo = $prefijo . str_pad($index + 1, 4, '0', STR_PAD_LEFT);

        // Generar código de barras EAN-13
        $codigo_barras = generateEAN13($codigo);

        // Verificar si el producto ya existe
        $check_query = "SELECT id FROM productos WHERE codigo = ?";
        $check_stmt = $conexion->prepare($check_query);
        $check_stmt->bind_param('s', $codigo);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();

        if ($existing) {
            // Actualizar producto existente
            $update_query = "
                UPDATE productos 
                SET descripcion = ?, precio_venta = ?, stock_actual = ?, codigo_barras = ?, 
                    categoria_simulacion = ?, fecha_actualizacion = NOW() 
                WHERE codigo = ?
            ";
            $update_stmt = $conexion->prepare($update_query);
            $update_stmt->bind_param('sdisss', $descripcion, $precio, $stock, $codigo_barras, $categoria, $codigo);
            $update_stmt->execute();
            $productos_actualizados++;
        } else {
            // Crear nuevo producto
            $insert_query = "
                INSERT INTO productos 
                (codigo, descripcion, precio_venta, precio_compra, stock_actual, stock_minimo, 
                 codigo_barras, categoria_simulacion, activo, fecha_creacion, es_simulacion) 
                VALUES (?, ?, ?, ?, ?, 5, ?, ?, 1, NOW(), 1)
            ";
            $precio_compra = $precio * 0.7; // 30% de margen
            $insert_stmt = $conexion->prepare($insert_query);
            $insert_stmt->bind_param('ssddiis', $codigo, $descripcion, $precio, $precio_compra, $stock, $codigo_barras, $categoria);
            $insert_stmt->execute();
            $productos_creados++;
        }
    }

    return [
        'productos_creados' => $productos_creados,
        'productos_actualizados' => $productos_actualizados,
        'total_productos' => count($productos_base),
        'mensaje' => "Simulación de productos completada exitosamente"
    ];
}

function generateEAN13($base_code)
{
    // Generar un código EAN-13 válido basado en el código base
    $code = str_pad(substr(preg_replace('/[^0-9]/', '', $base_code), 0, 11), 12, '0', STR_PAD_LEFT);

    // Calcular dígito verificador
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += (int)$code[$i] * (($i % 2 == 0) ? 1 : 3);
    }
    $check_digit = (10 - ($sum % 10)) % 10;

    return $code . $check_digit;
}

function generateCodigosBarras()
{
    global $conexion;

    // Obtener productos sin código de barras
    $query = "SELECT id, codigo FROM productos WHERE codigo_barras IS NULL OR codigo_barras = ''";
    $result = $conexion->query($query);

    $codigos_generados = 0;

    while ($producto = $result->fetch_assoc()) {
        $codigo_barras = generateEAN13($producto['codigo']);

        $update_query = "UPDATE productos SET codigo_barras = ? WHERE id = ?";
        $update_stmt = $conexion->prepare($update_query);
        $update_stmt->bind_param('si', $codigo_barras, $producto['id']);
        $update_stmt->execute();

        $codigos_generados++;
    }

    return [
        'codigos_generados' => $codigos_generados,
        'mensaje' => "Códigos de barras generados exitosamente"
    ];
}

function limpiarSimulacion()
{
    global $conexion;

    // Eliminar productos de simulación
    $delete_query = "DELETE FROM productos WHERE es_simulacion = 1";
    $result = $conexion->query($delete_query);
    $productos_eliminados = $conexion->affected_rows;

    return [
        'productos_eliminados' => $productos_eliminados,
        'mensaje' => "Simulación limpiada exitosamente"
    ];
}

// Obtener estadísticas actuales
$stats_query = "
    SELECT 
        COUNT(*) as total_productos,
        COUNT(CASE WHEN es_simulacion = 1 THEN 1 END) as productos_simulacion,
        COUNT(CASE WHEN codigo_barras IS NOT NULL AND codigo_barras != '' THEN 1 END) as con_codigo_barras,
        COUNT(DISTINCT categoria_simulacion) as categorias_simulacion
    FROM productos
";
$stats_result = $conexion->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Productos Simulados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card {
            border-left: 4px solid #007bff;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .action-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .product-preview {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
        }

        .barcode-display {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            background: #f8f9fa;
            padding: 5px;
            border-radius: 3px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-boxes"></i> Generador de Productos Simulados</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="control_center.php">
                    <i class="fas fa-eye"></i> Centro de Control
                </a>
                <span class="navbar-text">
                    <i class="fas fa-user"></i> <?php echo $_SESSION['usuario_nombre']; ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Estadísticas Actuales -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5><i class="fas fa-chart-bar"></i> Estadísticas Actuales</h5>
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <h3 class="text-primary"><?php echo $stats['total_productos']; ?></h3>
                                <small>Total Productos</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3 class="text-success"><?php echo $stats['productos_simulacion']; ?></h3>
                                <small>Productos Simulados</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3 class="text-info"><?php echo $stats['con_codigo_barras']; ?></h3>
                                <small>Con Código de Barras</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3 class="text-warning"><?php echo $stats['categorias_simulacion']; ?></h3>
                                <small>Categorías</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones Principales -->
        <div class="row mb-4">
            <div class="col-lg-4">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="text-success mb-3">
                            <i class="fas fa-magic fa-3x"></i>
                        </div>
                        <h5>Generar Productos Simulados</h5>
                        <p class="text-muted">Crea 50+ productos realistas con códigos EAN-13 para pruebas del sistema OCR</p>
                        <button class="btn btn-success" onclick="generarProductos()">
                            <i class="fas fa-play"></i> Generar Productos
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="text-primary mb-3">
                            <i class="fas fa-barcode fa-3x"></i>
                        </div>
                        <h5>Generar Códigos de Barras</h5>
                        <p class="text-muted">Asigna códigos EAN-13 válidos a productos existentes sin código de barras</p>
                        <button class="btn btn-primary" onclick="generarCodigosBarras()">
                            <i class="fas fa-qrcode"></i> Generar Códigos
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="text-danger mb-3">
                            <i class="fas fa-trash-alt fa-3x"></i>
                        </div>
                        <h5>Limpiar Simulación</h5>
                        <p class="text-muted">Elimina todos los productos de simulación para empezar de nuevo</p>
                        <button class="btn btn-danger" onclick="limpiarSimulacion()">
                            <i class="fas fa-broom"></i> Limpiar Todo
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview de Productos -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6><i class="fas fa-list"></i> Preview de Productos Simulados</h6>
                    </div>
                    <div class="card-body">
                        <div class="product-preview" id="productPreview">
                            <?php
                            $preview_query = "
                                SELECT codigo, descripcion, precio_venta, stock_actual, codigo_barras, categoria_simulacion 
                                FROM productos 
                                WHERE es_simulacion = 1 
                                ORDER BY categoria_simulacion, codigo 
                                LIMIT 20
                            ";
                            $preview_result = $conexion->query($preview_query);

                            if ($preview_result && $preview_result->num_rows > 0) {
                                echo "<div class='table-responsive'>";
                                echo "<table class='table table-sm table-striped'>";
                                echo "<thead><tr><th>Código</th><th>Descripción</th><th>Precio</th><th>Stock</th><th>Código de Barras</th></tr></thead>";
                                echo "<tbody>";

                                while ($producto = $preview_result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td><code>{$producto['codigo']}</code></td>";
                                    echo "<td>{$producto['descripcion']}</td>";
                                    echo "<td>\$" . number_format($producto['precio_venta'], 2) . "</td>";
                                    echo "<td><span class='badge bg-info'>{$producto['stock_actual']}</span></td>";
                                    echo "<td><span class='barcode-display'>{$producto['codigo_barras']}</span></td>";
                                    echo "</tr>";
                                }

                                echo "</tbody></table>";
                                echo "</div>";
                            } else {
                                echo "<div class='text-center text-muted'>";
                                echo "<i class='fas fa-box-open fa-3x mb-3'></i>";
                                echo "<p>No hay productos simulados. Genera algunos para ver el preview.</p>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h6><i class="fas fa-info-circle"></i> Información del Sistema</h6>
                    </div>
                    <div class="card-body">
                        <h6>Tipos de Productos Generados:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-shopping-basket text-success"></i> Almacén (10 productos)</li>
                            <li><i class="fas fa-soap text-primary"></i> Limpieza (5 productos)</li>
                            <li><i class="fas fa-wine-bottle text-info"></i> Bebidas (5 productos)</li>
                            <li><i class="fas fa-cookie text-warning"></i> Snacks (5 productos)</li>
                            <li><i class="fas fa-ham text-danger"></i> Fiambres (5 productos)</li>
                            <li><i class="fas fa-apple-alt text-success"></i> Frutas y Verduras (8 productos)</li>
                            <li><i class="fas fa-smoking text-secondary"></i> Kiosco (5 productos)</li>
                            <li><i class="fas fa-pills text-info"></i> Farmacia (5 productos)</li>
                            <li><i class="fas fa-ice-cream text-primary"></i> Especiales (5 productos)</li>
                        </ul>

                        <hr>

                        <h6>Características:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> Códigos únicos por categoría</li>
                            <li><i class="fas fa-check text-success"></i> Códigos EAN-13 válidos</li>
                            <li><i class="fas fa-check text-success"></i> Precios realistas</li>
                            <li><i class="fas fa-check text-success"></i> Stock inicial variable</li>
                            <li><i class="fas fa-check text-success"></i> Categorización automática</li>
                        </ul>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header bg-secondary text-white">
                        <h6><i class="fas fa-tools"></i> Acciones Rápidas</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="remito_generator.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-file-alt"></i> Crear Remitos Falsos
                            </a>
                            <a href="demo_center.php" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-play"></i> Centro de Demostración
                            </a>
                            <a href="control_center.php" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-eye"></i> Probar OCR
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function generarProductos() {
            if (!confirm('¿Generar productos simulados? Esto puede tomar unos segundos.')) return;

            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'generate_products');

                const response = await fetch('productos_simulator.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(`✅ Éxito!\n\nProductos creados: ${result.data.productos_creados}\nProductos actualizados: ${result.data.productos_actualizados}\n\n${result.data.mensaje}`);
                    location.reload();
                } else {
                    throw new Error(result.error);
                }

            } catch (error) {
                alert('❌ Error: ' + error.message);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function generarCodigosBarras() {
            if (!confirm('¿Generar códigos de barras para productos sin código?')) return;

            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'generate_barcodes');

                const response = await fetch('productos_simulator.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(`✅ Códigos de barras generados: ${result.data.codigos_generados}`);
                    location.reload();
                } else {
                    throw new Error(result.error);
                }

            } catch (error) {
                alert('❌ Error: ' + error.message);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function limpiarSimulacion() {
            if (!confirm('⚠️ ATENCIÓN: Esto eliminará TODOS los productos de simulación.\n\n¿Estás seguro?')) return;

            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Limpiando...';
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'clean_simulation');

                const response = await fetch('productos_simulator.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(`✅ Simulación limpiada!\n\nProductos eliminados: ${result.data.productos_eliminados}`);
                    location.reload();
                } else {
                    throw new Error(result.error);
                }

            } catch (error) {
                alert('❌ Error: ' + error.message);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>

</html>
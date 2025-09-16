<?php
require_once 'config/config.php';

iniciarSesionSegura();
requireLogin();

$pageTitle = "Dashboard - " . SISTEMA_NOMBRE;
$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';

// SOLUCIÓN: Obtener rol directamente de la base de datos
$usuario_rol = 'inventario'; // Valor por defecto
try {
    $pdo = conectarDB();
    if (isset($_SESSION['id_usuario'])) {
        $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ? AND activo = 1");
        $stmt->execute([$_SESSION['id_usuario']]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($resultado) {
            $usuario_rol = $resultado['rol'];
        }
    }
} catch (Exception $e) {
    // En caso de error, mantener valor por defecto
    $usuario_rol = 'inventario';
}

// Verificar si es administrador para mostrar módulo admin
$es_administrador = ($usuario_rol === 'admin' || $usuario_rol === 'administrador');

// Obtener estadísticas de forma segura
try {
    $pdo = conectarDB();

    // Estadísticas básicas de productos
    if (!isset($_SESSION['cuenta_id'])) {
        throw new Exception("ID de cuenta no encontrado en la sesión.");
    }
    $cuenta_id = (int)$_SESSION['cuenta_id'];
    $stats_productos = obtenerEstadisticasInventario($pdo, $cuenta_id);

    // Estadísticas adicionales con verificación de tablas
    $total_clientes = 0;
    $clientes_nuevos = 0;
    $pedidos_pendientes = 0;
    $pedidos_hoy = 0;
    $facturas_pendientes = 0;
    $monto_pendiente = 0;
    $ingresos_mes = 0;
    $compras_pendientes = 0;
    $compras_mes = 0;

    // Verificar tablas existentes
    $tablas_existentes = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tablas_existentes[] = $row[0];
    }

    // Estadísticas de clientes
    if (in_array('clientes', $tablas_existentes)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes WHERE activo = 1");
        $total_clientes = $stmt->fetch()['total'] ?? 0;

        $stmt = $pdo->query("SELECT COUNT(*) as nuevos FROM clientes WHERE DATE(fecha_creacion) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND activo = 1");
        $clientes_nuevos = $stmt->fetch()['nuevos'] ?? 0;
    }

    // Estadísticas de pedidos
    if (in_array('pedidos', $tablas_existentes)) {
        $stmt = $pdo->query("SELECT COUNT(*) as pendientes FROM pedidos WHERE estado = 'pendiente'");
        $pedidos_pendientes = $stmt->fetch()['pendientes'] ?? 0;

        $stmt = $pdo->query("SELECT COUNT(*) as hoy FROM pedidos WHERE DATE(fecha_pedido) = CURDATE()");
        $pedidos_hoy = $stmt->fetch()['hoy'] ?? 0;
    }

    // Estadísticas de facturas
    if (in_array('facturas', $tablas_existentes)) {
        $stmt = $pdo->query("SELECT COUNT(*) as pendientes, COALESCE(SUM(total), 0) as monto_pendiente FROM facturas WHERE estado = 'pendiente'");
        $facturas_data = $stmt->fetch();
        $facturas_pendientes = $facturas_data['pendientes'] ?? 0;
        $monto_pendiente = $facturas_data['monto_pendiente'] ?? 0;

        $stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) as ingresos_mes FROM facturas WHERE MONTH(fecha_factura) = MONTH(CURDATE()) AND YEAR(fecha_factura) = YEAR(CURDATE()) AND estado = 'pagada'");
        $ingresos_mes = $stmt->fetch()['ingresos_mes'] ?? 0;
    }

    // Estadísticas de compras
    if (in_array('compras', $tablas_existentes)) {
        $stmt = $pdo->query("SELECT COUNT(*) as pendientes FROM compras WHERE estado IN ('pendiente', 'confirmada')");
        $compras_pendientes = $stmt->fetch()['pendientes'] ?? 0;

        $stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) as compras_mes FROM compras WHERE MONTH(fecha_compra) = MONTH(CURDATE()) AND YEAR(fecha_compra) = YEAR(CURDATE())");
        $compras_mes = $stmt->fetch()['compras_mes'] ?? 0;
    }
} catch (Exception $e) {
    // Valores por defecto en caso de error
    $stats_productos = [
        'total_productos' => 0,
        'productos_bajo_stock' => 0,
        'valor_total_inventario' => 0,
        'precio_promedio' => 0
    ];
    $total_clientes = 0;
    $clientes_nuevos = 0;
    $pedidos_pendientes = 0;
    $pedidos_hoy = 0;
    $facturas_pendientes = 0;
    $monto_pendiente = 0;
    $ingresos_mes = 0;
    $compras_pendientes = 0;
    $compras_mes = 0;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .navbar-custom {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: bold;
            color: white !important;
            font-size: 1.1rem;
        }

        .navbar-nav .nav-link {
            color: white !important;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 2px;
            border-radius: 5px;
            padding: 8px 12px !important;
            font-size: 0.95rem;
        }

        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        .navbar-nav .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .dropdown-item {
            padding: 8px 16px;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .content-area {
            min-height: calc(100vh - 120px);
            padding: 20px 0;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 15px;
            text-align: center;
        }

        .dashboard-header h1 {
            font-size: 2.2rem;
            font-weight: 300;
            margin-bottom: 10px;
        }

        .dashboard-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
            margin-bottom: 20px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            font-size: 2.2rem;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-detail {
            font-size: 0.8rem;
            margin-top: 10px;
            padding: 6px 10px;
            border-radius: 15px;
        }

        .module-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
            margin-bottom: 20px;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .module-icon {
            font-size: 2.8rem;
            margin-bottom: 20px;
        }

        .module-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .module-description {
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .btn-module {
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .btn-module:hover {
            transform: translateY(-2px);
        }

        .actions-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #495057;
        }

        .quick-action-btn {
            border-radius: 10px;
            padding: 10px 18px;
            font-weight: 500;
            margin: 5px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .quick-action-btn:hover {
            transform: translateY(-2px);
        }

        .admin-only {
            position: relative;
        }

        .admin-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
        }

        .text-purple {
            color: #6f42c1 !important;
        }

        .btn-purple {
            background-color: #6f42c1;
            border-color: #6f42c1;
            color: white;
        }

        .btn-purple:hover {
            background-color: #5a32a3;
            border-color: #5a32a3;
            color: white;
        }

        /* Mejoras para móviles y tablets */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1rem;
            }

            .navbar-nav .nav-link {
                font-size: 0.9rem;
                padding: 6px 10px !important;
                margin: 1px;
            }

            .dashboard-header {
                padding: 20px 15px;
                margin-bottom: 20px;
            }

            .dashboard-header h1 {
                font-size: 1.8rem;
            }

            .dashboard-header p {
                font-size: 1rem;
            }

            .stat-card {
                padding: 15px;
                margin-bottom: 15px;
            }

            .stat-icon {
                font-size: 2rem;
            }

            .stat-number {
                font-size: 1.5rem;
            }

            .module-card {
                padding: 20px;
                margin-bottom: 15px;
            }

            .module-icon {
                font-size: 2.5rem;
            }

            .module-title {
                font-size: 1.1rem;
            }

            .actions-section {
                padding: 15px;
                margin-bottom: 20px;
            }

            .section-title {
                font-size: 1.2rem;
            }

            .quick-action-btn {
                padding: 8px 15px;
                margin: 3px;
                font-size: 0.85rem;
                width: 100%;
                margin-bottom: 8px;
            }

            .content-area {
                padding: 15px 0;
            }
        }

        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 0.9rem;
            }

            .navbar-nav .nav-link {
                font-size: 0.85rem;
                padding: 5px 8px !important;
            }

            .dashboard-header h1 {
                font-size: 1.6rem;
            }

            .stat-number {
                font-size: 1.3rem;
            }

            .module-icon {
                font-size: 2.2rem;
            }

            .module-title {
                font-size: 1rem;
            }

            .module-description {
                font-size: 0.8rem;
            }
        }

        /* Mejoras para tablets */
        @media (min-width: 769px) and (max-width: 1024px) {
            .navbar-nav .nav-link {
                font-size: 0.9rem;
                padding: 7px 11px !important;
            }

            .stat-card {
                padding: 18px;
            }

            .module-card {
                padding: 22px;
            }
        }

        /* Ocultar elementos en pantallas muy pequeñas */
        @media (max-width: 480px) {
            .stat-detail {
                display: none;
            }

            .module-description {
                display: none;
            }
        }
    </style>
</head>

<body>
    <!-- NAVBAR UNIFICADO -->
    <?php include 'config/navbar_code.php'; ?>

    <!-- Contenido Principal (Dashboard) -->
    <div class="content-area">
        <div class="container">

            <!-- Cabecera del Dashboard -->
            <div class="dashboard-header">
                <h1>Bienvenido, <?php echo htmlspecialchars($usuario_nombre); ?></h1>
                <p>Resumen general de tu actividad reciente.</p>
            </div>

            <!-- Tarjetas de Estadísticas -->
            <div class="row">
                <!-- Tarjeta Total Productos -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stat-label">Total Productos</p>
                                <h3 class="stat-number"><?php echo $stats_productos['total_productos']; ?></h3>
                            </div>
                            <i class="bi bi-box-seam stat-icon text-primary"></i>
                        </div>
                        <div class="stat-detail bg-light text-primary">
                            Valor: $<?php echo number_format($stats_productos['valor_total_inventario'], 2); ?>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta Bajo Stock -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stat-label">Bajo Stock</p>
                                <h3 class="stat-number text-danger"><?php echo $stats_productos['productos_bajo_stock']; ?></h3>
                            </div>
                            <i class="bi bi-exclamation-triangle stat-icon text-danger"></i>
                        </div>
                        <div class="stat-detail bg-light text-danger">
                            Revisar urgentemente
                        </div>
                    </div>
                </div>

                <!-- Tarjeta Pedidos Pendientes -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stat-label">Pedidos Pendientes</p>
                                <h3 class="stat-number text-warning"><?php echo $pedidos_pendientes; ?></h3>
                            </div>
                            <i class="bi bi-clock-history stat-icon text-warning"></i>
                        </div>
                        <div class="stat-detail bg-light text-warning">
                            <?php echo $pedidos_hoy; ?> pedidos hoy
                        </div>
                    </div>
                </div>

                <!-- Tarjeta Ingresos del Mes -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stat-label">Ingresos del Mes</p>
                                <h3 class="stat-number text-success">$<?php echo number_format($ingresos_mes, 2); ?></h3>
                            </div>
                            <i class="bi bi-cash-coin stat-icon text-success"></i>
                        </div>
                        <div class="stat-detail bg-light text-success">
                            Facturas pendientes: <?php echo $facturas_pendientes; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Acciones Rápidas -->
            <div class="actions-section">
                <h4 class="section-title">Acciones Rápidas</h4>
                <div class="text-center">
                    <a href="modulos/Inventario/producto_form.php" class="btn btn-primary quick-action-btn"><i class="bi bi-plus-circle"></i> Nuevo Producto</a>
                    <a href="modulos/pedidos/pedido_form.php" class="btn btn-info quick-action-btn text-white"><i class="bi bi-cart-plus"></i> Nuevo Pedido</a>
                    <a href="modulos/clientes/cliente_form.php" class="btn btn-secondary quick-action-btn"><i class="bi bi-person-plus"></i> Nuevo Cliente</a>
                    <a href="modulos/facturas/factura_form.php" class="btn btn-success quick-action-btn"><i class="bi bi-receipt"></i> Nueva Factura</a>
                    <a href="modulos/compras/compra_form.php" class="btn btn-warning quick-action-btn text-dark"><i class="bi bi-truck"></i> Nueva Compra</a>
                </div>
            </div>

            <!-- Módulos Principales -->
            <div class="row">
                <h4 class="section-title mb-3">Módulos Principales</h4>

                <!-- Módulo Inventario -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="module-card">
                        <i class="bi bi-boxes module-icon text-primary"></i>
                        <h5 class="module-title">Inventario</h5>
                        <p class="module-description">Gestiona productos, categorías, stock y ubicaciones.</p>
                        <a href="modulos/Inventario/productos.php" class="btn btn-primary btn-module">Acceder</a>
                    </div>
                </div>

                <!-- Módulo Clientes -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="module-card">
                        <i class="bi bi-people-fill module-icon text-secondary"></i>
                        <h5 class="module-title">Clientes</h5>
                        <p class="module-description">Administra tu base de datos de clientes y sus datos.</p>
                        <a href="modulos/clientes/clientes.php" class="btn btn-secondary btn-module">Acceder</a>
                    </div>
                </div>

                <!-- Módulo Pedidos y Ventas -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="module-card">
                        <i class="bi bi-cart-check-fill module-icon text-info"></i>
                        <h5 class="module-title">Pedidos y Ventas</h5>
                        <p class="module-description">Crea y sigue el estado de los pedidos de tus clientes.</p>
                        <a href="modulos/pedidos/pedidos.php" class="btn btn-info btn-module text-white">Acceder</a>
                    </div>
                </div>

                <!-- Módulo Compras -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="module-card">
                        <i class="bi bi-truck-flatbed module-icon text-warning"></i>
                        <h5 class="module-title">Compras</h5>
                        <p class="module-description">Registra compras a proveedores y gestiona la recepción.</p>
                        <a href="modulos/compras/compras.php" class="btn btn-warning btn-module text-dark">Acceder</a>
                    </div>
                </div>

                <!-- Módulo Facturación -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="module-card">
                        <i class="bi bi-receipt-cutoff module-icon text-success"></i>
                        <h5 class="module-title">Facturación</h5>
                        <p class="module-description">Genera y administra facturas, controla pagos pendientes.</p>
                        <a href="modulos/facturas/facturas.php" class="btn btn-success btn-module">Acceder</a>
                    </div>
                </div>

                <!-- Módulo Administración -->
                <?php if ($es_administrador): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="module-card admin-only">
                            <span class="admin-badge">Admin</span>
                            <i class="bi bi-shield-lock-fill module-icon text-danger"></i>
                            <h5 class="module-title">Administración</h5>
                            <p class="module-description">Gestiona usuarios, configuraciones y copias de seguridad.</p>
                            <a href="modulos/admin/admin_dashboard.php" class="btn btn-danger btn-module">Acceder</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Activar tooltips de Bootstrap si se usan
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // Lógica para mantener activo el link del menú actual
            const currentLocation = window.location.href;
            const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
            const dropdownItems = document.querySelectorAll('.dropdown-item');

            // Función para marcar como activo
            function setActive(element) {
                element.classList.add('active');
                // Si es un item de dropdown, también marcar el toggle principal
                const parentDropdown = element.closest('.nav-item.dropdown');
                if (parentDropdown) {
                    parentDropdown.querySelector('.nav-link.dropdown-toggle').classList.add('active');
                }
            }

            // Remover la clase 'active' del link de Dashboard si no estamos en la página principal
            if (!currentLocation.endsWith('menu_principal.php') && !currentLocation.endsWith('/')) {
                const dashboardLink = document.querySelector('a[href="menu_principal.php"]');
                if (dashboardLink) {
                    dashboardLink.classList.remove('active');
                }
            }

            // Revisar links principales
            navLinks.forEach(link => {
                if (link.href === currentLocation) {
                    setActive(link);
                }
            });

            // Revisar items de dropdown
            dropdownItems.forEach(item => {
                if (item.href === currentLocation) {
                    setActive(item);
                }
            });
        });
    </script>
</body>

</html>
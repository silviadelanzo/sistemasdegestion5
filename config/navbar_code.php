<?php
// Detectar automáticamente si estamos en root o en módulos
$current_path = $_SERVER['REQUEST_URI'];
$is_root = !strpos($current_path, '/modulos/');
$base_path = $is_root ? 'modulos/' : '../../modulos/';
$dashboard_path = $is_root ? 'menu_principal.php' : '../../menu_principal.php';
$logout_path = $is_root ? 'logout.php' : '../../logout.php';

// Verificar si es administrador
$es_administrador = isset($_SESSION['rol_usuario']) && ($_SESSION['rol_usuario'] === 'administrador' || $_SESSION['rol_usuario'] === 'admin');
?>
<!-- INICIO: Navbar Superior Modificado -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <style>
        .navbar-custom {
            background-color: #0074D9 !important;
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
    </style>
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo $dashboard_path; ?>">
            <i class="bi bi-speedometer2"></i> Gestión Administrativa
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon" style="background-image: url('data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 30 30\'%3e%3cpath stroke=\'rgba%28255, 255, 255, 0.75%29\' stroke-linecap=\'round\' stroke-miterlimit=\'10\' stroke-width=\'2\' d=\'M4 7h22M4 15h22M4 23h22\'/%3e%3c/svg%3e');"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $dashboard_path; ?>">
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
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/compras.php"><i class="bi bi-list-ul"></i> Ver Compras</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/remito_nuevo.php"><i class="bi bi-plus-circle"></i> Nuevo Remito</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/remitos.php"><i class="bi bi-file-earmark-text"></i> Ver Remitos</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/proveedores.php"><i class="bi bi-building"></i> Proveedores</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/recepcion_mercaderia.php"><i class="bi bi-box-arrow-in-down"></i> Recepción</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/reportes_compras.php"><i class="bi bi-graph-up"></i> Reportes</a></li>
                    </ul>
                </li>
                <!-- Menú Productos -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-box-seam"></i> Productos
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/productos.php"><i class="bi bi-list-ul"></i> Listado de Productos</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/producto_form.php"><i class="bi bi-plus-circle"></i> Nuevo Producto</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/productos_por_categoria.php"><i class="bi bi-tag"></i> Por Categoria</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/productos_por_lugar.php"><i class="bi bi-geo-alt"></i> Por Ubicación</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/productos_inactivos.php"><i class="bi bi-archive"></i> Productos Inactivos</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/reportes.php"><i class="bi bi-graph-up"></i> Reportes</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-people"></i> Clientes
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>clientes/clientes.php"><i class="bi bi-list-ul"></i> Ver Clientes</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>clientes/cliente_form.php"><i class="bi bi-person-plus"></i> Nuevo Cliente</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>clientes/clientes_inactivos.php"><i class="bi bi-person-x"></i> Clientes Inactivos</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>clientes/papelera_clientes.php"><i class="bi bi-trash"></i> Papelera</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-cart"></i> Pedidos
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>pedidos/pedidos.php"><i class="bi bi-list-ul"></i> Ver Pedidos</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>pedidos/pedido_form.php"><i class="bi bi-cart-plus"></i> Nuevo Pedido</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>pedidos/pedidos_pendientes.php"><i class="bi bi-clock"></i> Pedidos Pendientes</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>pedidos/reportes_pedidos.php"><i class="bi bi-graph-up"></i> Reportes</a></li>
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
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>facturas/facturas.php"><i class="bi bi-list-ul"></i> Ver Facturas</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>facturas/factura_form.php"><i class="bi bi-receipt"></i> Nueva Factura</a></li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>facturas/facturas_pendientes.php"><i class="bi bi-clock"></i> Facturas Pendientes</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="<?php echo $base_path; ?>facturas/reportes_facturas.php"><i class="bi bi-graph-up"></i> Reportes</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <h6 class="dropdown-header">Rol: <?php echo ucfirst(htmlspecialchars($_SESSION['rol_usuario'] ?? 'Usuario')); ?></h6>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <?php if ($es_administrador): ?>
                            <li>
                                <h6 class="dropdown-header text-danger"><i class="bi bi-shield-check"></i> Administración</h6>
                            </li>
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>admin/admin_dashboard.php"><i class="bi bi-speedometer2"></i> Panel Admin</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="<?php echo $logout_path; ?>"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<!-- FIN: Navbar Superior Modificado -->
<?php
// Detectar automáticamente si estamos en root o en módulos
$current_path = $_SERVER['REQUEST_URI'];
$is_root = (strpos($current_path, '/modulos/') === false);
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
    </a>
    <ul class="dropdown-menu">
    <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/compras.php"><i class="bi bi-list-ul me-2"></i>Ver Compras</a></li>
    </ul>
    </li>
    <!-- Menú Productos -->
    <!-- Men�fa Proveedores -->
    <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="bi bi-building"></i> Proveedores
    </a>
    <ul class="dropdown-menu">
    <li><a class="dropdown-item" href="http://localhost/sistemadgestion5/modulos/compras/proveedores.php"><i class="bi bi-list-ul me-2"></i>Listado Proveedores</a></li>
    <li><a class="dropdown-item" href="http://localhost/sistemadgestion5/modulos/compras/new_prov_complete.php?origen=proveedores"><i class="bi bi-pencil-square me-2"></i>A/B/M Proveedores</a></li>
    <li><a class="dropdown-item" href="http://localhost/sistemadgestion5/modulos/compras/ocom_listado.php"><i class="bi bi-file-earmark-plus me-2"></i>+ Orden de Compra</a></li>
    <li><a class="dropdown-item" href="http://localhost/sistemadgestion5/modulos/compras/recepcion_mercaderia.php"><i class="bi bi-box-arrow-in-down me-2"></i>Recep. Mercad. c/Remito</a></li>
    </ul>
    </li>

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
    <i class="bi bi-cart"></i> Ventas
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
<script>
(function(){
  try {
    var menu = <?php echo json_encode($menuActivo ?? ''); ?>;
    if(!menu) return;
    var map = {
    'compras': 'i.bi-truck',
    'proveedores': 'i.bi-building',
    'productos': 'i.bi-box-seam',
    'clientes': 'i.bi-people',
    'ventas': 'i.bi-cart'
    };
    var sel = map[menu];
    if (!sel) return;
    var icon = document.querySelector(sel);
    if (!icon) return;
    var a = icon.closest('a.nav-link');
    if (a) a.classList.add('active');
  } catch (e) {}
})();
</script>
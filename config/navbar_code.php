<?php
// Definir rutas absolutas usando la constante BASE_URL de config.php
$base_path = BASE_URL . '/modulos/'; // Ruta base para todos los módulos
$dashboard_path = BASE_URL . '/paneldecontrol.php';
$logout_path = BASE_URL . '/logout.php';

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
    <span class="navbar-toggler-icon" style="background-image: url('data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 30 30\'%3e%3cpath stroke=\'rgba%28255, 255, 255, 0.75%29\' stroke-linecap=\'round\' stroke-miterlimit=\'10\' stroke-width=\'2\' d=\'M4 7h22M4 15h22M4 23h22\'/_e3c/svg_e3e');"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav me-auto">
        
        <!-- Dashboard -->
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $dashboard_path; ?>"><i class="bi bi-house-door"></i> Dashboard</a>
        </li>

        <!-- Agenda -->
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $base_path; ?>agenda/index.php"><i class="bi bi-calendar-event"></i> Agenda</a>
        </li>

        <!-- Compras -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-cart4"></i> Compras
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/compras.php">Compras Detalle</a></li>
                <li><a class="dropdown-item" href="http://localhost/sistemadgestion5/modulos/compras/compra_form.php">Nueva O. de Compra</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/recepcion_mercaderia.php">Recepción de Mercadería</a></li>
            </ul>
        </li>

        <!-- Proveedores -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-truck"></i> Proveedores
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/proveedores.php">Listado de Proveedores</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/new_prov_complete.php">Nuevo proveedor</a></li>
            </ul>
        </li>

        <!-- Productos -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-box-seam"></i> Productos
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/productos.php">Listado de Productos</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/producto_form.php">Nuevo Producto</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/productos_por_categoria.php">Productos por Categoría</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/productos_por_lugar.php">Productos por Lugar</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/productos_inactivos.php">Productos Inactivos</a></li>
            </ul>
        </li>

        <!-- Clientes -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-people"></i> Clientes
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>clientes/clientes.php">Ver Clientes</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>clientes/cliente_form.php">Nuevo Cliente</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>clientes/clientes_inactivos.php">Clientes Inactivos</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>clientes/papelera_clientes.php">Papelera</a></li>
            </ul>
        </li>

        <!-- Pedidos -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-journal-text"></i> Pedidos
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>pedidos/pedidos.php">Ver Pedidos</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>pedidos/pedido_form.php">Nuevo Pedido</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>pedidos/pedidos_pendientes.php">Pedidos Pendientes</a></li>
            </ul>
        </li>

        <!-- Ventas -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-cash-coin"></i> Ventas
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>ventas/factura_form.php">Nueva Factura</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>ventas/remito_form.php">Nuevo Remito</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>ventas/presupuesto_form.php">Nuevo Presupuesto</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>ventas/notacredito_form.php">Nueva Nota de Crédito</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>ventas/cobranza_form.php">Registrar Cobranza</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>ventas/facturas.php">Ver Facturas</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>ventas/remitos.php">Ver Remitos</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>ventas/presupuestos.php">Ver Presupuestos</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>ventas/notas_credito.php">Ver Notas de Crédito</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>ventas/cobranzas.php">Ver Cobranzas</a></li>
            </ul>
        </li>

        <!-- Reportes -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-graph-up"></i> Reportes
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/reportes.php">Reportes de Inventario</a></li>
                <li><a class="dropdown-item" href="<?php echo $base_path; ?>pedidos/reportes_pedidos.php">Reportes de Pedidos</a></li>
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
    var menuActivo = <?php echo json_encode($menuActivo ?? ''); ?>;
    if(!menuActivo) return;
    
    var map = {
        'dashboard': 'i.bi-house-door',
        'agenda': 'i.bi-calendar-event',
        'compras': 'i.bi-cart4',
        'proveedores': 'i.bi-truck',
        'productos': 'i.bi-box-seam',
        'clientes': 'i.bi-people',
        'pedidos': 'i.bi-journal-text',
        'ventas': 'i.bi-cash-coin',
        'reportes': 'i.bi-graph-up'
    };

    var selector = map[menuActivo];
    if (!selector) return;
    
    var icon = document.querySelector(selector);
    if (!icon) return;

    var link = icon.closest('a.nav-link');
    if (link) {
        link.classList.add('active');
    }
  } catch (e) {
      console.error("Error al activar el menú:", e);
  }
})();
</script>
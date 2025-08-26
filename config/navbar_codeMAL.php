<?php
// Asegura sesión para mostrar usuario
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Detecta contexto (raíz o dentro de /modulos/)
$current_path   = $_SERVER['REQUEST_URI'] ?? '';
$is_root        = (strpos($current_path, '/modulos/') === false);
$base_path      = $is_root ? 'modulos/' : '../../modulos/';
$dashboard_path = $is_root ? 'menu_principal.php' : '../../menu_principal.php';
$logout_path    = $is_root ? 'logout.php' : '../../logout.php';

// Permite resaltar menú activo con $menuActivo = 'ventas' | 'compras' | 'proveedores' | 'productos' | 'clientes'
$menuActivo = $menuActivo ?? '';

// Permisos
$es_administrador = isset($_SESSION['rol_usuario']) && (
  $_SESSION['rol_usuario'] === 'administrador' || $_SESSION['rol_usuario'] === 'admin'
);
?>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background-color:#0074D9;box-shadow:0 2px 4px rgba(0,0,0,.1);z-index:1030;">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="<?php echo $dashboard_path; ?>">
      <i class="bi bi-speedometer2"></i> Gestión Administrativa
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon" style="background-image:url('data:image/svg+xml,%3csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 30 30%22%3e%3cpath stroke=%22rgba(255,255,255,0.75)%22 stroke-linecap=%22round%22 stroke-miterlimit=%2210%22 stroke-width=%222%22 d=%22M4 7h22M4 15h22M4 23h22%22/%3e%3c/svg%3e');"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?php echo $dashboard_path; ?>"><i class="bi bi-house-door"></i> Dashboard</a></li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?php echo ($menuActivo==='compras'?'active':''); ?>" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-truck"></i> Compras
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/compras.php"><i class="bi bi-list-ul me-2"></i>Ver Compras</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?php echo ($menuActivo==='proveedores'?'active':''); ?>" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-building"></i> Proveedores
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/proveedores.php"><i class="bi bi-list-ul me-2"></i>Listado Proveedores</a></li>
            <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/new_prov_complete.php?origen=proveedores"><i class="bi bi-pencil-square me-2"></i>A/B/M Proveedores</a></li>
            <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/ocom_listado.php"><i class="bi bi-file-earmark-plus me-2"></i>+ Orden de Compra</a></li>
            <li><a class="dropdown-item" href="<?php echo $base_path; ?>compras/recepcion_mercaderia.php"><i class="bi bi-box-arrow-in-down me-2"></i>Recep. Mercad. c/Remito</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?php echo ($menuActivo==='productos'?'active':''); ?>" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-box-seam"></i> Productos
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/productos.php"><i class="bi bi-list-ul"></i> Listado de Productos</a></li>
            <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/producto_form.php"><i class="bi bi-plus-circle"></i> Nuevo Producto</a></li>
            <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/productos_por_categoria.php"><i class="bi bi-tag"></i> Por Categoria</a></li>
            <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/productos_por_lugar.php"><i class="bi bi-geo-alt"></i> Por Ubicación</a></li>
            <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/productos_inactivos.php"><i class="bi bi-archive"></i> Productos Inactivos</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo $base_path; ?>Inventario/reportes.php"><i class="bi bi-graph-up"></i> Reportes</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?php echo ($menuActivo==='clientes'?'active':''); ?>" href="#" data-bs-toggle="dropdown">
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
          <a class="nav-link dropdown-toggle <?php echo ($menuActivo==='ventas'?'active':''); ?>" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-cart"></i> Ventas
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?php echo $base_path; ?>pedidos/pedidos.php"><i class="bi bi-list-ul"></i> Ver Pedidos</a></li>
            <li><a class="dropdown-item" href="<?php echo $base_path; ?>pedidos/pedido_form.php"><i class="bi bi-cart-plus"></i> Nuevo Pedido</a></li>
            <li><a class="dropdown-item" href="<?php echo $base_path; ?>pedidos/pedidos_pendientes.php"><i class="bi bi-clock"></i> Pedidos Pendientes</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo $base_path; ?>pedidos/reportes_pedidos.php"><i class="bi bi-graph-up"></i> Reportes</a></li>
          </ul>
        </li>
      </ul>

      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario'); ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><h6 class="dropdown-header">Rol: <?php echo ucfirst(htmlspecialchars($_SESSION['rol_usuario'] ?? 'Usuario')); ?></h6></li>
            <li><hr class="dropdown-divider"></li>
            <?php if ($es_administrador): ?>
              <li><h6 class="dropdown-header text-danger"><i class="bi bi-shield-check"></i> Administración</h6></li>
              <li><a class="dropdown-item" href="<?php echo $base_path; ?>admin/admin_dashboard.php"><i class="bi bi-speedometer2"></i> Panel Admin</a></li>
              <li><hr class="dropdown-divider"></li>
            <?php endif; ?>
            <li><a class="dropdown-item" href="<?php echo $logout_path; ?>"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<style>
/* Estilos mínimos para asegurar buena visual del navbar */
.navbar .dropdown-menu .dropdown-item i { width: 1.1rem; }
</style>

<script>
// Cargar assets (CSS/JS) solo si faltan. Evita repetir trabajo en cada página.
(function(){
  function hasCssMatch(match) {
    return Array.from(document.styleSheets || []).some(s => {
      try { return s.href && s.href.indexOf(match) !== -1; } catch(e){ return false; }
    });
  }
  function hasScriptMatch(match) {
    return Array.from(document.scripts || []).some(s => s.src && s.src.indexOf(match) !== -1);
  }
  function addCss(href, id) {
    if (id && document.getElementById(id)) return;
    if (hasCssMatch(href.split('/').slice(-3).join('/'))) return;
    var l=document.createElement('link'); if(id) l.id=id; l.rel='stylesheet'; l.href=href; document.head.appendChild(l);
  }
  function addJs(src, id) {
    if (id && document.getElementById(id)) return;
    if (hasScriptMatch(src.split('/').slice(-3).join('/'))) return;
    var s=document.createElement('script'); if(id) s.id=id; s.src=src; s.defer=true; document.head.appendChild(s);
  }
  // CSS necesarios
  addCss('https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css','bootstrap-css');
  addCss('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css','bootstrap-icons-css');
  addCss('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css','fa-css');

  // JS de Bootstrap (bundle con Popper) solo si falta
  if (!window.bootstrap || !window.bootstrap.Dropdown) {
    addJs('https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js','bootstrap-bundle');
  }
})();

// Resaltar el menú activo según $menuActivo
(function(){
  try{
    var menu = <?php echo json_encode($menuActivo); ?>;
    if(!menu) return;
    var map = {'compras':'i.bi-truck','proveedores':'i.bi-building','productos':'i.bi-box-seam','clientes':'i.bi-people','ventas':'i.bi-cart'};
    var sel = map[menu]; if(!sel) return;
    var icon = document.querySelector(sel); if(!icon) return;
    var a = icon.closest('a.nav-link'); if(a) a.classList.add('active');
  }catch(e){}
})();
</script>
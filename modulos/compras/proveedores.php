<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');
header('Content-Type: text/html; charset=UTF-8');

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$filtro_busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

$orden_campo = isset($_GET['orden']) ? $_GET['orden'] : 'razon_social';
$orden_direccion = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';

$campos_permitidos = ['codigo', 'razon_social', 'email', 'telefono', 'activo', 'fecha_creacion'];
if (!in_array($orden_campo, $campos_permitidos)) {
    $orden_campo = 'razon_social';
}

$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
$usuario_rol = $_SESSION['rol_usuario'] ?? 'inventario';
$es_administrador = ($usuario_rol === 'admin' || $usuario_rol === 'administrador');

$compras_pendientes = 0;
$facturas_pendientes = 0;

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Obtener datos para los selectores del modal
    $paises = $pdo->query("SELECT * FROM paises WHERE activo = 1 ORDER BY CASE WHEN nombre = 'Argentina' THEN 1 WHEN nombre = 'España' THEN 2 WHEN nombre = 'México' THEN 3 ELSE 4 END, nombre")->fetchAll(PDO::FETCH_ASSOC);
    $provincias = $pdo->query("SELECT * FROM provincias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $ciudades = $pdo->query("SELECT * FROM ciudades ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

    // 🔢 FUNCIÓN AUTO-NUMERACIÓN PROVEEDORES (7 DÍGITOS)
    function generarProximoCodigoProveedor($pdo) {
        $sql_code = "SELECT codigo FROM proveedores WHERE codigo LIKE 'PROV-%' ORDER BY CAST(SUBSTRING(codigo, 6) AS UNSIGNED) DESC, codigo DESC LIMIT 1";
        $stmt_code = $pdo->query($sql_code);
        $ultimo_codigo = $stmt_code->fetchColumn();
        $numero = $ultimo_codigo ? intval(substr($ultimo_codigo, 5)) + 1 : 1;
        return 'PROV-' . str_pad($numero, 7, '0', STR_PAD_LEFT);
    }
    
    $proximoCodigoProveedor = generarProximoCodigoProveedor($pdo);

    // 📱 LISTA DE PAÍSES CON CÓDIGOS TELEFÓNICOS (ESTILO CLIENTE_FORM)
    $lista_paises_telefonicos = [];
    foreach ($paises as $pais) {
        if (!empty($pais['codigo_telefono'])) {
            $lista_paises_telefonicos[$pais['nombre']] = $pais['codigo_telefono'];
        }
    }

    // Verificar si las columnas de papelera existen, si no crearlas
    $stmt_tables = $pdo->query("SHOW COLUMNS FROM proveedores LIKE 'eliminado'");
    if ($stmt_tables->rowCount() === 0) {
        $pdo->exec("ALTER TABLE proveedores ADD COLUMN eliminado TINYINT(1) DEFAULT 0");
        $pdo->exec("ALTER TABLE proveedores ADD COLUMN fecha_eliminacion DATETIME NULL");
        $pdo->exec("ALTER TABLE proveedores ADD COLUMN eliminado_por VARCHAR(100) NULL");
    }

    // Badges para el menú (compras/facturas pendientes)
    $tablas_existentes = [];
    $stmt_tables = $pdo->query("SHOW TABLES");
    if ($stmt_tables) {
        while ($row_table = $stmt_tables->fetch(PDO::FETCH_NUM)) {
            $tablas_existentes[] = $row_table[0];
        }
    }
    if (in_array('compras', $tablas_existentes)) {
        $stmt = $pdo->query("SELECT COUNT(*) as pendientes FROM compras WHERE estado IN ('pendiente', 'confirmada')");
        if ($stmt) $compras_pendientes = $stmt->fetch()['pendientes'] ?? 0;
    }
    if (in_array('facturas', $tablas_existentes)) {
        $stmt = $pdo->query("SELECT COUNT(*) as pendientes FROM facturas WHERE estado = 'pendiente'");
        if ($stmt) $facturas_pendientes = $stmt->fetch()['pendientes'] ?? 0;
    }

    // Dashboard - solo proveedores no eliminados
    $total_proveedores = $pdo->query("SELECT COUNT(*) FROM proveedores WHERE eliminado = 0")->fetchColumn();
    $proveedores_activos = $pdo->query("SELECT COUNT(*) FROM proveedores WHERE activo=1 AND eliminado = 0")->fetchColumn();
    $proveedores_inactivos = $pdo->query("SELECT COUNT(*) FROM proveedores WHERE activo=0 AND eliminado = 0")->fetchColumn();
    $compras_mes = $pdo->query("SELECT COUNT(*) FROM compras WHERE MONTH(fecha_compra) = MONTH(CURRENT_DATE) AND YEAR(fecha_compra) = YEAR(CURRENT_DATE)")->fetchColumn();

    // Filtros para listado - excluir eliminados
    $where_conditions = ['eliminado = 0'];
    $params = [];

    if ($filtro_busqueda !== '') {
        $where_conditions[] = "(codigo LIKE ? OR razon_social LIKE ? OR email LIKE ? OR telefono LIKE ?)";
        $busqueda = "%{$filtro_busqueda}%";
        array_push($params, $busqueda, $busqueda, $busqueda, $busqueda);
    }
    if ($filtro_estado !== '' && $filtro_estado !== 'todos') {
        $where_conditions[] = "activo = ?";
        $params[] = ($filtro_estado === 'activo') ? 1 : 0;
    }
    $where_clause = implode(' AND ', $where_conditions);

    $orden_sql = $orden_campo;

    // Listado de proveedores
    $sql = "SELECT * FROM proveedores WHERE $where_clause
            ORDER BY $orden_sql $orden_direccion
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $proveedores = $stmt->fetchAll();

    $count_sql = "SELECT COUNT(*) FROM proveedores WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $proveedores_filtrados = $count_stmt->fetchColumn();
    $total_pages = ceil($proveedores_filtrados / $per_page);

} catch (Exception $e) {
    $error_message = "Error al cargar proveedores: " . $e->getMessage();
    $proveedores = [];
    $total_pages = 1;
    $total_proveedores = 0;
    $proveedores_activos = 0;
    $proveedores_inactivos = 0;
    $compras_mes = 0;
}

$pageTitle = "Gestión de Proveedores - " . SISTEMA_NOMBRE;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .main-container {
            margin: 0 auto;
            max-width: 1200px;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-top: 30px;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .btn-action {
            padding: 4px 8px;
            margin: 0 1px;
            border-radius: 5px;
            font-size: 0.85rem;
        }

        .search-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 15px 20px;
            margin-top: 20px;
        }

        .info-cards {
            display: flex;
            gap: 20px;
            margin: 30px 0 10px 0;
        }

        .info-card {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 12px;
            color: #fff;
            font-weight: 600;
            font-size: 1.2rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            min-width: 200px;
        }

        .ic-purple {
            background: linear-gradient(90deg, #6a11cb 0%, #2575fc 100%);
        }

        .ic-pink {
            background: linear-gradient(90deg, #ff758c 0%, #ff7eb3 100%);
        }

        .ic-cyan {
            background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
        }

        .ic-green {
            background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
        }

        .info-card .icon {
            font-size: 2.3rem;
            margin-left: auto;
            opacity: 0.7;
        }

        .pagination-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-top: 30px;
            padding: 15px 0;
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

        .whatsapp-link {
            color: #25D366;
            font-size: 1.3em;
            margin-left: 3px;
            margin-right: 3px;
            vertical-align: middle;
        }

        .whatsapp-link:hover {
            color: #128C7E;
        }

        /* ESTILOS DEL MODAL UNIFICADO */
        :root {
            --primary-color: #0074D9;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }

        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .card-custom:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1rem 1.5rem;
        }

        .btn-nuevo {
            background-color: var(--success-color);
            border: none;
            color: white;
            border-radius: 8px;
            padding: 5px 15px;
            font-size: 0.85rem;
            margin-left: 10px;
        }

        .btn-nuevo:hover {
            background-color: #218838;
            color: white;
        }

        .phone-input {
            position: relative;
            display: flex;
            align-items: center;
        }

        .phone-prefix {
            width: 85px;
            height: 38px;
            border: 1px solid #ddd;
            border-right: none;
            border-radius: 4px 0 0 4px;
            background: white;
            font-size: 0.85rem;
            padding: 0 5px;
            flex-shrink: 0;
        }

        .phone-number {
            border-radius: 0 4px 4px 0;
            border-left: none;
            flex: 1;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }

        .btn-close-white {
            filter: brightness(0) invert(1);
        }
    </style>
    <script>
    function openWhatsApp(tel, nombre) {
        if (!tel || tel.trim() === "" || tel.trim() === "+") {
            var nuevoTel = prompt("Ingrese el número de WhatsApp para " + (nombre ? nombre : "el proveedor") + " (con código de país, sin espacios ni guiones):");
            if (!nuevoTel || nuevoTel.trim() === "") return false;
            tel = nuevoTel.trim();
        }
        tel = tel.replace(/[^0-9+]/g, "");
        var url = "https://wa.me/" + tel;
        window.open(url, "_blank");
        return false;
    }
    </script>
</head>

<body>
    <!-- NAVBAR UNIFICADO -->
    <?php include "../../config/navbar_code.php"; ?>

    <div class="main-container">

        <!-- Tarjetas resumen -->
        <div class="info-cards">
            <div class="info-card ic-purple">
                <?= number_format($total_proveedores) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Total Proveedores</span>
                <span class="icon ms-2"><i class="bi bi-truck"></i></span>
            </div>
            <div class="info-card ic-pink">
                <?= number_format($proveedores_activos) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Activos</span>
                <span class="icon ms-2"><i class="bi bi-check-circle"></i></span>
            </div>
            <div class="info-card ic-cyan">
                <?= number_format($proveedores_inactivos) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Inactivos</span>
                <span class="icon ms-2"><i class="bi bi-x-circle"></i></span>
            </div>
            <div class="info-card ic-green">
                <?= number_format($compras_mes) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Compras este mes</span>
                <span class="icon ms-2"><i class="bi bi-cart-plus"></i></span>
            </div>
        </div>

        <!-- Buscador/Filtros -->
        <div class="search-section">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Buscar</label>
                    <input type="text" class="form-control" name="busqueda" placeholder="Código, razón social, email, teléfono..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Estado</label>
                    <select class="form-select" name="estado">
                        <option value="todos">Todos</option>
                        <option value="activo" <?= $filtro_estado == "activo" ? "selected" : "" ?>>Activo</option>
                        <option value="inactivo" <?= $filtro_estado == "inactivo" ? "selected" : "" ?>>Inactivo</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid gap-2">
                    <a href="new_prov_complete.php?origen=proveedores" class="btn btn-success">
                        <i class="bi bi-plus-circle me-1"></i>Nuevo Proveedor
                    </a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
                </div>
                <div class="col-md-2 d-grid gap-2">
                    <a href="compras.php" class="btn btn-info"><i class="bi bi-cart"></i> Compras</a>
                    <a href="papelera_proveedores.php" class="btn btn-secondary"><i class="bi bi-trash"></i> Papelera</a>
                </div>
            </form>
        </div>

        <!-- Tabla Listado de Proveedores -->
        <div class="table-container p-3">
            <!-- Mensajes de éxito/error -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($_GET['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'eliminado'): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-trash-fill"></i> Proveedor movido a papelera. <a href="papelera_proveedores.php" class="alert-link">Ver papelera</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lista de Proveedores</h4>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>
                                <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'codigo', 'dir' => $orden_campo === 'codigo' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                                    Código <i class="bi bi-arrow-down-up"></i>
                                </a>
                            </th>
                            <th>
                                <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'razon_social', 'dir' => $orden_campo === 'razon_social' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                                    <b>Razón Social</b> <i class="bi bi-arrow-down-up"></i>
                                </a>
                            </th>
                            <th>
                                <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'email', 'dir' => $orden_campo === 'email' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                                    Email <i class="bi bi-arrow-down-up"></i>
                                </a>
                            </th>
                            <th>
                                <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'telefono', 'dir' => $orden_campo === 'telefono' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                                    Teléfono <i class="bi bi-arrow-down-up"></i>
                                </a>
                            </th>
                            <th class="text-center">
                                <span title="WhatsApp"><i class="bi bi-whatsapp whatsapp-link"></i></span>
                            </th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($proveedores)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="bi bi-inbox display-4 text-muted"></i>
                                    <p class="text-muted mt-2">No se encontraron proveedores</p>
                                    <?php if (isset($error_message)): ?>
                                        <p class="text-danger"><?= htmlspecialchars($error_message) ?></p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($proveedores as $proveedor): ?>
                                <tr>
                                    <td><code class="text-primary"><?= htmlspecialchars($proveedor['codigo'] ?? 'SIN-CODIGO-' . $proveedor['id']) ?></code></td>
                                    <td><b><?= htmlspecialchars($proveedor['razon_social']) ?></b></td>
                                    <td><?= htmlspecialchars($proveedor['email'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($proveedor['telefono'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <a href="#" class="whatsapp-link" title="Enviar WhatsApp"
                                            onclick="return openWhatsApp('<?= htmlspecialchars($proveedor['telefono']) ?>', '<?= htmlspecialchars($proveedor['razon_social']) ?>');">
                                            <i class="bi bi-whatsapp"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <?= $proveedor['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>'; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-warning btn-action" title="Editar" onclick="editarProveedor(<?= $proveedor['id'] ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="proveedor_detalle.php?id=<?= $proveedor['id'] ?>" class="btn btn-info btn-action" title="Ver Detalle">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="gestionar_proveedor.php?accion=cambiar_estado&id=<?= $proveedor['id'] ?>" class="btn btn-secondary btn-action" title="<?= $proveedor['activo'] ? 'Desactivar' : 'Activar' ?>">
                                                <i class="bi bi-<?= $proveedor['activo'] ? 'pause' : 'play' ?>"></i>
                                            </a>
                                            <a href="gestionar_proveedor.php?accion=eliminar&id=<?= $proveedor['id'] ?>" class="btn btn-danger btn-action" title="Mover a Papelera" onclick="return confirm('¿Mover este proveedor a la papelera?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Navegación de proveedores">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">
                                        <i class="bi bi-chevron-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>">
                                        <i class="bi bi-chevron-double-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                        <div class="text-center mt-2">
                            <small class="text-muted">
                                Página <?= $page ?> de <?= $total_pages ?>
                                (<?= number_format($proveedores_filtrados) ?> proveedores encontrados)
                            </small>
                        </div>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarProveedor(id) {
            // Cargar datos del proveedor en el modal
            fetch(`gestionar_proveedor.php?action=obtener_proveedor&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const form = document.getElementById('form-nuevo-proveedor');
                        const proveedor = data.proveedor;
                        
                        // Llenar el formulario con los datos
                        form.querySelector('[name="codigo"]').value = proveedor.codigo || '';
                        form.querySelector('[name="cuit"]').value = proveedor.cuit || '';
                        form.querySelector('[name="razon_social"]').value = proveedor.razon_social || '';
                        form.querySelector('[name="nombre_comercial"]').value = proveedor.nombre_comercial || '';
                        form.querySelector('[name="direccion"]').value = proveedor.direccion || '';
                        form.querySelector('[name="telefono"]').value = proveedor.telefono || '';
                        form.querySelector('[name="whatsapp"]').value = proveedor.whatsapp || '';
                        form.querySelector('[name="email"]').value = proveedor.email || '';
                        form.querySelector('[name="sitio_web"]').value = proveedor.sitio_web || '';
                        
                        // Cambiar el título y función del modal
                        document.querySelector('#modalNuevoProveedor .modal-title').innerHTML = '<i class="fas fa-pencil me-2"></i>Editar Proveedor';
                        document.querySelector('#modalNuevoProveedor .btn-primary').setAttribute('onclick', `actualizarProveedor(${id})`);
                        document.querySelector('#modalNuevoProveedor .btn-primary').innerHTML = '<i class="fas fa-save"></i> Actualizar Proveedor';
                        
                        // Mostrar modal
                        new bootstrap.Modal(document.getElementById('modalNuevoProveedor')).show();
                    } else {
                        alert('Error al cargar datos del proveedor');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar proveedor');
                });
        }

        function actualizarProveedor(id) {
            const form = document.getElementById('form-nuevo-proveedor');
            const formData = new FormData(form);
            formData.append('action', 'actualizar_proveedor');
            formData.append('id', id);

            fetch('gestionar_proveedor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Cerrar modal y limpiar form
                        bootstrap.Modal.getInstance(document.getElementById('modalNuevoProveedor')).hide();
                        form.reset();

                        // Recargar página
                        window.location.href = window.location.href + (window.location.href.includes('?') ? '&' : '?') + 'success=' + encodeURIComponent('Proveedor actualizado exitosamente');
                    } else {
                        alert('Error al actualizar proveedor: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al actualizar proveedor');
                });
        }

        function guardarNuevoProveedor() {
            const form = document.getElementById('form-nuevo-proveedor');
            const formData = new FormData(form);
            
            // 📱 PROCESAR TELÉFONOS COMO CLIENTE_FORM.PHP
            const telefonoCodPais = document.getElementById('telefono_cod_pais').value;
            const telefonoNumero = document.getElementById('telefono_numero').value;
            const whatsappCodPais = document.getElementById('whatsapp_cod_pais').value;
            const whatsappNumero = document.getElementById('whatsapp_numero').value;
            
            // Combinar código + número para campo único
            const telefonoCompleto = (telefonoCodPais && telefonoNumero) ? telefonoCodPais + telefonoNumero : '';
            const whatsappCompleto = (whatsappCodPais && whatsappNumero) ? whatsappCodPais + whatsappNumero : '';
            
            // Reemplazar en FormData
            formData.set('telefono', telefonoCompleto);
            formData.set('whatsapp', whatsappCompleto);
            formData.append('action', 'crear_proveedor');

            fetch('gestionar_proveedor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Cerrar modal y limpiar form
                        bootstrap.Modal.getInstance(document.getElementById('modalNuevoProveedor')).hide();
                        form.reset();

                        // Recargar página para mostrar el nuevo proveedor
                        window.location.href = window.location.href + (window.location.href.includes('?') ? '&' : '?') + 'success=' + encodeURIComponent('Proveedor creado exitosamente');
                    } else {
                        alert('Error al crear proveedor: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al crear proveedor');
                });
        }

        function nuevoItem(tipo) {
            const nombre = prompt(`Ingrese el nombre del nuevo ${tipo}:`);
            if (nombre) {
                // Aquí puedes agregar lógica para crear nuevos países, provincias, ciudades
                alert(`Funcionalidad para crear nuevo ${tipo} será implementada`);
            }
        }

        // 📞 FUNCIÓN UNIFICADA - MANEJO DE CÓDIGOS DE PAÍS
        function cambiarCodigoPais(tipo) {
            const select = document.getElementById(`${tipo}-pais`);
            const input = document.getElementById(`${tipo}-input`);
            const codigo = select.value;
            
            // Placeholders específicos por país
            const placeholders = {
                '+54': '11 1234-5678',  // Argentina
                '+1': '(555) 123-4567', // USA
                '+55': '11 99999-9999', // Brasil
                '+56': '9 1234 5678',   // Chile
                '+51': '999 999 999',   // Perú
                '+52': '55 1234 5678',  // México
                '+34': '612 34 56 78',  // España
                '+33': '06 12 34 56 78', // Francia
                '+39': '338 123 4567',  // Italia
                '+49': '0151 23456789'  // Alemania
            };
            
            input.placeholder = placeholders[codigo] || 'Número de teléfono';
        }

        // 🌍 UNIFICACIÓN DE CRITERIOS - MANEJO DE PAÍSES
        document.addEventListener('DOMContentLoaded', function() {
            const paisSelect = document.getElementById('pais_id');
            const provinciaSelect = document.getElementById('provincia_id');
            const ciudadSelect = document.getElementById('ciudad_id');

            if (paisSelect) {
                paisSelect.addEventListener('change', function() {
                    const paisId = this.value;
                    const paisTexto = this.options[this.selectedIndex].text;
                    
                    // Limpiar provincias y ciudades
                    provinciaSelect.innerHTML = '<option value="">-- Seleccionar --</option>';
                    ciudadSelect.innerHTML = '<option value="">-- Seleccionar --</option>';
                    
                    // Solo cargar automáticamente si es Argentina
                    if (paisTexto.toLowerCase().includes('argentina')) {
                        // Cargar provincias argentinas
                        fetch(`../../config/get_provincias.php?pais_id=${paisId}`)
                            .then(response => response.json())
                            .then(provincias => {
                                provincias.forEach(provincia => {
                                    const option = new Option(provincia.nombre, provincia.id);
                                    provinciaSelect.add(option);
                                });
                                // Agregar opción para nueva provincia
                                provinciaSelect.add(new Option('+ Nueva Provincia', 'nuevo'));
                            })
                            .catch(error => console.log('No se pudieron cargar las provincias'));
                    } else {
                        // Para otros países, dejar campos manuales
                        provinciaSelect.innerHTML = `
                            <option value="">-- Ingrese manualmente --</option>
                            <option value="manual">Escribir provincia/estado</option>
                            <option value="nuevo">+ Nueva Provincia</option>
                        `;
                        ciudadSelect.innerHTML = `
                            <option value="">-- Ingrese manualmente --</option>
                            <option value="manual">Escribir ciudad</option>
                            <option value="nuevo">+ Nueva Ciudad</option>
                        `;
                    }
                });
            }

            if (provinciaSelect) {
                provinciaSelect.addEventListener('change', function() {
                    const provinciaId = this.value;
                    const paisTexto = paisSelect.options[paisSelect.selectedIndex].text;
                    
                    // Limpiar ciudades
                    ciudadSelect.innerHTML = '<option value="">-- Seleccionar --</option>';
                    
                    // Solo cargar automáticamente si es Argentina y no es opción manual
                    if (paisTexto.toLowerCase().includes('argentina') && provinciaId !== 'manual' && provinciaId !== 'nuevo' && provinciaId !== '') {
                        fetch(`../../config/get_ciudades.php?provincia_id=${provinciaId}`)
                            .then(response => response.json())
                            .then(ciudades => {
                                ciudades.forEach(ciudad => {
                                    const option = new Option(ciudad.nombre, ciudad.id);
                                    ciudadSelect.add(option);
                                });
                                // Agregar opción para nueva ciudad
                                ciudadSelect.add(new Option('+ Nueva Ciudad', 'nuevo'));
                            })
                            .catch(error => console.log('No se pudieron cargar las ciudades'));
                    } else if (provinciaId === 'manual') {
                        // Cambiar a input manual para provincia
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.className = 'form-control';
                        input.name = 'provincia_manual';
                        input.placeholder = 'Escriba la provincia/estado';
                        provinciaSelect.parentNode.replaceChild(input, provinciaSelect);
                    }
                });
            }
        });

        // Resetear modal al cerrarse
        document.getElementById('modalNuevoProveedor').addEventListener('hidden.bs.modal', function () {
            const form = document.getElementById('form-nuevo-proveedor');
            form.reset();
            
            // Restaurar título y botón original
            document.querySelector('#modalNuevoProveedor .modal-title').innerHTML = '<i class="fas fa-plus-circle me-2"></i>Nuevo Proveedor';
            document.querySelector('#modalNuevoProveedor .btn-primary').setAttribute('onclick', 'guardarNuevoProveedor()');
            document.querySelector('#modalNuevoProveedor .btn-primary').innerHTML = '<i class="fas fa-save"></i> Guardar Proveedor';
            
            // Restaurar valores por defecto
            document.getElementById('pais').value = '1'; // Argentina por defecto
            
            // Configurar selectores telefónicos por defecto
            const telefonoCodSelect = document.getElementById('telefono_cod_pais');
            const whatsappCodSelect = document.getElementById('whatsapp_cod_pais');
            const telefonoInput = document.getElementById('telefono_numero');
            const whatsappInput = document.getElementById('whatsapp_numero');
            
            if (telefonoCodSelect) {
                telefonoCodSelect.value = '+54'; // Argentina por defecto
                if (telefonoInput) telefonoInput.value = '+54';
            }
            
            if (whatsappCodSelect) {
                whatsappCodSelect.value = '+54'; // Argentina por defecto  
                if (whatsappInput) whatsappInput.value = '+54';
            }
            
            // Regenerar código automático para siguiente uso
            generarCodigoAutomatico();
        });

        // Generar código automático al abrir modal
        document.getElementById('modalNuevoProveedor').addEventListener('shown.bs.modal', function () {
            generarCodigoAutomatico();
            
            // Configurar valores por defecto al abrir
            const telefonoCodSelect = document.getElementById('telefono_cod_pais');
            const whatsappCodSelect = document.getElementById('whatsapp_cod_pais');
            const telefonoInput = document.getElementById('telefono_numero');
            const whatsappInput = document.getElementById('whatsapp_numero');
            
            if (telefonoCodSelect && telefonoCodSelect.value === '+54') {
                if (telefonoInput) telefonoInput.value = '+54';
            }
            
            if (whatsappCodSelect && whatsappCodSelect.value === '+54') {
                if (whatsappInput) whatsappInput.value = '+54';
            }
        });

                // 📱 MANEJO INTELIGENTE DE SELECTORES DE PAÍS
        document.addEventListener('DOMContentLoaded', function() {
            const telefonoCodSelect = document.getElementById('telefono_cod_pais');
            const whatsappCodSelect = document.getElementById('whatsapp_cod_pais');
            const telefonoInput = document.getElementById('telefono_numero');
            const whatsappInput = document.getElementById('whatsapp_numero');
            
            // 🔍 BASE DE DATOS DE CÓDIGOS TELEFÓNICOS
            const codigosPaises = {
                'luxemburgo': '+352', 'suiza': '+41', 'austria': '+43', 'bélgica': '+32',
                'holanda': '+31', 'dinamarca': '+45', 'suecia': '+46', 'noruega': '+47',
                'finlandia': '+358', 'islandia': '+354', 'irlanda': '+353', 'polonia': '+48',
                'república checa': '+420', 'hungría': '+36', 'rumania': '+40', 'bulgaria': '+359',
                'grecia': '+30', 'croacia': '+385', 'eslovenia': '+386', 'eslovaquia': '+421',
                'estonia': '+372', 'letonia': '+371', 'lituania': '+370', 'malta': '+356',
                'chipre': '+357', 'portugal': '+351', 'turquía': '+90', 'rusia': '+7',
                'ucrania': '+380', 'belarus': '+375', 'moldova': '+373', 'georgia': '+995',
                'armenia': '+374', 'azerbaiyán': '+994', 'kazajistán': '+7', 'uzbekistán': '+998',
                'turkmenistán': '+993', 'tayikistán': '+992', 'kirguistán': '+996',
                'india': '+91', 'pakistán': '+92', 'bangladesh': '+880', 'sri lanka': '+94',
                'nepal': '+977', 'bután': '+975', 'maldivas': '+960', 'afganistán': '+93',
                'irán': '+98', 'irak': '+964', 'kuwait': '+965', 'arabia saudí': '+966',
                'emiratos árabes unidos': '+971', 'qatar': '+974', 'bahréin': '+973',
                'omán': '+968', 'yemen': '+967', 'jordania': '+962', 'líbano': '+961',
                'siria': '+963', 'israel': '+972', 'palestina': '+970', 'egipto': '+20',
                'libia': '+218', 'túnez': '+216', 'argelia': '+213', 'marruecos': '+212',
                'sudán': '+249', 'etiopía': '+251', 'kenia': '+254', 'uganda': '+256',
                'tanzania': '+255', 'ruanda': '+250', 'burundi': '+257', 'madagascar': '+261',
                'mauricio': '+230', 'seychelles': '+248', 'comoras': '+269', 'mayotte': '+262',
                'sudáfrica': '+27', 'namibia': '+264', 'botswana': '+267', 'zimbabwe': '+263',
                'zambia': '+260', 'malawi': '+265', 'mozambique': '+258', 'suazilandia': '+268',
                'lesotho': '+266', 'australia': '+61', 'nueva zelanda': '+64', 'papúa nueva guinea': '+675',
                'fiyi': '+679', 'vanuatu': '+678', 'nueva caledonia': '+687', 'samoa': '+685',
                'tonga': '+676', 'kiribati': '+686', 'tuvalu': '+688', 'nauru': '+674',
                'palau': '+680', 'micronesia': '+691', 'islas marshall': '+692', 'corea del sur': '+82',
                'corea del norte': '+850', 'mongolia': '+976', 'vietnam': '+84', 'camboya': '+855',
                'laos': '+856', 'tailandia': '+66', 'myanmar': '+95', 'malasia': '+60',
                'singapur': '+65', 'brunéi': '+673', 'indonesia': '+62', 'filipinas': '+63',
                'timor oriental': '+670', 'taiwán': '+886', 'hong kong': '+852', 'macao': '+853'
            };
            
            // 📱 MANEJAR SELECCIÓN DE PAÍS
            function configurarSelector(selectElement, inputElement) {
                selectElement.addEventListener('change', function() {
                    if (this.value === 'nuevo') {
                        manejarNuevoPais(selectElement, inputElement);
                    } else if (this.value && this.value !== '') {
                        // ✅ MOSTRAR CÓDIGO EN INPUT AUTOMÁTICAMENTE
                        inputElement.value = this.value;
                        inputElement.focus();
                    }
                });
            }
            
            // 🆕 MANEJAR NUEVO PAÍS (SOLO PIDE NOMBRE)
            function manejarNuevoPais(selectElement, inputElement) {
                const nombrePais = prompt('🏳️ Ingrese el nombre del país:', '');
                
                if (nombrePais && nombrePais.trim() !== '') {
                    const nombreLimpio = nombrePais.trim().toLowerCase();
                    
                    // 🔍 VALIDAR SI YA EXISTE
                    const yaExiste = Array.from(selectElement.options).some(option => {
                        const textoOpcion = (option.textContent || option.innerText).toLowerCase();
                        return textoOpcion.includes(nombreLimpio) && option.value !== 'nuevo';
                    });
                    
                    if (yaExiste) {
                        alert(`❌ El país "" ya existe en la lista.`);
                        selectElement.selectedIndex = 0;
                        return;
                    }
                    
                    // 🔍 BUSCAR CÓDIGO AUTOMÁTICAMENTE
                    const codigoEncontrado = codigosPaises[nombreLimpio];
                    
                    if (codigoEncontrado) {
                        // Verificar que el código no esté duplicado
                        const codigoExiste = Array.from(selectElement.options).some(option => 
                            option.value === codigoEncontrado
                        );
                        
                        if (codigoExiste) {
                            alert(`❌ El código  ya está asignado a otro país.`);
                            selectElement.selectedIndex = 0;
                            return;
                        }
                        
                        // ✅ AGREGAR PAÍS CON ÉXITO
                        agregarPaisASelector(selectElement, nombrePais, codigoEncontrado, inputElement);
                        sincronizarOtroSelector(selectElement, nombrePais, codigoEncontrado);
                        
                        alert(`✅  agregado exitosamente ()`);
                    } else {
                        alert(`⚠️ No se encontró código para "".\n\nPaíses disponibles: Luxemburgo, Suiza, Austria, Bélgica, etc.`);
                        selectElement.selectedIndex = 0;
                    }
                } else {
                    selectElement.selectedIndex = 0;
                }
            }
            
            // ➕ AGREGAR PAÍS AL SELECTOR
            function agregarPaisASelector(selectElement, nombrePais, codigo, inputElement) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = codigo;
                nuevaOpcion.textContent = `🌍 `;
                
                const opcionNuevo = selectElement.querySelector('option[value="nuevo"]');
                selectElement.insertBefore(nuevaOpcion, opcionNuevo);
                
                selectElement.value = codigo;
                inputElement.value = codigo;
                inputElement.focus();
            }
            
            // 🔄 SINCRONIZAR CON EL OTRO SELECTOR
            function sincronizarOtroSelector(selectorActual, nombrePais, codigo) {
                const otroSelector = (selectorActual === telefonoCodSelect) ? whatsappCodSelect : telefonoCodSelect;
                
                if (otroSelector) {
                    const yaExiste = Array.from(otroSelector.options).some(option => option.value === codigo);
                    
                    if (!yaExiste) {
                        const nuevaOpcion = document.createElement('option');
                        nuevaOpcion.value = codigo;
                        nuevaOpcion.textContent = `🌍 `;
                        
                        const opcionNuevo = otroSelector.querySelector('option[value="nuevo"]');
                        otroSelector.insertBefore(nuevaOpcion, opcionNuevo);
                    }
                }
            }
            
            // 🚀 CONFIGURAR AMBOS SELECTORES
            if (telefonoCodSelect && telefonoInput) {
                configurarSelector(telefonoCodSelect, telefonoInput);
            }
            
            if (whatsappCodSelect && whatsappInput) {
                configurarSelector(whatsappCodSelect, whatsappInput);
            }
        });
    </script>
</body>

</html>
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
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $proveedores_filtrados = 0;
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
    body { background-color: #f8f9fa; }
    .main-container { margin: 0 auto; max-width: 1200px; }
    .table-container { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.08); margin-top: 30px; }
    .table th, .table td { vertical-align: middle; }
    .btn-action { padding: 4px 8px; margin: 0 1px; border-radius: 5px; font-size: 0.85rem; }
    .search-section { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.08); padding: 15px 20px; margin-top: 20px; }
    .info-cards { display: flex; gap: 20px; margin: 30px 0 10px 0; }
    .info-card { flex: 1; display: flex; align-items: center; padding: 20px; border-radius: 12px; color: #fff; font-weight: 600; font-size: 1.2rem; box-shadow: 0 4px 16px rgba(0,0,0,.05); min-width: 200px; }
    .ic-purple { background: linear-gradient(90deg, #6a11cb 0%, #2575fc 100%); }
    .ic-pink   { background: linear-gradient(90deg, #ff758c 0%, #ff7eb3 100%); }
    .ic-cyan   { background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%); }
    .ic-green  { background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%); }
    .info-card .icon { font-size: 2.3rem; margin-left: auto; opacity: 0.7; }
    .pagination-container { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.08); margin-top: 30px; padding: 15px 0; }
    .whatsapp-link { color: #25D366; font-size: 1.3em; margin-left: 3px; margin-right: 3px; vertical-align: middle; }
    .whatsapp-link:hover { color: #128C7E; }
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
                                    <!-- Editar en pantalla dedicada -->
                                    <a href="edi_prov.php?id=<?= (int)$proveedor['id'] ?>&origen=proveedores"
                                       class="btn btn-warning btn-action" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <a href="proveedor_detalle.php?id=<?= (int)$proveedor['id'] ?>" class="btn btn-info btn-action" title="Ver Detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="gestionar_proveedor.php?accion=cambiar_estado&id=<?= (int)$proveedor['id'] ?>" class="btn btn-secondary btn-action" title="<?= $proveedor['activo'] ? 'Desactivar' : 'Activar' ?>">
                                        <i class="bi bi-<?= $proveedor['activo'] ? 'pause' : 'play' ?>"></i>
                                    </a>
                                    <a href="gestionar_proveedor.php?accion=eliminar&id=<?= (int)$proveedor['id'] ?>" class="btn btn-danger btn-action" title="Mover a Papelera" onclick="return confirm('¿Mover este proveedor a la papelera?')">
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
</body>
</html>
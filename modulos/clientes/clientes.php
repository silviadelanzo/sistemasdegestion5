<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');
header('Content-Type: text/html; charset=UTF-8');

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$filtro_busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_tipo = isset($_GET['tipo_cliente']) ? trim($_GET['tipo_cliente']) : '';
$filtro_estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

$orden_campo = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_creacion';
$orden_direccion = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';

$campos_permitidos = ['codigo', 'nombre', 'apellido', 'razon_social', 'tipo_cliente', 'telefono', 'activo', 'fecha_creacion'];
if (!in_array($orden_campo, $campos_permitidos)) {
    $orden_campo = 'fecha_creacion';
}

$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
$usuario_rol = $_SESSION['rol_usuario'] ?? 'inventario';
$es_administrador = ($usuario_rol === 'admin' || $usuario_rol === 'administrador');

$compras_pendientes = 0;
$facturas_pendientes = 0;

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Badges nav
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

    // Cards resumen (con seguridad de cuenta)
    $cuenta_id_actual = $_SESSION['cuenta_id'];
    $stmt_total = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE eliminado=0 AND cuenta_id = ?");
    $stmt_total->execute([$cuenta_id_actual]);
    $total_clientes = $stmt_total->fetchColumn();

    $stmt_inactivos = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE activo=0 AND eliminado=0 AND cuenta_id = ?");
    $stmt_inactivos->execute([$cuenta_id_actual]);
    $clientes_inactivos = $stmt_inactivos->fetchColumn();

    $stmt_mayoristas = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE tipo_cliente='mayorista' AND eliminado=0 AND cuenta_id = ?");
    $stmt_mayoristas->execute([$cuenta_id_actual]);
    $mayoristas = $stmt_mayoristas->fetchColumn();

    $stmt_minoristas = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE tipo_cliente='minorista' AND eliminado=0 AND cuenta_id = ?");
    $stmt_minoristas->execute([$cuenta_id_actual]);
    $minoristas = $stmt_minoristas->fetchColumn();

    $stmt_ambos = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE tipo_cliente='may_min' AND eliminado=0 AND cuenta_id = ?");
    $stmt_ambos->execute([$cuenta_id_actual]);
    $ambos = $stmt_ambos->fetchColumn();

    // Filtros para listado
    $where_conditions = ["eliminado=0", "cuenta_id = ?"];
    $params = [$_SESSION['cuenta_id']];

    if ($filtro_busqueda !== '') {
        $where_conditions[] = "(codigo LIKE ? OR nombre LIKE ? OR apellido LIKE ? OR razon_social LIKE ? OR telefono LIKE ?)";
        $busqueda = "%{$filtro_busqueda}%";
        array_push($params, $busqueda, $busqueda, $busqueda, $busqueda, $busqueda);
    }
    if ($filtro_tipo !== '' && $filtro_tipo !== 'todos') {
        $where_conditions[] = "tipo_cliente = ?";
        $params[] = $filtro_tipo;
    }
    if ($filtro_estado !== '' && $filtro_estado !== 'todos') {
        $where_conditions[] = "activo = ?";
        $params[] = ($filtro_estado === 'activo') ? 1 : 0;
    }
    $where_clause = $where_conditions ? implode(' AND ', $where_conditions) : '1';

    $orden_sql = $orden_campo;

    // Listado de clientes
    $sql = "SELECT * FROM clientes WHERE $where_clause
            ORDER BY $orden_sql $orden_direccion
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clientes = $stmt->fetchAll();

    $count_sql = "SELECT COUNT(*) FROM clientes WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $clientes_filtrados = $count_stmt->fetchColumn();
    $total_pages = ceil($clientes_filtrados / $per_page);

} catch (Exception $e) {
    $error_message = "Error al cargar clientes: " . $e->getMessage();
    $clientes = [];
    $total_pages = 1;
    $total_clientes = 0; $clientes_inactivos = 0; $mayoristas = 0; $minoristas = 0; $ambos = 0;
}

$pageTitle = "Gestión de Clientes - " . SISTEMA_NOMBRE;
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
    <style>
        body { background-color: #f8f9fa; }
        .main-container { margin: 0 auto; max-width: 1200px; }
        .table-container {
            background: white; border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-top: 30px;
        }
        .table th, .table td { vertical-align: middle; }
        .btn-action { padding: 4px 8px; margin: 0 1px; border-radius: 5px; font-size: 0.85rem; }
        .search-section { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 15px 20px; margin-top: 20px; }
        .info-cards { display: flex; gap: 20px; margin: 30px 0 10px 0;}
        @media (max-width: 991px) { .info-cards { flex-direction: column; gap:12px; } }
        .info-card {
            flex: 1; display: flex; align-items: center; padding: 20px;
            border-radius: 12px; color: #fff; font-weight: 600; font-size: 1.2rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.05); min-width: 200px;
        }
        .ic-blue { background: linear-gradient(90deg, #396afc 0%, #2948ff 100%);}
        .ic-gray { background: linear-gradient(90deg, #a7a7a7 0%, #636363 100%);}
        .ic-orange { background: linear-gradient(90deg, #fc4a1a 0%, #f7b733 100%);}
        .ic-pink { background: linear-gradient(90deg, #ff758c 0%, #ff7eb3 100%);}
        .ic-green { background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);}
        .info-card .icon { font-size:2.3rem; margin-left: auto; opacity:0.7;}
        .pagination-container { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-top: 30px; padding: 15px 0;}
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
    </style>
    <script>
    function openWhatsApp(tel, nombre) {
        if (!tel || tel.trim() === "" || tel.trim() === "+") {
            var nuevoTel = prompt("Ingrese el número de WhatsApp para " + (nombre ? nombre : "el cliente") + " (con código de país, sin espacios ni guiones):");
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
        <div class="info-card ic-blue">
            <?= number_format($total_clientes) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Clientes</span>
            <span class="icon ms-2"><i class="bi bi-people"></i></span>
        </div>
        <div class="info-card ic-gray">
            <?= number_format($clientes_inactivos) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Inactivos</span>
            <span class="icon ms-2"><i class="bi bi-person-x"></i></span>
        </div>
        <div class="info-card ic-orange">
            <?= number_format($mayoristas) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Mayoristas</span>
            <span class="icon ms-2"><i class="bi bi-briefcase"></i></span>
        </div>
        <div class="info-card ic-pink">
            <?= number_format($minoristas) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Minoristas</span>
            <span class="icon ms-2"><i class="bi bi-person"></i></span>
        </div>
        <div class="info-card ic-green">
            <?= number_format($ambos) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Ambos</span>
            <span class="icon ms-2"><i class="bi bi-people-fill"></i></span>
        </div>
    </div>

    <!-- Buscador/Filtros -->
    <div class="search-section">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold">Buscar</label>
                <input type="text" class="form-control" name="busqueda" placeholder="Código, nombre, empresa, teléfono..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Tipo Cliente</label>
                <select class="form-select" name="tipo_cliente">
                    <option value="todos">Todos</option>
                    <option value="mayorista" <?= $filtro_tipo == "mayorista" ? "selected" : "" ?>>Mayorista</option>
                    <option value="minorista" <?= $filtro_tipo == "minorista" ? "selected" : "" ?>>Minorista</option>
                    <option value="may_min" <?= $filtro_tipo == "may_min" ? "selected" : "" ?>>Ambos</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Estado</label>
                <select class="form-select" name="estado">
                    <option value="todos">Todos</option>
                    <option value="activo" <?= $filtro_estado == "activo" ? "selected" : "" ?>>Activo</option>
                    <option value="inactivo" <?= $filtro_estado == "inactivo" ? "selected" : "" ?>>Inactivo</option>
                </select>
            </div>
            <!-- Nuevo Cliente primero, Filtrar abajo -->
            <div class="col-md-2 d-grid gap-2">
                <a href="cliente_form.php" class="btn btn-success"><i class="bi bi-person-plus me-1"></i>Nuevo Cliente</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
            </div>
            <div class="col-md-2 d-grid gap-2">
                <a href="clientes_inactivos.php" class="btn btn-danger"><i class="bi bi-person-x"></i> Inactivos</a>
                <a href="papelera_clientes.php" class="btn btn-secondary"><i class="bi bi-trash"></i> Papelera</a>
            </div>
        </form>
    </div>

    <div class="table-container p-3">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h4 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lista de Clientes</h4>
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
                        <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'apellido', 'dir' => $orden_campo === 'apellido' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                            <b>Apellido</b> <i class="bi bi-arrow-down-up"></i>
                        </a>
                    </th>
                    <th>
                        <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'nombre', 'dir' => $orden_campo === 'nombre' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                            Nombre <i class="bi bi-arrow-down-up"></i>
                        </a>
                    </th>
                    <th>
                        <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'razon_social', 'dir' => $orden_campo === 'razon_social' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                            Razón Social <i class="bi bi-arrow-down-up"></i>
                        </a>
                    </th>
                    <th>
                        <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'telefono', 'dir' => $orden_campo === 'telefono' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                            Teléfono <i class="bi bi-arrow-down-up"></i>
                        </a>
                    </th>
                    <th class="text-center" style="width:45px">
                        <span title="WhatsApp"><i class="bi bi-whatsapp whatsapp-link"></i></span>
                    </th>
                    <th>
                        <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'tipo_cliente', 'dir' => $orden_campo === 'tipo_cliente' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                            Tipo <i class="bi bi-arrow-down-up"></i>
                        </a>
                    </th>
                    <th>
                        <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'activo', 'dir' => $orden_campo === 'activo' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                            Estado <i class="bi bi-arrow-down-up"></i>
                        </a>
                    </th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clientes)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="bi bi-inbox display-4 text-muted"></i>
                            <p class="text-muted mt-2">No se encontraron clientes</p>
                            <?php if (isset($error_message)): ?>
                                <p class="text-danger"><?= htmlspecialchars($error_message) ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clientes as $cli): ?>
                        <tr>
                            <td><code class="text-primary"><?= htmlspecialchars($cli['codigo']) ?></code></td>
                            <td><b><?= htmlspecialchars($cli['apellido']) ?></b></td>
                            <td><?= htmlspecialchars($cli['nombre']) ?></td>
                            <td><?= htmlspecialchars($cli['razon_social'] ?? '') ?></td>
                            <td><?= htmlspecialchars($cli['telefono']) ?></td>
                            <td class="text-center">
                                <a href="#" class="whatsapp-link" title="Enviar WhatsApp"
                                    onclick="return openWhatsApp('<?= htmlspecialchars($cli['telefono']) ?>', '<?= htmlspecialchars($cli['nombre'] . ' ' . $cli['apellido']) ?>');">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                            </td>
                            <td>
                                <?php
                                    $tipo = $cli['tipo_cliente'];
                                    echo $tipo == "mayorista" ? '<span class="badge bg-primary">Mayorista</span>' :
                                         ($tipo == "minorista" ? '<span class="badge bg-success">Minorista</span>' :
                                         '<span class="badge bg-warning text-dark">Ambos</span>');
                                ?>
                            </td>
                            <td>
                                <?= $cli['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>'; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="cliente_form.php?id=<?= $cli['id'] ?>" class="btn btn-warning btn-action" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <a href="cliente_detalle.php?id=<?= $cli['id'] ?>" class="btn btn-info btn-action" title="Ver Detalle"><i class="bi bi-eye"></i></a>
                                    <a href="cliente_inactivar.php?id=<?= $cli['id'] ?>" class="btn btn-secondary btn-action" title="Inactivar"><i class="bi bi-person-x"></i></a>
                                    <a href="gestionar_cliente_papelera.php?id=<?= $cli['id'] ?>" class="btn btn-danger btn-action" title="Mover a Papelera"><i class="bi bi-trash"></i></a>
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
                <nav aria-label="Navegación de clientes">
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
                            (<?= number_format($clientes_filtrados) ?> clientes encontrados)
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
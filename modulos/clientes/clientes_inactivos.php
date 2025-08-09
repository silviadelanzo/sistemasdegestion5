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

$orden_campo = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_creacion';
$orden_direccion = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';

$campos_permitidos = ['codigo', 'nombre', 'apellido', 'empresa', 'tipo_cliente', 'telefono', 'activo', 'fecha_creacion'];
if (!in_array($orden_campo, $campos_permitidos)) {
    $orden_campo = 'fecha_creacion';
}

$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
$usuario_rol = $_SESSION['rol_usuario'] ?? 'inventario';
$es_administrador = ($usuario_rol === 'admin' || $usuario_rol === 'administrador');

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Cards resumen
    $total_inactivos = $pdo->query("SELECT COUNT(*) FROM clientes WHERE activo=0 AND eliminado=0")->fetchColumn();

    // Filtros para listado
    $where_conditions = ["activo=0", "eliminado=0"];
    $params = [];

    if ($filtro_busqueda !== '') {
        $where_conditions[] = "(codigo LIKE ? OR nombre LIKE ? OR apellido LIKE ? OR empresa LIKE ? OR telefono LIKE ?)";
        $busqueda = "%{$filtro_busqueda}%";
        array_push($params, $busqueda, $busqueda, $busqueda, $busqueda, $busqueda);
    }
    if ($filtro_tipo !== '' && $filtro_tipo !== 'todos') {
        $where_conditions[] = "tipo_cliente = ?";
        $params[] = $filtro_tipo;
    }
    $where_clause = $where_conditions ? implode(' AND ', $where_conditions) : '1';

    // Listado de clientes
    $sql = "SELECT * FROM clientes WHERE $where_clause
            ORDER BY $orden_campo $orden_direccion
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
    $total_inactivos = 0;
}

$pageTitle = "Clientes Inactivos - " . SISTEMA_NOMBRE;
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
        @media (max-width: 991px) { 
            .info-cards { flex-direction: column; gap:12px;}
            .search-section, .table-container { padding: 10px 5px !important;}
        }
        .info-card {
            flex: 1; display: flex; align-items: center; padding: 20px;
            border-radius: 12px; color: #fff; font-weight: 600; font-size: 1.2rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.05); min-width: 200px;
        }
        .ic-gray { background: linear-gradient(90deg, #a7a7a7 0%, #636363 100%);}
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
        @media (max-width: 767px) {
            .table-responsive { font-size: 0.91rem; }
            .table th, .table td { padding: 0.32rem 0.2rem !important;}
            .btn-action { font-size: 0.8rem; }
            h4 { font-size: 1.15rem;}
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

    <!-- Tarjeta resumen -->
    <div class="info-cards">
        <div class="info-card ic-gray">
            <?= number_format($total_inactivos) ?> <span style="font-size:0.9rem;font-weight:400;margin-left:8px;">Clientes Inactivos</span>
            <span class="icon ms-2"><i class="bi bi-person-x"></i></span>
        </div>
    </div>

    <!-- Buscador/Filtros -->
    <div class="search-section">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-6 col-lg-4">
                <label class="form-label fw-bold">Buscar</label>
                <input type="text" class="form-control" name="busqueda" placeholder="Código, nombre, empresa, teléfono..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
            </div>
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label fw-bold">Tipo Cliente</label>
                <select class="form-select" name="tipo_cliente">
                    <option value="todos">Todos</option>
                    <option value="mayorista" <?= $filtro_tipo == "mayorista" ? "selected" : "" ?>>Mayorista</option>
                    <option value="minorista" <?= $filtro_tipo == "minorista" ? "selected" : "" ?>>Minorista</option>
                    <option value="may_min" <?= $filtro_tipo == "may_min" ? "selected" : "" ?>>Ambos</option>
                </select>
            </div>
            <div class="col-12 col-lg-3 d-grid gap-2 mt-2 mt-lg-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
                <a href="clientes.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i> Volver</a>
            </div>
        </form>
    </div>

    <!-- Tabla Listado de Clientes Inactivos -->
    <div class="table-container p-3">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h4 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lista de Clientes Inactivos</h4>
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
                        <a href="?<?= http_build_query(array_merge($_GET, ['orden' => 'empresa', 'dir' => $orden_campo === 'empresa' && $orden_direccion === 'ASC' ? 'desc' : 'asc'])) ?>">
                            Empresa <i class="bi bi-arrow-down-up"></i>
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
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clientes)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="bi bi-inbox display-4 text-muted"></i>
                            <p class="text-muted mt-2">No se encontraron clientes inactivos</p>
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
                            <td><?= htmlspecialchars($cli['empresa']) ?></td>
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
                                <div class="btn-group" role="group">
                                    <a href="cliente_form.php?id=<?= $cli['id'] ?>" class="btn btn-warning btn-action" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <a href="cliente_detalle.php?id=<?= $cli['id'] ?>" class="btn btn-info btn-action" title="Ver Detalle"><i class="bi bi-eye"></i></a>
                                    <a href="cliente_activar.php?id=<?= $cli['id'] ?>" class="btn btn-success btn-action" title="Activar"><i class="bi bi-person-check"></i></a>
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
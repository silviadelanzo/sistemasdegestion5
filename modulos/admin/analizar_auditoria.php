<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filtros
$filtro_usuario = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';
$filtro_accion = isset($_GET['accion']) ? trim($_GET['accion']) : '';
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';

$where_conditions = [];
$params = [];

if ($filtro_usuario !== '') {
    $where_conditions[] = "u.username LIKE ?";
    $params[] = "%{$filtro_usuario}%";
}
if ($filtro_accion !== '') {
    $where_conditions[] = "a.accion = ?";
    $params[] = $filtro_accion;
}
if ($filtro_fecha_desde !== '') {
    $where_conditions[] = "a.fecha >= ?";
    $params[] = $filtro_fecha_desde;
}
if ($filtro_fecha_hasta !== '') {
    $where_conditions[] = "a.fecha <= ?";
    $params[] = $filtro_fecha_hasta . ' 23:59:59';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT a.*, u.username FROM auditoria a JOIN usuarios u ON a.usuario_id = u.id $where_clause ORDER BY a.fecha DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll();

$count_sql = "SELECT COUNT(*) FROM auditoria a JOIN usuarios u ON a.usuario_id = u.id $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_registros = $count_stmt->fetchColumn();
$total_pages = ceil($total_registros / $per_page);

$acciones_sql = "SELECT DISTINCT accion FROM auditoria ORDER BY accion";
$acciones_stmt = $pdo->query($acciones_sql);
$acciones = $acciones_stmt->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Análisis de Auditoría</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include "../../config/navbar_code.php"; ?>
<div class="container mt-4">
    <h2>Análisis de Auditoría</h2>

    <div class="card mb-4">
        <div class="card-header">Filtros</div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="usuario" name="usuario" value="<?= htmlspecialchars($filtro_usuario) ?>">
                </div>
                <div class="col-md-3">
                    <label for="accion" class="form-label">Acción</label>
                    <select class="form-select" id="accion" name="accion">
                        <option value="">Todas</option>
                        <?php foreach ($acciones as $accion): ?>
                            <option value="<?= htmlspecialchars($accion) ?>" <?= ($filtro_accion == $accion) ? 'selected' : '' ?>><?= htmlspecialchars($accion) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fecha_desde" class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?= htmlspecialchars($filtro_fecha_desde) ?>">
                </div>
                <div class="col-md-3">
                    <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?= htmlspecialchars($filtro_fecha_hasta) ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="analizar_auditoria.php" class="btn btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Tabla Afectada</th>
                <th>Registro ID</th>
                <th>Detalle</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="7" class="text-center">No hay registros de auditoría.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($registros as $registro): ?>
                    <tr>
                        <td><?= $registro['id'] ?></td>
                        <td><?= htmlspecialchars($registro['username']) ?></td>
                        <td><?= htmlspecialchars($registro['accion']) ?></td>
                        <td><?= htmlspecialchars($registro['tabla_afectada'] ?? '') ?></td>
                        <td><?= htmlspecialchars($registro['registro_id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($registro['detalle'] ?? '') ?></td>
                        <td><?= htmlspecialchars($registro['fecha']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?= $i ?>&usuario=<?= urlencode($filtro_usuario) ?>&accion=<?= urlencode($filtro_accion) ?>&fecha_desde=<?= urlencode($filtro_fecha_desde) ?>&fecha_hasta=<?= urlencode($filtro_fecha_hasta) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Volver al Panel</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

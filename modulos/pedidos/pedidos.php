<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');
header('Content-Type: text/html; charset=UTF-8');

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$filtro_busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_estado   = isset($_GET['estado'])   ? trim($_GET['estado'])   : '';
$filtro_fecha    = isset($_GET['fecha'])    ? trim($_GET['fecha'])    : '';

$orden_campo     = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_pedido';
$orden_direccion = (isset($_GET['dir']) && $_GET['dir'] === 'asc') ? 'ASC' : 'DESC';

/* Campos permitidos para ordenar.
   Nota: “total_con_impuesto” es el alias calculado al vuelo. */
$campos_permitidos = ['codigo', 'cliente_nombre', 'fecha_pedido', 'estado', 'total_con_impuesto'];
if (!in_array($orden_campo, $campos_permitidos)) {
    $orden_campo = 'fecha_pedido';
}

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Tarjetas (valor total SIEMPRE con impuestos por producto)
    $valor_total_sql = "
        SELECT COALESCE(SUM(
            (SELECT COALESCE(SUM(pd.cantidad * pd.precio_unitario * (1 + COALESCE(i.porcentaje,0)/100.0)),0)
             FROM pedido_detalles pd
             LEFT JOIN productos pr ON pr.id = pd.producto_id
             LEFT JOIN impuestos i  ON i.id = pr.impuesto_id
             WHERE pd.pedido_id = p.id)
        ),0)
        FROM pedidos p
    ";
    $valor_total     = (float)$pdo->query($valor_total_sql)->fetchColumn();
    $total_pedidos   = (int)$pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
    $total_clientes  = (int)$pdo->query("SELECT COUNT(DISTINCT cliente_id) FROM pedidos")->fetchColumn();
    $total_articulos = (int)$pdo->query("SELECT COALESCE(SUM(cantidad),0) FROM pedido_detalles")->fetchColumn();

    // Filtros
    $where = [];
    $params = [];
    if ($filtro_busqueda !== '') {
        $where[] = "(p.codigo LIKE ? OR c.nombre LIKE ? OR c.apellido LIKE ? OR CONCAT(c.nombre,' ',c.apellido) LIKE ?)";
        $b = "%{$filtro_busqueda}%";
        array_push($params, $b,$b,$b,$b);
    }
    if ($filtro_estado !== '') {
        $where[] = "p.estado = ?";
        $params[] = $filtro_estado;
    }
    if ($filtro_fecha !== '') {
        $where[] = "DATE(p.fecha_pedido) = ?";
        $params[] = $filtro_fecha;
    }
    $where_clause = $where ? implode(' AND ', $where) : '1';

    // Campo de orden
    // cliente_nombre y total_con_impuesto son alias del SELECT
    $orden_sql = ($orden_campo === 'cliente_nombre' || $orden_campo === 'total_con_impuesto')
        ? $orden_campo
        : ('p.' . $orden_campo);

    // Listado: calcular total_con_impuesto por pedido
    $sql = "
        SELECT
            p.*,
            CONCAT(c.nombre,' ',c.apellido) AS cliente_nombre,
            (
                SELECT COALESCE(SUM(pd.cantidad * pd.precio_unitario * (1 + COALESCE(i.porcentaje,0)/100.0)),0)
                FROM pedido_detalles pd
                LEFT JOIN productos pr ON pr.id = pd.producto_id
                LEFT JOIN impuestos i  ON i.id = pr.impuesto_id
                WHERE pd.pedido_id = p.id
            ) AS total_con_impuesto
        FROM pedidos p
        LEFT JOIN clientes c ON c.id = p.cliente_id
        WHERE $where_clause
        ORDER BY $orden_sql $orden_direccion
        LIMIT $per_page OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count_sql = "
        SELECT COUNT(*)
        FROM pedidos p
        LEFT JOIN clientes c ON c.id = p.cliente_id
        WHERE $where_clause
    ";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $pedidos_filtrados = (int)$count_stmt->fetchColumn();
    $total_pages = max(1, (int)ceil($pedidos_filtrados / $per_page));

    $estados = ['pendiente','procesando','enviado','entregado','cancelado'];
} catch (Throwable $e) {
    $error_message = "Error al cargar pedidos: ".$e->getMessage();
    $pedidos = [];
    $estados = ['pendiente','procesando','enviado','entregado','cancelado'];
    $total_pages = 1;
    $pedidos_filtrados = 0;
    $total_pedidos = $total_clientes = $total_articulos = 0;
    $valor_total = 0;
}

$pageTitle = "Gestión de Pedidos - ".SISTEMA_NOMBRE;
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
  body{background:#f8f9fa}
  .main-container{max-width:1200px;margin:0 auto}
  .table-container{background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.08);margin-top:30px}
  .table th,.table td{vertical-align:middle}
  .btn-action{padding:4px 8px;margin:0 1px;border-radius:5px;font-size:.85rem}
  .search-section{background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.08);padding:15px 20px;margin-top:20px}
  .info-cards{display:flex;gap:20px;margin:30px 0 10px}
  .info-card{flex:1;display:flex;align-items:center;padding:20px;border-radius:12px;color:#fff;font-weight:600;font-size:1.2rem;box-shadow:0 4px 16px rgba(0,0,0,.05);min-width:200px}
  .ic-purple{background:linear-gradient(90deg,#6a11cb 0%,#2575fc 100%)}
  .ic-pink{background:linear-gradient(90deg,#ff758c 0%,#ff7eb3 100%)}
  .ic-cyan{background:linear-gradient(90deg,#43e97b 0%,#38f9d7 100%)}
  .ic-green{background:linear-gradient(90deg,#11998e 0%,#38ef7d 100%)}
  .info-card .icon{font-size:2.3rem;margin-left:auto;opacity:.7}
  .pagination-container{background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.08);margin-top:30px;padding:15px 0}
  /* Números alineados: */
  .num{text-align:right;font-variant-numeric:tabular-nums}
</style>
</head>
<body>
<?php include "../../config/navbar_code.php"; ?>

<div class="main-container">

  <div class="info-cards">
    <div class="info-card ic-purple">
      <?= number_format($total_pedidos) ?> <span style="font-size:.9rem;font-weight:400;margin-left:8px;">Total Pedidos</span>
      <span class="icon ms-2"><i class="bi bi-list-ul"></i></span>
    </div>
    <div class="info-card ic-pink">
      <?= number_format($total_clientes) ?> <span style="font-size:.9rem;font-weight:400;margin-left:8px;">Clientes c/ pedido</span>
      <span class="icon ms-2"><i class="bi bi-person-lines-fill"></i></span>
    </div>
    <div class="info-card ic-cyan">
      <?= number_format($total_articulos) ?> <span style="font-size:.9rem;font-weight:400;margin-left:8px;">Artículos pedidos</span>
      <span class="icon ms-2"><i class="bi bi-archive"></i></span>
    </div>
    <div class="info-card ic-green">
      $<?= number_format($valor_total, 2, ',', '.') ?> <span style="font-size:.9rem;font-weight:400;margin-left:8px;">Valor total</span>
      <span class="icon ms-2"><i class="bi bi-cash-stack"></i></span>
    </div>
  </div>

  <div class="search-section">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label fw-bold">Buscar</label>
        <input type="text" class="form-control" name="busqueda" placeholder="Código, cliente..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label fw-bold">Estado</label>
        <select class="form-select" name="estado">
          <option value="">Todos</option>
          <?php foreach ($estados as $estado): ?>
            <option value="<?= htmlspecialchars($estado) ?>" <?= $filtro_estado===$estado?'selected':'' ?>><?= ucfirst($estado) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-bold">Fecha Pedido</label>
        <input type="date" class="form-control" name="fecha" value="<?= htmlspecialchars($filtro_fecha) ?>">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filtrar</button>
      </div>
      <div class="col-md-2 text-end">
        <a href="pedido_form.php" class="btn btn-success w-100"><i class="bi bi-plus-circle me-1"></i>Nuevo Pedido</a>
      </div>
    </form>
  </div>

  <div class="table-container p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lista de Pedidos</h4>
      <a href="../../menu_principal.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>
            <a href="?<?= http_build_query(array_merge($_GET, ['orden'=>'codigo','dir'=>$orden_campo==='codigo' && $orden_direccion==='ASC'?'desc':'asc'])) ?>">
              Código <i class="bi bi-arrow-down-up"></i>
            </a>
          </th>
          <th>
            <a href="?<?= http_build_query(array_merge($_GET, ['orden'=>'cliente_nombre','dir'=>$orden_campo==='cliente_nombre' && $orden_direccion==='ASC'?'desc':'asc'])) ?>">
              Cliente <i class="bi bi-arrow-down-up"></i>
            </a>
          </th>
          <th>
            <a href="?<?= http_build_query(array_merge($_GET, ['orden'=>'fecha_pedido','dir'=>$orden_campo==='fecha_pedido' && $orden_direccion==='ASC'?'desc':'asc'])) ?>">
              Fecha <i class="bi bi-arrow-down-up"></i>
            </a>
          </th>
          <th>
            <a href="?<?= http_build_query(array_merge($_GET, ['orden'=>'estado','dir'=>$orden_campo==='estado' && $orden_direccion==='ASC'?'desc':'asc'])) ?>">
              Estado <i class="bi bi-arrow-down-up"></i>
            </a>
          </th>
          <th class="text-end">
            <a class="d-block text-end" href="?<?= http_build_query(array_merge($_GET, ['orden'=>'total_con_impuesto','dir'=>$orden_campo==='total_con_impuesto' && $orden_direccion==='ASC'?'desc':'asc'])) ?>">
              Total <i class="bi bi-arrow-down-up"></i>
            </a>
          </th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($pedidos)): ?>
        <tr>
          <td colspan="6" class="text-center py-4">
            <i class="bi bi-inbox display-4 text-muted"></i>
            <p class="text-muted mt-2">No se encontraron pedidos</p>
            <?php if (!empty($error_message)): ?>
              <p class="text-danger"><?= htmlspecialchars($error_message) ?></p>
            <?php endif; ?>
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($pedidos as $pedido): ?>
          <?php
            $estado = htmlspecialchars($pedido['estado']);
            $badge = "secondary";
            if ($estado === "pendiente") $badge = "warning";
            elseif ($estado === "procesando") $badge = "info";
            elseif ($estado === "enviado") $badge = "primary";
            elseif ($estado === "entregado") $badge = "success";
            elseif ($estado === "cancelado") $badge = "danger";
            $total_con_impuesto = (float)$pedido['total_con_impuesto'];
          ?>
          <tr>
            <td><code class="text-primary"><?= htmlspecialchars($pedido['codigo']) ?></code></td>
            <td><strong><?= htmlspecialchars($pedido['cliente_nombre']) ?></strong></td>
            <td><small class="text-muted"><?= date('d/m/Y', strtotime($pedido['fecha_pedido'])) ?></small></td>
            <td><span class="badge bg-<?= $badge ?>"><?= ucfirst($estado) ?></span></td>
            <td class="num"><strong>$<?= number_format($total_con_impuesto, 2, ',', '.') ?></strong></td>
            <td>
              <div class="btn-group" role="group">
                <a href="pedido_detalle.php?id=<?= $pedido['id'] ?>" class="btn btn-info btn-action" title="Ver detalles"><i class="bi bi-eye"></i></a>
                <a href="pedido_editar.php?id=<?= $pedido['id'] ?>" class="btn btn-warning btn-action" title="Editar"><i class="bi bi-pencil"></i></a>
                <a href="pedido_estado.php?id=<?= $pedido['id'] ?>" class="btn btn-secondary btn-action" title="Cambiar Estado"><i class="bi bi-arrow-repeat"></i></a>
                <a href="pedido_imprimir.php?id=<?= $pedido['id'] ?>" class="btn btn-success btn-action" title="Imprimir"><i class="bi bi-printer"></i></a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
      <div class="pagination-container">
        <nav aria-label="Navegación de pedidos">
          <ul class="pagination justify-content-center mb-0">
            <?php if ($page > 1): ?>
              <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>1])) ?>"><i class="bi bi-chevron-double-left"></i></a></li>
              <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$page-1])) ?>"><i class="bi bi-chevron-left"></i></a></li>
            <?php endif; ?>
            <?php
              $start = max(1, $page-2);
              $end   = min($total_pages, $page+2);
              for ($i=$start; $i<=$end; $i++):
            ?>
              <li class="page-item <?= $i===$page?'active':'' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
              <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$page+1])) ?>"><i class="bi bi-chevron-right"></i></a></li>
              <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$total_pages])) ?>"><i class="bi bi-chevron-double-right"></i></a></li>
            <?php endif; ?>
          </ul>
          <div class="text-center mt-2">
            <small class="text-muted">Página <?= $page ?> de <?= $total_pages ?> (<?= number_format($pedidos_filtrados) ?> pedidos encontrados)</small>
          </div>
        </nav>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
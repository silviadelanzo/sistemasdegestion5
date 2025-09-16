<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

$pdo = conectarDB();

// --- Lógica de obtención de datos ---
$proveedores = $pdo->query("SELECT id, razon_social FROM proveedores WHERE activo = 1 ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC);

$reporte_tipo = $_GET['action'] ?? '';
$reporte_data = null;
$reporte_titulo = '';
$total_general = 0;

// --- Parámetros comunes ---
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

// --- Procesamiento de Reportes ---
if ($reporte_tipo === 'proveedor') {
    $proveedor_id = $_GET['proveedor_id'] ?? null;
    if ($proveedor_id) {
        $stmt_prov = $pdo->prepare("SELECT razon_social FROM proveedores WHERE id = ?");
        $stmt_prov->execute([$proveedor_id]);
        $proveedor_seleccionado = $stmt_prov->fetch(PDO::FETCH_ASSOC);
        $reporte_titulo = 'Compras por Proveedor: ' . htmlspecialchars($proveedor_seleccionado['razon_social']);

        $sql = "SELECT c.id, c.fecha_compra, c.total, c.estado, p.razon_social as proveedor_nombre
                FROM compras c
                JOIN proveedores p ON c.proveedor_id = p.id
                WHERE c.proveedor_id = ? AND c.fecha_compra BETWEEN ? AND ?
                ORDER BY c.fecha_compra DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$proveedor_id, $fecha_desde, $fecha_hasta]);
        $reporte_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} elseif ($reporte_tipo === 'periodo') {
    $reporte_titulo = 'Compras por Período';
    $sql = "SELECT c.id, c.fecha_compra, c.total, c.estado, p.razon_social as proveedor_nombre
            FROM compras c
            JOIN proveedores p ON c.proveedor_id = p.id
            WHERE c.fecha_compra BETWEEN ? AND ?
            ORDER BY c.fecha_compra DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fecha_desde, $fecha_hasta]);
    $reporte_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($reporte_tipo === 'productos') {
    $periodo_dias = $_GET['periodo_dias'] ?? 30;
    $limite = $_GET['limite'] ?? 10;
    $fecha_inicio_productos = date('Y-m-d', strtotime("-$periodo_dias days"));
    $reporte_titulo = "Top $limite Productos Más Comprados (Últimos $periodo_dias días)";

    $sql = "SELECT p.nombre, p.codigo, SUM(cd.cantidad_pedida) as total_cantidad
            FROM compra_detalles cd
            JOIN productos p ON cd.producto_id = p.id
            JOIN compras c ON cd.compra_id = c.id
            WHERE c.fecha_compra >= ?
            GROUP BY p.id, p.nombre, p.codigo
            ORDER BY total_cantidad DESC
            LIMIT ?";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $fecha_inicio_productos);
    $stmt->bindValue(2, (int)$limite, PDO::PARAM_INT);
    $stmt->execute();
    $reporte_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes de Compras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .hero-blue {
            background: linear-gradient(90deg, #0d6efd, #0a58ca);
            color: #fff;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 18px 0 10px;
        }
        .hero-blue h3 { margin: 0; font-weight: 600; }
        .nav-tabs { border-bottom: 0; }
        .nav-tabs .nav-link {
            border: 0;
            color: #0d6efd;
            background-color: #e9f2ff;
            margin-right: 6px;
            border-radius: 8px 8px 0 0;
            padding: .5rem .9rem;
            font-weight: 600;
        }
        .nav-tabs .nav-link.active {
            background-color: #0d6efd;
            color: #fff;
        }
        .form-control, .form-select {
            background-color: #e7f5fe !important;
        }
        .select2-container--default .select2-selection--single { 
            background-color: #e7f5fe !important; 
            height: 38px; border: 1px solid #ced4da;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; }
        .select2-selection__arrow { height: 36px !important; }
    </style>
</head>
<body class="bg-light">
<?php include "../../config/navbar_code.php"; ?>
<div class="container">

    <div class="hero-blue">
        <h3><i class="bi bi-bar-chart-line-fill me-2"></i>Reportes de Compras</h3>
        <a href="compras.php" class="btn btn-light btn-sm">Volver a Compras <i class="bi bi-arrow-right-circle ms-1"></i></a>
    </div>

    <ul class="nav nav-tabs mb-3" id="reporte-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="proveedor-tab" data-bs-toggle="tab" data-bs-target="#proveedor-pane" type="button" role="tab">Por Proveedor</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="periodo-tab" data-bs-toggle="tab" data-bs-target="#periodo-pane" type="button" role="tab">Por Período</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="productos-tab" data-bs-toggle="tab" data-bs-target="#productos-pane" type="button" role="tab">Ranking Productos</button>
        </li>
    </ul>

    <div class="card p-4 bg-white">
        <div class="tab-content" id="reporte-tabs-content">
            <!-- Tab Proveedor -->
            <div class="tab-pane fade show active" id="proveedor-pane" role="tabpanel">
                <form method="GET" action="reportes_compras.php">
                    <input type="hidden" name="action" value="proveedor">
                    <div class="row align-items-end">
                        <div class="col-md-5"><label class="form-label">Proveedor</label><select class="form-select" name="proveedor_id" id="proveedor_id" required><option value="">Seleccionar...</option><?php foreach ($proveedores as $p) { $selected = (($_GET['proveedor_id'] ?? '') == $p['id']) ? 'selected' : ''; echo "<option value='" . htmlspecialchars($p['id']) . "' $selected>" . htmlspecialchars($p['razon_social']) . "</option>"; } ?></select></div>
                        <div class="col-md-3"><label class="form-label">Fecha Desde</label><input type="date" class="form-control" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Fecha Hasta</label><input type="date" class="form-control" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta) ?>"></div>
                        <div class="col-md-1"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button></div>
                    </div>
                </form>
            </div>
            <!-- Tab Período -->
            <div class="tab-pane fade" id="periodo-pane" role="tabpanel">
                <form method="GET" action="reportes_compras.php">
                    <input type="hidden" name="action" value="periodo">
                    <div class="row align-items-end">
                        <div class="col-md-5"><label class="form-label">Fecha Desde</label><input type="date" class="form-control" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde) ?>"></div>
                        <div class="col-md-5"><label class="form-label">Fecha Hasta</label><input type="date" class="form-control" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta) ?>"></div>
                        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Generar</button></div>
                    </div>
                </form>
            </div>
            <!-- Tab Productos -->
            <div class="tab-pane fade" id="productos-pane" role="tabpanel">
                <form method="GET" action="reportes_compras.php">
                    <input type="hidden" name="action" value="productos">
                    <div class="row align-items-end">
                        <div class="col-md-6"><label class="form-label">Período</label><select class="form-select" name="periodo_dias"><option value="30">Últimos 30 días</option><option value="90">Últimos 90 días</option><option value="365">Último Año</option></select></div>
                        <div class="col-md-4"><label class="form-label">Cantidad a mostrar</label><select class="form-select" name="limite"><option value="10">Top 10</option><option value="20">Top 20</option><option value="50">Top 50</option></select></div>
                        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Generar</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($reporte_data): ?>
    <div class="card mt-4">
        <div class="card-header hero-blue"><h5><i class="bi bi-table me-2"></i>Resultados: <?= $reporte_titulo ?></h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <?php if ($reporte_tipo === 'productos'): ?>
                            <tr class="table-light"><th>Ranking</th><th>Producto</th><th>Código</th><th class="text-end">Cantidad Comprada</th></tr>
                        <?php else: ?>
                            <tr class="table-light"><th>ID</th><th>Fecha</th><th>Proveedor</th><th>Estado</th><th class="text-end">Total</th></tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php if ($reporte_tipo === 'productos'): ?>
                            <?php foreach ($reporte_data as $index => $item): ?>
                                <tr><td><span class="badge bg-primary">#<?= $index + 1 ?></span></td><td><?= htmlspecialchars($item['nombre']) ?></td><td><?= htmlspecialchars($item['codigo']) ?></td><td class="text-end fw-bold"><?= number_format($item['total_cantidad']) ?></td></tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php foreach ($reporte_data as $compra): $total_general += $compra['total']; ?>
                                <tr><td><?= htmlspecialchars($compra['id']) ?></td><td><?= htmlspecialchars(date("d/m/Y", strtotime($compra['fecha_compra']))) ?></td><td><?= htmlspecialchars($compra['proveedor_nombre']) ?></td><td><span class="badge bg-info text-dark"><?= htmlspecialchars($compra['estado']) ?></span></td><td class="text-end">$<?= number_format($compra['total'], 2, ',', '.') ?></td></tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if ($reporte_tipo !== 'productos' && $total_general > 0): ?>
                    <tfoot class="table-light"><tr><th colspan="4" class="text-end">Total General:</th><th class="text-end fw-bold">$<?= number_format($total_general, 2, ',', '.') ?></th></tr></tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    <?php elseif ($reporte_tipo): ?>
        <div class="card mt-4"><div class="card-body text-center"><p class="text-muted">No se encontraron resultados para los criterios seleccionados.</p></div></div>
    <?php else: ?>
        <div class="card mt-4 bg-transparent border-0"><div class="card-body text-center"><i class="bi bi-graph-up-arrow" style="font-size: 4rem; color: #d0d0d0;"></i><p class="text-muted mt-2">Seleccione un reporte y aplique filtros para ver los resultados.</p></div></div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#proveedor_id').select2({ 
            placeholder: 'Buscar y seleccionar un proveedor',
            width: '100%' 
        });

        const urlParams = new URLSearchParams(window.location.search);
        const action = urlParams.get('action');
        if (action) {
            const tab = document.getElementById(action + '-tab');
            if(tab) new bootstrap.Tab(tab).show();
        }
    });
</script>
</body>
</html>

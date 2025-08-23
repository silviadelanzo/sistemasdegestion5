<?php
require_once '../../config/config.php';

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">ID de remito no especificado</div>';
    exit;
}

$pdo = conectarDB();
$remito_id = (int)$_GET['id'];

// Obtener datos del remito
$sql_remito = "SELECT 
    r.*,
    p.razon_social as proveedor_nombre,
    p.telefono as proveedor_telefono,
    p.email as proveedor_email,
    u.nombre as usuario_nombre
FROM remitos r
LEFT JOIN proveedores p ON r.proveedor_id = p.id
LEFT JOIN usuarios u ON r.usuario_id = u.id
WHERE r.id = ?";

$stmt = $pdo->prepare($sql_remito);
$stmt->execute([$remito_id]);
$remito = $stmt->fetch();

if (!$remito) {
    echo '<div class="alert alert-danger">Remito no encontrado</div>';
    exit;
}

// Paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 15; // 15 artículos por página
$offset = ($page - 1) * $items_per_page;

// Contar total de artículos para la paginación
$sql_count = "SELECT COUNT(*) FROM remito_detalles WHERE remito_id = ?";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute([$remito_id]);
$total_items = $stmt_count->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);


// Obtener detalles de productos paginados
$sql_detalles = "SELECT 
    rd.*,
    p.nombre as producto_nombre,
    p.codigo as producto_codigo,
    p.descripcion as producto_descripcion,
    c.nombre as categoria_nombre
FROM remito_detalles rd
LEFT JOIN productos p ON rd.producto_id = p.id
LEFT JOIN categorias c ON p.categoria_id = c.id
WHERE rd.remito_id = ?
ORDER BY p.nombre
LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql_detalles);
$stmt->bindValue(1, $remito_id, PDO::PARAM_INT);
$stmt->bindValue(2, $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$detalles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Remito - <?= htmlspecialchars($remito['codigo']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            margin-top: 20px;
        }
        .header-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-buttons">
            <a href="remitos.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <button class="btn btn-primary" onclick="imprimirRemito(<?= $remito['id'] ?>)">
                <i class="fas fa-print me-1"></i> Imprimir Remito
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-file-alt me-2"></i>Información del Remito
                        </h6>
                        <table class="table table-sm">
                            <tr>
                                <td class="fw-bold">Código:</td>
                                <td><?= htmlspecialchars($remito['codigo']) ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Nro. Remito Proveedor:</td>
                                <td><?= htmlspecialchars($remito['numero_remito_proveedor'] ?: 'No especificado') ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Fecha de Entrega:</td>
                                <td><?= date('d/m/Y', strtotime($remito['fecha_entrega'])) ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Estado:</td>
                                <td>
                                    <?php
                                    $badge_class = [
                                        'borrador' => 'bg-warning',
                                        'confirmado' => 'bg-success',
                                        'recibido' => 'bg-info'
                                    ];
                                    ?>
                                    <span class="badge <?= $badge_class[$remito['estado']] ?? 'bg-secondary' ?>">
                                        <?= ucfirst($remito['estado']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Creado:</td>
                                <td><?= date('d/m/Y H:i', strtotime($remito['fecha_creacion'])) ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Usuario:</td>
                                <td><?= htmlspecialchars($remito['usuario_nombre']) ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-truck me-2"></i>Información del Proveedor
                        </h6>
                        <table class="table table-sm">
                            <tr>
                                <td class="fw-bold">Nombre:</td>
                                <td><?= htmlspecialchars($remito['proveedor_nombre']) ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Teléfono:</td>
                                <td><?= htmlspecialchars($remito['proveedor_telefono'] ?: 'No especificado') ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Email:</td>
                                <td><?= htmlspecialchars($remito['proveedor_email'] ?: 'No especificado') ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if (!empty($remito['observaciones'])) : ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="text-primary mb-2">
                            <i class="fas fa-comment me-2"></i>Observaciones
                        </h6>
                        <div class="alert alert-light">
                            <?= nl2br(htmlspecialchars($remito['observaciones'])) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row mt-4">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-boxes me-2"></i>Productos del Remito
                        </h6>
                        
                        <?php if (empty($detalles)) : ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No hay productos en este remito.
                            </div>
                        <?php else : ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th>Categoría</th>
                                            <th class="text-center">Cantidad</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $total_cantidad = 0;
                                        foreach ($detalles as $detalle) :
                                            $total_cantidad += $detalle['cantidad'];
                                        ?>
                                            <tr>
                                                <td>
                                                    <code><?= htmlspecialchars($detalle['producto_codigo']) ?></code>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($detalle['producto_nombre']) ?></strong>
                                                    <?php if (!empty($detalle['producto_descripcion'])) : ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($detalle['producto_descripcion']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?= htmlspecialchars($detalle['categoria_nombre'] ?: 'Sin categoría') ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary">
                                                        <?= number_format($detalle['cantidad'], 2) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($detalle['observaciones'])) : ?>
                                                        <small><?= htmlspecialchars($detalle['observaciones']) ?></small>
                                                    <?php else : ?>
                                                        <small class="text-muted">Sin observaciones</small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="3" class="text-end">Total:</th>
                                            <th class="text-center">
                                                <span class="badge bg-success">
                                                    <?= number_format($total_cantidad, 2) ?> unidades
                                                </span>
                                            </th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <?php if ($total_pages > 1) : ?>
                                <nav class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1) : ?>
                                            <li class="page-item"><a class="page-link" href="?id=<?= $remito_id ?>&page=<?= $page - 1 ?>">Anterior</a></li>
                                        <?php endif; ?>
                                        <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                                            <li class="page-item <?php if ($i == $page) echo 'active'; ?>"><a class="page-link" href="?id=<?= $remito_id ?>&page=<?= $i ?>"><?= $i ?></a></li>
                                        <?php endfor; ?>
                                        <?php if ($page < $total_pages) : ?>
                                            <li class="page-item"><a class="page-link" href="?id=<?= $remito_id ?>&page=<?= $page + 1 ?>">Siguiente</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function imprimirRemito(id) {
            window.open('remito_imprimir.php?id=' + id, '_blank');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

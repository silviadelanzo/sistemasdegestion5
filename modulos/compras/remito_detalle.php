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
    COALESCE(p.razon_social, p.nombre_comercial, 'Proveedor') as proveedor_nombre,
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

// Paginación de productos
$productos_por_pagina = 20;
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;

// Contar total de productos
$sql_count = "SELECT COUNT(*) FROM remito_detalles WHERE remito_id = ?";
$stmt = $pdo->prepare($sql_count);
$stmt->execute([$remito_id]);
$total_productos = $stmt->fetchColumn();
$total_paginas = ceil($total_productos / $productos_por_pagina);
$offset = ($pagina - 1) * $productos_por_pagina;

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
LIMIT $productos_por_pagina OFFSET $offset";

$stmt = $pdo->prepare($sql_detalles);
$stmt->execute([$remito_id]);
$detalles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Remito #<?= htmlspecialchars($remito['codigo']) ?></title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Font Awesome 6.5 CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Google Fonts - Open Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f0f2f5;
            /* Un gris más suave para el fondo */
            line-height: 1.6;
            color: #333;
        }

        .remito-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            position: relative;
        }

        .remito-botones-cabecera {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        /* Mejorar el estilo de los botones */
        .btn-custom-light {
            background-color: #d1d8df;
            /* Un gris ligeramente más oscuro para mayor prominencia */
            border-color: #c9d0d6;
            color: #343a40;
            /* Texto más oscuro para contraste */
            font-weight: 600;
            /* Hace el texto más negrita */
            padding: .6rem 1.2rem;
            /* Aumenta un poco el tamaño del padding */
            border-radius: .5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
            /* Sombra sutil para darle profundidad */
        }

        .btn-custom-light:hover {
            background-color: #c0c6cc;
            border-color: #b7bec4;
            color: #212529;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        h6.text-primary {
            color: #0056b3 !important;
            /* Un azul más oscuro */
            font-weight: 600;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }

        .table {
            margin-bottom: 20px;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, .03);
        }

        .badge {
            font-size: 0.85em;
            padding: .4em .6em;
        }

        @media print {
            body {
                background-color: #fff;
            }

            .remito-container {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 0;
            }

            .remito-botones-cabecera,
            .remito-paginado {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <div class="remito-container">
            <div class="remito-botones-cabecera">
                <a href="remitos.php" class="btn btn-custom-light">
                    <i class="fas fa-arrow-left me-1"></i> Volver a Remitos
                </a>
                <button class="btn btn-custom-light" onclick="imprimirRemito(<?= $remito['id'] ?>)">
                    <i class="fas fa-print me-1"></i> Imprimir Remito
                </button>
            </div>

            <div class="row mb-4">
                <div class="col-12 text-center">
                    <h2 class="text-primary fw-bold mb-3">Remito de Entrada #<?= htmlspecialchars($remito['codigo']) ?></h2>
                    <p class="lead text-muted">Fecha de Creación: <?= date('d/m/Y H:i', strtotime($remito['fecha_creacion'])) ?></p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-file-alt me-2"></i>Información del Remito
                    </h6>
                    <table class="table table-sm table-bordered">
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
                                    'recibido' => 'bg-info',
                                    'en_revision' => 'bg-secondary',
                                    'cancelado' => 'bg-danger',
                                    'pendiente' => 'bg-warning'
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
                    <table class="table table-sm table-bordered">
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

            <?php if (!empty($remito['observaciones'])): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="text-primary mb-2">
                            <i class="fas fa-comment me-2"></i>Observaciones
                        </h6>
                        <div class="alert alert-light alert-dismissible fade show" role="alert">
                            <?= nl2br(htmlspecialchars($remito['observaciones'])) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row mt-4">
                <div class="col-12">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-boxes me-2"></i>Productos del Remito
                    </h6>
                    <?php if (empty($detalles)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No hay productos en este remito.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-sm">
                                <thead class="table-dark">
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
                                    foreach ($detalles as $detalle):
                                        $total_cantidad += $detalle['cantidad'];
                                    ?>
                                        <tr>
                                            <td>
                                                <code><?= htmlspecialchars($detalle['producto_codigo']) ?></code>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($detalle['producto_nombre']) ?></strong>
                                                <?php if (!empty($detalle['producto_descripcion'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($detalle['producto_descripcion']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= htmlspecialchars($detalle['categoria_nombre'] ?: 'Sin categoría') ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary">
                                                    <?= number_format($detalle['cantidad'], 2) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($detalle['observaciones'])): ?>
                                                    <small><?= htmlspecialchars($detalle['observaciones']) ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">Sin observaciones</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-dark">
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
                        <?php if ($total_paginas > 1): ?>
                            <div class="remito-paginado d-flex justify-content-center mt-4">
                                <nav aria-label="Paginación de productos">
                                    <ul class="pagination">
                                        <?php if ($pagina > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?id=<?= $remito_id ?>&pagina=<?= $pagina - 1 ?>" aria-label="Anterior">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                            <li class="page-item <?= $i == $pagina ? 'active' : '' ?>"><a class="page-link" href="?id=<?= $remito_id ?>&pagina=<?= $i ?>"> <?= $i ?> </a></li>
                                        <?php endfor; ?>
                                        <?php if ($pagina < $total_paginas): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?id=<?= $remito_id ?>&pagina=<?= $pagina + 1 ?>" aria-label="Siguiente">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script>
        function imprimirRemito(id) {
            window.print();
        }
    </script>
</body>

</html>
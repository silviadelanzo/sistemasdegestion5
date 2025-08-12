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

<style>
    .remito-container {
        max-width: 900px;
        margin: 40px auto 40px auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
        padding: 40px 40px 32px 40px;
        position: relative;
    }

    .remito-botones-cabecera {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        /* Espacio debajo de los botones */
    }

    /* Asegura que los botones .btn-light tengan el formato correcto */
    .btn.btn-light {
        background-color: #f8f9fa;
        /* Color de fondo claro de Bootstrap */
        border: 1px solid #dee2e6;
        /* Borde claro de Bootstrap */
        color: #212529;
        /* Color de texto oscuro para contraste */
        text-decoration: none;
        /* Quita el subrayado de los enlaces */
        display: inline-flex;
        /* Permite alinear ícono y texto */
        align-items: center;
        padding: .375rem .75rem;
        /* Relleno estándar de botón de Bootstrap */
        border-radius: .25rem;
        /* Borde redondeado estándar */
        font-size: 1rem !important;
        /* Fuerza el tamaño de fuente estándar de Bootstrap */
        font-weight: 400 !important;
        /* Fuerza el grosor de fuente estándar */
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol" !important;
        /* Fuerza la familia de fuente */
        transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }

    .btn.btn-light:hover {
        background-color: #e2e6ea;
        border-color: #dae0e5;
        color: #212529;
    }

    @media print {

        .remito-print-btn,
        .remito-paginado,
        .remito-botones-cabecera {
            /* Ocultar en impresión */
            display: none !important;
        }

        .remito-container {
            box-shadow: none;
            border: none;
            margin: 0;
            padding: 0;
        }
    }

    .remito-paginado {
        margin: 24px 0 0 0;
        text-align: center;
    }

    .remito-paginado .btn {
        margin: 0 2px;
    }
</style>

<div class="remito-container">
    <div class="remito-botones-cabecera">
        <a href="remitos.php" class="btn btn-light">
            <i class="fas fa-arrow-left me-1"></i> Volver a Remitos
        </a>
        <button class="btn btn-light" onclick="imprimirRemito(<?= $remito['id'] ?>)">
            <i class="fas fa-print me-1"></i> Imprimir Remito
        </button>
    </div>

    <!-- ...existing code... -->

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

    <?php if (!empty($remito['observaciones'])): ?>
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
            <?php if (empty($detalles)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No hay productos en este remito.
                </div>
            <?php else: ?>
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
                                        <?php if (!empty($detalle['observaciones'])): ?>
                                            <small><?= htmlspecialchars($detalle['observaciones']) ?></small>
                                        <?php else: ?>
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
                <?php if ($total_paginas > 1): ?>
                    <div class="remito-paginado">
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <a href="?id=<?= $remito_id ?>&pagina=<?= $i ?>" class="btn btn-outline-primary btn-sm<?= $i == $pagina ? ' active' : '' ?>">Página <?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function imprimirRemito(id) {
        window.print();
    }
</script>

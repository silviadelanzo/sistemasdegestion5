<?php
require_once '../../config/config.php';

if (!isset($_GET['id'])) {
    die('ID de remito no especificado');
}

$pdo = conectarDB();
$remito_id = (int)$_GET['id'];

// Obtener datos del remito
$sql_remito = "SELECT 
    r.*,
    p.razon_social as proveedor_nombre,
    p.direccion as proveedor_direccion,
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
    die('Remito no encontrado');
}

// Obtener detalles de productos
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
ORDER BY p.nombre";

$stmt = $pdo->prepare($sql_detalles);
$stmt->execute([$remito_id]);
$detalles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remito <?= $remito['codigo'] ?> - Impresión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
            body { font-size: 12px; }
            .container { max-width: 100% !important; }
        }
        
        .remito-header {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .info-table th {
            background: #e9ecef;
            font-weight: 600;
        }
        
        .productos-table th {
            background: #495057;
            color: white;
        }
        
        .estado-badge {
            font-size: 1.1em;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .checkbox-recibido {
            width: 20px;
            height: 20px;
            border: 2px solid #333;
            display: inline-block;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Botón de imprimir -->
        <div class="text-center mb-4 no-print">
            <button onclick="window.print()" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-print"></i> Imprimir
            </button>
            <button onclick="window.close()" class="btn btn-secondary btn-lg">
                <i class="fas fa-times"></i> Cerrar
            </button>
        </div>

        <!-- Header del remito -->
        <div class="remito-header">
            <div class="row">
                <div class="col-md-6">
                    <h1 class="mb-3">📋 REMITO DE ENTREGA</h1>
                    <h2 class="text-primary"><?= $remito['codigo'] ?></h2>
                    <p class="mb-1"><strong>Fecha de Entrega:</strong> <?= date('d/m/Y', strtotime($remito['fecha_entrega'])) ?></p>
                    <p class="mb-1"><strong>Fecha de Creación:</strong> <?= date('d/m/Y H:i', strtotime($remito['fecha_creacion'])) ?></p>
                    <p class="mb-0"><strong>Usuario:</strong> <?= htmlspecialchars($remito['usuario_nombre']) ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="mb-3">
                        <span class="estado-badge 
                            <?= $remito['estado'] === 'borrador' ? 'bg-warning text-dark' : '' ?>
                            <?= $remito['estado'] === 'confirmado' ? 'bg-success text-white' : '' ?>
                            <?= $remito['estado'] === 'recibido' ? 'bg-info text-white' : '' ?>">
                            <?= strtoupper($remito['estado']) ?>
                        </span>
                    </div>
                    <?php if (!empty($remito['numero_remito_proveedor'])): ?>
                        <p><strong>Nro. Remito Proveedor:</strong><br><?= htmlspecialchars($remito['numero_remito_proveedor']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Información del proveedor -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3">🚚 Información del Proveedor</h4>
                <table class="table table-bordered info-table">
                    <tr>
                        <th width="150">Proveedor:</th>
                        <td><strong><?= htmlspecialchars($remito['proveedor_nombre']) ?></strong></td>
                        <th width="150">Teléfono:</th>
                        <td><?= htmlspecialchars($remito['proveedor_telefono'] ?: 'No especificado') ?></td>
                    </tr>
                    <tr>
                        <th>Dirección:</th>
                        <td><?= htmlspecialchars($remito['proveedor_direccion'] ?: 'No especificada') ?></td>
                        <th>Email:</th>
                        <td><?= htmlspecialchars($remito['proveedor_email'] ?: 'No especificado') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Lista de productos -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3">📦 Productos del Remito</h4>
                
                <?php if (empty($detalles)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> No hay productos en este remito.
                    </div>
                <?php else: ?>
                    <table class="table table-bordered productos-table">
                        <thead>
                            <tr>
                                <th width="80">Código</th>
                                <th>Producto</th>
                                <th width="100">Categoría</th>
                                <th width="100" class="text-center">Cantidad</th>
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
                                        <small><?= htmlspecialchars($detalle['categoria_nombre'] ?: 'Sin categoría') ?></small>
                                    </td>
                                    <td class="text-center">
                                        <strong><?= number_format($detalle['cantidad'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($detalle['observaciones'] ?: '-') ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <th colspan="3" class="text-end">TOTAL:</th>
                                <th class="text-center"><?= number_format($total_cantidad, 2) ?> unidades</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Observaciones -->
        <?php if (!empty($remito['observaciones'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h4 class="mb-3">💬 Observaciones</h4>
                    <div class="alert alert-light border">
                        <?= nl2br(htmlspecialchars($remito['observaciones'])) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Sección de firmas -->
        <div class="row mt-5">
            <div class="col-md-6">
                <div class="border-top pt-3 text-center">
                    <p class="mb-1"><strong>Entregado por:</strong></p>
                    <p class="mb-0">_________________________</p>
                    <small class="text-muted">Firma y aclaración</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border-top pt-3 text-center">
                    <p class="mb-1"><strong>Recibido por:</strong></p>
                    <p class="mb-0">_________________________</p>
                    <small class="text-muted">Firma y aclaración</small>
                </div>
            </div>
        </div>

        <!-- Fecha y hora de recepción -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="border-top pt-3">
                    <p><strong>Fecha y hora de recepción:</strong> ___________________</p>
                    <p><strong>Observaciones de recepción:</strong></p>
                    <div style="border: 1px solid #ccc; height: 80px; margin-top: 10px;"></div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <small class="text-muted">
                    Remito generado el <?= date('d/m/Y H:i') ?> | Sistema de Gestión
                </small>
            </div>
        </div>
    </div>

    <script>
        // Auto-imprimir al cargar (opcional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>

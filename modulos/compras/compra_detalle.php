<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();
$id = $_GET['id'] ?? 0;

if ($id == 0) {
    // If no ID, redirect to the list
    header('Location: compras.php');
    exit;
}

$compra = null;
$detalles = [];

// Cargar datos de la orden de compra existente
$stmt = $pdo->prepare("SELECT oc.*, p.razon_social, d.nombre_deposito, e.nombre_estado 
                       FROM oc_ordenes oc
                       LEFT JOIN proveedores p ON oc.proveedor_id = p.id
                       LEFT JOIN oc_depositos d ON oc.deposito_id = d.id_deposito
                       LEFT JOIN oc_estados e ON oc.estado_id = e.id_estado
                       WHERE oc.id_orden = ?");
$stmt->execute([$id]);
$compra = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$compra) {
    // If order not found, redirect
    $_SESSION['error_message'] = "Orden de compra no encontrada.";
    header('Location: compras.php');
    exit;
}

$stmt_detalles = $pdo->prepare("SELECT cd.*, p.nombre, p.codigo, p.stock, p.stock_minimo, p.codigo_barra 
                               FROM oc_detalle cd 
                               JOIN productos p ON cd.producto_id = p.id 
                               WHERE cd.id_orden = ?");
$stmt_detalles->execute([$id]);
$detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Orden de Compra</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .form-container { max-width: 900px; margin: 30px auto; background: #fff; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,.08); overflow: hidden; }
        .form-header { background: #17a2b8; color: #fff; padding: 16px 20px; } /* Changed color to info */
        .form-control[readonly], .form-select[disabled] {
            background-color: #e9ecef;
            opacity: 1;
        }
        #form-compra .table > :not(caption) > * > * {
            padding: .4rem .4rem;
            font-size: 0.9rem;
            vertical-align: middle;
        }
        #form-compra .table th { text-align: center; }
        #form-compra .table td:nth-child(3),
        #form-compra .table td:nth-child(4),
        #form-compra .table td:nth-child(5),
        #form-compra .table td:nth-child(6),
        #form-compra .table td:nth-child(7) {
            text-align: right;
        }
    </style>
</head>
<body>
<?php include "../../config/navbar_code.php"; ?>
    <div class="container form-container">
        <div class="row">
            <div class="col-12">
                <div class="form-header">
                    <h4 class="mb-0"><i class="fas fa-eye"></i> Detalle de Orden de Compra</h4>
                </div>
                
                <form id="form-compra">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle"></i> Información General</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Número de Orden</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($compra['numero_orden'] ?? '') ?>" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Proveedor</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($compra['razon_social'] ?? '') ?>" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Fecha de O.Compra</label>
                                    <input type="date" class="form-control" value="<?= htmlspecialchars($compra['fecha_orden'] ?? '') ?>" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Condición de Pago</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($compra['condicion_pago'] ?? '') ?>" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Estado</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($compra['nombre_estado'] ?? '') ?>" readonly style="font-weight: bold;">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Depósito de Entrega</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($compra['nombre_deposito'] ?? 'N/A') ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Observaciones</label>
                                    <textarea class="form-control" rows="2" readonly><?= htmlspecialchars($compra['observaciones'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5><i class="fas fa-box"></i> Productos</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>CB</th>
                                            <th>Producto</th>
                                            <th>Precio Neto</th>
                                            <th>Cantidad</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productos-tbody">
                                        <?php 
                                        $total_general = 0;
                                        foreach ($detalles as $detalle): 
                                            $subtotal = (int)$detalle['cantidad'] * $detalle['precio_unitario'];
                                            $total_general += $subtotal;
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($detalle['codigo_barra'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($detalle['nombre']) ?></td>
                                                <td style="text-align: right;">$<?= number_format($detalle['precio_unitario'], 2) ?></td>
                                                <td style="text-align: right;"><?= (int)$detalle['cantidad'] ?></td>
                                                <td style="text-align: right;">$<?= number_format($subtotal, 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="row justify-content-end">
                                <div class="col-md-5">
                                    <div class="d-flex justify-content-between h5">
                                        <strong>Total:</strong>
                                        <span>$<?= number_format($total_general, 2) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="compras.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Listado
                        </a>
                        <a href="compra_imprimir.php?id=<?= $id ?>" class="btn btn-primary" target="_blank">
                            <i class="fas fa-print"></i> Imprimir
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
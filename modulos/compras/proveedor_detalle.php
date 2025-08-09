<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');
header('Content-Type: text/html; charset=UTF-8');

$proveedor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($proveedor_id <= 0) {
    header('Location: proveedores.php?error=id_invalido');
    exit;
}

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Obtener datos del proveedor
    $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = ?");
    $stmt->execute([$proveedor_id]);
    $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$proveedor) {
        header('Location: proveedores.php?error=proveedor_no_encontrado');
        exit;
    }

    // Obtener estadísticas de compras
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_compras, 
                                  SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as compras_pendientes,
                                  SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as compras_completadas
                           FROM compras WHERE proveedor_id = ?");
    $stmt->execute([$proveedor_id]);
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

    // Últimas compras
    $stmt = $pdo->prepare("SELECT * FROM compras WHERE proveedor_id = ? ORDER BY fecha_compra DESC LIMIT 5");
    $stmt->execute([$proveedor_id]);
    $ultimas_compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_message = "Error al cargar proveedor: " . $e->getMessage();
    header('Location: proveedores.php?error=' . urlencode($error_message));
    exit;
}

$pageTitle = "Detalle de Proveedor - " . htmlspecialchars($proveedor['razon_social']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .detail-card { 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 4px 16px rgba(0,0,0,0.1); 
            margin-bottom: 30px; 
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include "../../config/navbar_code.php"; ?>

    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-building text-primary"></i> Detalle del Proveedor</h2>
            <div>
                <a href="proveedor_form.php?id=<?= $proveedor['id'] ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                <a href="proveedores.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <!-- Información básica -->
        <div class="detail-card p-4">
            <h4 class="text-primary mb-3">
                <i class="bi bi-info-circle"></i> Información Básica
            </h4>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Código:</strong> 
                        <code class="text-primary"><?= htmlspecialchars($proveedor['codigo'] ?? 'SIN-CODIGO-' . $proveedor['id']) ?></code>
                    </p>
                    <p><strong>Razón Social:</strong> <?= htmlspecialchars($proveedor['razon_social']) ?></p>
                    <p><strong>Nombre Comercial:</strong> <?= htmlspecialchars($proveedor['nombre_comercial'] ?? '-') ?></p>
                    <p><strong>CUIT:</strong> <?= htmlspecialchars($proveedor['cuit'] ?? '-') ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Email:</strong> 
                        <?php if ($proveedor['email']): ?>
                            <a href="mailto:<?= htmlspecialchars($proveedor['email']) ?>"><?= htmlspecialchars($proveedor['email']) ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </p>
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($proveedor['telefono'] ?? '-') ?></p>
                    <p><strong>Estado:</strong> 
                        <?= $proveedor['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>' ?>
                    </p>
                    <p><strong>Fecha de Creación:</strong> <?= date('d/m/Y H:i', strtotime($proveedor['fecha_creacion'])) ?></p>
                </div>
            </div>
        </div>

        <!-- Dirección -->
        <div class="detail-card p-4">
            <h4 class="text-primary mb-3">
                <i class="bi bi-geo-alt"></i> Dirección
            </h4>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Dirección:</strong> <?= htmlspecialchars($proveedor['direccion'] ?? '-') ?></p>
                    <p><strong>Ciudad:</strong> <?= htmlspecialchars($proveedor['ciudad'] ?? '-') ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Provincia:</strong> <?= htmlspecialchars($proveedor['provincia'] ?? '-') ?></p>
                    <p><strong>Código Postal:</strong> <?= htmlspecialchars($proveedor['codigo_postal'] ?? '-') ?></p>
                </div>
            </div>
        </div>

        <!-- Información de contacto -->
        <div class="detail-card p-4">
            <h4 class="text-primary mb-3">
                <i class="bi bi-person-rolodex"></i> Contacto
            </h4>
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($proveedor['contacto_nombre'] ?? '-') ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($proveedor['contacto_telefono'] ?? '-') ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Email:</strong> 
                        <?php if ($proveedor['contacto_email']): ?>
                            <a href="mailto:<?= htmlspecialchars($proveedor['contacto_email']) ?>"><?= htmlspecialchars($proveedor['contacto_email']) ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Estadísticas de compras -->
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card">
                    <h3><?= number_format($estadisticas['total_compras'] ?? 0) ?></h3>
                    <p class="mb-0">Total Compras</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h3><?= number_format($estadisticas['compras_pendientes'] ?? 0) ?></h3>
                    <p class="mb-0">Pendientes</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h3><?= number_format($estadisticas['compras_completadas'] ?? 0) ?></h3>
                    <p class="mb-0">Completadas</p>
                </div>
            </div>
        </div>

        <!-- Últimas compras -->
        <?php if (!empty($ultimas_compras)): ?>
        <div class="detail-card p-4">
            <h4 class="text-primary mb-3">
                <i class="bi bi-cart"></i> Últimas Compras
            </h4>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimas_compras as $compra): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($compra['fecha_compra'])) ?></td>
                            <td>$<?= number_format($compra['total'] ?? 0, 2) ?></td>
                            <td>
                                <?php
                                $badge_class = '';
                                switch ($compra['estado']) {
                                    case 'pendiente': $badge_class = 'bg-warning'; break;
                                    case 'completada': $badge_class = 'bg-success'; break;
                                    case 'cancelada': $badge_class = 'bg-danger'; break;
                                    default: $badge_class = 'bg-secondary';
                                }
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= ucfirst($compra['estado']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($compra['observaciones'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

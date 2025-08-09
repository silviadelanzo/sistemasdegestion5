<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

// Configurar charset UTF-8
header('Content-Type: text/html; charset=UTF-8');

// --- Lógica para datos del Navbar (estandarizada) ---
$usuario_nombre = $_SESSION['nombre_usuario'] ?? 'Usuario';
$usuario_rol = $_SESSION['rol_usuario'] ?? 'inventario';
$es_administrador = ($usuario_rol === 'admin' || $usuario_rol === 'administrador');

// Para que la navbar funcione, se definen las variables que espera.
$compras_pendientes = 0; // Valor de ejemplo
$facturas_pendientes = 0; // Valor de ejemplo
// (Lógica para calcular los badges reales iría aquí)
// --- FIN Lógica Navbar ---

// Validar ID del cliente
$cliente_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$cliente_id) {
    header("Location: clientes.php");
    exit;
}

$cliente = null;
$stats = ['total_compras' => 0, 'monto_total' => 0];
$pedidos_recientes = [];
$error_mensaje = '';

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // 1. Obtener datos del cliente
    $stmt_cliente = $pdo->prepare("SELECT * FROM clientes WHERE id = :id AND eliminado = 0");
    $stmt_cliente->execute(['id' => $cliente_id]);
    $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        throw new Exception("El cliente no fue encontrado o ha sido eliminado.");
    }

    // 2. Obtener estadísticas de compras (basado en la tabla de pedidos)
    $stmt_stats = $pdo->prepare("SELECT COUNT(id) AS total_compras, SUM(total) AS monto_total FROM pedidos WHERE cliente_id = :cliente_id");
    $stmt_stats->execute(['cliente_id' => $cliente_id]);
    $stats_data = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    if ($stats_data) {
        $stats['total_compras'] = $stats_data['total_compras'] ?? 0;
        $stats['monto_total'] = $stats_data['monto_total'] ?? 0;
    }

    // 3. Obtener los últimos 10 pedidos del cliente
    $stmt_pedidos = $pdo->prepare("SELECT codigo, fecha_pedido, total, estado FROM pedidos WHERE cliente_id = :cliente_id ORDER BY fecha_pedido DESC LIMIT 10");
    $stmt_pedidos->execute(['cliente_id' => $cliente_id]);
    $pedidos_recientes = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_mensaje = "Error al cargar los datos del cliente: " . $e->getMessage();
    error_log($error_mensaje);
}

// Funciones auxiliares para la vista
function getTipoClienteClass($tipo)
{
    switch ($tipo) {
        case 'mayorista':
            return 'bg-primary';
        case 'minorista':
            return 'bg-success';
        case 'may_min':
            return 'bg-warning text-dark';
        default:
            return 'bg-secondary';
    }
}

function getTipoClienteTexto($tipo)
{
    $tipos = ['mayorista' => 'Mayorista', 'minorista' => 'Minorista', 'may_min' => 'Mayorista/Minorista'];
    return $tipos[$tipo] ?? 'No definido';
}

function getEstadoPedidoClass($estado)
{
    $clases = [
        'pendiente' => 'bg-warning text-dark',
        'procesando' => 'bg-info text-dark',
        'enviado' => 'bg-primary',
        'entregado' => 'bg-success',
        'cancelado' => 'bg-danger',
    ];
    return $clases[$estado] ?? 'bg-secondary';
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Cliente - <?php echo htmlspecialchars($cliente['nombre'] ?? 'Cliente'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }

        .navbar-custom {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: white !important;
        }

        .navbar-custom .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
        }

        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #f0f0f0;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .stats-card {
            text-align: center;
            padding: 1.5rem;
        }

        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            opacity: 0.7;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
        }

        .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .list-group-item {
            border: none;
            padding: 0.8rem 1.25rem;
        }

        .list-group-item strong {
            color: #343a40;
        }

        .table th {
            font-weight: 600;
        }

        .badge-tipo {
            font-size: 0.9rem;
            padding: 0.4em 0.7em;
        }
    </style>
</head>

<body>

    <div class="page-wrapper">
        <!-- NAVBAR UNIFICADO -->
        <?php include "../../config/navbar_code.php"; ?>

        <main class="container mt-4">
            <?php if ($error_mensaje): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error_mensaje); ?>
                    <a href="clientes.php" class="btn btn-secondary btn-sm ms-3">Volver al listado</a>
                </div>
            <?php elseif ($cliente): ?>
                <!-- Encabezado de la página -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0"><?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?></h2>
                        <p class="text-muted mb-0">
                            Código: <code><?php echo htmlspecialchars($cliente['codigo']); ?></code>
                            <?php if ($cliente['empresa']): ?>
                                | Empresa: <strong><?php echo htmlspecialchars($cliente['empresa']); ?></strong>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <a href="clientes.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver al Listado</a>
                        <a href="../pedidos/pedido_form.php?cliente_id=<?php echo $cliente['id']; ?>" class="btn btn-success"><i class="bi bi-cart-plus me-1"></i>Nuevo Pedido</a>
                        <a href="cliente_form.php?id=<?php echo $cliente['id']; ?>" class="btn btn-primary"><i class="bi bi-pencil-square me-1"></i>Editar Cliente</a>
                    </div>
                </div>

                <!-- Tarjetas de estadísticas -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <i class="bi bi-box-seam text-primary stats-icon"></i>
                            <div class="stats-number"><?php echo number_format($stats['total_compras']); ?></div>
                            <div class="stats-label">Total de Pedidos</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <i class="bi bi-cash-coin text-success stats-icon"></i>
                            <div class="stats-number">$<?php echo number_format($stats['monto_total'], 2); ?></div>
                            <div class="stats-label">Monto Total Comprado</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card">
                            <i class="bi bi-calendar-check text-info stats-icon"></i>
                            <div class="stats-number" style="font-size: 1.5rem;"><?php echo date('d/m/Y', strtotime($cliente['fecha_creacion'])); ?></div>
                            <div class="stats-label">Cliente Desde</div>
                        </div>
                    </div>
                </div>

                <!-- Información detallada -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header"><i class="bi bi-person-vcard me-2"></i>Información de Contacto</div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Tipo de Cliente</strong>
                                    <span class="badge badge-tipo <?php echo getTipoClienteClass($cliente['tipo_cliente']); ?>"><?php echo getTipoClienteTexto($cliente['tipo_cliente']); ?></span>
                                </li>
                                <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($cliente['email'] ?: '-'); ?></li>
                                <li class="list-group-item"><strong>Teléfono:</strong> <?php echo htmlspecialchars($cliente['telefono'] ?: '-'); ?></li>
                                <li class="list-group-item">
                                    <strong>Identificación:</strong>
                                    <?php echo htmlspecialchars($cliente['tipo_identificacion'] ?: '-'); ?>:
                                    <code><?php echo htmlspecialchars($cliente['numero_identificacion'] ?: '-'); ?></code>
                                </li>
                                <li class="list-group-item"><strong>Estado:</strong>
                                    <?php if ($cliente['activo']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header"><i class="bi bi-geo-alt me-2"></i>Dirección y Ubicación</div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Dirección:</strong> <?php echo htmlspecialchars($cliente['direccion'] ?: '-'); ?></li>
                                <li class="list-group-item"><strong>Ciudad:</strong> <?php echo htmlspecialchars($cliente['ciudad'] ?: '-'); ?></li>
                                <li class="list-group-item"><strong>Provincia:</strong> <?php echo htmlspecialchars($cliente['provincia'] ?: '-'); ?></li>
                                <li class="list-group-item"><strong>Código Postal:</strong> <?php echo htmlspecialchars($cliente['codigo_postal'] ?: '-'); ?></li>
                                <li class="list-group-item"><strong>País:</strong> <?php echo htmlspecialchars($cliente['pais'] ?: '-'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <?php if (!empty($cliente['notas'])): ?>
                    <div class="card">
                        <div class="card-header"><i class="bi bi-journal-text me-2"></i>Notas Adicionales</div>
                        <div class="card-body">
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($cliente['notas'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Historial de Pedidos -->
                <div class="card">
                    <div class="card-header"><i class="bi bi-clock-history me-2"></i>Historial de Pedidos Recientes</div>
                    <div class="card-body p-0">
                        <?php if (!empty($pedidos_recientes)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Código Pedido</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pedidos_recientes as $pedido): ?>
                                            <tr>
                                                <td><code><?php echo htmlspecialchars($pedido['codigo']); ?></code></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></td>
                                                <td><span class="badge <?php echo getEstadoPedidoClass($pedido['estado']); ?>"><?php echo ucfirst(htmlspecialchars($pedido['estado'])); ?></span></td>
                                                <td class="text-end">$<?php echo number_format($pedido['total'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-4">
                                <i class="bi bi-inbox fs-3 text-muted"></i>
                                <p class="text-muted mt-2">Este cliente aún no tiene pedidos registrados.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
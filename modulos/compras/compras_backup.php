<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();

// Obtener estadísticas
try {
    $stats = [
        'total_compras' => $pdo->query("SELECT COUNT(*) FROM compras WHERE activo = 1")->fetchColumn(),
        'pendientes' => $pdo->query("SELECT COUNT(*) FROM compras WHERE estado = 'pendiente' AND activo = 1")->fetchColumn(),
        'valor_total' => $pdo->query("SELECT COALESCE(SUM(total), 0) FROM compras WHERE activo = 1")->fetchColumn(),
        'total_proveedores' => $pdo->query("SELECT COUNT(*) FROM proveedores WHERE activo = 1")->fetchColumn()
    ];
} catch (Exception $e) {
    $stats = ['total_compras' => 0, 'pendientes' => 0, 'valor_total' => 0, 'total_proveedores' => 0];
}

// Filtros
$filtro_buscar = $_GET['buscar'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$filtro_proveedor = $_GET['proveedor'] ?? '';

// Construir consulta
$where_conditions = ["c.activo = 1"];
$params = [];

if (!empty($filtro_buscar)) {
    $where_conditions[] = "(c.codigo LIKE ? OR p.razon_social LIKE ?)";
    $params[] = "%$filtro_buscar%";
    $params[] = "%$filtro_buscar%";
}

if (!empty($filtro_estado)) {
    $where_conditions[] = "c.estado = ?";
    $params[] = $filtro_estado;
}

if (!empty($filtro_proveedor)) {
    $where_conditions[] = "c.proveedor_id = ?";
    $params[] = $filtro_proveedor;
}

$where_clause = implode(' AND ', $where_conditions);

// Consulta principal
$query = "
    SELECT c.*, p.razon_social as proveedor_nombre, p.nombre_comercial,
           u.nombre as usuario_nombre
    FROM compras c
    LEFT JOIN proveedores p ON c.proveedor_id = p.id
    LEFT JOIN usuarios u ON c.usuario_id = u.id
    WHERE $where_clause
    ORDER BY c.fecha_compra DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener proveedores para filtro
$proveedores = $pdo->query("SELECT id, razon_social FROM proveedores WHERE activo = 1 ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Compras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .stats-card.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stats-card.yellow { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        .stats-card.green { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
        .stats-card.cyan { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; }
        
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        
        .badge-estado {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .btn-action {
            margin: 0 2px;
            border-radius: 6px;
        }
        
        .search-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../../config/navbar_code.php'; ?>
    
    <div class="container-fluid mt-4">
        <!-- Header con breadcrumb -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="fas fa-shopping-cart text-primary"></i> Gestión de Compras</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../../menu_principal.php">Inicio</a></li>
                                <li class="breadcrumb-item active">Compras</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card blue text-center p-3">
                    <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                    <h3><?php echo number_format($stats['total_compras']); ?></h3>
                    <small>Total Compras</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card yellow text-center p-3">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h3><?php echo number_format($stats['pendientes']); ?></h3>
                    <small>Pendientes</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card green text-center p-3">
                    <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                    <h3>$<?php echo number_format($stats['valor_total'], 2); ?></h3>
                    <small>Valor Total</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card cyan text-center p-3">
                    <i class="fas fa-truck fa-2x mb-2"></i>
                    <h3><?php echo number_format($stats['total_proveedores']); ?></h3>
                    <small>Proveedores</small>
                </div>
            </div>
        </div>

        <!-- Filtros y búsqueda -->
        <div class="search-container">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="buscar" class="form-control" placeholder="Código, proveedor..." value="<?php echo htmlspecialchars($filtro_buscar); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendiente" <?php echo $filtro_estado == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="confirmada" <?php echo $filtro_estado == 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                        <option value="parcial" <?php echo $filtro_estado == 'parcial' ? 'selected' : ''; ?>>Parcial</option>
                        <option value="recibida" <?php echo $filtro_estado == 'recibida' ? 'selected' : ''; ?>>Recibida</option>
                        <option value="cancelada" <?php echo $filtro_estado == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Proveedor</label>
                    <select name="proveedor" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <option value="<?php echo $proveedor['id']; ?>" <?php echo $filtro_proveedor == $proveedor['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($proveedor['razon_social']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Botones de acción -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="bi bi-list-ul"></i> Lista de Compras</h5>
            <div>
                <a href="compra_form.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Nueva Compra
                </a>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-secondary">
                        <i class="bi bi-file-excel"></i> Exportar
                    </button>
                    <button type="button" class="btn btn-outline-secondary">
                        <i class="bi bi-printer"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Código</th>
                                        <th>Proveedor</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Total</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-compras">
                                    <!-- Contenido dinámico -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cargar compras al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarCompras();
        });
        
        function cargarCompras() {
            // Implementar carga de compras via AJAX
            console.log('Cargando compras...');
        }
    </script>
</body>
</html>
<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');
require_once '../../config/navbar_code.php';

$pdo = conectarDB();

// Obtener filtros
$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? 'activos';

// Construir consulta
$sql = "SELECT p.*, 
               pa.nombre as pais_nombre,
               pr.nombre as provincia_nombre,
               c.nombre as ciudad_nombre,
               COUNT(cp.id) as total_compras,
               COALESCE(SUM(cp.total), 0) as total_comprado
        FROM proveedores p
        LEFT JOIN paises pa ON p.pais_id = pa.id
        LEFT JOIN provincias pr ON p.provincia_id = pr.id
        LEFT JOIN ciudades c ON p.ciudad_id = c.id
        LEFT JOIN compras cp ON p.id = cp.proveedor_id
        WHERE 1=1";

$params = [];

if ($estado === 'activos') {
    $sql .= " AND p.activo = 1";
} elseif ($estado === 'inactivos') {
    $sql .= " AND p.activo = 0";
}

if (!empty($busqueda)) {
    $sql .= " AND (p.razon_social LIKE ? OR p.nombre_comercial LIKE ? OR p.codigo LIKE ? OR p.cuit LIKE ?)";
    $busquedaParam = "%$busqueda%";
    $params = array_fill(0, 4, $busquedaParam);
}

$sql .= " GROUP BY p.id ORDER BY p.razon_social";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores - Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0074D9;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            margin-top: 20px;
        }

        .header-section {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card-custom:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .proveedor-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .proveedor-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .proveedor-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .proveedor-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .proveedor-subtitle {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 0;
        }

        .badge-estado {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
            border-radius: 50px;
        }

        .whatsapp-btn {
            background-color: #25D366;
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .whatsapp-btn:hover {
            background-color: #1da851;
            transform: scale(1.05);
            color: white;
        }

        .btn-accion {
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
            border-radius: 6px;
            font-size: 0.8rem;
            padding: 0.375rem 0.75rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }

        .info-item i {
            width: 20px;
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        .search-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stats-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            margin-bottom: 1rem;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        @media (max-width: 768px) {
            .proveedor-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid main-container">
        <!-- Header -->
        <div class="header-section">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">
                        <i class="fas fa-truck me-3"></i>
                        Gestión de Proveedores
                    </h1>
                    <p class="mb-0 opacity-75">
                        Administra la información de tus proveedores y mantén contacto directo
                    </p>
                </div>
                <div>
                    <a href="compra_form_new.php" class="btn btn-light btn-lg me-2">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Nueva Compra
                    </a>
                    <a href="proveedor_form.php" class="btn btn-warning btn-lg">
                        <i class="fas fa-plus me-2"></i>
                        Nuevo Proveedor
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sidebar con estadísticas -->
            <div class="col-lg-3">
                <div class="card-custom">
                    <div class="card-body">
                        <h5 class="card-title text-center text-primary mb-3">
                            <i class="fas fa-chart-bar me-2"></i>
                            Estadísticas
                        </h5>

                        <div class="stats-card">
                            <div class="stats-number"><?php echo count(array_filter($proveedores, fn($p) => $p['activo'] == 1)); ?></div>
                            <div class="stats-label">Proveedores Activos</div>
                        </div>

                        <div class="stats-card">
                            <div class="stats-number"><?php echo count(array_filter($proveedores, fn($p) => $p['activo'] == 0)); ?></div>
                            <div class="stats-label">Proveedores Inactivos</div>
                        </div>

                        <div class="stats-card">
                            <div class="stats-number"><?php echo array_sum(array_column($proveedores, 'total_compras')); ?></div>
                            <div class="stats-label">Total Compras</div>
                        </div>

                        <div class="stats-card">
                            <div class="stats-number">$<?php echo number_format(array_sum(array_column($proveedores, 'total_comprado')), 2); ?></div>
                            <div class="stats-label">Total Comprado</div>
                        </div>
                    </div>
                </div>

                <!-- Filtros rápidos -->
                <div class="card-custom mt-3">
                    <div class="card-body">
                        <h6 class="card-title text-primary">
                            <i class="fas fa-filter me-2"></i>
                            Filtros Rápidos
                        </h6>
                        <div class="d-grid gap-2">
                            <a href="?estado=activos" class="btn btn-outline-success btn-sm <?php echo $estado === 'activos' ? 'active' : ''; ?>">
                                <i class="fas fa-check-circle me-1"></i> Activos
                            </a>
                            <a href="?estado=inactivos" class="btn btn-outline-danger btn-sm <?php echo $estado === 'inactivos' ? 'active' : ''; ?>">
                                <i class="fas fa-times-circle me-1"></i> Inactivos
                            </a>
                            <a href="?estado=todos" class="btn btn-outline-primary btn-sm <?php echo $estado === 'todos' ? 'active' : ''; ?>">
                                <i class="fas fa-list me-1"></i> Todos
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="col-lg-9">
                <!-- Barra de búsqueda -->
                <div class="search-section">
                    <form method="GET" class="row align-items-center">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" name="busqueda"
                                    value="<?php echo htmlspecialchars($busqueda); ?>"
                                    placeholder="Buscar por razón social, nombre comercial, código o CUIT...">
                                <input type="hidden" name="estado" value="<?php echo $estado; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> Buscar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lista de proveedores -->
                <div class="proveedores-container">
                    <?php if (empty($proveedores)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No hay proveedores</h4>
                            <p class="text-muted">
                                <?php if (!empty($busqueda)): ?>
                                    No se encontraron proveedores que coincidan con tu búsqueda.
                                <?php else: ?>
                                    Comienza agregando tu primer proveedor.
                                <?php endif; ?>
                            </p>
                            <a href="proveedor_form.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Agregar Proveedor
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <div class="proveedor-card">
                                <div class="proveedor-header">
                                    <div class="flex-grow-1">
                                        <h5 class="proveedor-title">
                                            <?php echo htmlspecialchars($proveedor['razon_social']); ?>
                                            <?php if ($proveedor['activo']): ?>
                                                <span class="badge bg-success badge-estado ms-2">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger badge-estado ms-2">Inactivo</span>
                                            <?php endif; ?>
                                        </h5>
                                        <?php if ($proveedor['nombre_comercial']): ?>
                                            <p class="proveedor-subtitle">
                                                <i class="fas fa-store me-1"></i>
                                                <?php echo htmlspecialchars($proveedor['nombre_comercial']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block">Código: <?php echo htmlspecialchars($proveedor['codigo']); ?></small>
                                        <?php if ($proveedor['total_compras'] > 0): ?>
                                            <small class="text-success">
                                                <i class="fas fa-shopping-cart me-1"></i>
                                                <?php echo $proveedor['total_compras']; ?> compras
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="info-grid">
                                    <?php if ($proveedor['cuit']): ?>
                                        <div class="info-item">
                                            <i class="fas fa-id-card"></i>
                                            <span>CUIT: <?php echo htmlspecialchars($proveedor['cuit']); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($proveedor['telefono']): ?>
                                        <div class="info-item">
                                            <i class="fas fa-phone"></i>
                                            <a href="tel:<?php echo htmlspecialchars($proveedor['telefono']); ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($proveedor['telefono']); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($proveedor['email']): ?>
                                        <div class="info-item">
                                            <i class="fas fa-envelope"></i>
                                            <a href="mailto:<?php echo htmlspecialchars($proveedor['email']); ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($proveedor['email']); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($proveedor['direccion']): ?>
                                        <div class="info-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span>
                                                <?php echo htmlspecialchars($proveedor['direccion']); ?>
                                                <?php if ($proveedor['ciudad_nombre']): ?>
                                                    , <?php echo htmlspecialchars($proveedor['ciudad_nombre']); ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        <?php if ($proveedor['whatsapp']): ?>
                                            <button type="button" class="whatsapp-btn" onclick="abrirWhatsApp('<?php echo htmlspecialchars($proveedor['whatsapp']); ?>')">
                                                <i class="fab fa-whatsapp me-1"></i>
                                                WhatsApp
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <a href="compra_form_new.php?proveedor_id=<?php echo $proveedor['id']; ?>"
                                            class="btn btn-primary btn-accion" title="Nueva Compra">
                                            <i class="fas fa-shopping-cart"></i>
                                        </a>
                                        <a href="proveedor_form.php?id=<?php echo $proveedor['id']; ?>"
                                            class="btn btn-outline-primary btn-accion" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($proveedor['activo']): ?>
                                            <button type="button" class="btn btn-outline-warning btn-accion"
                                                onclick="cambiarEstado(<?php echo $proveedor['id']; ?>, 0)" title="Desactivar">
                                                <i class="fas fa-eye-slash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-success btn-accion"
                                                onclick="cambiarEstado(<?php echo $proveedor['id']; ?>, 1)" title="Activar">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($proveedor['total_compras'] == 0): ?>
                                            <button type="button" class="btn btn-outline-danger btn-accion"
                                                onclick="eliminarProveedor(<?php echo $proveedor['id']; ?>)" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function abrirWhatsApp(numero) {
            const mensaje = encodeURIComponent('Hola, me pongo en contacto desde el Sistema de Gestión para consultar sobre productos y servicios.');
            const numeroLimpio = numero.replace(/[^0-9]/g, '');
            const url = `https://wa.me/${numeroLimpio}?text=${mensaje}`;
            window.open(url, '_blank');
        }

        function cambiarEstado(id, nuevoEstado) {
            const accion = nuevoEstado ? 'activar' : 'desactivar';
            if (confirm(`¿Está seguro que desea ${accion} este proveedor?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'gestionar_proveedor_ajax.php';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = nuevoEstado ? 'activar_proveedor' : 'desactivar_proveedor';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;

                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function eliminarProveedor(id) {
            if (confirm('¿Está seguro que desea eliminar este proveedor? Esta acción no se puede deshacer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'gestionar_proveedor_ajax.php';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'eliminar_proveedor';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;

                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Auto-refresh cada 30 segundos si hay filtros activos
        <?php if (!empty($busqueda) || $estado !== 'activos'): ?>
            setTimeout(() => {
                location.reload();
            }, 30000);
        <?php endif; ?>
    </script>
</body>

</html>
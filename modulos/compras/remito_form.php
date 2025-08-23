<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();

// Obtener ID del remito a editar
$remito_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$remito_id) {
    echo '<div class="alert alert-danger">ID de remito no especificado</div>';
    exit;
}

// Obtener datos actuales del remito
$sql = "SELECT * FROM remitos WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$remito_id]);
$remito = $stmt->fetch();

if (!$remito) {
    echo '<div class="alert alert-danger">Remito no encontrado</div>';
    exit;
}

// Obtener proveedores
$proveedores = $pdo->query("SELECT id, COALESCE(razon_social, nombre_comercial, 'Proveedor') as nombre FROM proveedores WHERE eliminado=0 AND activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos del remito
$sql_detalles = "SELECT rd.*, p.nombre as producto_nombre, p.codigo as producto_codigo, p.descripcion as producto_descripcion, c.nombre as categoria_nombre FROM remito_detalles rd LEFT JOIN productos p ON rd.producto_id = p.id LEFT JOIN categorias c ON p.categoria_id = c.id WHERE rd.remito_id = ? ORDER BY p.nombre";
$stmt = $pdo->prepare($sql_detalles);
$stmt->execute([$remito_id]);
$detalles = $stmt->fetchAll();

// Obtener todos los productos para el selector
$productos = $pdo->query("SELECT id, nombre, codigo FROM productos WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Definir estados válidos como en remitos.php
$estados = [
    'pendiente' => 'Pendiente',
    'confirmado' => 'Confirmado',
    'recibido' => 'Recibido',
    'cancelado' => 'Cancelado'
];

// Procesar formulario (actualización de proveedor y productos)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proveedor_id = (int)$_POST['proveedor_id'] ?? 0;
    $numero_remito_proveedor = trim($_POST['numero_remito_proveedor'] ?? '');
    $fecha_entrega = trim($_POST['fecha_entrega'] ?? '');
    $estado = trim($_POST['estado'] ?? 'pendiente');
    $observaciones = trim($_POST['observaciones'] ?? '');

    try {
        $pdo->beginTransaction();

        // Actualizar remito
        $sql_update = "UPDATE remitos SET proveedor_id=?, numero_remito_proveedor=?, fecha_entrega=?, estado=?, observaciones=? WHERE id=?";
        $stmt = $pdo->prepare($sql_update);
        $stmt->execute([$proveedor_id, $numero_remito_proveedor, $fecha_entrega, $estado, $observaciones, $remito_id]);

        // Actualizar productos
        if (isset($_POST['producto_id']) && is_array($_POST['producto_id'])) {
            foreach ($_POST['producto_id'] as $i => $prod_id) {
                $detalle_id = (int)$_POST['detalle_id'][$i];
                $cantidad = (float)$_POST['cantidad'][$i];
                $obs = trim($_POST['detalle_observaciones'][$i]);

                $sql_upd_det = "UPDATE remito_detalles SET producto_id=?, cantidad=?, observaciones=? WHERE id=? AND remito_id=?";
                $stmt = $pdo->prepare($sql_upd_det);
                $stmt->execute([$prod_id, $cantidad, $obs, $detalle_id, $remito_id]);
            }
        }

        $pdo->commit();
        
        // Redireccionar con mensaje de éxito
        $_SESSION['mensaje_exito'] = 'Remito actualizado correctamente.';
        header('Location: remitos.php');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollback();
        $mensaje_error = 'Error al actualizar el remito: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Remito</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            /* Colores principales */
            --primary-color: #2563eb;      /* Azul moderno */
            --primary-light: #3b82f6;     /* Azul claro */
            --primary-dark: #1d4ed8;      /* Azul oscuro */
            
            /* Colores secundarios */
            --secondary-color: #64748b;    /* Gris azulado */
            --accent-color: #10b981;       /* Verde esmeralda */
            --warning-color: #f59e0b;      /* Amarillo/naranja */
            --danger-color: #ef4444;       /* Rojo */
            
            /* Fondos */
            --bg-primary: #f8fafc;         /* Fondo principal */
            --bg-secondary: #ffffff;       /* Fondo secundario */
            --bg-card: #ffffff;            /* Fondo de tarjetas */
            
            /* Bordes y sombras */
            --border-color: #e2e8f0;       /* Color de bordes */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        body {
            background-color: var(--bg-primary);
            font-family: sans-serif;
            line-height: 1.3; /* Reducido de 1.5 a 1.3 */
        }

        .main-container {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 1rem; /* Reducido de 2rem a 1rem */
        }

        .form-card {
            background: var(--bg-card);
            border-radius: 12px; /* Reducido de 16px a 12px */
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.3s ease;
            max-width: 1000px;
            margin: 1rem auto; /* Reducido de 2rem a 1rem */
        }

        .form-card:hover {
            transform: translateY(-1px); /* Reducido de -2px a -1px */
            box-shadow: 0 15px 20px -5px rgb(0 0 0 / 0.1); /* Reducido */
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 1rem 1.5rem; /* Reducido de 1.5rem 2rem a 1rem 1.5rem */
            border-bottom: 2px solid var(--primary-dark); /* Reducido de 3px a 2px */
        }

        .form-title {
            font-size: 1.25rem; /* Reducido de 1.5rem a 1.25rem */
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-title::before {
            content: "📋";
            font-size: 1rem; /* Reducido de 1.25rem a 1rem */
        }

        .form-group {
            margin-bottom: 1rem; /* Reducido de 1.5rem a 1rem */
        }

        .form-label {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 0.25rem; /* Reducido de 0.5rem a 0.25rem */
            display: block;
            font-size: 0.8rem; /* Reducido de 0.875rem a 0.8rem */
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 6px; /* Reducido de 8px a 6px */
            padding: 0.5rem 0.75rem; /* Reducido de 0.75rem 1rem a 0.5rem 0.75rem */
            font-size: 0.9rem; /* Reducido de 1rem a 0.9rem */
            transition: all 0.3s ease;
            background-color: #fafbfc;
            line-height: 1.2; /* Añadido para reducir altura */
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgb(37 99 235 / 0.1); /* Reducido de 3px a 2px */
            background-color: white;
            outline: none;
        }

        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 20 20\'%3e%3cpath stroke=\'%236b7280\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'m6 8 4 4 4-4\'%3e%3c/path%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .table-container {
            background: var(--bg-card);
            border-radius: 8px; /* Reducido de 12px a 8px */
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-top: 1rem; /* Reducido de 2rem a 1rem */
        }

        .table-header {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            padding: 0.75rem 1rem; /* Reducido de 1rem 1.5rem a 0.75rem 1rem */
            border-bottom: 1px solid var(--border-color); /* Reducido de 2px a 1px */
        }

        .table-title {
            font-size: 1rem; /* Reducido de 1.25rem a 1rem */
            font-weight: 600;
            color: var(--secondary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-title::before {
            content: "📦";
            font-size: 0.9rem; /* Reducido de 1.125rem a 0.9rem */
        }

        .table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .table th {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--secondary-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem; /* Reducido de 0.75rem a 0.7rem */
            letter-spacing: 0.05em;
            padding: 0.5rem; /* Reducido de 1rem a 0.5rem */
            border-bottom: 1px solid var(--border-color); /* Reducido de 2px a 1px */
            text-align: left;
            line-height: 1.1; /* Añadido para reducir altura */
        }

        .table td {
            padding: 0.5rem; /* Reducido de 1rem a 0.5rem */
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            line-height: 1.1; /* Añadido para reducir altura */
        }

        .table tbody tr:hover {
            background-color: #f8fafc;
            transform: scale(1.002); /* Reducido de 1.005 a 1.002 */
            transition: all 0.2s ease;
        }

        .pagination-container {
            background: var(--bg-card);
            border-radius: 8px; /* Reducido de 12px a 8px */
            padding: 1rem; /* Reducido de 1.5rem a 1rem */
            margin-top: 0.5rem; /* Reducido de 1rem a 0.5rem */
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.25rem; /* Reducido de 0.5rem a 0.25rem */
        }

        .pagination .page-link {
            border: 1px solid var(--border-color); /* Reducido de 2px a 1px */
            border-radius: 6px; /* Reducido de 8px a 6px */
            padding: 0.375rem 0.75rem; /* Reducido de 0.5rem 1rem a 0.375rem 0.75rem */
            color: var(--secondary-color);
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 0 0.125rem; /* Reducido de 0.25rem a 0.125rem */
            font-size: 0.875rem; /* Añadido para reducir tamaño */
        }

        .pagination .page-link:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border: none;
            border-radius: 6px; /* Reducido de 8px a 6px */
            padding: 0.5rem 1rem; /* Reducido de 0.75rem 1.5rem a 0.5rem 1rem */
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            color: white;
            font-size: 0.875rem; /* Añadido para reducir tamaño */
        }

        .btn-primary:hover {
            transform: translateY(-1px); /* Reducido de -2px a -1px */
            box-shadow: var(--shadow-md);
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--accent-color) 0%, #059669 100%);
            border: none;
            border-radius: 6px; /* Reducido de 8px a 6px */
            padding: 0.5rem 1rem; /* Reducido de 0.75rem 1.5rem a 0.5rem 1rem */
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            color: white;
            font-size: 0.875rem; /* Añadido para reducir tamaño */
        }

        .btn-success:hover {
            transform: translateY(-1px); /* Reducido de -2px a -1px */
            box-shadow: var(--shadow-md);
            background: linear-gradient(135deg, #059669 0%, var(--accent-color) 100%);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #d97706 100%);
            border: none;
            border-radius: 6px; /* Reducido de 8px a 6px */
            padding: 0.5rem 1rem; /* Reducido de 0.75rem 1.5rem a 0.5rem 1rem */
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            color: white;
            font-size: 0.875rem; /* Añadido para reducir tamaño */
        }

        .btn-warning:hover {
            transform: translateY(-1px); /* Reducido de -2px a -1px */
            box-shadow: var(--shadow-md);
            background: linear-gradient(135deg, #d97706 0%, var(--warning-color) 100%);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%);
            border: none;
            border-radius: 6px;
            padding: 0.375rem 0.75rem; /* Más pequeño para botones de eliminar */
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            color: white;
            font-size: 0.75rem; /* Más pequeño */
        }

        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            background: linear-gradient(135deg, #dc2626 0%, var(--danger-color) 100%);
        }

        .alert {
            border-radius: 8px; /* Reducido de 12px a 8px */
            border: none;
            padding: 0.75rem 1rem; /* Reducido de 1rem 1.5rem a 0.75rem 1rem */
            margin-bottom: 1rem; /* Reducido de 1.5rem a 1rem */
            box-shadow: var(--shadow-sm);
            font-size: 0.875rem; /* Añadido para reducir tamaño */
        }

        .alert-success {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            color: #065f46;
            border-left: 3px solid var(--accent-color); /* Reducido de 4px a 3px */
        }

        .alert-danger {
            background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
            color: #991b1b;
            border-left: 3px solid var(--danger-color); /* Reducido de 4px a 3px */
        }

        /* Reducir espaciado específico en la tabla de remitos */
        .table-responsive {
            padding: 0; /* Eliminar padding extra */
        }

        .row {
            margin-bottom: 0.75rem; /* Reducido de 1rem a 0.75rem */
        }

        .col-md-6 {
            padding-left: 0.5rem; /* Reducido de 0.75rem a 0.5rem */
            padding-right: 0.5rem; /* Reducido de 0.75rem a 0.5rem */
        }

        /* Optimizar espaciado en contenedor principal */
        .p-4 {
            padding: 1rem !important; /* Reducido de 1.5rem a 1rem */
        }

        /* Reducir espaciado entre botones */
        .d-flex.justify-content-between {
            margin-top: 1rem; /* Reducido de 1.5rem a 1rem */
            gap: 0.5rem;
        }

        .btn-volver {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); /* Gris */
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            color: white;
            font-size: 0.875rem;
            text-decoration: none; /* Para que se vea como botón aunque sea un enlace */
            display: inline-block; /* Para aplicar padding y centrado */
            text-align: center;
        }

        .btn-volver:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            background: linear-gradient(135deg, #5a6268 0%, #6c757d 100%);
            color: white;
        }

        .button-group-center {
            display: flex;
            justify-content: center;
            width: 100%;
            margin-top: 1rem; /* Espacio superior */
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="d-flex align-items-center mb-3">
            <a href="remitos.php" class="btn btn-outline-secondary btn-lg px-4 me-3" style="font-weight:600;">
                <i class="bi bi-arrow-left-circle me-2"></i> Volver
            </a>
            <h2 class="form-title mb-0">Editar Remito</h2>
        </div>
        <div class="form-card">
            <div class="form-header d-none"><!-- oculto, ya que el título está arriba --></div>
            <div class="p-4">
                <?php if (isset($mensaje_error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($mensaje_error); ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="row mb-3">
                        <div class="col-md-6 form-group">
                            <label for="proveedor_id" class="form-label">Proveedor</label>
                            <select name="proveedor_id" id="proveedor_id" class="form-select" required>
                                <option value="">Seleccione proveedor...</option>
                                <?php foreach ($proveedores as $prov): ?>
                                    <option value="<?php echo $prov['id']; ?>" <?php echo ($remito['proveedor_id'] == $prov['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($prov['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="numero_remito_proveedor" class="form-label">Nro. Remito Proveedor</label>
                            <input type="text" name="numero_remito_proveedor" id="numero_remito_proveedor" class="form-control" value="<?php echo htmlspecialchars($remito['numero_remito_proveedor']); ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6 form-group">
                            <label for="fecha_entrega" class="form-label">Fecha de Entrega</label>
                            <input type="date" name="fecha_entrega" id="fecha_entrega" class="form-control" value="<?php echo htmlspecialchars(substr($remito['fecha_entrega'], 0, 10)); ?>">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="estado" class="form-label">Estado</label>
                            <select name="estado" id="estado" class="form-select" required>
                                <?php foreach ($estados as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($remito['estado'] === $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea name="observaciones" id="observaciones" class="form-control" rows="2"><?php echo htmlspecialchars($remito['observaciones']); ?></textarea>
                    </div>

                    <div class="table-container">
                        <div class="table-header">
                            <h5 class="table-title">Productos del Remito</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Observaciones</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $productos_por_pagina = 20;
                                    $pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
                                    $total_productos = count($detalles);
                                    $total_paginas = ceil($total_productos / $productos_por_pagina);
                                    $offset = ($pagina - 1) * $productos_por_pagina;

                                    $detalles_paginados = array_slice($detalles, $offset, $productos_por_pagina);

                                    foreach ($detalles_paginados as $detalle):
                                    ?>
                                        <tr>
                                            <td>
                                                <input type="hidden" name="detalle_id[]" value="<?php echo $detalle['id']; ?>">
                                                <select name="producto_id[]" class="form-select" required>
                                                    <?php foreach ($productos as $prod): ?>
                                                        <option value="<?php echo $prod['id']; ?>" <?php echo ($detalle['producto_id'] == $prod['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($prod['nombre'] . ' (' . $prod['codigo'] . ')'); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="cantidad[]" class="form-control" value="<?php echo htmlspecialchars($detalle['cantidad']); ?>" step="0.01" required>
                                            </td>
                                            <td>
                                                <input type="text" name="detalle_observaciones[]" class="form-control" value="<?php echo htmlspecialchars($detalle['observaciones']); ?>">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove();">Eliminar</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        <button type="button" class="btn btn-success" onclick="addRow();">Agregar Producto</button>
                    </div>

                    <?php if ($total_paginas > 1): ?>
                        <nav class="pagination-container mt-4">
                            <ul class="pagination">
                                <?php if ($pagina > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?id=<?php echo $remito_id; ?>&pagina=<?php echo $pagina - 1; ?>">Anterior</a></li>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <li class="page-item <?php echo ($i == $pagina) ? 'active' : ''; ?>"><a class="page-link" href="?id=<?php echo $remito_id; ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                                <?php endfor; ?>
                                <?php if ($pagina < $total_paginas): ?>
                                    <li class="page-item"><a class="page-link" href="?id=<?php echo $remito_id; ?>&pagina=<?php echo $pagina + 1; ?>">Siguiente</a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addRow() {
            const tableBody = document.querySelector('.table tbody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <input type="hidden" name="detalle_id[]" value="0"> <!-- Nuevo detalle, ID 0 o similar -->
                    <select name="producto_id[]" class="form-select" required>
                        <option value="">Seleccione producto...</option>
                        <?php foreach ($productos as $prod): ?>
                            <option value="<?php echo $prod['id']; ?>"><?php echo htmlspecialchars($prod['nombre'] . ' (' . $prod['codigo'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="number" name="cantidad[]" class="form-control" value="1" step="0.01" required>
                </td>
                <td>
                    <input type="text" name="detalle_observaciones[]" class="form-control" value="">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove();">Eliminar</button>
                </td>
            `;
            tableBody.appendChild(newRow);
        }
    </script>
</body>
</html>
<?php
/*
MEJORAS IMPLEMENTADAS:

1. ESPACIADO INTERLINEAL OPTIMIZADO:
   - Reducido line-height de 1.5 a 1.3 en body
   - Padding reducido en contenedores principales
   - Márgenes optimizados en form-group (de 1.5rem a 1rem)
   - Padding reducido en form-control (de 0.75rem 1rem a 0.5rem 0.75rem)
   - Tamaños de fuente reducidos para mayor densidad visual
   - Espaciado en tabla optimizado (padding de celdas de 1rem a 0.5rem)
   - Bordes y sombras reducidos para menos espacio visual

2. FLUJO DE GUARDADO MEJORADO:
   - Implementado try-catch con transacciones de base de datos
   - Mensaje de éxito almacenado en sesión
   - Redirección automática a remitos.php después del guardado exitoso
   - Manejo de errores con rollback de transacción
   - Eliminación del mensaje de éxito en pantalla (ahora se muestra en la página de destino)

3. MEJORAS ADICIONALES:
   - Botones más compactos con tamaños de fuente reducidos
   - Paginación optimizada con espaciado reducido
   - Alertas más compactas
   - Tabla responsive sin padding extra
   - Contenedores con menos espaciado vertical

NOTA: Para que el mensaje de éxito se muestre correctamente en remitos.php,
asegúrate de que esa página tenga el código para mostrar $_SESSION['mensaje_exito']
y luego limpie la variable de sesión.
*/
?>
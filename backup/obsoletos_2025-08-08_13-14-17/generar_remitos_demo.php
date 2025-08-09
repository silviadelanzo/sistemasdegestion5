<?php
// modulos/compras/ocr_remitos/generar_remitos_demo.php
session_start();
require_once '../../../config/config.php';

// Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../../login.php');
    exit;
}

class GeneradorRemitosDemo
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function obtenerProductosDemo($limit = 15)
    {
        $query = "
            SELECT p.codigo, p.nombre, p.codigo_barra, p.precio_compra, p.codigo_proveedor,
                   pr.razon_social as proveedor_nombre, pr.codigo as proveedor_codigo
            FROM productos p
            LEFT JOIN proveedores pr ON p.proveedor_principal_id = pr.id
            WHERE p.codigo LIKE 'DEMO%' AND p.activo = 1
            ORDER BY RAND()
            LIMIT ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerProveedores()
    {
        $query = "SELECT * FROM proveedores WHERE codigo LIKE 'PROV%' AND activo = 1";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generarRemito($proveedor_id, $tipo = 'compra')
    {
        // Obtener datos del proveedor
        $proveedor_query = "SELECT * FROM proveedores WHERE id = ?";
        $stmt = $this->db->prepare($proveedor_query);
        $stmt->execute([$proveedor_id]);
        $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

        // Obtener productos aleatorios
        $cantidad_productos = rand(8, 15);
        $productos = $this->obtenerProductosDemo($cantidad_productos);

        // Datos del remito
        $numero_remito = 'R-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $fecha = date('d/m/Y');
        $fecha_vencimiento = date('d/m/Y', strtotime('+30 days'));

        return [
            'numero' => $numero_remito,
            'fecha' => $fecha,
            'fecha_vencimiento' => $fecha_vencimiento,
            'proveedor' => $proveedor,
            'productos' => $productos,
            'tipo' => $tipo
        ];
    }

    public function generarRemitoHTML($remito_data)
    {
        $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Remito ' . $remito_data['numero'] . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; }
        .header { border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .company { font-size: 16px; font-weight: bold; }
        .remito-info { background: #f0f0f0; padding: 10px; margin: 10px 0; }
        .supplier-info { border: 1px solid #ccc; padding: 10px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background: #e0e0e0; font-weight: bold; }
        .number { text-align: right; }
        .total { font-weight: bold; background: #f0f0f0; }
        .footer { margin-top: 20px; font-size: 10px; }
        .barcode { font-family: "Libre Barcode 128", monospace; font-size: 24px; text-align: center; }
    </style>
</head>
<body>';

        $html .= '<div class="header">
            <div class="company">EMPRESA DEMO S.A.</div>
            <div>CUIT: 20-12345678-9</div>
            <div>Dirección: Av. Principal 1234, Ciudad</div>
            <div>Tel: (011) 4567-8900</div>
        </div>';

        $html .= '<div class="remito-info">
            <h2>REMITO DE COMPRA N° ' . $remito_data['numero'] . '</h2>
            <div><strong>Fecha:</strong> ' . $remito_data['fecha'] . '</div>
            <div><strong>Vencimiento:</strong> ' . $remito_data['fecha_vencimiento'] . '</div>
        </div>';

        $html .= '<div class="supplier-info">
            <h3>PROVEEDOR</h3>
            <div><strong>Código:</strong> ' . $remito_data['proveedor']['codigo'] . '</div>
            <div><strong>Razón Social:</strong> ' . $remito_data['proveedor']['razon_social'] . '</div>
            <div><strong>CUIT:</strong> ' . ($remito_data['proveedor']['cuit'] ?? 'N/A') . '</div>
            <div><strong>Dirección:</strong> ' . ($remito_data['proveedor']['direccion'] ?? 'N/A') . '</div>
            <div><strong>Teléfono:</strong> ' . ($remito_data['proveedor']['telefono'] ?? 'N/A') . '</div>
        </div>';

        $html .= '<table>
            <thead>
                <tr>
                    <th style="width: 10%">Código</th>
                    <th style="width: 40%">Descripción</th>
                    <th style="width: 15%">Código Barra</th>
                    <th style="width: 10%">Cantidad</th>
                    <th style="width: 12%">Precio Unit.</th>
                    <th style="width: 13%">Subtotal</th>
                </tr>
            </thead>
            <tbody>';

        $total_general = 0;
        foreach ($remito_data['productos'] as $producto) {
            $cantidad = rand(1, 25);
            $precio = $producto['precio_compra'];
            $subtotal = $cantidad * $precio;
            $total_general += $subtotal;

            $html .= '<tr>
                <td>' . $producto['codigo_proveedor'] . '</td>
                <td>' . $producto['nombre'] . '</td>
                <td class="barcode">' . $producto['codigo_barra'] . '</td>
                <td class="number">' . $cantidad . '</td>
                <td class="number">$' . number_format($precio, 2) . '</td>
                <td class="number">$' . number_format($subtotal, 2) . '</td>
            </tr>';
        }

        $html .= '<tr class="total">
                <td colspan="5"><strong>TOTAL GENERAL:</strong></td>
                <td class="number"><strong>$' . number_format($total_general, 2) . '</strong></td>
            </tr>';

        $html .= '</tbody></table>';

        $html .= '<div class="footer">
            <p><strong>Condiciones:</strong></p>
            <ul>
                <li>Verificar mercadería al momento de recepción</li>
                <li>Reclamos dentro de las 24 horas</li>
                <li>Productos sujetos a disponibilidad</li>
            </ul>
            <br>
            <div style="text-align: center;">
                <div>_________________________</div>
                <div>Firma y Sello Receptor</div>
            </div>
        </div>';

        $html .= '</body></html>';

        return $html;
    }
}

// Manejo de acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $generador = new GeneradorRemitosDemo($conexion);

    if (isset($_POST['generar_remito'])) {
        $proveedor_id = $_POST['proveedor_id'];
        $tipo = $_POST['tipo'] ?? 'compra';

        $remito = $generador->generarRemito($proveedor_id, $tipo);
        $html_remito = $generador->generarRemitoHTML($remito);

        // Guardar en archivo para imprimir
        $filename = 'remito_demo_' . $remito['numero'] . '_' . date('Y-m-d_H-i-s') . '.html';
        $filepath = '../../../assets/demo_docs/' . $filename;
        file_put_contents($filepath, $html_remito);

        // Mostrar el remito
        echo $html_remito;
        exit;
    }
}

$generador = new GeneradorRemitosDemo($conexion);
$proveedores = $generador->obtenerProveedores();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Remitos Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .demo-card {
            border-left: 4px solid #007bff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .preview-remito {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            background: #f8f9fa;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-file-invoice"></i> Generador Remitos Demo</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="control_center.php">
                    <i class="fas fa-eye"></i> Centro de Control
                </a>
                <a class="nav-link" href="hp_scanner_monitor.php">
                    <i class="fas fa-scanner"></i> Monitor Scanner
                </a>
                <span class="navbar-text">
                    <i class="fas fa-user"></i> <?php echo $_SESSION['usuario_nombre']; ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-4">
                <div class="card demo-card">
                    <div class="card-header bg-primary text-white">
                        <h6><i class="fas fa-plus-circle"></i> Generar Nuevo Remito</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" target="_blank">
                            <div class="mb-3">
                                <label class="form-label">Proveedor:</label>
                                <select name="proveedor_id" class="form-select" required>
                                    <option value="">Seleccionar proveedor...</option>
                                    <?php foreach ($proveedores as $proveedor): ?>
                                        <option value="<?php echo $proveedor['id']; ?>">
                                            <?php echo $proveedor['codigo'] . ' - ' . $proveedor['razon_social']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tipo de Documento:</label>
                                <select name="tipo" class="form-select">
                                    <option value="compra">Remito de Compra</option>
                                    <option value="inventario">Lista de Inventario</option>
                                </select>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="generar_remito" class="btn btn-primary">
                                    <i class="fas fa-file-plus"></i> Generar Remito
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header bg-info text-white">
                        <h6><i class="fas fa-info-circle"></i> Información</h6>
                    </div>
                    <div class="card-body">
                        <h6>Productos Disponibles:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> 50 productos demo</li>
                            <li><i class="fas fa-check text-success"></i> Códigos EAN-13 reales</li>
                            <li><i class="fas fa-check text-success"></i> 5 proveedores ficticios</li>
                            <li><i class="fas fa-check text-success"></i> 7 categorías</li>
                        </ul>

                        <hr>

                        <h6>Proceso Recomendado:</h6>
                        <ol class="list-group list-group-numbered">
                            <li class="list-group-item">Generar remito</li>
                            <li class="list-group-item">Imprimir documento</li>
                            <li class="list-group-item">Escanear con HP</li>
                            <li class="list-group-item">Procesar en OCR</li>
                            <li class="list-group-item">Verificar precisión</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6><i class="fas fa-list"></i> Productos Demo Disponibles</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $productos_muestra = $generador->obtenerProductosDemo(20);
                        ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th>Código Barra</th>
                                        <th>Proveedor</th>
                                        <th>Precio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos_muestra as $producto): ?>
                                        <tr>
                                            <td><code><?php echo $producto['codigo']; ?></code></td>
                                            <td><?php echo substr($producto['nombre'], 0, 30) . '...'; ?></td>
                                            <td><small><?php echo $producto['codigo_barra']; ?></small></td>
                                            <td><?php echo $producto['proveedor_codigo'] ?? 'N/A'; ?></td>
                                            <td>$<?php echo number_format($producto['precio_compra'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb"></i>
                            <strong>Tip:</strong> Los remitos generados incluirán una selección aleatoria de estos productos
                            con cantidades y precios variables para crear documentos realistas.
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header bg-warning text-dark">
                        <h6><i class="fas fa-cogs"></i> Configuración OCR</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Scanner HP:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-printer text-success"></i> HP Ink Tank 410 conectado</li>
                                    <li><i class="fas fa-wifi text-success"></i> IP: 192.168.0.100</li>
                                    <li><i class="fas fa-folder text-success"></i> Carpeta: scanner_input/</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Procesamiento:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-eye text-primary"></i> OCR automático activo</li>
                                    <li><i class="fas fa-brain text-primary"></i> Matching inteligente</li>
                                    <li><i class="fas fa-check text-primary"></i> Doble control</li>
                                </ul>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="control_center.php" class="btn btn-outline-primary">
                                <i class="fas fa-upload"></i> Centro Upload
                            </a>
                            <a href="hp_scanner_monitor.php" class="btn btn-outline-success">
                                <i class="fas fa-scanner"></i> Monitor Scanner
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
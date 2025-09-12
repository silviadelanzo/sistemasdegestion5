<?php
// Configuración de la base de datos
define('DB_HOST', '45.143.162.54');
define('DB_USER', 'sistemasia_inventpro');
define('DB_PASS', 'Santiago2980%%');
define('DB_NAME', 'sistemasia_inventpro');
define('DB_CHARSET', 'utf8mb4');

function connectDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión a la base de datos: " . $e->getMessage());
    }
}

$pdo = connectDB();

$paises = [];
$monedas = [];
$condiciones_fiscales = [];

// Obtener datos para desplegables
try {
    $stmt = $pdo->query("SELECT id, nombre FROM paises ORDER BY nombre");
    $paises = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT id, nombre, simbolo FROM monedas ORDER BY nombre");
    $monedas = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT id, nombre_condicion FROM condiciones_fiscales ORDER BY nombre_condicion");
    $condiciones_fiscales = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al cargar datos para desplegables: " . $e->getMessage());
}

$message = '';
$message_type = ''; // 'success' or 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_empresa = $_POST['nombre_empresa'] ?? '';
    $fecha_suscripcion = $_POST['fecha_suscripcion'] ?? date('Y-m-d');
    $estado = $_POST['estado'] ?? 'activa';
    $plan = $_POST['plan'] ?? 'basico';
    $pais_id = $_POST['pais_id'] ?? null;
    $moneda_id = $_POST['moneda_id'] ?? null;
    $tipo_identificacion_fiscal = $_POST['tipo_identificacion_fiscal'] ?? '';
    $numero_identificacion_fiscal = $_POST['numero_identificacion_fiscal'] ?? '';
    $telefono_contacto = $_POST['telefono_contacto'] ?? '';
    $direccion_empresa = $_POST['direccion_empresa'] ?? '';
    $email_contacto = $_POST['email_contacto'] ?? '';

    // Validación básica
    if (empty($nombre_empresa)) {
        $message = 'El nombre de la empresa es obligatorio.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO cuentas (
                    nombre_empresa, fecha_suscripcion, estado, plan, 
                    pais_id, moneda_id, tipo_identificacion_fiscal, 
                    numero_identificacion_fiscal, telefono_contacto, 
                    direccion_empresa, email_contacto
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $nombre_empresa, $fecha_suscripcion, $estado, $plan,
                $pais_id, $moneda_id, $tipo_identificacion_fiscal,
                $numero_identificacion_fiscal, $telefono_contacto,
                $direccion_empresa, $email_contacto
            ]);
            $message = 'Cliente registrado exitosamente. ID: ' . $pdo->lastInsertId();
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Error al registrar cliente: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Nuevo Cliente</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); max-width: 600px; margin: auto; }
        h1 { color: #333; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input[type="text"], input[type="email"], input[type="date"], textarea, select { width: calc(100% - 22px); padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        input[type="submit"] { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; }
        input[type="submit"]:hover { background-color: #218838; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registrar Nuevo Cliente</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="nombre_empresa">Nombre de la Empresa:</label>
                <input type="text" id="nombre_empresa" name="nombre_empresa" required>
            </div>

            <div class="form-group">
                <label for="fecha_suscripcion">Fecha de Suscripción:</label>
                <input type="date" id="fecha_suscripcion" name="fecha_suscripcion" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label for="estado">Estado:</label>
                <select id="estado" name="estado">
                    <option value="activa">Activa</option>
                    <option value="suspendida">Suspendida</option>
                    <option value="cancelada">Cancelada</option>
                </select>
            </div>

            <div class="form-group">
                <label for="plan">Plan:</label>
                <select id="plan" name="plan">
                    <option value="basico">Básico</option>
                    <option value="premium">Premium</option>
                    <option value="enterprise">Enterprise</option>
                </select>
            </div>

            <div class="form-group">
                <label for="pais_id">País:</label>
                <select id="pais_id" name="pais_id">
                    <option value="">Seleccione un país</option>
                    <?php foreach ($paises as $pais): ?>
                        <option value="<?php echo $pais['id']; ?>"><?php echo $pais['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="moneda_id">Moneda:</label>
                <select id="moneda_id" name="moneda_id">
                    <option value="">Seleccione una moneda</option>
                    <?php foreach ($monedas as $moneda): ?>
                        <option value="<?php echo $moneda['id']; ?>"><?php echo $moneda['nombre'] . ' (' . $moneda['simbolo'] . ')'; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="tipo_identificacion_fiscal">Tipo de Identificación Fiscal:</label>
                <select id="tipo_identificacion_fiscal" name="tipo_identificacion_fiscal">
                    <option value="">Seleccione tipo</option>
                    <?php foreach ($condiciones_fiscales as $condicion): ?>
                        <option value="<?php echo $condicion['nombre_condicion']; ?>"><?php echo $condicion['nombre_condicion']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="numero_identificacion_fiscal">Número de Identificación Fiscal:</label>
                <input type="text" id="numero_identificacion_fiscal" name="numero_identificacion_fiscal">
            </div>

            <div class="form-group">
                <label for="telefono_contacto">Teléfono de Contacto:</label>
                <input type="text" id="telefono_contacto" name="telefono_contacto">
            </div>

            <div class="form-group">
                <label for="direccion_empresa">Dirección de la Empresa:</label>
                <textarea id="direccion_empresa" name="direccion_empresa"></textarea>
            </div>

            <div class="form-group">
                <label for="email_contacto">Email de Contacto:</label>
                <input type="email" id="email_contacto" name="email_contacto">
            </div>

            <div class="form-group">
                <input type="submit" value="Registrar Cliente">
            </div>
        </form>
    </div>
</body>
</html>
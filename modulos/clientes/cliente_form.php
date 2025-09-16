<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

// --- Lógica de carga de datos ---
$cliente_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$es_edicion = $cliente_id > 0;
$mensaje_error = '';
$mensaje_exito = '';
$cliente_data = [];

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Cargar catálogos para los menús desplegables
    $paises = $pdo->query("SELECT id, nombre, codigo_iso, identificacion_fiscal FROM paises WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $provincias = $pdo->query("SELECT id, nombre FROM provincias WHERE pais_id = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC); // Asumimos pais_id=1 para Argentina
    
    $condStmt = $pdo->query("SELECT id, pais_id, nombre_condicion FROM condiciones_fiscales ORDER BY nombre_condicion");
    $condiciones_por_pais = [];
    foreach ($condStmt as $r) {
        $condiciones_por_pais[(int)$r['pais_id']][] = ['id' => (int)$r['id'], 'nombre' => $r['nombre_condicion']];
    }
    
    $condStmt = $pdo->query("SELECT id, pais_id, nombre_condicion FROM condiciones_fiscales ORDER BY nombre_condicion");
    $condiciones_por_pais = [];
    foreach ($condStmt as $r) {
        $condiciones_por_pais[(int)$r['pais_id']][] = ['id' => (int)$r['id'], 'nombre' => $r['nombre_condicion']];
    }

    // Lógica de carga o creación de cliente
    if ($es_edicion) {
        $stmt = $pdo->prepare("SELECT *, whatsapp, condicion_fiscal_id FROM clientes WHERE id = ? AND eliminado = 0");
        $stmt->execute([$cliente_id]);
        $cliente_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente_data) {
            throw new Exception("Cliente no encontrado.");
        }
    } else {
        // Generar nuevo código de cliente
        $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(codigo, 6) AS UNSIGNED)) as max_correlativo FROM clientes WHERE codigo LIKE 'CLIE-%'");
        $max_correlativo = $stmt->fetchColumn() ?? 0;
        $siguiente_correlativo = $max_correlativo + 1;
        $cliente_data['codigo'] = 'CLIE-' . str_pad($siguiente_correlativo, 7, '0', STR_PAD_LEFT);
    }

    // Lógica de guardado
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recoger todos los datos del formulario
        $cuenta_id = $_SESSION['cuenta_id']; // ¡IMPORTANTE! Asumiendo que esto se guardará en la sesión
        $codigo = $_POST['codigo'] ?? $cliente_data['codigo'];
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $razon_social = trim($_POST['razon_social'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $ciudad = trim($_POST['ciudad'] ?? '');
        $codigo_postal = trim($_POST['codigo_postal'] ?? '');
        
        $pais_id = intval($_POST['pais_id'] ?? 0);
        $pais_iso_stmt = $pdo->prepare("SELECT codigo_iso FROM paises WHERE id = ?");
        $pais_iso_stmt->execute([$pais_id]);
        $pais_iso = $pais_iso_stmt->fetchColumn() ?: '';

        $provincia_id = ($pais_iso === 'ARG' && isset($_POST['provincia_id'])) ? intval($_POST['provincia_id']) : null;
        $provincia_texto = ($pais_iso !== 'ARG') ? trim($_POST['provincia_texto'] ?? '') : null;

        $tipo_identificacion = trim($_POST['tipo_identificacion'] ?? '');
        $numero_identificacion = trim($_POST['numero_identificacion'] ?? '');
        $condicion_fiscal_id = ($pais_iso === 'ARG' && isset($_POST['condicion_fiscal_id'])) ? intval($_POST['condicion_fiscal_id']) : null;
        $activo = isset($_POST['activo']) ? 1 : 0;

        $errors = [];
        if (empty($nombre)) {
            $errors[] = "El nombre es obligatorio.";
        }
        if (empty($apellido)) {
            $errors[] = "El apellido es obligatorio.";
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El formato del email no es válido.";
        }
        if (empty($pais_id)) {
            $errors[] = "El país es obligatorio.";
        }
        if ($pais_iso === 'ARG') {
            if (empty($provincia_id)) {
                $errors[] = "La provincia es obligatoria para Argentina.";
            }
            if (empty($condicion_fiscal_id)) {
                $errors[] = "La condición fiscal es obligatoria para Argentina.";
            }
        }
        if (empty($tipo_identificacion)) {
            $errors[] = "El tipo de identificación es obligatorio.";
        }
        if (empty($numero_identificacion)) {
            $errors[] = "El número de identificación es obligatorio.";
        }

        if (!empty($errors)) {
            $mensaje_error = implode("<br>", $errors);
        } else {
            if ($es_edicion) {
                $sql = "UPDATE clientes SET nombre=?, apellido=?, razon_social=?, email=?, telefono=?, whatsapp=?, direccion=?, ciudad=?, codigo_postal=?, pais_id=?, provincia_id=?, provincia=?, tipo_identificacion=?, numero_identificacion=?, condicion_fiscal_id=?, activo=?, fecha_modificacion=NOW() WHERE id=?";
                $params = [$nombre, $apellido, $razon_social, $email, $telefono, $whatsapp, $direccion, $ciudad, $codigo_postal, $pais_id, $provincia_id, $provincia_texto, $tipo_identificacion, $numero_identificacion, $condicion_fiscal_id, $activo, $cliente_id];
                registrar_auditoria('MODIFICACION_CLIENTE', 'clientes', $cliente_id, "Cliente modificado: " . $nombre . " " . $apellido);
            } else {
                $sql = "INSERT INTO clientes (cuenta_id, codigo, nombre, apellido, razon_social, email, telefono, whatsapp, direccion, ciudad, codigo_postal, pais_id, provincia_id, provincia, tipo_identificacion, numero_identificacion, condicion_fiscal_id, activo, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $params = [$cuenta_id, $codigo, $nombre, $apellido, $razon_social, $email, $telefono, $whatsapp, $direccion, $ciudad, $codigo_postal, $pais_id, $provincia_id, $provincia_texto, $tipo_identificacion, $numero_identificacion, $condicion_fiscal_id, $activo];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            if (!$es_edicion) {
                $cliente_id = $pdo->lastInsertId();
                header("Location: cliente_form.php?id=$cliente_id&mensaje=exito");
                exit;
            }
            $mensaje_exito = "Cliente guardado correctamente.";
            // Recargar datos para mostrar los cambios
            $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
            $stmt->execute([$cliente_id]);
            $cliente_data = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

} catch (Exception $e) {
    $mensaje_error = "Error: " . $e->getMessage();
}

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $es_edicion ? 'Editar' : 'Nuevo'; ?> Cliente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .form-container { max-width: 900px; margin: 30px auto; background: #fff; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,.08); overflow: hidden; }
        .form-header { background: #0d6efd; color: #fff; padding: 16px 20px; }
        .form-body { padding: 25px; }
        .hidden { display: none; }
        .form-label { font-weight: 600; }
        .form-body .form-control, .form-body .form-select { background-color: #e7f5fe !important; }
        .codigo-resaltado { border-color: #0d6efd !important; color: #0d6efd !important; font-weight: bold; }
        .idf-input { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; }
        .idf-help { margin-top: .25rem; font-size: .8125rem; color: #6c757d; }
    </style>
</head>
<body>

<?php include "../../config/navbar_code.php"; ?>

<div class="container form-container">
    <div class="form-header">
        <h4 class="mb-0"><?php echo $es_edicion ? 'Editar' : 'Nuevo'; ?> Cliente</h4>
    </div>
    <div class="form-body">
        <?php if ($mensaje_error): ?><div class="alert alert-danger"><?php echo $mensaje_error; ?></div><?php endif; ?>
        <?php if ($mensaje_exito): ?><div class="alert alert-success"><?php echo h($mensaje_exito); ?></div><?php endif; ?>
        <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'exito'): ?><div class="alert alert-success">Cliente creado correctamente.</div><?php endif; ?>

        <form method="POST" autocomplete="off">
            <input type="hidden" name="codigo" value="<?php echo h($cliente_data['codigo'] ?? ''); ?>">
            
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Código Interno</label>
                    <input type="text" class="form-control codigo-resaltado" value="<?php echo h($cliente_data['codigo'] ?? ''); ?>" readonly>
                </div>
                <div class="col-md-7">
                    <label class="form-label" for="nombre">Nombre y Apellido *</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre(s)" value="<?php echo h($cliente_data['nombre'] ?? ''); ?>" required>
                        <input type="text" class="form-control" id="apellido" name="apellido" placeholder="Apellido(s)" value="<?php echo h($cliente_data['apellido'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label d-block">Activo</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="activo" name="activo" <?php echo (isset($cliente_data['activo']) && $cliente_data['activo'] == 1) || !$es_edicion ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="activo">Sí</label>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="razon_social">Razón Social / Nombre Empresa</label>
                <input type="text" class="form-control" id="razon_social" name="razon_social" value="<?php echo h($cliente_data['razon_social'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label" for="direccion">Dirección (Calle y Número)</label>
                <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo h($cliente_data['direccion'] ?? ''); ?>">
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label" for="pais_id">País</label>
                    <select class="form-select" id="pais_id" name="pais_id" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($paises as $p): ?>
                            <option value="<?php echo (int)$p['id']; ?>" data-iso="<?php echo h($p['codigo_iso']); ?>" data-idfdefault="<?php echo h($p['identificacion_fiscal']); ?>" <?php echo (isset($cliente_data['pais_id']) && $cliente_data['pais_id'] == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo h($p['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="provincia_id">Provincia</label>
                    <select class="form-select" id="provincia_id" name="provincia_id">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($provincias as $pv): ?>
                            <option value="<?php echo (int)$pv['id']; ?>" <?php echo (isset($cliente_data['provincia_id']) && $cliente_data['provincia_id'] == $pv['id']) ? 'selected' : ''; ?>><?php echo h($pv['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" class="form-control hidden mt-1" id="provincia_texto" name="provincia_texto" placeholder="Provincia/Estado (exterior)" value="<?php echo h($cliente_data['provincia'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="ciudad">Localidad</label>
                    <input type="text" class="form-control" id="ciudad" name="ciudad" placeholder="Ingrese la localidad" value="<?php echo h($cliente_data['ciudad'] ?? ''); ?>">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label" for="codigo_postal">C.P.</label>
                    <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" placeholder="Ingrese el código postal" value="<?php echo h($cliente_data['codigo_postal'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="telefono">Teléfono</label>
                    <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo h($cliente_data['telefono'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="whatsapp">Whatsapp</label>
                    <input type="text" class="form-control" id="whatsapp" name="whatsapp" value="<?php echo h($cliente_data['whatsapp'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="email">Mail</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo h($cliente_data['email'] ?? ''); ?>">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label" for="tipo_identificacion">Identificador Fiscal</label>
                    <select class="form-select" id="tipo_identificacion" name="tipo_identificacion">
                         <option value="">Seleccionar...</option>
                        <?php $tipos_doc = ["DNI","CUIT","CUIL","CDI","PASAPORTE"]; foreach ($tipos_doc as $tipo): ?>
                            <option value="<?php echo h($tipo); ?>" <?php echo (isset($cliente_data['tipo_identificacion']) && $cliente_data['tipo_identificacion'] == $tipo) ? 'selected' : ''; ?>><?php echo h($tipo); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="numero_identificacion">Nº Identificación</label>
                    <input type="text" class="form-control idf-input" id="numero_identificacion" name="numero_identificacion" value="<?php echo h($cliente_data['numero_identificacion'] ?? ''); ?>" title="Formato según tipo">
                    <div class="idf-help text-end" id="ayuda_id_fiscal"></div>
                </div>
                <div class="col-md-5" id="wrap_cond_fiscal">
                    <label class="form-label" for="condicion_fiscal_id">Condición Fiscal</label>
                    <select class="form-select" id="condicion_fiscal_id" name="condicion_fiscal_id">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($condiciones_por_pais as $paisId => $conds): ?>
                            <?php foreach ($conds as $c): ?>
                                <option data-pais="<?php echo (int)$paisId; ?>" value="<?php echo (int)$c['id']; ?>" <?php echo (isset($cliente_data['condicion_fiscal_id']) && $cliente_data['condicion_fiscal_id'] == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo h($c['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="clientes.php" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const paisSelect = document.getElementById('pais_id');
    const provinciaSelect = document.getElementById('provincia_id');
    const provinciaTxt = document.getElementById('provincia_texto');
    const wrapCondFiscal = document.getElementById('wrap_cond_fiscal');
    const condFiscalSelect = document.getElementById('condicion_fiscal_id');
    const idfTipoSelect = document.getElementById('tipo_identificacion');
    const idFiscalInput = document.getElementById('numero_identificacion');
    const ayudaIdFiscal = document.getElementById('ayuda_id_fiscal');

    function toggleSegunPais() {
        const selectedOption = paisSelect.options[paisSelect.selectedIndex];
        const iso = selectedOption ? selectedOption.dataset.iso : '';
        const paisId = paisSelect.value;
        const esAR = (iso === 'ARG');

        provinciaSelect.classList.toggle('hidden', !esAR);
        provinciaTxt.classList.toggle('hidden', esAR);
        wrapCondFiscal.classList.toggle('hidden', !esAR);

        if (!esAR) {
            provinciaSelect.value = '';
            condFiscalSelect.value = '';
        }
        
        // Filtrar condiciones fiscales
        [...condFiscalSelect.options].forEach(opt => {
            if (opt.value === '') return;
            const p = opt.getAttribute('data-pais');
            const visible = String(paisId) === p;
            opt.classList.toggle('hidden', !visible);
            if (!visible && condFiscalSelect.value === opt.value) {
                condFiscalSelect.value = '';
            }
        });
    }

    function filtrarCondicionesPorPais(paisId) {
        [...condFiscalSelect.options].forEach(opt => {
            if (opt.value === '') return;
            const p = opt.getAttribute('data-pais');
            const visible = String(paisId) === p;
            opt.classList.toggle('hidden', !visible);
            opt.disabled = !visible;
            if (!visible && condFiscalSelect.value === opt.value) condFiscalSelect.value = '';
        });
    }

    provinciaSelect.classList.toggle('hidden', !esAR);
        provinciaTxt.classList.toggle('hidden', esAR);
        wrapCondFiscal.classList.toggle('hidden', !esAR);

        if (!esAR) {
            provinciaSelect.value = '';
            condFiscalSelect.value = '';
        }
        
        // Filtrar condiciones fiscales
        filtrarCondicionesPorPais(paisId);
    }

    function filtrarCondicionesPorPais(paisId) {
        [...condFiscalSelect.options].forEach(opt => {
            if (opt.value === '') return;
            const p = opt.getAttribute('data-pais');
            const visible = String(paisId) === p;
            opt.classList.toggle('hidden', !visible);
            opt.disabled = !visible;
            if (!visible && condFiscalSelect.value === opt.value) condFiscalSelect.value = '';
        });
    }

    function setMaskAndWidth() {
        const tipo = idfTipoSelect.value;
        let val = idFiscalInput.value;
        let masked = val;
        let placeholder = '';
        let helpText = '';

        switch (tipo) {
            case 'CUIT':
            case 'CUIL':
                helpText = 'Formato: XX-XXXXXXXX-X';
                masked = val.replace(/\D+/g, '').slice(0, 11);
                if (masked.length >= 3) masked = masked.slice(0, 2) + '-' + masked.slice(2);
                if (masked.length >= 12) masked = masked.slice(0, 11) + '-' + masked.slice(11);
                placeholder = '20-12345678-9';
                break;
            default:
                helpText = 'Solo números';
                masked = val.replace(/\D+/g, '').slice(0, 11);
        }
        
        ayudaIdFiscal.textContent = helpText;
        idFiscalInput.placeholder = placeholder;
        idFiscalInput.value = masked;
    }

    paisSelect.addEventListener('change', toggleSegunPais);
    idfTipoSelect.addEventListener('change', setMaskAndWidth);
    idFiscalInput.addEventListener('input', setMaskAndWidth);

    // Init
    toggleSegunPais();
    setMaskAndWidth();

    paisSelect.addEventListener('change', toggleSegunPais);
    idfTipoSelect.addEventListener('change', setMaskAndWidth);
    idFiscalInput.addEventListener('input', setMaskAndWidth);

    // Init
    toggleSegunPais();
    setMaskAndWidth();
});
</script>
</body>
</html>

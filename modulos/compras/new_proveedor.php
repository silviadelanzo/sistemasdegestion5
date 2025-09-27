<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$mensaje_error = '';
$prov = null;
$edit_mode = false;
$page_title = 'Nuevo Proveedor';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $edit_mode = true;
    $page_title = 'Editar Proveedor';
}

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Países, Provincias, Condiciones Fiscales (common for both modes)
    $paises = $pdo->query("SELECT id, nombre, codigo_iso, identificacion_fiscal FROM paises WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $provincias = $pdo->query("SELECT id, nombre FROM provincias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $condStmt = $pdo->query("SELECT id, pais_id, nombre_condicion FROM condiciones_fiscales ORDER BY nombre_condicion");
    $condiciones_por_pais = [];
    foreach ($condStmt as $r) {
        $condiciones_por_pais[(int)$r['pais_id']][] = ['id'=>(int)$r['id'],'nombre'=>$r['nombre_condicion']];
    }

    if ($edit_mode) {
        $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $prov = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$prov) {
            throw new Exception("Proveedor no encontrado.");
        }
    } else {
        // Create mode: generate new provider code
        $sql_codigo = "SELECT codigo FROM proveedores WHERE codigo LIKE 'PROV-%' ORDER BY CAST(SUBSTRING(codigo, 6) AS UNSIGNED) DESC LIMIT 1";
        $ultimo_codigo = $pdo->query($sql_codigo)->fetchColumn();
        $numero = $ultimo_codigo ? (intval(substr($ultimo_codigo, 5)) + 1) : 1;
        $nuevo_codigo = 'PROV-' . str_pad($numero, 7, '0', STR_PAD_LEFT);
        $prov = [
            'codigo' => $nuevo_codigo,
            'contacto_nombre' => '',
            'razon_social' => '',
            'direccion' => '',
            'pais_id' => 1, // Default to Argentina
            'provincia_id' => null,
            'provincia' => '',
            'ciudad' => '',
            'codigo_postal' => '',
            'telefono' => '',
            'whatsapp' => '',
            'identificacion_fiscal' => '',
            'cuit' => '',
            'condicion_fiscal_id' => null,
            'email' => '',
            'sitio_web' => ''
        ];
    }
} catch (Throwable $e) {
    error_log("Error inicial: " . $e->getMessage());
    $mensaje_error = "No se pudieron cargar los datos: " . $e->getMessage();
}

// Unified Save Logic (INSERT/UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($mensaje_error)) {
    try {
        // Common fields from POST
        $contacto      = trim($_POST['contacto'] ?? '');
        $razon_social  = trim($_POST['razon_social'] ?? '');
        $direccion     = trim($_POST['direccion'] ?? '');
        $pais_id       = intval($_POST['pais'] ?? 0);
        $pais_iso      = trim($_POST['pais_iso'] ?? '');
        $provincia_id  = isset($_POST['provincia_id']) && $_POST['provincia_id'] !== '' ? intval($_POST['provincia_id']) : null;
        $provincia_tx  = trim($_POST['provincia_texto'] ?? '');
        $ciudad        = trim($_POST['ciudad'] ?? '');
        $codigo_postal = trim($_POST['codigo_postal'] ?? '');
        $telefono      = trim($_POST['telefono'] ?? '');
        $whatsapp      = trim($_POST['whatsapp'] ?? '');
        $idf_tipo      = trim($_POST['idf_tipo'] ?? '');
        $id_fiscal     = trim($_POST['id_fiscal'] ?? '');
        $id_fiscal_digits = preg_replace('/\W+/u', '', $id_fiscal);
        $cond_fisc_id  = isset($_POST['condicion_fiscal_id']) && $_POST['condicion_fiscal_id'] !== '' ? intval($_POST['condicion_fiscal_id']) : null;
        $email         = trim($_POST['email'] ?? '');
        $sitio_web     = trim($_POST['sitio_web'] ?? '');

        if ($razon_social === '') throw new Exception("La Razón Social es obligatoria");
        if ($pais_id <= 0)        throw new Exception("Debe seleccionar un País");

        if ($pais_iso !== 'ARG') { // Exterior
            $provincia_id = null;
            $cond_fisc_id = null;
        } else {
            $provincia_tx = null;
        }

        $params = [
            ':razon_social'              => $razon_social,
            ':contacto_nombre'           => $contacto ?: null,
            ':identificacion_fiscal'     => $idf_tipo ?: null,
            ':cuit'                      => $id_fiscal ?: null,
            ':nro_identificacion_fiscal' => $id_fiscal_digits ?: null,
            ':pais_id'                   => $pais_id,
            ':condicion_fiscal_id'       => $cond_fisc_id,
            ':provincia_id'              => $provincia_id,
            ':provincia'                 => $provincia_tx ?: null,
            ':ciudad'                    => $ciudad ?: null,
            ':codigo_postal'             => $codigo_postal ?: null,
            ':direccion'                 => $direccion ?: null,
            ':telefono'                  => $telefono ?: null,
            ':whatsapp'                  => $whatsapp ?: null,
            ':email'                     => $email ?: null,
            ':sitio_web'                 => $sitio_web ?: null
        ];

        if ($edit_mode) {
            $params[':id'] = $id;
            $sql = "UPDATE proveedores SET
                        razon_social = :razon_social,
                        contacto_nombre = :contacto_nombre,
                        identificacion_fiscal = :identificacion_fiscal,
                        cuit = :cuit,
                        nro_identificacion_fiscal = :nro_identificacion_fiscal,
                        pais_id = :pais_id,
                        condicion_fiscal_id = :condicion_fiscal_id,
                        provincia_id = :provincia_id,
                        provincia = :provincia,
                        ciudad = :ciudad,
                        codigo_postal = :codigo_postal,
                        direccion = :direccion,
                        telefono = :telefono,
                        whatsapp = :whatsapp,
                        email = :email,
                        sitio_web = :sitio_web
                    WHERE id = :id
                    LIMIT 1";
        } else {
            $params[':cuenta_id'] = $_SESSION['cuenta_id'];
            $params[':codigo'] = $prov['codigo'];
            $params[':activo'] = 1;
            $sql = "INSERT INTO proveedores
                (cuenta_id, codigo, razon_social, contacto_nombre,
                 identificacion_fiscal, cuit, nro_identificacion_fiscal,
                 pais_id, condicion_fiscal_id,
                 provincia_id, provincia, ciudad, codigo_postal,
                 direccion, telefono, whatsapp, email, sitio_web,
                 activo, fecha_creacion)
                VALUES
                (:cuenta_id, :codigo, :razon_social, :contacto_nombre,
                 :identificacion_fiscal, :cuit, :nro_identificacion_fiscal,
                 :pais_id, :condicion_fiscal_id,
                 :provincia_id, :provincia, :ciudad, :codigo_postal,
                 :direccion, :telefono, :whatsapp, :email, :sitio_web,
                 :activo, NOW())";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $msg = $edit_mode ? 'proveedor_actualizado' : 'proveedor_creado';
        header("Location: proveedores.php?msg=$msg");
        exit;

    } catch (Throwable $e) {
        $mensaje_error = "Error al guardar: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?php echo h($page_title); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.10.1/build/css/intlTelInput.css">
  <style>
    body{background:#f8f9fa}
    .form-container{max-width:900px;margin:30px auto;background:#fff;border-radius:10px;box-shadow:0 0 15px rgba(0,0,0,.08);overflow:hidden}
    .form-header{background:#0d6efd;color:#fff;padding:16px 20px}
    .hidden{display:none}
    .form-control, .form-select { background-color: #e7f5fe !important; }
    .codigo-auto{background:#e7f5fe !important; border:2px solid #0d6efd; color:#0d6efd; font-weight:600}
    .idf-input{ width: 26ch; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; }
    .idf-help{ margin-top:.25rem; font-size:.8125rem; color:#6c757d; }
  </style>
</head>
<body>

<?php include "../../config/navbar_code.php"; ?>

<div class="container form-container">
  <div class="form-header">
    <h4 class="mb-0"><?php echo h($page_title); ?></h4>
  </div>
  <div class="p-4">
    <?php if (!empty($mensaje_error)): ?>
      <div class="alert alert-danger"><?php echo h($mensaje_error); ?></div>
    <?php endif; ?>

    <?php if ($prov): ?>
    <form id="form-proveedor" method="POST" autocomplete="off">
      <?php if ($edit_mode): ?>
        <input type="hidden" name="id" value="<?php echo (int)$prov['id']; ?>">
      <?php endif; ?>
      <input type="hidden" id="pais_iso" name="pais_iso" value="">

      <div class="row g-3 mb-2">
        <div class="col-md-3">
          <label class="form-label">Código</label>
          <input type="text" class="form-control codigo-auto" value="<?php echo h($prov['codigo']); ?>" readonly>
        </div>
        <div class="col-md-9">
          <label class="form-label" for="contacto">Contacto</label>
          <input type="text" class="form-control" id="contacto" name="contacto" value="<?php echo h($prov['contacto_nombre']); ?>">
        </div>
      </div>

      <div class="mb-2">
        <label class="form-label" for="razon_social">Razón Social</label>
        <input type="text" class="form-control" id="razon_social" name="razon_social" required value="<?php echo h($prov['razon_social']); ?>">
      </div>

      <div class="mb-2">
        <label class="form-label" for="direccion">Dirección (Calle y Número)</label>
        <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo h($prov['direccion']); ?>">
      </div>

      <div class="row g-3 mb-2">
        <div class="col-md-4">
          <label class="form-label" for="pais">País</label>
          <select class="form-select" id="pais" name="pais" required>
            <option value="">-- Seleccionar --</option>
            <?php foreach ($paises as $p): ?>
              <option value="<?php echo (int)$p['id']; ?>"
                      data-iso="<?php echo h($p['codigo_iso']); ?>"
                      data-idfdefault="<?php echo h($p['identificacion_fiscal']); ?>"
                      <?php echo ((int)$prov['pais_id'] === (int)$p['id']) ? 'selected' : ''; ?>>
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
              <option value="<?php echo (int)$pv['id']; ?>"
                <?php echo ((int)$prov['provincia_id'] === (int)$pv['id']) ? 'selected' : ''; ?>>
                <?php echo h($pv['nombre']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="text" class="form-control mt-1 <?php echo ((int)$prov['pais_id'] == 1) ? 'hidden' : ''; ?>"
                 id="provincia_texto" name="provincia_texto" placeholder="Provincia/Estado (exterior)"
                 value="<?php echo h($prov['provincia']); ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label" for="ciudad">Localidad</label>
          <input type="text" class="form-control" id="ciudad" name="ciudad" placeholder="Ingrese la localidad"
                 value="<?php echo h($prov['ciudad']); ?>">
        </div>
      </div>

      <div class="row g-3 mb-2">
        <div class="col-md-4">
          <label class="form-label" for="codigo_postal">C.P.</label>
          <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" placeholder="Ingrese el código postal"
                 value="<?php echo h($prov['codigo_postal']); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="telefono">Teléfono</label>
          <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo h($prov['telefono']); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="whatsapp">Whatsapp</label>
          <input type="tel" class="form-control" id="whatsapp" name="whatsapp" value="<?php echo h($prov['whatsapp']); ?>">
        </div>
      </div>

      <div class="row g-3 mb-2">
        <div class="col-md-4">
          <label class="form-label" for="idf_tipo">Identificador Fiscal</label>
          <select class="form-select" id="idf_tipo" name="idf_tipo">
            <option value="">Seleccionar...</option>
            <?php
              $tipos_doc = ["DNI","NIE","CIF","CUIT","RUC","RFC","CNPJ","CPF","NIT","CI","CEDULA","DUI","RTN","RNC","RIF","PASAPORTE"];
              foreach ($tipos_doc as $tipo) {
                $sel = (strcasecmp($prov['identificacion_fiscal'] ?? '', $tipo) === 0) ? 'selected' : '';
                echo '<option value="'.h($tipo).'" '.$sel.'>'.h($tipo).'</option>';
              }
            ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="id_fiscal">Nº Identificación</label>
          <input type="text" class="form-control idf-input" id="id_fiscal" name="id_fiscal"
                 value="<?php echo h($prov['cuit']); ?>" placeholder="20-10129267-4" title="Formato según tipo">
          <div class="idf-help text-end" id="ayuda_id_fiscal"></div>
        </div>

        <div class="col-md-5" id="wrap_cond_fiscal">
          <label class="form-label" for="condicion_fiscal_id">Condición Fiscal</label>
          <select class="form-select" id="condicion_fiscal_id" name="condicion_fiscal_id">
            <option value="">-- Seleccionar --</option>
            <?php foreach ($condiciones_por_pais as $paisId => $conds): ?>
              <?php foreach ($conds as $c): ?>
                <option data-pais="<?php echo (int)$paisId; ?>" value="<?php echo (int)$c['id']; ?>"
                  <?php echo ((int)($prov['condicion_fiscal_id'] ?? 0) === (int)$c['id']) ? 'selected' : ''; ?>>
                  <?php echo h($c['nombre']); ?>
                </option>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row g-3 mb-3 mt-1">
        <div class="col-md-6">
          <label class="form-label" for="email">Mail</label>
          <input type="email" class="form-control" id="email" name="email" value="<?php echo h($prov['email']); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="sitio_web">Web</label>
          <input type="text" class="form-control" id="sitio_web" name="sitio_web" placeholder="https://" value="<?php echo h($prov['sitio_web']); ?>">
        </div>
      </div>

      <div class="mt-2">
        <a href="proveedores.php" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Guardar Cambios' : 'Guardar Proveedor'; ?></button>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.10.1/build/js/intlTelInput.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const telefonoInput = document.querySelector("#telefono");
    const whatsappInput = document.querySelector("#whatsapp");
    const form = document.querySelector("#form-proveedor");

    const itiTelefono = window.intlTelInput(telefonoInput, {
        utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@25.10.1/build/js/utils.js",
        initialCountry: "auto",
        geoIpLookup: cb => fetch("https://ipapi.co/json").then(r => r.json()).then(d => cb(d.country_code)).catch(() => cb("ar")),
    });

    const itiWhatsapp = window.intlTelInput(whatsappInput, {
        utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@25.10.1/build/js/utils.js",
        initialCountry: "auto",
        geoIpLookup: cb => fetch("https://ipapi.co/json").then(r => r.json()).then(d => cb(d.country_code)).catch(() => cb("ar")),
    });

    form.addEventListener("submit", e => {
        if (telefonoInput.value.trim()) {
            telefonoInput.value = itiTelefono.getNumber();
        }
        if (whatsappInput.value.trim()) {
            whatsappInput.value = itiWhatsapp.getNumber();
        }
    });

    // Resto de tu script para países, etc.
    const $pais = document.getElementById('pais');
    const $paisIso = document.getElementById('pais_iso');
    const $provinciaSelect = document.getElementById('provincia_id');
    const $provinciaTxt = document.getElementById('provincia_texto');
    const $wrapCond = document.getElementById('wrap_cond_fiscal');
    const $condFiscal = document.getElementById('condicion_fiscal_id');
    const $idfTipo = document.getElementById('idf_tipo');
    const $idFiscal = document.getElementById('id_fiscal');
    const $ayudaId = document.getElementById('ayuda_id_fiscal');

    function onPaisChange(init=false) {
      const opt = $pais.selectedOptions[0];
      const iso = opt ? (opt.dataset.iso || '') : '';
      const paisId = opt ? parseInt(opt.value || '0', 10) : 0;
      const tipoDefault = opt ? (opt.dataset.idfdefault || '') : '';

      $paisIso.value = iso;
      
      const esAR = (iso === 'ARG');
      $provinciaSelect.classList.toggle('hidden', !esAR);
      $provinciaTxt.classList.toggle('hidden', esAR);
      $wrapCond.classList.toggle('hidden', !esAR);

      [...$condFiscal.options].forEach(opt => {
        if (opt.value === '') return;
        const p = opt.getAttribute('data-pais');
        const visible = String(paisId) === p;
        opt.classList.toggle('hidden', !visible);
        opt.disabled = !visible;
        if (!visible && $condFiscal.value === opt.value) $condFiscal.value = '';
      });

      if (!init || !$idfTipo.value) {
        if (tipoDefault) {
          const match = [...$idfTipo.options].find(o => o.value.toUpperCase() === tipoDefault.toUpperCase());
          $idfTipo.value = match ? match.value : '';
        } else {
          $idfTipo.value = '';
        }
      }
      onTipoChange();
    }

    function onTipoChange(){
      $idFiscal.value = setMaskAndWidth($idfTipo.value, $idFiscal.value);
    }

    function setMaskAndWidth(tipo, val){
      let masked = val;
      let placeholder = '';
      const masks = {
          CUIT: v => { v=v.replace(/\D+/g,'').slice(0,11); if(v.length>=3)v=v.slice(0,2)+'-'+v.slice(2); if(v.length>=12)v=v.slice(0,11)+'-'+v.slice(11); return v; },
          CPF: v => { v=v.replace(/\D+/g,'').slice(0,11); if(v.length>3)v=v.slice(0,3)+'.'+v.slice(3); if(v.length>7)v=v.slice(0,7)+'.'+v.slice(7); if(v.length>11)v=v.slice(0,11)+'-'+v.slice(11); return v; },
          CNPJ: v => { v=v.replace(/\D+/g,'').slice(0,14); if(v.length>2)v=v.slice(0,2)+'.'+v.slice(2); if(v.length>6)v=v.slice(0,6)+'.'+v.slice(6); if(v.length>10)v=v.slice(0,10)+'/'+v.slice(10); if(v.length>15)v=v.slice(0,15)+'-'+v.slice(15); return v; },
          RUT: v => { v=v.replace(/\D+/g,'').slice(0,9); if(v.length>2)v=v.slice(0,2)+'.'+v.slice(2); if(v.length>6)v=v.slice(0,6)+'.'+v.slice(6); if(v.length>10)v=v.slice(0,10)+'-'+v.slice(10); return v; },
          RFC: v => v.toUpperCase().replace(/[^A-Z0-9]/g,'').slice(0,13),
          DEFAULT: v => v.replace(/\D+/g,'').slice(0,13)
      };
      const details = {
          CUIT: { help: 'Formato: XX-XXXXXXX-X', ph: '20-10129267-4', w: 26 },
          CPF: { help: 'Formato: 000.000.000-00', ph: '000.000.000-00', w: 22 },
          CNPJ: { help: 'Formato: 00.000.000/0000-00', ph: '00.000.000/0000-00', w: 26 },
          RUT: { help: 'Formato: 12.345.678-9', ph: '12.345.678-9', w: 22 },
          RFC: { help: 'Alfanumérico 12-13', ph: 'ABCD010203XYZ', w: 24 },
          RUC: { help: 'Numérico', ph: '1234567890123', w: 24 },
          DEFAULT: { help: tipo ? 'Numérico' : '', ph: '', w: 26 }
      };
      const d = details[tipo] || details.DEFAULT;
      const m = masks[tipo] || masks.DEFAULT;
      $ayudaId.textContent = d.help;
      $idFiscal.placeholder = d.ph;
      $idFiscal.style.width = d.w + 'ch';
      return m(val);
    }

    $idFiscal.addEventListener('input', () => onTipoChange());
    $idfTipo.addEventListener('change', onTipoChange);
    $pais.addEventListener('change', () => onPaisChange(false));
    onPaisChange(true);
});
</script>
</body>
</html>

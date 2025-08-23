<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$mensaje_error = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$origen = isset($_GET['origen']) ? trim($_GET['origen']) : (isset($_POST['origen']) ? trim($_POST['origen']) : '');

if ($id <= 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
  die('ID de proveedor inválido.');
}

try {
  $pdo = conectarDB();
  $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

  // Países
  $paises = $pdo->query("SELECT id, nombre, codigo_iso, identificacion_fiscal FROM paises WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
  $paisById = [];
  $idPaisARG = null;
  foreach ($paises as $p) {
    $paisById[(int)$p['id']] = $p;
    if (strtoupper($p['codigo_iso'] ?? '') === 'ARG') {
      $idPaisARG = (int)$p['id'];
    }
  }

  // Provincias (Argentina)
  $provincias = $pdo->query("SELECT id, nombre FROM provincias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

  // Condiciones fiscales por país
  $condStmt = $pdo->query("SELECT id, pais_id, nombre_condicion FROM condiciones_fiscales ORDER BY nombre_condicion");
  $condiciones_por_pais = [];
  foreach ($condStmt as $r) {
    $condiciones_por_pais[(int)$r['pais_id']][] = ['id'=>(int)$r['id'],'nombre'=>$r['nombre_condicion']];
  }

  // Cargar proveedor actual
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$proveedor) {
      throw new Exception("Proveedor no encontrado.");
    }
  }

} catch (Throwable $e) {
  error_log("Error DB (carga inicial): " . $e->getMessage());
  $mensaje_error = "No se pudieron cargar los datos.";
  $paises = $provincias = $condiciones_por_pais = [];
  $proveedor = null;
}

// Actualizar (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) throw new Exception("ID inválido.");

    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pais_id = (int)($_POST['pais'] ?? 0);
    $pais_iso = trim($_POST['pais_iso'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $razon_social = trim($_POST['razon_social'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $provincia_id = isset($_POST['provincia_id']) && $_POST['provincia_id'] !== '' ? (int)$_POST['provincia_id'] : null;
    $provincia_tx = trim($_POST['provincia_texto'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? ( $_POST['ciudad_texto'] ?? '' ));
    $codigo_postal = trim($_POST['codigo_postal'] ?? ( $_POST['cp_ext'] ?? '' ));
    $telefono = trim($_POST['telefono'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $idf_tipo = trim($_POST['idf_tipo'] ?? '');
    $id_fiscal = trim($_POST['id_fiscal'] ?? '');
    $id_fiscal_digits = preg_replace('/\W+/u', '', $id_fiscal);
    $cond_fisc_id = isset($_POST['condicion_fiscal_id']) && $_POST['condicion_fiscal_id'] !== '' ? (int)$_POST['condicion_fiscal_id'] : null;
    $email = trim($_POST['email'] ?? '');
    $sitio_web = trim($_POST['sitio_web'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($razon_social === '') throw new Exception("La Razón Social es obligatoria");
    if ($pais_id <= 0) throw new Exception("Debe seleccionar un País");

    if ($pais_iso !== 'ARG') {
      $provincia_id = null;
      $cond_fisc_id = null;
    } else {
      $provincia_tx = null;
    }

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
      sitio_web = :sitio_web,
      activo = :activo
      WHERE id = :id
      LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':razon_social' => $razon_social,
      ':contacto_nombre' => $contacto ?: null,
      ':identificacion_fiscal' => $idf_tipo ?: null,
      ':cuit' => $id_fiscal ?: null,
      ':nro_identificacion_fiscal' => $id_fiscal_digits ?: null,
      ':pais_id' => $pais_id,
      ':condicion_fiscal_id' => $cond_fisc_id,
      ':provincia_id' => $provincia_id,
      ':provincia' => $provincia_tx ?: null,
      ':ciudad' => $ciudad ?: null,
      ':codigo_postal' => $codigo_postal ?: null,
      ':direccion' => $direccion ?: null,
      ':telefono' => $telefono ?: null,
      ':whatsapp' => $whatsapp ?: null,
      ':email' => $email ?: null,
      ':sitio_web' => $sitio_web ?: null,
      ':activo' => $activo,
      ':id' => $id
    ]);

    if ($origen === 'proveedores') {
      header("Location: proveedores.php?success=" . urlencode("Proveedor actualizado exitosamente"));
    } else {
      header("Location: proveedor_detalle.php?id=" . $id . "&success=" . urlencode("Proveedor actualizado"));
    }
    exit;

  } catch (Throwable $e) {
    $mensaje_error = "Error al actualizar: " . $e->getMessage();
    try {
      $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = :id LIMIT 1");
      $stmt->execute([':id' => $id]);
      $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e2) { /* ignore */ }
  }
}

if (empty($proveedor)) {
  $proveedor = [
    'id' => $id,
    'codigo' => '',
    'contacto_nombre' => '',
    'razon_social' => '',
    'direccion' => '',
    'pais_id' => '',
    'provincia_id' => '',
    'provincia' => '',
    'ciudad' => '',
    'codigo_postal' => '',
    'telefono' => '',
    'whatsapp' => '',
    'email' => '',
    'sitio_web' => '',
    'identificacion_fiscal' => '',
    'cuit' => '',
    'nro_identificacion_fiscal' => '',
    'condicion_fiscal_id' => '',
    'activo' => 1
  ];
}

// Selección inicial de país
$selectedPaisId = (int)($proveedor['pais_id'] ?? 0);
$selectedPaisISO = ($selectedPaisId && isset($paisById[$selectedPaisId])) ? ($paisById[$selectedPaisId]['codigo_iso'] ?? '') : '';

// Inferir Argentina si no hay país pero hay CUIT con formato
if (!$selectedPaisId) {
  $cuitCand = trim($proveedor['cuit'] ?? '');
  if ($cuitCand !== '' && $idPaisARG) {
    // Si parece CUIT (XX-XXXXXXXX-X), forzamos ARG visualmente
    if (preg_match('/^\d{2}-\d{7,8}-\d$/', $cuitCand)) {
      $selectedPaisId = $idPaisARG;
      $selectedPaisISO = 'ARG';
    }
  }
}

$selectedCondId = (int)($proveedor['condicion_fiscal_id'] ?? 0);

// Tipo de ID fiscal
$tipoDocActual = trim($proveedor['identificacion_fiscal'] ?? '');
if ($tipoDocActual === '' && $selectedPaisISO === 'ARG') {
  $tipoDocActual = 'CUIT';
}

// Valor mostrado en el campo numérico
$idFiscalValor = trim($proveedor['cuit'] ?? '');
if ($idFiscalValor === '') {
  $idFiscalValor = trim($proveedor['nro_identificacion_fiscal'] ?? '');
}
if ($idFiscalValor === '' && preg_match('/^\d{2}-\d{7,8}-\d$/', trim($proveedor['identificacion_fiscal'] ?? ''))) {
  $idFiscalValor = trim($proveedor['identificacion_fiscal']);
}

$cancelUrl = ($origen === 'proveedores') ? 'proveedores.php' : 'proveedor_detalle.php?id=' . (int)$proveedor['id'];

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Proveedor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body{background:#f8f9fa}
    .form-container{max-width:900px;margin:30px auto;background:#fff;border-radius:10px;box-shadow:0 0 15px rgba(0,0,0,.08);overflow:hidden}
    .form-header{background:#0d6efd;color:#fff;padding:16px 20px}
    .hidden{display:none}
    .suggest{list-style:none; padding:6px; margin:0; border:1px solid #ccc; max-height:220px; overflow:auto; position:absolute; width:100%; background:#fff; z-index:10}
    .suggest li{padding:6px; cursor:pointer}
    .suggest li:hover{background:#f3f3f3}
    .codigo-auto{background:#f8f9fa; border:2px solid #6c757d; color:#495057; font-weight:600}
    .idf-input{
      width: 26ch;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    }
    .idf-help{ margin-top:.25rem; font-size:.8125rem; color:#6c757d; }
  </style>
</head>
<body>

<?php include "../../config/navbar_code.php"; ?>

<div class="container form-container">
  <div class="form-header">
    <h4 class="mb-0">Editar Proveedor</h4>
  </div>
  <div class="p-4">
    <?php if (!empty($mensaje_error)): ?>
      <div class="alert alert-danger"><?php echo h($mensaje_error); ?></div>
    <?php endif; ?>

    <form id="form-editar-proveedor" method="POST" autocomplete="off">
      <input type="hidden" name="id" value="<?php echo (int)$proveedor['id']; ?>">
      <input type="hidden" id="pais_iso" name="pais_iso" value="<?php echo h($selectedPaisISO); ?>">
      <input type="hidden" id="codigo_postal_hidden" name="codigo_postal" value="<?php echo h($proveedor['codigo_postal']); ?>">
      <input type="hidden" name="origen" value="<?php echo h($origen); ?>">

      <!-- 1) Código (readonly), Contacto, Activo -->
      <div class="row g-3 mb-2">
        <div class="col-md-3">
          <label class="form-label">Código</label>
          <input type="text" class="form-control codigo-auto" value="<?php echo h($proveedor['codigo']); ?>" readonly>
        </div>
        <div class="col-md-7">
          <label class="form-label" for="contacto">Contacto</label>
          <input type="text" class="form-control" id="contacto" name="contacto" value="<?php echo h($proveedor['contacto_nombre']); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label d-block">Activo</label>
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" id="activo" name="activo" <?php echo !empty($proveedor['activo']) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="activo">Sí</label>
          </div>
        </div>
      </div>

      <!-- 2) Razón Social -->
      <div class="mb-2">
        <label class="form-label" for="razon_social">Razón Social</label>
        <input type="text" class="form-control" id="razon_social" name="razon_social" value="<?php echo h($proveedor['razon_social']); ?>" required>
      </div>

      <!-- 3) Dirección -->
      <div class="mb-2">
        <label class="form-label" for="direccion">Dirección (Calle y Número)</label>
        <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo h($proveedor['direccion']); ?>">
      </div>

      <!-- 4) País, Provincia, Localidad -->
      <div class="row g-3 mb-2">
        <div class="col-md-4">
          <label class="form-label" for="pais">País</label>
          <select class="form-select" id="pais" name="pais" required>
            <option value="">-- Seleccionar --</option>
            <?php foreach ($paises as $p): ?>
              <option value="<?php echo (int)$p['id']; ?>"
                data-iso="<?php echo h($p['codigo_iso']); ?>"
                data-idfdefault="<?php echo h($p['identificacion_fiscal']); ?>"
                <?php
                  $sel = ((int)$p['id'] === (int)$proveedor['pais_id']) ||
                         (!$proveedor['pais_id'] && strtoupper($p['codigo_iso'])==='ARG' && $selectedPaisISO==='ARG');
                  echo $sel ? 'selected' : '';
                ?>>
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
                <?php echo ((int)$pv['id'] === (int)$proveedor['provincia_id']) ? 'selected' : ''; ?>>
                <?php echo h($pv['nombre']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="text" class="form-control hidden mt-1" id="provincia_texto" name="provincia_texto" placeholder="Provincia/Estado (exterior)"
                 value="<?php echo h($proveedor['provincia']); ?>">
        </div>

        <div class="col-md-4 position-relative">
          <label class="form-label" for="localidad_buscar">Localidad</label>
          <input type="text" class="form-control" id="localidad_buscar" placeholder="Escribí al menos 2 letras..." value="<?php echo h($proveedor['ciudad']); ?>">
          <ul id="sugerencias" class="suggest hidden"></ul>
          <input type="hidden" id="localidad" name="ciudad" value="<?php echo h($proveedor['ciudad']); ?>">
          <input type="text" class="form-control hidden mt-1" id="ciudad_texto" name="ciudad_texto" placeholder="Ciudad (exterior)" value="<?php echo h($proveedor['ciudad']); ?>">
        </div>
      </div>

      <!-- 5) C.P., Teléfono, Whatsapp -->
      <div class="row g-3 mb-2">
        <div class="col-md-4">
          <label class="form-label" for="cp">C.P.</label>
          <input type="text" class="form-control" id="cp" value="<?php echo h($proveedor['codigo_postal']); ?>" readonly>
          <input type="text" class="form-control hidden mt-1" id="cp_ext" name="cp_ext" placeholder="Código Postal (exterior)" value="<?php echo h($proveedor['codigo_postal']); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="telefono">Teléfono</label>
          <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo h($proveedor['telefono']); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="whatsapp">Whatsapp</label>
          <input type="text" class="form-control" id="whatsapp" name="whatsapp" value="<?php echo h($proveedor['whatsapp']); ?>">
        </div>
      </div>

      <!-- 6) Identificador Fiscal + Nº + Condición Fiscal -->
      <div class="row g-3 mb-2">
        <div class="col-md-4">
          <label class="form-label" for="idf_tipo">Identificador Fiscal</label>
          <select class="form-select" id="idf_tipo" name="idf_tipo">
            <option value="">Seleccionar...</option>
            <?php
              $tipos_doc = ["DNI","NIE","CIF","CUIT","RUC","RFC","CNPJ","CPF","NIT","CI","CEDULA","DUI","RTN","RNC","RIF","PASAPORTE"];
              foreach ($tipos_doc as $tipo) {
                $sel = (strtoupper($tipoDocActual) === strtoupper($tipo)) ? 'selected' : '';
                echo '<option value="'.h($tipo).'" '.$sel.'>'.h($tipo).'</option>';
              }
            ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="id_fiscal">Nº Identificación</label>
          <input type="text" class="form-control idf-input" id="id_fiscal" name="id_fiscal" value="<?php echo h($idFiscalValor); ?>" placeholder="20-10129267-4" title="Formato según tipo">
          <div class="idf-help text-end" id="ayuda_id_fiscal"></div>
        </div>

        <div class="col-md-5" id="wrap_cond_fiscal">
          <label class="form-label" for="condicion_fiscal_id">Condición Fiscal</label>
          <select class="form-select" id="condicion_fiscal_id" name="condicion_fiscal_id">
            <option value="">-- Seleccionar --</option>
            <?php foreach ($condiciones_por_pais as $paisId => $conds): ?>
              <?php foreach ($conds as $c): ?>
                <option data-pais="<?php echo (int)$paisId; ?>"
                        value="<?php echo (int)$c['id']; ?>"
                        <?php echo ((int)$c['id'] === $selectedCondId) ? 'selected' : ''; ?>>
                  <?php echo h($c['nombre']); ?>
                </option>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- 7) Mail y Web -->
      <div class="row g-3 mb-3 mt-1">
        <div class="col-md-6">
          <label class="form-label" for="email">Mail</label>
          <input type="email" class="form-control" id="email" name="email" value="<?php echo h($proveedor['email']); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="sitio_web">Web</label>
          <input type="text" class="form-control" id="sitio_web" name="sitio_web" placeholder="https://"
                 value="<?php echo h($proveedor['sitio_web']); ?>">
        </div>
      </div>

      <div class="mt-2">
        <a href="<?php echo h($cancelUrl); ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Actualizar Proveedor</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const URL_BUSCAR_LOCALIDADES = '/modulos/compras/api/buscar_localidades.php';

const $pais = document.getElementById('pais');
const $paisIso = document.getElementById('pais_iso');
const $provinciaSelect = document.getElementById('provincia_id');
const $provinciaTxt = document.getElementById('provincia_texto');
const $localidadBuscar = document.getElementById('localidad_buscar');
const $ciudadTxt = document.getElementById('ciudad_texto');
const $sug = document.getElementById('sugerencias');
const $localidadHidden = document.getElementById('localidad');
const $cp = document.getElementById('cp');
const $cpHidden = document.getElementById('codigo_postal_hidden');
const $cpExt = document.getElementById('cp_ext');

const $wrapCond = document.getElementById('wrap_cond_fiscal');
const $condFiscal = document.getElementById('condicion_fiscal_id');

const $idfTipo = document.getElementById('idf_tipo');
const $idFiscal = document.getElementById('id_fiscal');
const $ayudaId = document.getElementById('ayuda_id_fiscal');

// Máscaras
function maskCUIT(v){ v=v.replace(/\D+/g,'').slice(0,11); if(v.length>=3)v=v.slice(0,2)+'-'+v.slice(2); if(v.length>=12)v=v.slice(0,11)+'-'+v.slice(11); return v; }
function maskCPF(v){ v=v.replace(/\D+/g,'').slice(0,11); if(v.length>3)v=v.slice(0,3)+'.'+v.slice(3); if(v.length>7)v=v.slice(0,7)+'.'+v.slice(7); if(v.length>11)v=v.slice(0,11)+'-'+v.slice(11); return v; }
function maskCNPJ(v){ v=v.replace(/\D+/g,'').slice(0,14); if(v.length>2)v=v.slice(0,2)+'.'+v.slice(2); if(v.length>6)v=v.slice(0,6)+'.'+v.slice(6); if(v.length>10)v=v.slice(0,10)+'/'+v.slice(10); if(v.length>15)v=v.slice(0,15)+'-'+v.slice(15); return v; }
function maskRUT(v){ v=v.replace(/\D+/g,'').slice(0,9); if(v.length>2)v=v.slice(0,2)+'.'+v.slice(2); if(v.length>6)v=v.slice(0,6)+'.'+v.slice(6); if(v.length>10)v=v.slice(0,10)+'-'+v.slice(10); return v; }
function maskDigits(v, max=13){ return v.replace(/\D+/g,'').slice(0,max); }
function maskRFC(v){ return v.toUpperCase().replace(/[^A-Z0-9]/g,'').slice(0,13); }

const widthByTipo = {
  CUIT: 26, CPF: 22, CNPJ: 26, RUT: 22, RFC: 24, RUC: 24,
  NIT: 22, CI: 22, DNI: 22, CEDULA: 24, DUI: 22, RTN: 24, RNC: 22, RIF: 24, PASAPORTE: 24
};

function setMaskAndWidth(tipo, val){
  let masked = val;
  let placeholder = '';
  switch (tipo) {
    case 'CUIT': $ayudaId.textContent = 'Formato: XX-XXXXXXX-X'; masked = maskCUIT(val); placeholder='20-10129267-4'; break;
    case 'CPF':  $ayudaId.textContent = 'Formato: 000.000.000-00'; masked = maskCPF(val); placeholder='000.000.000-00'; break;
    case 'CNPJ': $ayudaId.textContent = 'Formato: 00.000.000/0000-00'; masked = maskCNPJ(val); placeholder='00.000.000/0000-00'; break;
    case 'RUT':  $ayudaId.textContent = 'Formato: 12.345.678-9'; masked = maskRUT(val); placeholder='12.345.678-9'; break;
    case 'RFC':  $ayudaId.textContent = 'Alfanumérico 12-13'; masked = maskRFC(val); placeholder='ABCD010203XYZ'; break;
    case 'RUC':  $ayudaId.textContent = 'Numérico'; masked = maskDigits(val, 13); placeholder='1234567890123'; break;
    default:     $ayudaId.textContent = tipo ? 'Numérico' : ''; masked = maskDigits(val, 13);
  }
  const w = widthByTipo[tipo] || 26;
  $idFiscal.style.width = w + 'ch';
  if (placeholder) $idFiscal.placeholder = placeholder;
  return masked;
}

function onTipoChange(){
  $idFiscal.value = setMaskAndWidth($idfTipo.value, $idFiscal.value);
}
$idFiscal.addEventListener('input', () => {
  $idFiscal.value = setMaskAndWidth($idfTipo.value, $idFiscal.value);
});
$idfTipo.addEventListener('change', onTipoChange);

let INITIALIZING = true;

// Mostrar/ocultar campos AR vs Exterior
function toggleSegunPais(iso) {
  const esAR = (iso === 'ARG');

  $provinciaSelect.classList.toggle('hidden', !esAR);
  $provinciaTxt.classList.toggle('hidden', esAR);

  $localidadBuscar.classList.toggle('hidden', !esAR);
  $ciudadTxt.classList.toggle('hidden', esAR);
  $cp.classList.toggle('hidden', !esAR);
  $cpExt.classList.toggle('hidden', esAR);

  $wrapCond.classList.toggle('hidden', !esAR);

  if (!INITIALIZING) {
    $cpHidden.value = '';
    if (!esAR) {
      $localidadHidden.value = '';
    }
  }
}

function filtrarCondicionesPorPais(paisId) {
  [...$condFiscal.options].forEach(opt => {
    if (opt.value === '') return;
    const p = opt.getAttribute('data-pais');
    const visible = String(paisId) === p;
    opt.classList.toggle('hidden', !visible);
    opt.disabled = !visible;
  });
}

function onPaisChange() {
  const opt = $pais.selectedOptions[0];
  const iso = opt ? (opt.dataset.iso || '') : '';
  const paisId = opt ? parseInt(opt.value || '0', 10) : 0;

  $paisIso.value = iso;
  toggleSegunPais(iso);
  filtrarCondicionesPorPais(paisId);

  // Tipo por defecto del país (si viene definido)
  const tipoDefault = opt ? (opt.dataset.idfdefault || '') : '';
  if (tipoDefault && !$idfTipo.value) {
    const match = [...$idfTipo.options].find(o => o.value.toUpperCase() === tipoDefault.toUpperCase());
    if (match) $idfTipo.value = match.value;
  }
  onTipoChange();
}

$pais.addEventListener('change', () => {
  INITIALIZING = false;
  onPaisChange();
});

document.addEventListener('DOMContentLoaded', () => {
  // Si no hay país seleccionado pero el número parece CUIT, seleccionar ARG
  const hasPais = !!$pais.selectedOptions[0]?.value;
  if (!hasPais && $idFiscal.value && /^\d{2}-\d{7,8}-\d$/.test($idFiscal.value)) {
    const optArg = [...$pais.options].find(o => (o.dataset.iso || '').toUpperCase() === 'ARG');
    if (optArg) optArg.selected = true;
  }
  onPaisChange();
  onTipoChange();

  if ($cp && $cp.value) $cpHidden.value = $cp.value;
  INITIALIZING = false;
});

// Autocompletar localidades
let timeoutId = null;
const $prov = document.getElementById('provincia_id');
$localidadBuscar.addEventListener('input', () => {
  const q = $localidadBuscar.value.trim();
  $sug.innerHTML = '';
  $sug.classList.add('hidden');
  if (q.length < 2) return;

  clearTimeout(timeoutId);
  timeoutId = setTimeout(async () => {
    try {
      const prov = $prov?.value || '';
      const url = `${URL_BUSCAR_LOCALIDADES}?q=${encodeURIComponent(q)}${prov ? `&provincia_id=${prov}` : ''}`;
      const res = await fetch(url);
      const data = await res.json();
      if (!data.ok) return;
      const items = data.items || [];
      if (!items.length) return;

      $sug.innerHTML = '';
      items.forEach(it => {
        const li = document.createElement('li');
        li.textContent = `${it.localidad} (${it.provincia}) — CP ${it.cp}`;
        li.addEventListener('click', () => {
          $localidadBuscar.value = it.localidad;
          $localidadHidden.value = it.localidad;
          $cp.value = it.cp;
          $cpHidden.value = String(it.cp);
          if ($prov && it.provincia_id) {
            const opt = [...$prov.options].find(o => o.value == it.provincia_id);
            if (opt) $prov.value = it.provincia_id;
          }
          $sug.classList.add('hidden');
        });
        $sug.appendChild(li);
      });
      $sug.classList.remove('hidden');
    } catch (err) { console.error('Error buscando localidades', err); }
  }, 250);
});

// Cerrar sugerencias al click fuera
document.addEventListener('click', (e) => {
  if (!e.target.closest('#sugerencias') && e.target !== $localidadBuscar) {
    $sug.classList.add('hidden');
  }
});

// Submit: copiar campos de exterior al backend cuando no es Argentina
document.getElementById('form-editar-proveedor').addEventListener('submit', () => {
  const iso = $pais.selectedOptions[0]?.dataset?.iso || '';
  if (iso !== 'ARG') {
    $localidadHidden.value = $ciudadTxt.value || '';
    document.getElementById('codigo_postal_hidden').value = $cpExt.value || '';
  }
});
</script>
</body>
</html>
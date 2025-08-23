<?php
require_once '../../config/config.php';

iniciarSesionSegura();
requireLogin('../../login.php');

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$mensaje_error = '';

try {
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Código PROV- autoincremental visual
    $sql_codigo = "SELECT codigo FROM proveedores WHERE codigo LIKE 'PROV-%' ORDER BY CAST(SUBSTRING(codigo, 6) AS UNSIGNED) DESC LIMIT 1";
    $ultimo_codigo = $pdo->query($sql_codigo)->fetchColumn();
    $numero = $ultimo_codigo ? (intval(substr($ultimo_codigo, 5)) + 1) : 1;
    $nuevo_codigo = 'PROV-' . str_pad($numero, 7, '0', STR_PAD_LEFT);

    // Países
    $paises = $pdo->query("SELECT id, nombre, codigo_iso, identificacion_fiscal FROM paises WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

    // Provincias (Argentina)
    $provincias = $pdo->query("SELECT id, nombre FROM provincias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

    // Condiciones fiscales por país
    $condStmt = $pdo->query("SELECT id, pais_id, nombre_condicion FROM condiciones_fiscales ORDER BY nombre_condicion");
    $condiciones_por_pais = [];
    foreach ($condStmt as $r) {
        $condiciones_por_pais[(int)$r['pais_id']][] = ['id'=>(int)$r['id'],'nombre'=>$r['nombre_condicion']];
    }
} catch (Throwable $e) {
    error_log("Error DB: " . $e->getMessage());
    $mensaje_error = "No se pudieron cargar datos iniciales.";
    $nuevo_codigo = 'PROV-0000001';
    $paises = $provincias = $condiciones_por_pais = [];
}

// Guardado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $codigo       = $nuevo_codigo;

        $contacto     = trim($_POST['contacto'] ?? '');
        $razon_social = trim($_POST['razon_social'] ?? '');
        $direccion    = trim($_POST['direccion'] ?? '');

        $pais_id      = intval($_POST['pais'] ?? 0);
        $pais_iso     = trim($_POST['pais_iso'] ?? '');

        $provincia_id = isset($_POST['provincia_id']) && $_POST['provincia_id'] !== '' ? intval($_POST['provincia_id']) : null;
        $provincia_tx = trim($_POST['provincia_texto'] ?? '');
        $ciudad       = trim($_POST['ciudad'] ?? ( $_POST['ciudad_texto'] ?? '' ));
        $codigo_postal= trim($_POST['codigo_postal'] ?? ( $_POST['cp_ext'] ?? '' ));

        $telefono     = trim($_POST['telefono'] ?? '');
        $whatsapp     = trim($_POST['whatsapp'] ?? '');

        // Identificador fiscal
        $idf_tipo     = trim($_POST['idf_tipo'] ?? '');
        $id_fiscal    = trim($_POST['id_fiscal'] ?? '');
        $id_fiscal_digits = preg_replace('/\W+/u', '', $id_fiscal);
        $cond_fisc_id = isset($_POST['condicion_fiscal_id']) && $_POST['condicion_fiscal_id'] !== '' ? intval($_POST['condicion_fiscal_id']) : null;

        $email        = trim($_POST['email'] ?? '');
        $sitio_web    = trim($_POST['sitio_web'] ?? '');
        $activo       = isset($_POST['activo']) ? 1 : 1;

        if ($razon_social === '') throw new Exception("La Razón Social es obligatoria");
        if ($pais_id <= 0)        throw new Exception("Debe seleccionar un País");

        if ($pais_iso !== 'ARG') { // Exterior
            $provincia_id = null;
            $cond_fisc_id = null;
        } else {
            $provincia_tx = null;
        }

        $sql = "INSERT INTO proveedores
          (codigo, razon_social, contacto_nombre,
           identificacion_fiscal, cuit, nro_identificacion_fiscal,
           pais_id, condicion_fiscal_id,
           provincia_id, provincia, ciudad, codigo_postal,
           direccion, telefono, whatsapp, email, sitio_web,
           activo, fecha_creacion)
        VALUES
          (:codigo, :razon_social, :contacto_nombre,
           :identificacion_fiscal, :cuit, :nro_identificacion_fiscal,
           :pais_id, :condicion_fiscal_id,
           :provincia_id, :provincia, :ciudad, :codigo_postal,
           :direccion, :telefono, :whatsapp, :email, :sitio_web,
           :activo, NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':codigo'                 => $codigo,
            ':razon_social'           => $razon_social,
            ':contacto_nombre'        => $contacto ?: null,
            ':identificacion_fiscal'  => $idf_tipo ?: null,
            ':cuit'                   => $id_fiscal ?: null,
            ':nro_identificacion_fiscal' => $id_fiscal_digits ?: null,
            ':pais_id'                => $pais_id,
            ':condicion_fiscal_id'    => $cond_fisc_id,
            ':provincia_id'           => $provincia_id,
            ':provincia'              => $provincia_tx ?: null,
            ':ciudad'                 => $ciudad ?: null,
            ':codigo_postal'          => $codigo_postal ?: null,
            ':direccion'              => $direccion ?: null,
            ':telefono'               => $telefono ?: null,
            ':whatsapp'               => $whatsapp ?: null,
            ':email'                  => $email ?: null,
            ':sitio_web'              => $sitio_web ?: null,
            ':activo'                 => $activo
        ]);

        header("Location: proveedores.php?msg=proveedor_creado");
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
  <title>Nuevo Proveedor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons (para los íconos del navbar) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body{background:#f8f9fa}
    .form-container{max-width:900px;margin:30px auto;background:#fff;border-radius:10px;box-shadow:0 0 15px rgba(0,0,0,.08);overflow:hidden}
    .form-header{background:#0d6efd;color:#fff;padding:16px 20px}
    .hidden{display:none}
    .suggest{list-style:none; padding:6px; margin:0; border:1px solid #ccc; max-height:220px; overflow:auto; position:absolute; width:100%; background:#fff; z-index:10}
    .suggest li{padding:6px; cursor:pointer}
    .suggest li:hover{background:#f3f3f3}
    .codigo-auto{background:#f8f9fa; border:2px solid #28a745; color:#28a745; font-weight:600}
    /* Campo Nº Identificación: más ancho por defecto (entra 20-10129267-4) */
    .idf-input{
      width: 26ch; /* ancho base amplio */
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    }
    .idf-help{ margin-top:.25rem; font-size:.8125rem; color:#6c757d; }
  </style>
</head>
<body>

<?php include "../../config/navbar_code.php"; ?>

<div class="container form-container">
  <div class="form-header">
    <h4 class="mb-0">Nuevo Proveedor - Editar Proveedor</h4>
  </div>
  <div class="p-4">
    <?php if (!empty($mensaje_error)): ?>
      <div class="alert alert-danger"><?php echo h($mensaje_error); ?></div>
    <?php endif; ?>

    <form id="form-nuevo-proveedor" method="POST" autocomplete="off">
      <input type="hidden" id="pais_iso" name="pais_iso" value="">
      <input type="hidden" id="codigo_postal_hidden" name="codigo_postal" value="">

      <!-- 1) Código, Contacto, Activo -->
      <div class="row g-3 mb-2">
        <div class="col-md-3">
          <label class="form-label">Código (Automático)</label>
          <input type="text" class="form-control codigo-auto" value="<?php echo h($nuevo_codigo); ?>" readonly>
        </div>
        <div class="col-md-7">
          <label class="form-label" for="contacto">Contacto</label>
          <input type="text" class="form-control" id="contacto" name="contacto">
        </div>
        <div class="col-md-2">
          <label class="form-label d-block">Activo</label>
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
            <label class="form-check-label" for="activo">Sí</label>
          </div>
        </div>
      </div>

      <!-- 2) Razón Social -->
      <div class="mb-2">
        <label class="form-label" for="razon_social">Razón Social</label>
        <input type="text" class="form-control" id="razon_social" name="razon_social" required>
      </div>

      <!-- 3) Dirección -->
      <div class="mb-2">
        <label class="form-label" for="direccion">Dirección (Calle y Número)</label>
        <input type="text" class="form-control" id="direccion" name="direccion">
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
                      <?php echo ($p['codigo_iso'] === 'ARG') ? 'selected' : ''; ?>>
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
              <option value="<?php echo (int)$pv['id']; ?>"><?php echo h($pv['nombre']); ?></option>
            <?php endforeach; ?>
          </select>
          <input type="text" class="form-control hidden mt-1" id="provincia_texto" name="provincia_texto" placeholder="Provincia/Estado (exterior)">
        </div>

        <div class="col-md-4 position-relative">
          <label class="form-label" for="localidad_buscar">Localidad</label>
          <input type="text" class="form-control" id="localidad_buscar" placeholder="Escribí al menos 2 letras...">
          <ul id="sugerencias" class="suggest hidden"></ul>
          <input type="hidden" id="localidad" name="ciudad">
          <input type="text" class="form-control hidden mt-1" id="ciudad_texto" name="ciudad_texto" placeholder="Ciudad (exterior)">
        </div>
      </div>

      <!-- 5) C.P., Teléfono, Whatsapp -->
      <div class="row g-3 mb-2">
        <div class="col-md-4">
          <label class="form-label" for="cp">C.P.</label>
          <input type="text" class="form-control" id="cp" readonly>
          <input type="text" class="form-control hidden mt-1" id="cp_ext" name="cp_ext" placeholder="Código Postal (exterior)">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="telefono">Teléfono</label>
          <input type="text" class="form-control" id="telefono" name="telefono">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="whatsapp">Whatsapp</label>
          <input type="text" class="form-control" id="whatsapp" name="whatsapp">
        </div>
      </div>

      <!-- 6) Identificador Fiscal + Nº + Condición Fiscal (mismo renglón) -->
      <div class="row g-3 mb-2">
        <div class="col-md-4">
          <label class="form-label" for="idf_tipo">Identificador Fiscal</label>
          <select class="form-select" id="idf_tipo" name="idf_tipo">
            <option value="">Seleccionar...</option>
            <?php
              $tipos_doc = ["DNI","NIE","CIF","CUIT","RUC","RFC","CNPJ","CPF","NIT","CI","CEDULA","DUI","RTN","RNC","RIF","PASAPORTE"];
              foreach ($tipos_doc as $tipo) {
                echo '<option value="'.h($tipo).'">'.h($tipo).'</option>';
              }
            ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="id_fiscal">Nº Identificación</label>
          <input type="text" class="form-control idf-input" id="id_fiscal" name="id_fiscal" placeholder="20-10129267-4" title="Formato según tipo">
          <div class="idf-help text-end" id="ayuda_id_fiscal"></div>
        </div>

        <div class="col-md-5" id="wrap_cond_fiscal">
          <label class="form-label" for="condicion_fiscal_id">Condición Fiscal</label>
          <select class="form-select" id="condicion_fiscal_id" name="condicion_fiscal_id">
            <option value="">-- Seleccionar --</option>
            <?php foreach ($condiciones_por_pais as $paisId => $conds): ?>
              <?php foreach ($conds as $c): ?>
                <option data-pais="<?php echo (int)$paisId; ?>" value="<?php echo (int)$c['id']; ?>">
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
          <input type="email" class="form-control" id="email" name="email">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="sitio_web">Web</label>
          <input type="text" class="form-control" id="sitio_web" name="sitio_web" placeholder="https://">
        </div>
      </div>

      <div class="mt-2">
        <a href="proveedores.php" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Guardar Proveedor</button>
      </div>
    </form>
  </div>
</div>

<!-- Bootstrap JS (necesario para que funcionen los dropdowns del navbar) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
const URL_BUSCAR_LOCALIDADES = 'api/buscar_localidades.php';

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

// Ancho recomendado por tipo (en ch); aplico un mínimo generoso para que no quede corto
const widthByTipo = {
  CUIT: 26,  // ancho mínimo amplio (entra 20-10129267-4)
  CPF: 22,
  CNPJ: 26,
  RUT: 22,
  RFC: 24,
  RUC: 24,
  NIT: 22, CI: 22, DNI: 22, CEDULA: 24, DUI: 22, RTN: 24, RNC: 22, RIF: 24, PASAPORTE: 24
};

function setMaskAndWidth(tipo, val){
  let masked = val;
  let placeholder = '';
  switch (tipo) {
    case 'CUIT': $ayudaId.textContent = 'Formato: XX-XXXXXXXX-X'; masked = maskCUIT(val); placeholder='20-10129267-4'; break;
    case 'CPF':  $ayudaId.textContent = 'Formato: 000.000.000-00'; masked = maskCPF(val); placeholder='000.000.000-00'; break;
    case 'CNPJ': $ayudaId.textContent = 'Formato: 00.000.000/0000-00'; masked = maskCNPJ(val); placeholder='00.000.000/0000-00'; break;
    case 'RUT':  $ayudaId.textContent = 'Formato: 12.345.678-9'; masked = maskRUT(val); placeholder='12.345.678-9'; break;
    case 'RFC':  $ayudaId.textContent = 'Alfanumérico 12-13'; masked = maskRFC(val); placeholder='ABCD010203XYZ'; break;
    case 'RUC':  $ayudaId.textContent = 'Numérico'; masked = maskDigits(val, 13); placeholder='1234567890123'; break;
    default:     $ayudaId.textContent = tipo ? 'Numérico' : ''; masked = maskDigits(val, 13);
  }
  const w = widthByTipo[tipo] || 26; // mínimo ancho amplio
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

// Mostrar/ocultar campos AR vs Exterior
function toggleSegunPais(iso) {
  const esAR = (iso === 'ARG');

  // Provincia
  $provinciaSelect.classList.toggle('hidden', !esAR);
  $provinciaTxt.classList.toggle('hidden', esAR);

  // Localidad + CP (AR) vs Ciudad + CP Ext (Exterior)
  $localidadBuscar.classList.toggle('hidden', !esAR);
  $ciudadTxt.classList.toggle('hidden', esAR);
  $cp.classList.toggle('hidden', !esAR);
  $cpExt.classList.toggle('hidden', esAR);

  // Condición Fiscal solo Argentina
  $wrapCond.classList.toggle('hidden', !esAR);

  // Limpiar
  $localidadBuscar.value = '';
  $ciudadTxt.value = '';
  $localidadHidden.value = '';
  $cp.value = '';
  $cpHidden.value = '';
  $cpExt.value = '';
}

function filtrarCondicionesPorPais(paisId) {
  [...$condFiscal.options].forEach(opt => {
    if (opt.value === '') return;
    const p = opt.getAttribute('data-pais');
    const visible = String(paisId) === p;
    opt.classList.toggle('hidden', !visible);
    opt.disabled = !visible;
    if (!visible && $condFiscal.value === opt.value) $condFiscal.value = '';
  });
}

// Cuando cambia país: set ISO, bloques y tipo de ID por defecto
function onPaisChange() {
  const opt = $pais.selectedOptions[0];
  const iso = opt ? (opt.dataset.iso || '') : '';
  const paisId = opt ? parseInt(opt.value || '0', 10) : 0;
  const tipoDefault = opt ? (opt.dataset.idfdefault || '') : '';

  $paisIso.value = iso;
  toggleSegunPais(iso);
  filtrarCondicionesPorPais(paisId);

  if (tipoDefault) {
    const match = [...$idfTipo.options].find(o => o.value.toUpperCase() === tipoDefault.toUpperCase());
    $idfTipo.value = match ? match.value : '';
  } else {
    $idfTipo.value = '';
  }
  onTipoChange();
}
$pais.addEventListener('change', onPaisChange);
document.addEventListener('DOMContentLoaded', onPaisChange);

// Autocompletar localidades
let timeoutId = null;
const $prov = document.getElementById('provincia_id');
$localidadBuscar.addEventListener('input', () => {
  const q = $localidadBuscar.value.trim();
  $sug.innerHTML = '';
  $sug.classList.add('hidden');
  $cp.value = '';
  $cpHidden.value = '';
  $localidadHidden.value = '';
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
document.getElementById('form-nuevo-proveedor').addEventListener('submit', () => {
  const iso = $pais.selectedOptions[0]?.dataset?.iso || '';
  if (iso !== 'ARG') {
    $localidadHidden.value = $ciudadTxt.value || '';
    document.getElementById('codigo_postal_hidden').value = $cpExt.value || '';
  }
});
</script>
</body>
</html>
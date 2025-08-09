<?php
/**
 * 🎯 MODAL UNIFICADO DE PROVEEDORES
 * Este archivo contiene el modal exactamente igual para usar en:
 * - proveedores.php (crear y editar)
 * - compra_form_new.php (crear desde compras)
 */
?>

<!-- 🎯 MODAL UNIFICADO - PROVEEDOR -->
<div class="modal fade" id="modalNuevoProveedor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    Nuevo Proveedor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-nuevo-proveedor">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Código *</label>
                                <input type="text" class="form-control" name="codigo" required
                                    placeholder="Ej: PROV001">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">CUIT/CUIL</label>
                                <input type="text" class="form-control" name="cuit"
                                    placeholder="Ej: 20-12345678-9">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Razón Social *</label>
                                <input type="text" class="form-control" name="razon_social" required
                                    placeholder="Nombre oficial de la empresa">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nombre Comercial</label>
                                <input type="text" class="form-control" name="nombre_comercial"
                                    placeholder="Nombre comercial o de fantasía">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Dirección</label>
                                <input type="text" class="form-control" name="direccion"
                                    placeholder="Dirección completa">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">País</label>
                                <div class="d-flex">
                                    <select class="form-select" name="pais_id" id="pais_id">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($paises as $pais): ?>
                                            <option value="<?php echo $pais['id']; ?>" 
                                                <?= strtolower($pais['nombre']) == 'argentina' ? 'selected' : '' ?>>
                                                <?php echo htmlspecialchars($pais['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="nuevo">+ Nuevo País</option>
                                    </select>
                                    <button type="button" class="btn btn-nuevo btn-sm ms-1" onclick="nuevoItem('pais')">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    Solo Argentina carga provincias automáticamente
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Provincia/Estado</label>
                                <div class="d-flex">
                                    <select class="form-select" name="provincia_id" id="provincia_id">
                                        <option value="">-- Seleccione país primero --</option>
                                        <?php foreach ($provincias as $provincia): ?>
                                            <option value="<?php echo $provincia['id']; ?>">
                                                <?php echo htmlspecialchars($provincia['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="nuevo">+ Nueva Provincia</option>
                                    </select>
                                    <button type="button" class="btn btn-nuevo btn-sm ms-1" onclick="nuevoItem('provincia')">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ciudad</label>
                                <div class="d-flex">
                                    <select class="form-select" name="ciudad_id" id="ciudad_id">
                                        <option value="">-- Seleccione provincia primero --</option>
                                        <?php foreach ($ciudades as $ciudad): ?>
                                            <option value="<?php echo $ciudad['id']; ?>">
                                                <?php echo htmlspecialchars($ciudad['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="nuevo">+ Nueva Ciudad</option>
                                    </select>
                                    <button type="button" class="btn btn-nuevo btn-sm ms-1" onclick="nuevoItem('ciudad')">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Teléfono</label>
                                <div class="phone-input">
                                    <select class="phone-prefix" id="telefono-pais" onchange="cambiarCodigoPais('telefono')">
                                        <option value="+54" data-flag="🇦🇷">🇦🇷 +54</option>
                                        <option value="+1" data-flag="🇺🇸">🇺🇸 +1</option>
                                        <option value="+55" data-flag="🇧🇷">🇧🇷 +55</option>
                                        <option value="+56" data-flag="🇨🇱">🇨🇱 +56</option>
                                        <option value="+51" data-flag="🇵🇪">🇵🇪 +51</option>
                                        <option value="+52" data-flag="🇲🇽">🇲🇽 +52</option>
                                        <option value="+34" data-flag="🇪🇸">🇪🇸 +34</option>
                                        <option value="+33" data-flag="🇫🇷">🇫🇷 +33</option>
                                        <option value="+39" data-flag="🇮🇹">🇮🇹 +39</option>
                                        <option value="+49" data-flag="🇩🇪">🇩🇪 +49</option>
                                    </select>
                                    <input type="tel" class="form-control phone-number" name="telefono" id="telefono-input"
                                        placeholder="11 1234-5678">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">WhatsApp</label>
                                <div class="phone-input">
                                    <select class="phone-prefix" id="whatsapp-pais" onchange="cambiarCodigoPais('whatsapp')">
                                        <option value="+54" data-flag="🇦🇷">🇦🇷 +54</option>
                                        <option value="+1" data-flag="🇺🇸">🇺🇸 +1</option>
                                        <option value="+55" data-flag="🇧🇷">🇧🇷 +55</option>
                                        <option value="+56" data-flag="🇨🇱">🇨🇱 +56</option>
                                        <option value="+51" data-flag="🇵🇪">🇵🇪 +51</option>
                                        <option value="+52" data-flag="🇲🇽">🇲🇽 +52</option>
                                        <option value="+34" data-flag="🇪🇸">🇪🇸 +34</option>
                                        <option value="+33" data-flag="🇫🇷">🇫🇷 +33</option>
                                        <option value="+39" data-flag="🇮🇹">🇮🇹 +39</option>
                                        <option value="+49" data-flag="🇩🇪">🇩🇪 +49</option>
                                    </select>
                                    <input type="tel" class="form-control phone-number" name="whatsapp" id="whatsapp-input"
                                        placeholder="11 1234-5678">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" class="form-control" name="email"
                                    placeholder="contacto@empresa.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Sitio Web</label>
                                <input type="url" class="form-control" name="sitio_web"
                                    placeholder="https://www.empresa.com">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="guardarNuevoProveedor()">
                    <i class="fas fa-save"></i> Guardar Proveedor
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* 🎨 ESTILOS UNIFICADOS - MODAL PROVEEDOR */
.phone-input {
    position: relative;
    display: flex;
    align-items: center;
}

.phone-prefix {
    width: 85px;
    height: 38px;
    border: 1px solid #ddd;
    border-right: none;
    border-radius: 4px 0 0 4px;
    background: white;
    font-size: 0.85rem;
    padding: 0 5px;
    flex-shrink: 0;
}

.phone-number {
    border-radius: 0 4px 4px 0;
    border-left: none;
    flex: 1;
}

.modal-header {
    background: linear-gradient(135deg, var(--primary-color), #0056b3);
    color: white;
}

.btn-nuevo {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border: none;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-nuevo:hover {
    background: linear-gradient(135deg, #20c997, #28a745);
    color: white;
}
</style>

<script>
/**
 * 🔧 FUNCIONES UNIFICADAS - MODAL PROVEEDOR
 */

// 📞 FUNCIÓN UNIFICADA - MANEJO DE CÓDIGOS DE PAÍS
function cambiarCodigoPais(tipo) {
    const select = document.getElementById(`${tipo}-pais`);
    const input = document.getElementById(`${tipo}-input`);
    const codigo = select.value;
    
    // Placeholders específicos por país
    const placeholders = {
        '+54': '11 1234-5678',  // Argentina
        '+1': '(555) 123-4567', // USA
        '+55': '11 99999-9999', // Brasil
        '+56': '9 1234 5678',   // Chile
        '+51': '999 999 999',   // Perú
        '+52': '55 1234 5678',  // México
        '+34': '612 34 56 78',  // España
        '+33': '06 12 34 56 78', // Francia
        '+39': '338 123 4567',  // Italia
        '+49': '0151 23456789'  // Alemania
    };
    
    input.placeholder = placeholders[codigo] || 'Número de teléfono';
}

function nuevoItem(tipo) {
    const nombre = prompt(`Ingrese el nombre del nuevo ${tipo}:`);
    if (nombre) {
        // Aquí puedes agregar lógica para crear nuevos países, provincias, ciudades
        alert(`Funcionalidad para crear nuevo ${tipo} será implementada`);
    }
}

// 🌍 UNIFICACIÓN DE CRITERIOS - MANEJO DE PAÍSES
document.addEventListener('DOMContentLoaded', function() {
    const paisSelect = document.getElementById('pais_id');
    const provinciaSelect = document.getElementById('provincia_id');
    const ciudadSelect = document.getElementById('ciudad_id');

    if (paisSelect) {
        paisSelect.addEventListener('change', function() {
            const paisId = this.value;
            const paisTexto = this.options[this.selectedIndex].text;
            
            // Limpiar provincias y ciudades
            provinciaSelect.innerHTML = '<option value="">-- Seleccionar --</option>';
            ciudadSelect.innerHTML = '<option value="">-- Seleccionar --</option>';
            
            // Solo cargar automáticamente si es Argentina
            if (paisTexto.toLowerCase().includes('argentina')) {
                // Cargar provincias argentinas
                fetch(`../../config/get_provincias.php?pais_id=${paisId}`)
                    .then(response => response.json())
                    .then(provincias => {
                        provincias.forEach(provincia => {
                            const option = new Option(provincia.nombre, provincia.id);
                            provinciaSelect.add(option);
                        });
                        // Agregar opción para nueva provincia
                        provinciaSelect.add(new Option('+ Nueva Provincia', 'nuevo'));
                    })
                    .catch(error => console.log('No se pudieron cargar las provincias'));
            } else {
                // Para otros países, dejar campos manuales
                provinciaSelect.innerHTML = `
                    <option value="">-- Ingrese manualmente --</option>
                    <option value="manual">Escribir provincia/estado</option>
                    <option value="nuevo">+ Nueva Provincia</option>
                `;
                ciudadSelect.innerHTML = `
                    <option value="">-- Ingrese manualmente --</option>
                    <option value="manual">Escribir ciudad</option>
                    <option value="nuevo">+ Nueva Ciudad</option>
                `;
            }
        });
    }

    if (provinciaSelect) {
        provinciaSelect.addEventListener('change', function() {
            const provinciaId = this.value;
            const paisTexto = paisSelect.options[paisSelect.selectedIndex].text;
            
            // Limpiar ciudades
            ciudadSelect.innerHTML = '<option value="">-- Seleccionar --</option>';
            
            // Solo cargar automáticamente si es Argentina y no es opción manual
            if (paisTexto.toLowerCase().includes('argentina') && provinciaId !== 'manual' && provinciaId !== 'nuevo' && provinciaId !== '') {
                fetch(`../../config/get_ciudades.php?provincia_id=${provinciaId}`)
                    .then(response => response.json())
                    .then(ciudades => {
                        ciudades.forEach(ciudad => {
                            const option = new Option(ciudad.nombre, ciudad.id);
                            ciudadSelect.add(option);
                        });
                        // Agregar opción para nueva ciudad
                        ciudadSelect.add(new Option('+ Nueva Ciudad', 'nuevo'));
                    })
                    .catch(error => console.log('No se pudieron cargar las ciudades'));
            } else if (provinciaId === 'manual') {
                // Cambiar a input manual para provincia
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.name = 'provincia_manual';
                input.placeholder = 'Escriba la provincia/estado';
                provinciaSelect.parentNode.replaceChild(input, provinciaSelect);
            }
        });
    }
});
</script>

# Bitácora de Desarrollo - 27 de Septiembre de 2025

## Tareas Realizadas

### 1. Funcionalidad de Activar/Inactivar Proveedores

- **Objetivo:** Implementar la capacidad de marcar proveedores como activos o inactivos.
- **Archivos Modificados:**
    - `modulos/compras/gestionar_proveedor.php`: Se unificó la lógica para manejar las acciones de `inactivar` y `reactivar` mediante `fetch` y JSON.
    - `modulos/compras/proveedores_inactivos.php`: Se reemplazaron los enlaces de acción por botones con modals de confirmación y se añadió el script para la comunicación con el backend.
    - `modulos/compras/proveedores.php`: Se ajustó el botón de inactivar para que funcione con la nueva lógica de modals.

### 2. Refactorización y Unificación de Formularios de Proveedores

- **Objetivo:** Unificar los formularios de creación y edición de proveedores en un solo archivo.
- **Archivos Creados:**
    - `modulos/compras/new_proveedor.php`: Nuevo archivo que contiene el formulario y la lógica para crear y editar proveedores.
- **Archivos Modificados:**
    - `config/navbar_code.php`
    - `config/navbar_codeMAL.php`
    - `modulos/compras/compra_form_new.php`
    - `modulos/compras/compra_form_responsive.php`
    - `modulos/compras/compras_manual.php`
    - `modulos/compras/proveedores.php`
    - `modulos/compras/proveedores_inactivos.php`
    - `modulos/compras/proveedores_inactivosbak.php`
- **Archivos Eliminados:**
    - `modulos/compras/new_prov_complete.php`
    - `modulos/compras/edi_prov.php`

### 3. Mejora en la Entrada de Números de Teléfono

- **Objetivo:** Estandarizar la captura de números de teléfono y WhatsApp.
- **Archivos Modificados:**
    - `modulos/compras/new_proveedor.php`: Se integró la librería `intl-tel-input`, se modificaron los campos de teléfono y se añadió el script de inicialización y procesamiento.

### 4. Implementación de Restricciones de Eliminación de Proveedores

- **Objetivo:** Impedir la eliminación de proveedores con dependencias.
- **Archivos Modificados:**
    - `modulos/compras/gestionar_proveedor.php`: Se añadió la lógica para verificar si el proveedor tiene órdenes de compra en `oc_ordenes` o si está asignado a productos en `productos_proveedores` antes de permitir un borrado suave. Se mejoró el mensaje de error para ser más descriptivo.

### 5. Gestión de la Bitácora

- **Objetivo:** Mantener un registro de los cambios realizados.
- **Archivos Creados:**
    - `BITACORA_DESARROLLO.md`
- **Archivos Renombrados:**
    - `BITACORA_DESARROLLO.md` -> `BITACORA_DESARROLLO_2025-09-27.md`
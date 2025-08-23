# TAREAS PENDIENTES DE ORGANIZACIÓN DEL PROYECTO (Asistente Gemini)

Este archivo sirve como recordatorio de las tareas de organización que aún quedan por completar en el proyecto.

## 1. Scripts de Utilidad en la Carpeta `scripts/`

**Estado:** La mayoría de los scripts PHP de utilidad han sido movidos y corregidos.

**Scripts PHP pendientes de mover desde la raíz a `scripts/`:**
*   `ai_parser.php` (Clase, considerar mover a `classes/` o `lib/`)
*   `barcode.php` (Función, considerar mover a `lib/` o `classes/`)
*   `fix_include_dev_paths.php`
*   `obtener_ultimo_codigo.php`
*   `product_matcher.php` (Clase, considerar mover a `classes/` o `lib/`)
*   `simple_pdf.php` (Clase, considerar mover a `classes/` o `lib/`)

**Archivos problemáticos (requieren revisión manual o solución específica):**
*   `auditar_compatibilidad_bd.php` (No se pudo leer/corregir automáticamente)
*   `excel_php81.php` (No se pudo corregir completamente)
*   `excel_planmaker.php` (No se pudo corregir completamente)

**Próximos pasos para scripts:**
*   Mover los scripts PHP restantes a `scripts/`.
*   Analizar y corregir sus rutas internas.
*   Revisar los archivos problemáticos (`auditar_compatibilidad_bd.php`, `excel_php81.php`, `excel_planmaker.php`).
*   Considerar mover las clases (`ai_parser.php`, `barcode.php`, `product_matcher.php`, `simple_pdf.php`) a una carpeta más apropiada como `classes/` o `lib/` si no son scripts de ejecución directa.

---

## 2. Documentación `.md` en `sistemadgestion5_docs/legacy_docs/`

**Estado:** Pendiente de mover.

**Archivos `.md` a mover:**
*   `ANALISIS_SISTEMA_GEOGRAFICO_COMPLETO.md`
*   `CORRECCIONES_COMPLETADAS.md`
*   `CORRECCIONES_FINALES_PROVEEDORES.md`
*   `GUIA_ESCANER_CELULAR.md`
*   `GUIA_ESCANER_MEJORADO.md`
*   `MEJORAS_PROVEEDORES_COMPLETADAS.md`
*   `MEMORIA_PROYECTO_COMPRAS.md`
*   `MODALES_UNIFICADOS_COMPLETO.md`
*   `PROVEEDORES_CORREGIDO_COMPLETO.md`
*   `README_DOCKER.md`
*   `RESUMEN_FINAL_UNIFICACION.md`
*   `RESUMEN_MODAL_UNIFICADO.md`
*   `SISTEMA_FINAL_COMPLETO.md`
*   `SISTEMA_FINAL_CONCENTRADO.md`
*   `SISTEMA_UNIFICADO_COMPLETO.md`
*   `SOLUCION_FINAL_NEW_PROV.md`

**Nota:** El archivo `README.md` principal **NO** será movido.

---

# Lista de Archivos para Subir al Servidor

## Archivos Esenciales:
✅ **Archivos PHP principales:**
- index.php
- login.php
- logout.php
- menu_principal.php
- test_excel_servidor.php
- test_excel_simple_servidor.php
- generar_excel_inventario.php

✅ **Carpeta config/:**
- config.php (ajustar datos de BD)
- config_servidor.php (configuración específica)
- navbar_code.php

✅ **Carpeta modulos/ completa:**
- modulos/Inventario/
- modulos/admin/
- modulos/clientes/
- modulos/compras/
- modulos/facturas/
- modulos/pedidos/

✅ **Carpeta vendor/ COMPLETA:**
- vendor/autoload.php
- vendor/phpoffice/
- vendor/composer/
- (Toda la estructura de Composer)

✅ **Archivos de dependencias:**
- composer.json
- composer.lock

✅ **Carpeta assets/:**
- assets/img/
- assets/uploads/

## Configuración en el Servidor:

### 1. Base de Datos:
- Crear BD en el hosting
- Importar: sistemasia_inventpro_30_07_2025.sql
- Ajustar config.php con datos del servidor

### 2. Permisos (cPanel/SSH):
```bash
chmod 755 carpetas/
chmod 644 archivos.php
chmod 777 assets/uploads/
```

### 3. Verificar PHP:
- Versión: 7.4+
- Extensiones: zip, xml, mbstring, mysql, curl
- Memory: 256MB+

### 4. Probar:
1. Subir test_excel_servidor.php
2. Acceder: tudominio.com/carpeta/test_excel_servidor.php
3. Verificar status de dependencias
4. Probar descarga de Excel

## Si hay errores:

### Error "Class not found":
- Verificar que vendor/ esté completo
- Comprobar permisos de lectura

### Error de memoria:
- Aumentar memory_limit en PHP
- O contactar hosting

### Error de BD:
- Verificar datos en config.php
- Comprobar que la BD existe

### Error de permisos:
- Verificar chmod de archivos
- Carpeta uploads debe ser 777

## Archivos de Prueba (puedes borrar después):
- test_excel_servidor.php
- test_excel_simple_servidor.php
- diagnostico_login.php
- verificar_extensiones.php
- verificar_gd.php

¡Listo para subir al servidor! 🚀

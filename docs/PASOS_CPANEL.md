# LISTA DE ARCHIVOS PARA SUBIR A CPANEL

## Archivos de Configuración (OBLIGATORIOS)
1. config/config.php (renombrar config.servidor.php como config.php)
2. config/navbar_code.php 
3. index.php
4. login.php
5. logout.php

## Archivos de Prueba de Excel (PRIORITARIOS)
6. excel_xlsx_nativo.php
7. modulos/Inventario/reporte_completo_excel.php
8. verificar_servidor.php

## Base de Datos
9. sistemasia_inventpro_30_07_2025.sql (importar en cPanel MySQL)

## Carpetas Necesarias
10. assets/uploads/ (crear carpetas vacías)
11. assets/img/productos/ (crear carpetas vacías)

## PASOS DE CONFIGURACIÓN EN CPANEL:

### 1. Base de Datos:
- Crear nueva base de datos MySQL
- Crear usuario con todos los privilegios
- Importar sistemasia_inventpro_30_07_2025.sql

### 2. Editar config.php:
```php
define('DB_HOST', 'localhost'); // Confirmar con hosting
define('DB_USER', 'tuusuario_dbname'); // Usuario real de cPanel
define('DB_PASS', 'tu_password_real'); // Contraseña real
define('DB_NAME', 'tuusuario_sistemasia'); // Nombre real de DB
```

### 3. Permisos de Carpetas:
- assets/uploads/ → 755 o 777
- assets/img/productos/ → 755 o 777

### 4. Pruebas Después de Subir:
1. Acceder a: tudominio.com/verificar_servidor.php
2. Verificar conexión DB y extensiones
3. Probar: tudominio.com/excel_xlsx_nativo.php
4. Probar login con usuario admin

### 5. URLs de Prueba:
- tudominio.com/verificar_servidor.php (capacidades)
- tudominio.com/excel_xlsx_nativo.php (Excel básico)
- tudominio.com/login.php (acceso sistema)
- tudominio.com/modulos/Inventario/reporte_completo_excel.php (Excel completo)

## CREDENCIALES TEMPORALES:
- Usuario: admin
- Contraseña: admin123
(CAMBIAR después de la primera prueba)

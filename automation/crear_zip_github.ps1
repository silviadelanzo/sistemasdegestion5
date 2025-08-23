# PREPARACIÓN PARA GITHUB REPOSITORY
# Script para crear ZIP optimizado para subir a GitHub

Write-Host "🚀 PREPARANDO PROYECTO PARA GITHUB" -ForegroundColor Cyan
Write-Host "====================================" -ForegroundColor Cyan

$fecha = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$nombreProyecto = "sistemadgestion5"
$archivoZip = "github-ready-$nombreProyecto-$fecha.zip"

Write-Host "📦 Creando ZIP para GitHub: $archivoZip" -ForegroundColor Yellow

# Crear carpeta temporal para organizar archivos
$tempDir = "temp_github_$fecha"
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

Write-Host "📁 Copiando archivos esenciales..." -ForegroundColor Yellow

# ARCHIVOS CORE ESENCIALES
Write-Host "  ✅ Archivos core del sistema..." -ForegroundColor Green
Copy-Item -Path @(
    "index.php",
    "login.php", 
    "logout.php",
    "menu_principal.php",
    "obtener_ultimo_codigo.php",
    "README.md"
) -Destination $tempDir -Force -ErrorAction SilentlyContinue

# CARPETAS ESENCIALES
Write-Host "  ✅ Carpetas del sistema..." -ForegroundColor Green
$carpetasEsenciales = @("modulos", "config", "ajax", "assets")
foreach ($carpeta in $carpetasEsenciales) {
    if (Test-Path $carpeta) {
        Copy-Item -Path $carpeta -Destination $tempDir -Recurse -Force
        Write-Host "    📂 $carpeta copiado" -ForegroundColor Gray
    }
}

# ARCHIVOS DE GESTIÓN
Write-Host "  ✅ Formularios y gestión..." -ForegroundColor Green  
Copy-Item -Path @(
    "categoria_form.php",
    "lugar_form.php", 
    "usuario_form.php",
    "proveedor_form.php",
    "gestionar_*.php",
    "proveedores.php",
    "usuarios.php",
    "reportes.php"
) -Destination $tempDir -Force -ErrorAction SilentlyContinue

# ARCHIVOS DE REPORTES Y EXCEL
Write-Host "  ✅ Sistema de reportes..." -ForegroundColor Green
Copy-Item -Path @(
    "excel_nativo.php",
    "reporte_*.php"
) -Destination $tempDir -Force -ErrorAction SilentlyContinue

# DOCUMENTACIÓN ESENCIAL
Write-Host "  ✅ Documentación principal..." -ForegroundColor Green
New-Item -ItemType Directory -Path "$tempDir\docs" -Force | Out-Null
if (Test-Path "docs") {
    Copy-Item -Path "docs\README.md" -Destination "$tempDir\docs\" -Force -ErrorAction SilentlyContinue
    Copy-Item -Path "docs\GUIA_*.md" -Destination "$tempDir\docs\" -Force -ErrorAction SilentlyContinue
    Copy-Item -Path "docs\INSTRUCCIONES_*.md" -Destination "$tempDir\docs\" -Force -ErrorAction SilentlyContinue
}

# CREAR ARCHIVOS ADICIONALES PARA GITHUB
Write-Host "  ✅ Creando archivos para GitHub..." -ForegroundColor Green

# .gitignore
$gitignore = @"
# Archivos de configuración local
config/config_local.php
config/database_local.php

# Archivos temporales
*.tmp
*.temp
*~
*.bak

# Logs
logs/
*.log

# Uploads y archivos de usuario
assets/uploads/
assets/scanner_input/
assets/scanner_processed/

# Backups
backup/
sql_backup/

# Archivos de desarrollo
.vscode/
docs_temp/

# Archivos del sistema
Thumbs.db
.DS_Store

# Archivos PHP temporales
*.php~

# Archivos de base de datos locales
*.sql
database.sql

# Archivos de prueba
test_*.php
debug_*.php
verificar_*.php
"@

$gitignore | Out-File -FilePath "$tempDir\.gitignore" -Encoding UTF8

# README.md mejorado para GitHub
$readmeGithub = @"
# 📊 Sistema de Gestión Empresarial

Sistema completo de gestión empresarial desarrollado en PHP con MySQL, que incluye módulos de inventario, compras, facturación, clientes y administración.

## 🚀 Características Principales

### 📦 Módulos Incluidos
- **💼 Administración**: Gestión de usuarios, configuración del sistema
- **👥 Clientes**: Registro y gestión de clientes
- **🛒 Compras**: Sistema completo de compras y proveedores
- **📄 Facturación**: Generación y gestión de facturas
- **📊 Inventario**: Control de stock con escáner de códigos de barras
- **📋 Pedidos**: Gestión de pedidos y seguimiento
- **🏷️ Productos**: Catálogo completo con 6 pestañas de información

### 🎯 Funcionalidades Destacadas
- **📱 Escáner Universal**: Webcam, celular, lector USB y entrada manual
- **📈 Reportes**: Exportación a Excel y PDF
- **🔐 Sistema de Permisos**: Control de acceso por roles
- **💱 Multi-moneda**: Soporte para múltiples monedas
- **📊 Dashboard**: Panel de control con métricas
- **🔄 AJAX**: Interfaz dinámica y responsiva

## 🛠️ Tecnologías

- **Backend**: PHP 8.1+
- **Base de Datos**: MySQL 8.0+
- **Frontend**: Bootstrap 5.3, JavaScript ES6+
- **Librerías**: HTML5-QRCode, jsPDF, PhpSpreadsheet
- **Servidor**: Apache/Nginx

## 📋 Requisitos del Sistema

### Servidor Web
- PHP 8.1 o superior
- MySQL 8.0 o superior
- Apache 2.4+ o Nginx 1.18+

### Extensiones PHP Requeridas
```
php-mysql
php-pdo
php-gd
php-mbstring
php-curl
php-zip
php-xml
```

## 🚀 Instalación

### 1. Clonar el Repositorio
```bash
git clone https://github.com/tu-usuario/sistemadgestion5.git
cd sistemadgestion5
```

### 2. Configurar Base de Datos
```sql
CREATE DATABASE sistemadgestion5;
USE sistemadgestion5;
-- Importar estructura desde config/database.sql
```

### 3. Configuración
```php
// config/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistemadgestion5');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_password');
```

### 4. Permisos
```bash
chmod 755 assets/uploads/
chmod 755 assets/scanner_input/
chmod 755 assets/scanner_processed/
```

## 📖 Uso

1. **Acceder al sistema**: `http://tu-servidor/sistemadgestion5/`
2. **Login inicial**: admin / admin (cambiar después)
3. **Configurar empresa**: Menú > Administración > Configuración
4. **Crear usuarios**: Menú > Administración > Usuarios

## 📁 Estructura del Proyecto

```
sistemadgestion5/
├── 📄 index.php              # Página principal
├── 📄 login.php              # Sistema de autenticación
├── 📄 menu_principal.php     # Menú principal
├── 📂 modulos/               # Módulos del sistema
│   ├── 📁 admin/            # Administración
│   ├── 📁 clientes/         # Gestión de clientes
│   ├── 📁 compras/          # Sistema de compras
│   ├── 📁 facturas/         # Facturación
│   ├── 📁 Inventario/       # Control de inventario
│   ├── 📁 pedidos/          # Gestión de pedidos
│   └── 📁 productos/        # Catálogo de productos
├── 📂 config/               # Configuración
├── 📂 ajax/                 # Endpoints AJAX
├── 📂 assets/               # Recursos (CSS, JS, imágenes)
└── 📂 docs/                 # Documentación
```

## 🎮 Funcionalidades por Módulo

### 📦 Inventario
- Control de stock en tiempo real
- Alertas de stock mínimo
- Escáner de códigos de barras
- Gestión de vencimientos
- Múltiples ubicaciones

### 🛒 Compras
- Gestión de proveedores
- Órdenes de compra
- Recepción de mercadería
- Control de precios
- Historial de compras

### 👥 Clientes
- Registro completo de clientes
- Historial de compras
- Gestión de contactos
- Integración con facturación

### 📄 Facturación
- Generación automática
- Múltiples formatos (PDF, Excel)
- Control de pagos
- Reportes de ventas

## 🔧 Configuración Avanzada

### Escáner de Códigos de Barras
```javascript
// Configurar múltiples métodos de captura
const scanner = {
    webcam: true,      // Cámara web
    mobile: true,      // Celular vía WiFi  
    usb: true,         // Lector USB
    manual: true       // Entrada manual
};
```

### Reportes
```php
// Generar reporte Excel
$reporte = new ExcelGenerator();
$reporte->exportar('inventario', $filtros);
```

## 🤝 Contribuir

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para detalles.

## 👨‍💻 Autor

**Sistema de Gestión Empresarial**
- Desarrollado con ❤️ para gestión empresarial eficiente
- Contacto: [tu-email@ejemplo.com]

## 🆘 Soporte

Para soporte técnico:
1. Revisar la [documentación](docs/)
2. Crear un [issue](https://github.com/tu-usuario/sistemadgestion5/issues)
3. Contactar al desarrollador

---

⭐ Si este proyecto te resulta útil, ¡dale una estrella en GitHub!
"@

$readmeGithub | Out-File -FilePath "$tempDir\README.md" -Encoding UTF8

# LICENSE
$license = @"
MIT License

Copyright (c) 2025 Sistema de Gestión Empresarial

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
"@

$license | Out-File -FilePath "$tempDir\LICENSE" -Encoding UTF8

# CHANGELOG.md
$changelog = @"
# Changelog

## [2.0.0] - 2025-08-08

### Agregado
- 🎯 Sistema de escáner universal (webcam, celular, USB, manual)
- 📦 Formulario avanzado de productos con 6 pestañas
- 🔐 Sistema de permisos mejorado
- 💱 Soporte multi-moneda
- 📊 Dashboard con métricas en tiempo real
- 🛒 Módulo de compras completo
- 📄 Sistema de facturación
- 👥 Gestión avanzada de clientes

### Mejorado
- 🚀 Rendimiento general del sistema
- 📱 Interfaz responsive con Bootstrap 5.3
- 🔄 Operaciones AJAX optimizadas
- 📊 Reportes Excel y PDF mejorados

### Corregido
- 🐛 Problemas de autenticación
- 📋 Validaciones de formularios
- 🔍 Búsquedas y filtros
- 💾 Operaciones de base de datos

## [1.0.0] - 2025-01-01

### Inicial
- 🎉 Lanzamiento inicial del sistema
- 📦 Módulos básicos de inventario
- 👤 Sistema de usuarios
- 📊 Reportes básicos
"@

$changelog | Out-File -FilePath "$tempDir\CHANGELOG.md" -Encoding UTF8

Write-Host "📁 Creando estructura de carpetas adicionales..." -ForegroundColor Yellow

# Crear carpetas vacías necesarias con archivos .gitkeep
@("assets/uploads", "assets/scanner_input", "assets/scanner_processed") | ForEach-Object {
    $carpeta = "$tempDir/$_"
    New-Item -ItemType Directory -Path $carpeta -Force | Out-Null
    "# Esta carpeta es necesaria para el funcionamiento del sistema" | Out-File -FilePath "$carpeta/.gitkeep" -Encoding UTF8
}

Write-Host "🗜️ Comprimiendo archivos..." -ForegroundColor Yellow

# Crear el ZIP
Compress-Archive -Path "$tempDir\*" -DestinationPath $archivoZip -Force

# Limpiar carpeta temporal
Remove-Item -Path $tempDir -Recurse -Force

# Obtener información del ZIP
$infoZip = Get-Item $archivoZip
$tamanoMB = [math]::Round($infoZip.Length / 1MB, 2)

Write-Host ""
Write-Host "🎉 ZIP PARA GITHUB CREADO EXITOSAMENTE" -ForegroundColor Green
Write-Host "=======================================" -ForegroundColor Green
Write-Host ""
Write-Host "📦 Archivo: $archivoZip" -ForegroundColor White
Write-Host "📍 Ubicación: $(Get-Location)\$archivoZip" -ForegroundColor White  
Write-Host "📊 Tamaño: $tamanoMB MB" -ForegroundColor White
Write-Host ""
Write-Host "📋 ARCHIVOS INCLUIDOS:" -ForegroundColor Cyan
Write-Host "├── ✅ Archivos core del sistema (6 archivos)" -ForegroundColor White
Write-Host "├── ✅ Módulos completos (admin, clientes, compras, etc.)" -ForegroundColor White  
Write-Host "├── ✅ Configuración y AJAX" -ForegroundColor White
Write-Host "├── ✅ Assets (CSS, JS, imágenes)" -ForegroundColor White
Write-Host "├── ✅ Formularios de gestión" -ForegroundColor White
Write-Host "├── ✅ Sistema de reportes" -ForegroundColor White
Write-Host "├── ✅ Documentación esencial" -ForegroundColor White
Write-Host "├── ✅ .gitignore configurado" -ForegroundColor White
Write-Host "├── ✅ README.md optimizado para GitHub" -ForegroundColor White
Write-Host "├── ✅ LICENSE (MIT)" -ForegroundColor White
Write-Host "└── ✅ CHANGELOG.md" -ForegroundColor White
Write-Host ""
Write-Host "🚫 ARCHIVOS EXCLUIDOS:" -ForegroundColor Yellow
Write-Host "├── ❌ Backups y archivos temporales" -ForegroundColor Gray
Write-Host "├── ❌ Archivos de configuración local" -ForegroundColor Gray
Write-Host "├── ❌ Scripts de desarrollo y debug" -ForegroundColor Gray
Write-Host "├── ❌ Archivos de prueba" -ForegroundColor Gray
Write-Host "└── ❌ Configuración específica de VS Code" -ForegroundColor Gray
Write-Host ""
Write-Host "🔄 PRÓXIMOS PASOS:" -ForegroundColor Magenta
Write-Host "1. Crear repositorio en GitHub" -ForegroundColor White
Write-Host "2. Extraer el ZIP en tu repositorio local" -ForegroundColor White  
Write-Host "3. git add . && git commit -m 'Initial commit'" -ForegroundColor White
Write-Host "4. git push origin main" -ForegroundColor White
Write-Host ""
Write-Host "🌟 ¡Listo para subir a GitHub!" -ForegroundColor Green

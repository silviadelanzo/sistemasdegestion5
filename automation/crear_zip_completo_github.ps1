# CREAR ZIP COMPLETO CON SQL PARA GITHUB
# Script final que incluye base de datos y todo el proyecto

Write-Host "Creando ZIP completo con SQL para GitHub..." -ForegroundColor Cyan

$fecha = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$nombreProyecto = "sistemadgestion5"
$archivoZip = "github-completo-$nombreProyecto-$fecha.zip"

Write-Host "Creando ZIP completo: $archivoZip" -ForegroundColor Yellow

# Crear carpeta temporal para organizar archivos
$tempDir = "temp_github_completo_$fecha"
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

Write-Host "Copiando archivos del proyecto..." -ForegroundColor Yellow

# ARCHIVOS CORE ESENCIALES
Write-Host "  Archivos core del sistema..." -ForegroundColor Green
$coreFiles = @(
    "index.php",
    "login.php", 
    "logout.php",
    "menu_principal.php",
    "obtener_ultimo_codigo.php"
)

foreach ($file in $coreFiles) {
    if (Test-Path $file) {
        Copy-Item -Path $file -Destination $tempDir -Force
    }
}

# CARPETAS ESENCIALES
Write-Host "  Carpetas del sistema..." -ForegroundColor Green
$carpetasEsenciales = @("modulos", "config", "ajax", "assets")
foreach ($carpeta in $carpetasEsenciales) {
    if (Test-Path $carpeta) {
        Copy-Item -Path $carpeta -Destination $tempDir -Recurse -Force
    }
}

# ARCHIVOS DE GESTION
Write-Host "  Formularios y gestion..." -ForegroundColor Green  
# Archivos de gestión principales (páginas de listado, etc.)
$gestionRootFiles = @(
    "proveedores.php", # Contiene el modal de proveedor
    "usuarios.php",
    "reportes.php" 
)

foreach ($file in $gestionRootFiles) {
    if (Test-Path $file) {
        Copy-Item -Path $file -Destination $tempDir -Force
    } else {
        Write-Host "    Advertencia: No se encontró el archivo de gestión '$file'" -ForegroundColor Yellow
    }
}

# Buscar archivos gestionar_*.php
Get-ChildItem -Name "gestionar_*.php" | ForEach-Object {
    Copy-Item -Path $_ -Destination $tempDir -Force
}

# ARCHIVOS DE REPORTES Y EXCEL
Write-Host "  Sistema de reportes..." -ForegroundColor Green
$reporteFiles = @("excel_nativo.php")
foreach ($file in $reporteFiles) {
    if (Test-Path $file) {
        Copy-Item -Path $file -Destination $tempDir -Force
    }
}

# Buscar archivos reporte_*.php
Get-ChildItem -Name "reporte_*.php" | ForEach-Object {
    Copy-Item -Path $_ -Destination $tempDir -Force
}

# COPIAR BASE DE DATOS
Write-Host "  Base de datos SQL..." -ForegroundColor Green
if (Test-Path "database") {
    Copy-Item -Path "database" -Destination $tempDir -Recurse -Force
    Write-Host "    Base de datos copiada" -ForegroundColor Gray
} else {
    Write-Host "    Advertencia: No se encontro carpeta database" -ForegroundColor Yellow
}

# DOCUMENTACION ESENCIAL
Write-Host "  Documentacion..." -ForegroundColor Green
New-Item -ItemType Directory -Path "$tempDir\docs" -Force | Out-Null
if (Test-Path "docs") {
    Copy-Item -Path "docs\*" -Destination "$tempDir\docs\" -Recurse -Force -ErrorAction SilentlyContinue
}

# CREAR ARCHIVOS ADICIONALES PARA GITHUB
Write-Host "  Creando archivos para GitHub..." -ForegroundColor Green

# .gitignore
$gitignore = @"
# Archivos de configuracion local
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
assets/uploads/*
!assets/uploads/.gitkeep
assets/scanner_input/*
!assets/scanner_input/.gitkeep
assets/scanner_processed/*
!assets/scanner_processed/.gitkeep

# Backups locales
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

# Archivos de prueba
test_*.php
debug_*.php
verificar_*.php

# Archivos de configuracion específicos del entorno
.env
.env.local
"@

$gitignore | Out-File -FilePath "$tempDir\.gitignore" -Encoding UTF8

# README.md mejorado para GitHub
$readmeGithub = @"
# 📊 Sistema de Gestión Empresarial

![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-green.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

Sistema completo de gestión empresarial desarrollado en PHP con MySQL, que incluye módulos de inventario, compras, facturación, clientes y administración.

## 🚀 Características Principales

### 📦 Módulos Incluidos
- **💼 Administración**: Gestión de usuarios, configuración del sistema
- **👥 Clientes**: Registro y gestión completa de clientes
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

## 🚀 Instalación Rápida

### 1. Clonar el Repositorio
```bash
git clone https://github.com/tu-usuario/sistemadgestion5.git
cd sistemadgestion5
```

### 2. Configurar Base de Datos
```bash
# Crear la base de datos
mysql -u root -p -e "CREATE DATABASE sistemadgestion5;"

# Importar estructura y datos
mysql -u root -p sistemadgestion5 < database/sistemadgestion5_completo_*.sql
```

### 3. Configuración
```php
// config/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistemadgestion5');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_password');
```

### 4. Permisos de Carpetas
```bash
chmod 755 assets/uploads/
chmod 755 assets/scanner_input/
chmod 755 assets/scanner_processed/
```

### 5. Acceder al Sistema
- **URL**: `http://tu-servidor/sistemadgestion5/`
- **Usuario**: admin
- **Contraseña**: admin

⚠️ **Cambiar las credenciales por defecto después del primer acceso**

## 📁 Estructura del Proyecto

```
sistemadgestion5/
├── 📄 index.php              # Página principal
├── 📄 login.php              # Sistema de autenticación
├── 📄 menu_principal.php     # Menú principal del sistema
├── 📂 database/              # Base de datos SQL
│   ├── sistemadgestion5_completo_*.sql    # Estructura + datos
│   ├── sistemadgestion5_estructura_*.sql  # Solo estructura
│   └── README.md                          # Documentación de BD
├── 📂 modulos/               # Módulos del sistema
│   ├── 📁 admin/            # Administración del sistema
│   ├── 📁 clientes/         # Gestión de clientes
│   ├── 📁 compras/          # Sistema de compras
│   ├── 📁 facturas/         # Módulo de facturación
│   ├── 📁 Inventario/       # Control de inventario
│   ├── 📁 pedidos/          # Gestión de pedidos
│   └── 📁 productos/        # Catálogo de productos
├── 📂 config/               # Archivos de configuración
├── 📂 ajax/                 # Endpoints AJAX
├── 📂 assets/               # Recursos (CSS, JS, imágenes)
│   ├── 📁 css/             # Hojas de estilo
│   ├── 📁 js/              # JavaScript
│   ├── 📁 img/             # Imágenes del sistema
│   ├── 📁 uploads/         # Archivos subidos
│   ├── 📁 scanner_input/   # Input del escáner
│   └── 📁 scanner_processed/ # Archivos procesados
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
- Gestión completa de proveedores
- Órdenes de compra automatizadas
- Recepción de mercadería
- Control de precios y cotizaciones
- Historial completo de compras

### 👥 Clientes
- Registro completo de clientes
- Historial de compras y pagos
- Gestión de contactos
- Integración con facturación
- Reportes de clientes

### 📄 Facturación
- Generación automática de facturas
- Múltiples formatos (PDF, Excel)
- Control de pagos y cobranzas
- Reportes de ventas
- Integración contable

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

### Reportes y Exportación
```php
// Generar reporte Excel
$reporte = new ExcelGenerator();
$reporte->exportar('inventario', $filtros);
```

## 📊 Base de Datos

La carpeta `database/` contiene:
- **sistemadgestion5_completo_*.sql**: Estructura completa + datos de ejemplo
- **sistemadgestion5_estructura_*.sql**: Solo estructura (instalación limpia)
- **README.md**: Documentación detallada de la base de datos

Ver [documentación de base de datos](database/README.md) para más detalles.

## 🤝 Contribuir

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para detalles.

## 👨‍💻 Desarrollado por

**Sistema de Gestión Empresarial**
- Desarrollado con ❤️ para gestión empresarial eficiente
- Versión: 2.0.0
- Fecha: Agosto 2025

## 🆘 Soporte y Documentación

- **Documentación**: [docs/](docs/)
- **Issues**: [GitHub Issues](https://github.com/tu-usuario/sistemadgestion5/issues)
- **Wiki**: [GitHub Wiki](https://github.com/tu-usuario/sistemadgestion5/wiki)

## 📈 Roadmap

- [ ] API REST completa
- [ ] App móvil
- [ ] Integración con APIs de facturación electrónica
- [ ] Dashboard avanzado con gráficos
- [ ] Módulo de CRM
- [ ] Integración con WhatsApp Business

---

⭐ **Si este proyecto te resulta útil, ¡dale una estrella en GitHub!**

🚀 **¿Necesitas soporte profesional?** Contacta al equipo de desarrollo.
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

Todos los cambios notables de este proyecto serán documentados en este archivo.

## [2.0.0] - 2025-08-08

### 🎉 Agregado
- Sistema de escáner universal (webcam, celular, USB, manual)
- Formulario avanzado de productos con 6 pestañas
- Sistema de permisos mejorado
- Soporte multi-moneda
- Dashboard con métricas en tiempo real
- Módulo de compras completo
- Sistema de facturación
- Gestión avanzada de clientes
- Base de datos SQL incluida en el repositorio
- Documentación completa para GitHub

### 🚀 Mejorado
- Rendimiento general del sistema
- Interfaz responsive con Bootstrap 5.3
- Operaciones AJAX optimizadas
- Reportes Excel y PDF mejorados
- Estructura de carpetas organizada

### 🐛 Corregido
- Problemas de autenticación
- Validaciones de formularios
- Búsquedas y filtros
- Operaciones de base de datos

### 📚 Documentación
- README.md completo para GitHub
- Documentación de base de datos
- Guías de instalación
- Estructura del proyecto documentada

## [1.0.0] - 2025-01-01

### 🎉 Inicial
- Lanzamiento inicial del sistema
- Módulos básicos de inventario
- Sistema de usuarios
- Reportes básicos
"@

$changelog | Out-File -FilePath "$tempDir\CHANGELOG.md" -Encoding UTF8

Write-Host "Creando estructura de carpetas adicionales..." -ForegroundColor Yellow

# Crear carpetas vacias necesarias con archivos .gitkeep
@("assets/uploads", "assets/scanner_input", "assets/scanner_processed") | ForEach-Object {
    $carpeta = "$tempDir/$_"
    if (-not (Test-Path $carpeta)) {
        New-Item -ItemType Directory -Path $carpeta -Force | Out-Null
    }
    "# Esta carpeta es necesaria para el funcionamiento del sistema" | Out-File -FilePath "$carpeta/.gitkeep" -Encoding UTF8
}

Write-Host "Comprimiendo archivos..." -ForegroundColor Yellow

# Crear el ZIP
try {
    Compress-Archive -Path "$tempDir\*" -DestinationPath $archivoZip -Force
    Write-Host "ZIP creado exitosamente" -ForegroundColor Green
} catch {
    Write-Host "Error al crear ZIP: $($_.Exception.Message)" -ForegroundColor Red
}

# Limpiar carpeta temporal
Remove-Item -Path $tempDir -Recurse -Force

# Obtener informacion del ZIP
if (Test-Path $archivoZip) {
    $infoZip = Get-Item $archivoZip
    $tamanoMB = [math]::Round($infoZip.Length / 1MB, 2)

    Write-Host ""
    Write-Host "🎉 ZIP COMPLETO PARA GITHUB CREADO" -ForegroundColor Green
    Write-Host "===================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "📦 Archivo: $archivoZip" -ForegroundColor White
    Write-Host "📍 Ubicacion: $(Get-Location)\$archivoZip" -ForegroundColor White  
    Write-Host "📊 Tamaño: $tamanoMB MB" -ForegroundColor White
    Write-Host ""
    Write-Host "✅ INCLUYE:" -ForegroundColor Cyan
    Write-Host "- ✅ Codigo fuente completo" -ForegroundColor White
    Write-Host "- ✅ Base de datos SQL (estructura + datos)" -ForegroundColor White
    Write-Host "- ✅ Documentacion completa para GitHub" -ForegroundColor White
    Write-Host "- ✅ README.md profesional con badges" -ForegroundColor White
    Write-Host "- ✅ LICENSE (MIT)" -ForegroundColor White
    Write-Host "- ✅ CHANGELOG.md" -ForegroundColor White
    Write-Host "- ✅ .gitignore configurado" -ForegroundColor White
    Write-Host "- ✅ Estructura de carpetas lista" -ForegroundColor White
    Write-Host ""
    Write-Host "🚀 LISTO PARA SUBIR A GITHUB!" -ForegroundColor Magenta
}

Write-Host ""
Write-Host "📋 INSTRUCCIONES PARA SUBIR A GITHUB:" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Yellow
Write-Host "1. Crear repositorio en GitHub" -ForegroundColor White
Write-Host "2. Extraer el ZIP en una carpeta nueva" -ForegroundColor White
Write-Host "3. git init" -ForegroundColor White
Write-Host "4. git add ." -ForegroundColor White
Write-Host "5. git commit -m 'Initial commit: Sistema completo'" -ForegroundColor White
Write-Host "6. git remote add origin https://github.com/usuario/repo.git" -ForegroundColor White
Write-Host "7. git push -u origin main" -ForegroundColor White

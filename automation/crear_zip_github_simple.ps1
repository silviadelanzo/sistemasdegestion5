# PREPARACION PARA GITHUB REPOSITORY
# Script para crear ZIP optimizado para subir a GitHub

Write-Host "Preparando proyecto para GitHub..." -ForegroundColor Cyan

$fecha = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$nombreProyecto = "sistemadgestion5"
$archivoZip = "github-ready-$nombreProyecto-$fecha.zip"

Write-Host "Creando ZIP para GitHub: $archivoZip" -ForegroundColor Yellow

# Crear carpeta temporal para organizar archivos
$tempDir = "temp_github_$fecha"
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

Write-Host "Copiando archivos esenciales..." -ForegroundColor Yellow

# ARCHIVOS CORE ESENCIALES
Write-Host "  Archivos core del sistema..." -ForegroundColor Green
$coreFiles = @(
    "index.php",
    "login.php", 
    "logout.php",
    "menu_principal.php",
    "obtener_ultimo_codigo.php",
    "README.md"
)

foreach ($file in $coreFiles) {
    if (Test-Path $file) {
        Copy-Item -Path $file -Destination $tempDir -Force
        Write-Host "    $file copiado" -ForegroundColor Gray
    }
}

# CARPETAS ESENCIALES
Write-Host "  Carpetas del sistema..." -ForegroundColor Green
$carpetasEsenciales = @("modulos", "config", "ajax", "assets")
foreach ($carpeta in $carpetasEsenciales) {
    if (Test-Path $carpeta) {
        Copy-Item -Path $carpeta -Destination $tempDir -Recurse -Force
        Write-Host "    Carpeta $carpeta copiada" -ForegroundColor Gray
    }
}

# ARCHIVOS DE GESTION
Write-Host "  Formularios y gestion..." -ForegroundColor Green  
$gestionFiles = @(
    "categoria_form.php",
    "lugar_form.php", 
    "usuario_form.php",
    "proveedor_form.php",
    "proveedores.php",
    "usuarios.php",
    "reportes.php"
)

foreach ($file in $gestionFiles) {
    if (Test-Path $file) {
        Copy-Item -Path $file -Destination $tempDir -Force
        Write-Host "    $file copiado" -ForegroundColor Gray
    }
}

# Buscar archivos gestionar_*.php
Get-ChildItem -Name "gestionar_*.php" | ForEach-Object {
    Copy-Item -Path $_ -Destination $tempDir -Force
    Write-Host "    $_ copiado" -ForegroundColor Gray
}

# ARCHIVOS DE REPORTES Y EXCEL
Write-Host "  Sistema de reportes..." -ForegroundColor Green
$reporteFiles = @("excel_nativo.php")
foreach ($file in $reporteFiles) {
    if (Test-Path $file) {
        Copy-Item -Path $file -Destination $tempDir -Force
        Write-Host "    $file copiado" -ForegroundColor Gray
    }
}

# Buscar archivos reporte_*.php
Get-ChildItem -Name "reporte_*.php" | ForEach-Object {
    Copy-Item -Path $_ -Destination $tempDir -Force
    Write-Host "    $_ copiado" -ForegroundColor Gray
}

# DOCUMENTACION ESENCIAL
Write-Host "  Documentacion principal..." -ForegroundColor Green
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
# Sistema de Gestion Empresarial

Sistema completo de gestion empresarial desarrollado en PHP con MySQL, que incluye modulos de inventario, compras, facturacion, clientes y administracion.

## Caracteristicas Principales

### Modulos Incluidos
- **Administracion**: Gestion de usuarios, configuracion del sistema
- **Clientes**: Registro y gestion de clientes
- **Compras**: Sistema completo de compras y proveedores
- **Facturacion**: Generacion y gestion de facturas
- **Inventario**: Control de stock con escaner de codigos de barras
- **Pedidos**: Gestion de pedidos y seguimiento
- **Productos**: Catalogo completo con 6 pestanas de informacion

### Funcionalidades Destacadas
- **Escaner Universal**: Webcam, celular, lector USB y entrada manual
- **Reportes**: Exportacion a Excel y PDF
- **Sistema de Permisos**: Control de acceso por roles
- **Multi-moneda**: Soporte para multiples monedas
- **Dashboard**: Panel de control con metricas
- **AJAX**: Interfaz dinamica y responsiva

## Tecnologias

- **Backend**: PHP 8.1+
- **Base de Datos**: MySQL 8.0+
- **Frontend**: Bootstrap 5.3, JavaScript ES6+
- **Librerias**: HTML5-QRCode, jsPDF, PhpSpreadsheet
- **Servidor**: Apache/Nginx

## Requisitos del Sistema

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

## Instalacion

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

### 3. Configuracion
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

## Uso

1. **Acceder al sistema**: `http://tu-servidor/sistemadgestion5/`
2. **Login inicial**: admin / admin (cambiar despues)
3. **Configurar empresa**: Menu > Administracion > Configuracion
4. **Crear usuarios**: Menu > Administracion > Usuarios

## Estructura del Proyecto

```
sistemadgestion5/
├── index.php              # Pagina principal
├── login.php              # Sistema de autenticacion
├── menu_principal.php     # Menu principal
├── modulos/               # Modulos del sistema
│   ├── admin/            # Administracion
│   ├── clientes/         # Gestion de clientes
│   ├── compras/          # Sistema de compras
│   ├── facturas/         # Facturacion
│   ├── Inventario/       # Control de inventario
│   ├── pedidos/          # Gestion de pedidos
│   └── productos/        # Catalogo de productos
├── config/               # Configuracion
├── ajax/                 # Endpoints AJAX
├── assets/               # Recursos (CSS, JS, imagenes)
└── docs/                 # Documentacion
```

## Contribuir

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## Licencia

Este proyecto esta bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para detalles.

## Autor

**Sistema de Gestion Empresarial**
- Desarrollado para gestion empresarial eficiente

## Soporte

Para soporte tecnico:
1. Revisar la [documentacion](docs/)
2. Crear un [issue](https://github.com/tu-usuario/sistemadgestion5/issues)
3. Contactar al desarrollador

---

Si este proyecto te resulta util, dale una estrella en GitHub!
"@

$readmeGithub | Out-File -FilePath "$tempDir\README.md" -Encoding UTF8

# LICENSE
$license = @"
MIT License

Copyright (c) 2025 Sistema de Gestion Empresarial

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

Write-Host "Creando estructura de carpetas adicionales..." -ForegroundColor Yellow

# Crear carpetas vacias necesarias con archivos .gitkeep
@("assets/uploads", "assets/scanner_input", "assets/scanner_processed") | ForEach-Object {
    $carpeta = "$tempDir/$_"
    New-Item -ItemType Directory -Path $carpeta -Force | Out-Null
    "# Esta carpeta es necesaria para el funcionamiento del sistema" | Out-File -FilePath "$carpeta/.gitkeep" -Encoding UTF8
}

Write-Host "Comprimiendo archivos..." -ForegroundColor Yellow

# Crear el ZIP
Compress-Archive -Path "$tempDir\*" -DestinationPath $archivoZip -Force

# Limpiar carpeta temporal
Remove-Item -Path $tempDir -Recurse -Force

# Obtener informacion del ZIP
$infoZip = Get-Item $archivoZip
$tamanoMB = [math]::Round($infoZip.Length / 1MB, 2)

Write-Host ""
Write-Host "ZIP PARA GITHUB CREADO EXITOSAMENTE" -ForegroundColor Green
Write-Host "===================================" -ForegroundColor Green
Write-Host ""
Write-Host "Archivo: $archivoZip" -ForegroundColor White
Write-Host "Ubicacion: $(Get-Location)\$archivoZip" -ForegroundColor White  
Write-Host "Tamano: $tamanoMB MB" -ForegroundColor White
Write-Host ""
Write-Host "ARCHIVOS INCLUIDOS:" -ForegroundColor Cyan
Write-Host "- Archivos core del sistema (6 archivos)" -ForegroundColor White
Write-Host "- Modulos completos (admin, clientes, compras, etc.)" -ForegroundColor White  
Write-Host "- Configuracion y AJAX" -ForegroundColor White
Write-Host "- Assets (CSS, JS, imagenes)" -ForegroundColor White
Write-Host "- Formularios de gestion" -ForegroundColor White
Write-Host "- Sistema de reportes" -ForegroundColor White
Write-Host "- Documentacion esencial" -ForegroundColor White
Write-Host "- .gitignore configurado" -ForegroundColor White
Write-Host "- README.md optimizado para GitHub" -ForegroundColor White
Write-Host "- LICENSE (MIT)" -ForegroundColor White
Write-Host ""
Write-Host "ARCHIVOS EXCLUIDOS:" -ForegroundColor Yellow
Write-Host "- Backups y archivos temporales" -ForegroundColor Gray
Write-Host "- Archivos de configuracion local" -ForegroundColor Gray
Write-Host "- Scripts de desarrollo y debug" -ForegroundColor Gray
Write-Host "- Archivos de prueba" -ForegroundColor Gray
Write-Host "- Configuracion especifica de VS Code" -ForegroundColor Gray
Write-Host ""
Write-Host "PROXIMOS PASOS:" -ForegroundColor Magenta
Write-Host "1. Crear repositorio en GitHub" -ForegroundColor White
Write-Host "2. Extraer el ZIP en tu repositorio local" -ForegroundColor White  
Write-Host "3. git add . && git commit -m 'Initial commit'" -ForegroundColor White
Write-Host "4. git push origin main" -ForegroundColor White
Write-Host ""
Write-Host "Listo para subir a GitHub!" -ForegroundColor Green

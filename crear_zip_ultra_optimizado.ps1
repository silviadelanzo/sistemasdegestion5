# CREAR ZIP ULTRA-OPTIMIZADO PARA GITHUB
# Solo con los 32 archivos esenciales del sistema

Write-Host "CREANDO ZIP ULTRA-OPTIMIZADO PARA GITHUB" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

$fecha = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$nombreZip = "github-ultra-optimizado-$fecha.zip"

Write-Host ""
Write-Host "Creando ZIP optimizado: $nombreZip" -ForegroundColor Yellow

# Crear carpeta temporal
$tempDir = "temp_optimizado_$fecha"
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

Write-Host ""
Write-Host "COPIANDO SOLO ARCHIVOS ESENCIALES..." -ForegroundColor Green

# ARCHIVOS ESENCIALES DEL SISTEMA (17 archivos)
$esenciales = @(
    "index.php",
    "login.php", 
    "logout.php",
    "menu_principal.php",
    "obtener_ultimo_codigo.php",
    "categoria_form.php",
    "lugar_form.php",
    "usuario_form.php",
    "proveedor_form.php",
    "usuarios.php",
    "proveedores.php",
    "reportes.php",
    "gestionar_categoria.php",
    "gestionar_configuracion.php",
    "gestionar_lugar.php",
    "gestionar_usuario.php",
    "README.md"
)

Write-Host "1. Archivos esenciales del sistema:" -ForegroundColor Yellow
foreach ($archivo in $esenciales) {
    if (Test-Path $archivo) {
        Copy-Item -Path $archivo -Destination $tempDir -Force
        Write-Host "   $archivo" -ForegroundColor White
    }
}

# REPORTES Y EXCEL (11 archivos)
$reportes = @(
    "excel_nativo.php",
    "reporte_categorias_con_precios_pdf.php",
    "reporte_categorias_excel.php",
    "reporte_categorias_sin_precios_pdf.php",
    "reporte_completo_excel.php",
    "reporte_completo_excel_v2.php",
    "reporte_lugares_excel.php",
    "reporte_lugares_pdf.php",
    "reporte_pdf_html.php",
    "reporte_total_pdf.php",
    "reporte_total_sin_precios_pdf.php"
)

Write-Host ""
Write-Host "2. Sistema de reportes y Excel:" -ForegroundColor Yellow
foreach ($archivo in $reportes) {
    if (Test-Path $archivo) {
        Copy-Item -Path $archivo -Destination $tempDir -Force
        Write-Host "   $archivo" -ForegroundColor White
    }
}

# CONFIGURACION Y AJAX (4 archivos)
$configuracion = @(
    "ajax_categorias.php",
    "ajax_lugares.php", 
    "configuracion_sistema.php",
    "setup_compras_mejorado.sql"
)

Write-Host ""
Write-Host "3. Configuracion y AJAX:" -ForegroundColor Yellow
foreach ($archivo in $configuracion) {
    if (Test-Path $archivo) {
        Copy-Item -Path $archivo -Destination $tempDir -Force
        Write-Host "   $archivo" -ForegroundColor White
    }
}

# COPIAR CARPETAS ESENCIALES
Write-Host ""
Write-Host "4. Carpetas del sistema:" -ForegroundColor Yellow
$carpetas = @("modulos", "config", "ajax", "assets")
foreach ($carpeta in $carpetas) {
    if (Test-Path $carpeta) {
        Copy-Item -Path $carpeta -Destination $tempDir -Recurse -Force
        Write-Host "   $carpeta/ (completa)" -ForegroundColor White
    }
}

# CREAR ARCHIVOS PARA GITHUB
Write-Host ""
Write-Host "5. Archivos especiales para GitHub:" -ForegroundColor Yellow

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
"@

$gitignore | Out-File -FilePath "$tempDir\.gitignore" -Encoding UTF8
Write-Host "   .gitignore" -ForegroundColor White

# README optimizado
$readme = @"
# Sistema de Gestion Empresarial

Sistema completo de gestion empresarial desarrollado en PHP con MySQL.

## Caracteristicas Principales

- **Gestion de Inventario**: Control de stock con escaner de codigos de barras
- **Modulo de Compras**: Sistema completo de compras y proveedores  
- **Facturacion**: Generacion y gestion de facturas
- **Clientes**: Registro y gestion de clientes
- **Reportes**: Exportacion a Excel y PDF
- **Administracion**: Gestion de usuarios y configuracion

## Tecnologias

- PHP 8.1+
- MySQL 8.0+
- Bootstrap 5.3
- JavaScript ES6+

## Instalacion

1. Clonar repositorio
2. Configurar base de datos
3. Importar estructura SQL
4. Configurar archivo config.php

## Estructura

- modulos/ - Modulos del sistema
- config/ - Configuracion
- assets/ - Recursos (CSS, JS, imagenes)
- ajax/ - Endpoints AJAX

## Licencia

MIT License
"@

$readme | Out-File -FilePath "$tempDir\README.md" -Encoding UTF8 -Force
Write-Host "   README.md (optimizado)" -ForegroundColor White

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
Write-Host "   LICENSE (MIT)" -ForegroundColor White

# Crear carpetas necesarias
@("assets/uploads", "assets/scanner_input", "assets/scanner_processed") | ForEach-Object {
    $carpeta = "$tempDir/$_"
    New-Item -ItemType Directory -Path $carpeta -Force | Out-Null
    "# Carpeta necesaria para el sistema" | Out-File -FilePath "$carpeta/.gitkeep" -Encoding UTF8
}

Write-Host ""
Write-Host "COMPRIMIENDO ARCHIVOS..." -ForegroundColor Yellow

# Crear el ZIP optimizado
Compress-Archive -Path "$tempDir\*" -DestinationPath $nombreZip -Force

# Limpiar carpeta temporal
Remove-Item -Path $tempDir -Recurse -Force

# Informacion del ZIP
$infoZip = Get-Item $nombreZip
$tamanoMB = [math]::Round($infoZip.Length / 1MB, 2)

Write-Host ""
Write-Host "ZIP ULTRA-OPTIMIZADO CREADO EXITOSAMENTE" -ForegroundColor Green
Write-Host "=======================================" -ForegroundColor Green
Write-Host ""
Write-Host "Archivo: $nombreZip" -ForegroundColor White
Write-Host "Ubicacion: $(Get-Location)\$nombreZip" -ForegroundColor White
Write-Host "Tamaño: $tamanoMB MB" -ForegroundColor White
Write-Host ""
Write-Host "CONTENIDO OPTIMIZADO:" -ForegroundColor Cyan
Write-Host "- 17 archivos esenciales del sistema" -ForegroundColor White
Write-Host "- 11 archivos de reportes y Excel" -ForegroundColor White
Write-Host "- 4 archivos de configuracion y AJAX" -ForegroundColor White  
Write-Host "- Modulos completos (admin, clientes, compras, etc.)" -ForegroundColor White
Write-Host "- Assets y configuracion" -ForegroundColor White
Write-Host "- .gitignore configurado" -ForegroundColor White
Write-Host "- README.md optimizado" -ForegroundColor White
Write-Host "- LICENSE MIT" -ForegroundColor White
Write-Host ""
Write-Host "ELIMINADOS TODOS LOS ARCHIVOS INNECESARIOS:" -ForegroundColor Red
Write-Host "- Scripts de desarrollo y debug (23 archivos)" -ForegroundColor Gray
Write-Host "- Backups y archivos temporales (7 archivos)" -ForegroundColor Gray
Write-Host "- Archivos obsoletos (15 archivos)" -ForegroundColor Gray
Write-Host ""
Write-Host "PROYECTO LISTO PARA GITHUB!" -ForegroundColor Green
Write-Host "Solo archivos esenciales incluidos" -ForegroundColor White

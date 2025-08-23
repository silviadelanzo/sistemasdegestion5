# GENERADOR DE SQL PARA GITHUB - VERSION CORREGIDA
# Script para exportar la base de datos actual y crear ZIP completo

Write-Host "Generando SQL de la base de datos actual..." -ForegroundColor Cyan

$fecha = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"

# Configuracion de la base de datos
$dbHost = "localhost"
$dbName = "sistemadgestion5"
$dbUser = "root"
$dbPass = ""
$mysqldumpPath = "C:\xampp\mysql\bin\mysqldump.exe"

Write-Host "Configuracion de la base de datos:" -ForegroundColor Yellow
Write-Host "   Host: $dbHost" -ForegroundColor Gray
Write-Host "   Base de datos: $dbName" -ForegroundColor Gray
Write-Host "   Usuario: $dbUser" -ForegroundColor Gray

# Verificar si mysqldump existe
if (-not (Test-Path $mysqldumpPath)) {
    Write-Host "ERROR: No se encontro mysqldump en: $mysqldumpPath" -ForegroundColor Red
    Write-Host "Buscando mysqldump en rutas alternativas..." -ForegroundColor Yellow
    
    $alternatePaths = @(
        "C:\xampp\mysql\bin\mysqldump.exe",
        "C:\wamp\bin\mysql\mysql8.0.31\bin\mysqldump.exe",
        "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe"
    )
    
    foreach ($path in $alternatePaths) {
        if (Test-Path $path) {
            $mysqldumpPath = $path
            Write-Host "Encontrado en: $path" -ForegroundColor Green
            break
        }
    }
    
    if (-not (Test-Path $mysqldumpPath)) {
        Write-Host "No se pudo encontrar mysqldump. Verifica la instalacion de MySQL/XAMPP" -ForegroundColor Red
        exit 1
    }
}

Write-Host "Exportando base de datos..." -ForegroundColor Yellow

# Crear directorio para SQL si no existe
$sqlDir = "database"
New-Item -ItemType Directory -Path $sqlDir -Force | Out-Null

# Nombres de archivos SQL
$sqlCompleto = "$sqlDir\sistemadgestion5_completo_$fecha.sql"
$sqlEstructura = "$sqlDir\sistemadgestion5_estructura_$fecha.sql"

try {
    # 1. Exportar estructura completa con datos
    Write-Host "   Exportando estructura completa con datos..." -ForegroundColor Gray
    
    if ($dbPass) {
        & $mysqldumpPath -h $dbHost -u $dbUser -p$dbPass --routines --triggers --events --single-transaction $dbName | Out-File -FilePath $sqlCompleto -Encoding UTF8
    } else {
        & $mysqldumpPath -h $dbHost -u $dbUser --routines --triggers --events --single-transaction $dbName | Out-File -FilePath $sqlCompleto -Encoding UTF8
    }
    
    if (Test-Path $sqlCompleto) {
        $tamano1 = [math]::Round((Get-Item $sqlCompleto).Length / 1KB, 2)
        Write-Host "   Exportacion completa: $tamano1 KB" -ForegroundColor Green
    }
    
    # 2. Exportar solo estructura (sin datos)
    Write-Host "   Exportando solo estructura..." -ForegroundColor Gray
    
    if ($dbPass) {
        & $mysqldumpPath -h $dbHost -u $dbUser -p$dbPass --no-data --routines --triggers --events $dbName | Out-File -FilePath $sqlEstructura -Encoding UTF8
    } else {
        & $mysqldumpPath -h $dbHost -u $dbUser --no-data --routines --triggers --events $dbName | Out-File -FilePath $sqlEstructura -Encoding UTF8
    }
    
    if (Test-Path $sqlEstructura) {
        $tamano2 = [math]::Round((Get-Item $sqlEstructura).Length / 1KB, 2)
        Write-Host "   Exportacion estructura: $tamano2 KB" -ForegroundColor Green
    }
    
} catch {
    Write-Host "Error durante la exportacion: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Verifica que MySQL este ejecutandose y las credenciales sean correctas" -ForegroundColor Yellow
}

# Crear archivo README para la base de datos
$readmeDB = @"
# Base de Datos - Sistema de Gestion Empresarial

## Archivos incluidos

### 1. sistemadgestion5_completo_$fecha.sql
- **Descripcion**: Estructura completa + datos de ejemplo
- **Uso**: Para instalacion completa con datos de prueba
- **Comando de importacion**:
``````sql
mysql -u root -p sistemadgestion5 < sistemadgestion5_completo_$fecha.sql
``````

### 2. sistemadgestion5_estructura_$fecha.sql
- **Descripcion**: Solo estructura de tablas (sin datos)
- **Uso**: Para instalacion limpia sin datos
- **Comando de importacion**:
``````sql
mysql -u root -p sistemadgestion5 < sistemadgestion5_estructura_$fecha.sql
``````

## Instalacion de la Base de Datos

### Opcion 1: Instalacion Completa (Recomendada)
``````bash
# 1. Crear la base de datos
mysql -u root -p -e "CREATE DATABASE sistemadgestion5;"

# 2. Importar estructura y datos
mysql -u root -p sistemadgestion5 < sistemadgestion5_completo_$fecha.sql
``````

### Opcion 2: Instalacion Solo Estructura
``````bash
# 1. Crear la base de datos
mysql -u root -p -e "CREATE DATABASE sistemadgestion5;"

# 2. Importar solo la estructura
mysql -u root -p sistemadgestion5 < sistemadgestion5_estructura_$fecha.sql
``````

## Requisitos
- MySQL 8.0+
- PHP 8.1+
- Extensiones PHP: mysqli, pdo_mysql

## Configuracion
Despues de importar la base de datos, configura la conexion en:
``````php
// config/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistemadgestion5');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_password');
``````

## Usuarios por Defecto
- **Admin**: admin / admin
- **Usuario**: usuario / usuario

**IMPORTANTE**: Cambia las contraseñas por defecto despues de la instalacion.
"@

$readmeDB | Out-File -FilePath "$sqlDir\README.md" -Encoding UTF8

Write-Host ""
Write-Host "EXPORTACION COMPLETADA" -ForegroundColor Green
Write-Host "======================" -ForegroundColor Green

if (Test-Path $sqlDir) {
    Write-Host "Archivos generados en carpeta 'database':" -ForegroundColor Cyan
    Get-ChildItem $sqlDir | ForEach-Object {
        $tamano = [math]::Round($_.Length / 1KB, 2)
        Write-Host "   $($_.Name) - $tamano KB" -ForegroundColor White
    }
}

Write-Host ""
Write-Host "PROXIMO PASO:" -ForegroundColor Magenta
Write-Host "Crear ZIP completo con SQL incluido..." -ForegroundColor Yellow

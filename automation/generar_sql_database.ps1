# GENERADOR DE SQL PARA GITHUB
# Script para exportar la base de datos actual y crear ZIP completo

Write-Host "🗄️ GENERANDO SQL DE LA BASE DE DATOS ACTUAL" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan

$fecha = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"

# Configuración de la base de datos (ajustar según tu configuración)
$dbHost = "localhost"
$dbName = "sistemadgestion5"
$dbUser = "root"
$dbPass = ""
$mysqldumpPath = "C:\xampp\mysql\bin\mysqldump.exe"

Write-Host "📊 Configuración de la base de datos:" -ForegroundColor Yellow
Write-Host "   Host: $dbHost" -ForegroundColor Gray
Write-Host "   Base de datos: $dbName" -ForegroundColor Gray
Write-Host "   Usuario: $dbUser" -ForegroundColor Gray

# Verificar si mysqldump existe
if (-not (Test-Path $mysqldumpPath)) {
    Write-Host "❌ ERROR: No se encontró mysqldump en: $mysqldumpPath" -ForegroundColor Red
    Write-Host "💡 Buscando mysqldump en rutas alternativas..." -ForegroundColor Yellow
    
    $alternatePaths = @(
        "C:\xampp\mysql\bin\mysqldump.exe",
        "C:\wamp\bin\mysql\mysql8.0.31\bin\mysqldump.exe",
        "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe",
        "mysqldump.exe"
    )
    
    foreach ($path in $alternatePaths) {
        if (Test-Path $path) {
            $mysqldumpPath = $path
            Write-Host "✅ Encontrado en: $path" -ForegroundColor Green
            break
        }
    }
    
    if (-not (Test-Path $mysqldumpPath)) {
        Write-Host "❌ No se pudo encontrar mysqldump. Verifica la instalación de MySQL/XAMPP" -ForegroundColor Red
        exit 1
    }
}

Write-Host "🔄 Exportando base de datos..." -ForegroundColor Yellow

# Crear directorio para SQL si no existe
$sqlDir = "database"
New-Item -ItemType Directory -Path $sqlDir -Force | Out-Null

# Nombres de archivos SQL
$sqlCompleto = "$sqlDir\sistemadgestion5_completo_$fecha.sql"
$sqlEstructura = "$sqlDir\sistemadgestion5_estructura_$fecha.sql"
$sqlDatos = "$sqlDir\sistemadgestion5_datos_$fecha.sql"

try {
    # 1. Exportar estructura completa con datos
    Write-Host "   📋 Exportando estructura completa con datos..." -ForegroundColor Gray
    $comando1 = "`"$mysqldumpPath`" -h $dbHost -u $dbUser"
    if ($dbPass) { $comando1 += " -p$dbPass" }
    $comando1 += " --routines --triggers --events --single-transaction $dbName"
    
    Invoke-Expression "$comando1 > `"$sqlCompleto`""
    
    if (Test-Path $sqlCompleto) {
        $tamano1 = [math]::Round((Get-Item $sqlCompleto).Length / 1KB, 2)
        Write-Host "   ✅ Exportación completa: $tamano1 KB" -ForegroundColor Green
    }
    
    # 2. Exportar solo estructura (sin datos)
    Write-Host "   🏗️ Exportando solo estructura..." -ForegroundColor Gray
    $comando2 = "`"$mysqldumpPath`" -h $dbHost -u $dbUser"
    if ($dbPass) { $comando2 += " -p$dbPass" }
    $comando2 += " --no-data --routines --triggers --events $dbName"
    
    Invoke-Expression "$comando2 > `"$sqlEstructura`""
    
    if (Test-Path $sqlEstructura) {
        $tamano2 = [math]::Round((Get-Item $sqlEstructura).Length / 1KB, 2)
        Write-Host "   ✅ Exportación estructura: $tamano2 KB" -ForegroundColor Green
    }
    
    # 3. Exportar solo datos (sin estructura)
    Write-Host "   💾 Exportando solo datos..." -ForegroundColor Gray
    $comando3 = "`"$mysqldumpPath`" -h $dbHost -u $dbUser"
    if ($dbPass) { $comando3 += " -p$dbPass" }
    $comando3 += " --no-create-info --single-transaction $dbName"
    
    Invoke-Expression "$comando3 > `"$sqlDatos`""
    
    if (Test-Path $sqlDatos) {
        $tamano3 = [math]::Round((Get-Item $sqlDatos).Length / 1KB, 2)
        Write-Host "   ✅ Exportación datos: $tamano3 KB" -ForegroundColor Green
    }
    
} catch {
    Write-Host "❌ Error durante la exportación: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "💡 Verifica que MySQL esté ejecutándose y las credenciales sean correctas" -ForegroundColor Yellow
}

# Crear archivo README para la base de datos
$readmeDB = @"
# Base de Datos - Sistema de Gestión Empresarial

## Archivos incluidos

### 1. sistemadgestion5_completo_$fecha.sql
- **Descripción**: Estructura completa + datos de ejemplo
- **Uso**: Para instalación completa con datos de prueba
- **Comando de importación**:
```sql
mysql -u root -p sistemadgestion5 < sistemadgestion5_completo_$fecha.sql
```

### 2. sistemadgestion5_estructura_$fecha.sql
- **Descripción**: Solo estructura de tablas (sin datos)
- **Uso**: Para instalación limpia sin datos
- **Comando de importación**:
```sql
mysql -u root -p sistemadgestion5 < sistemadgestion5_estructura_$fecha.sql
```

### 3. sistemadgestion5_datos_$fecha.sql
- **Descripción**: Solo datos (sin estructura)
- **Uso**: Para agregar datos a una estructura existente
- **Comando de importación**:
```sql
mysql -u root -p sistemadgestion5 < sistemadgestion5_datos_$fecha.sql
```

## Instalación de la Base de Datos

### Opción 1: Instalación Completa (Recomendada)
```bash
# 1. Crear la base de datos
mysql -u root -p -e "CREATE DATABASE sistemadgestion5;"

# 2. Importar estructura y datos
mysql -u root -p sistemadgestion5 < sistemadgestion5_completo_$fecha.sql
```

### Opción 2: Instalación Solo Estructura
```bash
# 1. Crear la base de datos
mysql -u root -p -e "CREATE DATABASE sistemadgestion5;"

# 2. Importar solo la estructura
mysql -u root -p sistemadgestion5 < sistemadgestion5_estructura_$fecha.sql
```

## Requisitos
- MySQL 8.0+
- PHP 8.1+
- Extensiones PHP: mysqli, pdo_mysql

## Configuración
Después de importar la base de datos, configura la conexión en:
```php
// config/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistemadgestion5');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_password');
```

## Usuarios por Defecto
- **Admin**: admin / admin
- **Usuario**: usuario / usuario

⚠️ **IMPORTANTE**: Cambia las contraseñas por defecto después de la instalación.
"@

$readmeDB | Out-File -FilePath "$sqlDir\README.md" -Encoding UTF8

Write-Host ""
Write-Host "🎉 EXPORTACIÓN COMPLETADA" -ForegroundColor Green
Write-Host "=========================" -ForegroundColor Green

if (Test-Path $sqlDir) {
    Write-Host "📁 Archivos generados en carpeta 'database':" -ForegroundColor Cyan
    Get-ChildItem $sqlDir | ForEach-Object {
        $tamano = [math]::Round($_.Length / 1KB, 2)
        Write-Host "   📄 $($_.Name) - $tamano KB" -ForegroundColor White
    }
}

Write-Host ""
Write-Host "🚀 PRÓXIMO PASO:" -ForegroundColor Magenta
Write-Host "Ejecutar 'crear_zip_completo_con_sql.ps1' para generar el ZIP final con SQL incluido" -ForegroundColor Yellow

# LIMPIEZA COMPLETA DEL SISTEMA
# Script para organizar y limpiar el proyecto

Write-Host "INICIANDO LIMPIEZA COMPLETA DEL SISTEMA" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

$fecha = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$rutaProyecto = Get-Location
$carpetaBackup = "backup\limpieza_$fecha"
$carpetaObsoletos = "backup\obsoletos_$fecha"

# Crear carpetas de backup
New-Item -ItemType Directory -Path $carpetaBackup -Force | Out-Null
New-Item -ItemType Directory -Path $carpetaObsoletos -Force | Out-Null
New-Item -ItemType Directory -Path "docs" -Force | Out-Null
New-Item -ItemType Directory -Path "scripts" -Force | Out-Null

Write-Host "Carpetas de backup creadas" -ForegroundColor Green

# PASO 1: Backup completo antes de limpiar
Write-Host "PASO 1: Creando backup completo..." -ForegroundColor Yellow
$archivoBackup = "$carpetaBackup\backup_completo_pre_limpieza_$fecha.zip"

# Comprimir archivos principales (excluyendo .git y node_modules)
$archivosAComprimir = Get-ChildItem -Path "." -Exclude @(".git", "node_modules", "backup") 
Compress-Archive -Path $archivosAComprimir -DestinationPath $archivoBackup -Force
Write-Host "Backup completo creado: $archivoBackup" -ForegroundColor Green

# PASO 2: Mover archivos obsoletos
Write-Host "PASO 2: Moviendo archivos obsoletos..." -ForegroundColor Yellow

$archivosObsoletos = @(
    "test_*.php",
    "verificar_*.php", 
    "debug_*.php",
    "diagnostico_*.php",
    "migrar_*.php",
    "crear_tablas_*.php",
    "corregir_*.php",
    "limpiar_*.php",
    "reparar_*.php",
    "instalador_*.php",
    "auto_login_*.php",
    "analisis_*.php",
    "analizar_*.php"
)

$contadorObsoletos = 0
foreach ($patron in $archivosObsoletos) {
    $archivos = Get-ChildItem -Path $patron -ErrorAction SilentlyContinue
    foreach ($archivo in $archivos) {
        if ($archivo.Name -ne "analizar_sistema_completo.php") {
            Move-Item -Path $archivo.FullName -Destination $carpetaObsoletos -Force
            Write-Host "  Movido: $($archivo.Name)" -ForegroundColor Gray
            $contadorObsoletos++
        }
    }
}

Write-Host "$contadorObsoletos archivos obsoletos movidos" -ForegroundColor Green

# PASO 3: Organizar documentación
Write-Host "PASO 3: Organizando documentacion..." -ForegroundColor Yellow
$archivosDoc = Get-ChildItem -Path "*.md" -ErrorAction SilentlyContinue
foreach ($doc in $archivosDoc) {
    if ($doc.Name -ne "README.md") {
        Move-Item -Path $doc.FullName -Destination "docs\" -Force
        Write-Host "  Movido a docs/: $($doc.Name)" -ForegroundColor Gray
    }
}

# PASO 4: Organizar scripts
Write-Host "PASO 4: Organizando scripts..." -ForegroundColor Yellow
$scripts = Get-ChildItem -Path "*.ps1" -ErrorAction SilentlyContinue
foreach ($script in $scripts) {
    if ($script.Name -ne "limpiar_sistema_completo.ps1") {
        Move-Item -Path $script.FullName -Destination "scripts\" -Force
        Write-Host "  Movido a scripts/: $($script.Name)" -ForegroundColor Gray
    }
}

Write-Host "LIMPIEZA COMPLETADA" -ForegroundColor Green
Write-Host "Backup completo creado" -ForegroundColor White
Write-Host "$contadorObsoletos archivos obsoletos movidos" -ForegroundColor White  
Write-Host "Documentacion organizada en docs/" -ForegroundColor White
Write-Host "Scripts organizados en scripts/" -ForegroundColor White

Write-Host "Sistema limpio y organizado!" -ForegroundColor Magenta

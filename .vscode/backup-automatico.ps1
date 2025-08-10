# ===================================
# SCRIPT DE BACKUP AUTOMÁTICO - VERSIÓN SIMPLIFICADA
# Sistema de Gestión - Backup Inteligente
# ===================================

param(
    [string]$TipoBackup = "auto"
)

# Configuración
$ProyectoPath = "C:\xampp\htdocs\sistemadgestion5"
$BackupBasePath = "$ProyectoPath\.vscode\backups"
$Timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"

Write-Host ""
Write-Host "SISTEMA DE BACKUP AUTOMATICO" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan
Write-Host "Proyecto: Sistema de Gestión"
Write-Host "Fecha: $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')"
Write-Host "Tipo: $TipoBackup"
Write-Host ""

# Crear directorios si no existen
$DirectoriosBackup = @(
    "$BackupBasePath\daily",
    "$BackupBasePath\hourly", 
    "$BackupBasePath\manual",
    "$BackupBasePath\auto"
)

foreach ($dir in $DirectoriosBackup) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
}

# Verificar que el proyecto existe
if (!(Test-Path $ProyectoPath)) {
    Write-Host "❌ No se encuentra el proyecto en: $ProyectoPath" -ForegroundColor Red
    exit 1
}

# Crear backup
Write-Host "Creando backup $TipoBackup..." -ForegroundColor Yellow

try {
    $DestinoBackup = Join-Path $BackupBasePath $TipoBackup
    $NombreBackup = "backup_sistemadgestion_${TipoBackup}_${Timestamp}.zip"
    $RutaBackup = Join-Path $DestinoBackup $NombreBackup
    
    # Archivos a incluir
    $ArchivosIncluir = @(
        "$ProyectoPath\modulos",
        "$ProyectoPath\config",
        "$ProyectoPath\*.php",
        "$ProyectoPath\*.md",
        "$ProyectoPath\*.sql",
        "$ProyectoPath\*.txt",
        "$ProyectoPath\.vscode\settings.json"
    )
    
    # Obtener archivos
    $TodosLosArchivos = @()
    foreach ($patron in $ArchivosIncluir) {
        $archivos = Get-ChildItem -Path $patron -Recurse -File -ErrorAction SilentlyContinue
        $TodosLosArchivos += $archivos
    }
    
    # Filtrar archivos no deseados
    $ArchivosLimpios = $TodosLosArchivos | Where-Object {
        $_.FullName -notlike "*\.vscode\backups\*" -and
        $_.FullName -notlike "*\assets\uploads\*" -and
        $_.Extension -ne ".log" -and
        $_.Name -notlike "temp*"
    }
    
    if ($ArchivosLimpios.Count -gt 0) {
        # Crear el ZIP
        Compress-Archive -Path $ArchivosLimpios.FullName -DestinationPath $RutaBackup -Force
        
        $TamanoMB = [math]::Round((Get-Item $RutaBackup).Length / 1MB, 2)
    Write-Host "Backup creado: $NombreBackup ($TamanoMB MB)" -ForegroundColor Green
        
        # Limpiar backups antiguos según tipo
        $DiasRetener = switch ($TipoBackup.ToLower()) {
            "hourly" { 2 }
            "daily" { 30 }
            "auto" { 7 }
            default { 999 } # manual no se limpia
        }
        
        if ($DiasRetener -lt 999) {
            $FechaLimite = (Get-Date).AddDays(-$DiasRetener)
            $ArchivosAntiguos = Get-ChildItem $DestinoBackup -Filter "*.zip" | Where-Object { $_.CreationTime -lt $FechaLimite }
            
            foreach ($archivo in $ArchivosAntiguos) {
                Remove-Item $archivo.FullName -Force -ErrorAction SilentlyContinue
                Write-Host "Eliminado backup antiguo: $($archivo.Name)" -ForegroundColor DarkYellow
            }
        }
        
        $BackupExitoso = $true
    } else {
        Write-Host "No se encontraron archivos para el backup" -ForegroundColor Red
        $BackupExitoso = $false
    }
    
} catch {
    Write-Host "Error al crear backup: $($_.Exception.Message)" -ForegroundColor Red
    $BackupExitoso = $false
}

# Mostrar resumen
Write-Host ""
Write-Host "RESUMEN DEL BACKUP" -ForegroundColor Cyan
Write-Host "===================="

if ($BackupExitoso) {
    Write-Host "Backup completado exitosamente" -ForegroundColor Green
    Write-Host "Ubicacion: $RutaBackup"
} else {
    Write-Host "El backup fallo" -ForegroundColor Red
}

# Mostrar estadísticas
Write-Host ""
Write-Host "ESTADISTICAS DE BACKUPS:" -ForegroundColor Cyan
foreach ($tipo in @("auto", "hourly", "daily", "manual")) {
    $dirTipo = Join-Path $BackupBasePath $tipo
    if (Test-Path $dirTipo) {
        $cantidad = (Get-ChildItem $dirTipo -Filter "*.zip" -ErrorAction SilentlyContinue | Measure-Object).Count
        $ultimoBackup = Get-ChildItem $dirTipo -Filter "*.zip" -ErrorAction SilentlyContinue | Sort-Object CreationTime -Descending | Select-Object -First 1
        
        if ($ultimoBackup) {
            $fechaUltimo = $ultimoBackup.CreationTime.ToString("dd/MM/yyyy HH:mm")
            Write-Host "  $($tipo.ToUpper()): $cantidad backups (ultimo: $fechaUltimo)"
        } else {
            Write-Host "  $($tipo.ToUpper()): $cantidad backups"
        }
    }
}

Write-Host ""
Write-Host "Backup automatico completado" -ForegroundColor Green

#!/usr/bin/env powershell
# Script de backup automático al cerrar VS Code
# Se ejecuta automáticamente antes de cerrar

param(
    [string]$Motivo = "cierre_vscode"
)

$FechaHora = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$RutaProyecto = $PSScriptRoot | Split-Path
$CarpetaBackup = "$RutaProyecto\.vscode\backups\auto"
$NombreBackup = "backup_auto_cierre_$FechaHora.zip"
$RutaCompleta = "$CarpetaBackup\$NombreBackup"

try {
    Write-Host "🔄 BACKUP AUTOMÁTICO AL CERRAR VS CODE" -ForegroundColor Cyan
    Write-Host "Motivo: $Motivo" -ForegroundColor Yellow
    Write-Host "Fecha: $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')" -ForegroundColor Yellow
    
    # Crear carpeta si no existe
    if (-not (Test-Path $CarpetaBackup)) {
        New-Item -ItemType Directory -Path $CarpetaBackup -Force | Out-Null
    }
    
    # Crear backup temporal
    $TempPath = "$env:TEMP\backup_temp_$(Get-Random)"
    New-Item -ItemType Directory -Path $TempPath -Force | Out-Null
    
    # Copiar archivos importantes
    $ArchivosACopiar = @(
        "*.php", "*.html", "*.css", "*.js", "*.sql", "*.md", "*.txt",
        "config\*", "modulos\*", ".vscode\settings.json", ".vscode\tasks.json"
    )
    
    foreach ($patron in $ArchivosACopiar) {
        $archivos = Get-ChildItem -Path $RutaProyecto -Include $patron -Recurse -File -ErrorAction SilentlyContinue
        foreach ($archivo in $archivos) {
            $rutaRelativa = $archivo.FullName.Replace($RutaProyecto, "").TrimStart("\")
            $rutaDestino = Join-Path $TempPath $rutaRelativa
            $carpetaDestino = Split-Path $rutaDestino -Parent
            
            if (-not (Test-Path $carpetaDestino)) {
                New-Item -ItemType Directory -Path $carpetaDestino -Force | Out-Null
            }
            
            Copy-Item -Path $archivo.FullName -Destination $rutaDestino -Force
        }
    }
    
    # Crear ZIP
    Compress-Archive -Path "$TempPath\*" -DestinationPath $RutaCompleta -Force
    
    # Limpiar temporal
    Remove-Item -Path $TempPath -Recurse -Force
    
    # Verificar tamaño
    $tamano = (Get-Item $RutaCompleta).Length / 1MB
    
    Write-Host "✅ BACKUP COMPLETADO" -ForegroundColor Green
    Write-Host "Archivo: $NombreBackup" -ForegroundColor White
    Write-Host "Tamaño: $([math]::Round($tamano, 2)) MB" -ForegroundColor White
    Write-Host "Ubicación: $CarpetaBackup" -ForegroundColor White
    
    # Limpiar backups antiguos (mantener solo los últimos 5)
    $backupsAntiguos = Get-ChildItem -Path $CarpetaBackup -Filter "backup_auto_cierre_*.zip" | Sort-Object CreationTime -Descending | Select-Object -Skip 5
    if ($backupsAntiguos) {
        $backupsAntiguos | Remove-Item -Force
        Write-Host "🗑️ Eliminados $($backupsAntiguos.Count) backups antiguos" -ForegroundColor Gray
    }
    
    return $true
    
} catch {
    Write-Host "❌ ERROR en backup automático: $($_.Exception.Message)" -ForegroundColor Red
    return $false
}

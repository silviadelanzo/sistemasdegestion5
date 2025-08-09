# SCRIPT DE BACKUP SIMPLE Y FUNCIONAL
param([string]$TipoBackup = "manual")

$ProyectoPath = "C:\xampp\htdocs\sistemadgestion5"
$BackupBasePath = "$ProyectoPath\.vscode\backups"
$Timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"

Write-Host "BACKUP $TipoBackup - $(Get-Date)" -ForegroundColor Cyan

# Crear directorio de backup
$DestinoBackup = "$BackupBasePath\$TipoBackup"
if (!(Test-Path $DestinoBackup)) {
    New-Item -ItemType Directory -Path $DestinoBackup -Force | Out-Null
}

try {
    $NombreBackup = "backup_$TipoBackup`_$Timestamp.zip"
    $RutaBackup = "$DestinoBackup\$NombreBackup"
    
    # Crear backup con robocopy y zip
    $TempDir = "$env:TEMP\backup_sistemadgestion_$Timestamp"
    New-Item -ItemType Directory -Path $TempDir -Force | Out-Null
    
    # Copiar archivos importantes
    Write-Host "Copiando archivos..." -ForegroundColor Yellow
    
    robocopy "$ProyectoPath\modulos" "$TempDir\modulos" /E /XD backups uploads /XF *.log /NFL /NDL /NJH /NJS
    robocopy "$ProyectoPath\config" "$TempDir\config" /E /XF *.log /NFL /NDL /NJH /NJS
    robocopy "$ProyectoPath" "$TempDir" *.php /NFL /NDL /NJH /NJS
    robocopy "$ProyectoPath" "$TempDir" *.md /NFL /NDL /NJH /NJS
    robocopy "$ProyectoPath" "$TempDir" *.sql /NFL /NDL /NJH /NJS
    robocopy "$ProyectoPath" "$TempDir" *.txt /NFL /NDL /NJH /NJS
    
    # Crear ZIP
    Write-Host "Creando archivo ZIP..." -ForegroundColor Yellow
    Compress-Archive -Path "$TempDir\*" -DestinationPath $RutaBackup -Force
    
    # Limpiar directorio temporal
    Remove-Item $TempDir -Recurse -Force
    
    $TamanoMB = [math]::Round((Get-Item $RutaBackup).Length / 1MB, 2)
    Write-Host "EXITO: $NombreBackup ($TamanoMB MB)" -ForegroundColor Green
    
    # Mostrar ubicacion
    Write-Host "Ubicacion: $RutaBackup" -ForegroundColor White
    
    # Limpiar backups antiguos
    if ($TipoBackup -ne "manual") {
        $DiasRetener = if ($TipoBackup -eq "hourly") { 2 } elseif ($TipoBackup -eq "daily") { 30 } else { 7 }
        $FechaLimite = (Get-Date).AddDays(-$DiasRetener)
        $ArchivosAntiguos = Get-ChildItem $DestinoBackup -Filter "*.zip" | Where-Object { $_.CreationTime -lt $FechaLimite }
        foreach ($archivo in $ArchivosAntiguos) {
            Remove-Item $archivo.FullName -Force -ErrorAction SilentlyContinue
            Write-Host "Eliminado: $($archivo.Name)" -ForegroundColor DarkYellow
        }
    }
    
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "Backup completado" -ForegroundColor Green

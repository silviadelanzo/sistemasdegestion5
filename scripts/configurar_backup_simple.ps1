# CONFIGURADOR DE BACKUP AUTOMATICO - VERSION SIMPLIFICADA
# Sistema de Gestion - Backup Automatico

Write-Host ""
Write-Host "CONFIGURADOR DE BACKUP AUTOMATICO" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""

$ProyectoPath = "C:\xampp\htdocs\sistemadgestion5"
$ScriptBackup = "$ProyectoPath\.vscode\backup-simple.ps1"

# Verificar que el script existe
if (!(Test-Path $ScriptBackup)) {
    Write-Host "ERROR: No se encuentra el script de backup en: $ScriptBackup" -ForegroundColor Red
    Write-Host "Creando script de backup simplificado..." -ForegroundColor Yellow
    
    # Crear script de backup simple
    $BackupScript = @'
# SCRIPT DE BACKUP SIMPLE
param([string]$TipoBackup = "auto")

$ProyectoPath = "C:\xampp\htdocs\sistemadgestion5"
$BackupBasePath = "$ProyectoPath\.vscode\backups"
$Timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"

Write-Host "Backup $TipoBackup - $(Get-Date)" -ForegroundColor Cyan

# Crear directorio de backup
$DestinoBackup = "$BackupBasePath\$TipoBackup"
if (!(Test-Path $DestinoBackup)) {
    New-Item -ItemType Directory -Path $DestinoBackup -Force | Out-Null
}

try {
    $NombreBackup = "backup_$TipoBackup`_$Timestamp.zip"
    $RutaBackup = "$DestinoBackup\$NombreBackup"
    
    # Archivos a incluir
    $Archivos = @()
    $Archivos += Get-ChildItem "$ProyectoPath\modulos" -Recurse -File -ErrorAction SilentlyContinue
    $Archivos += Get-ChildItem "$ProyectoPath\config" -Recurse -File -ErrorAction SilentlyContinue
    $Archivos += Get-ChildItem "$ProyectoPath\*.php" -File -ErrorAction SilentlyContinue
    $Archivos += Get-ChildItem "$ProyectoPath\*.md" -File -ErrorAction SilentlyContinue
    $Archivos += Get-ChildItem "$ProyectoPath\*.sql" -File -ErrorAction SilentlyContinue
    
    if ($Archivos.Count -gt 0) {
        Compress-Archive -Path $Archivos.FullName -DestinationPath $RutaBackup -Force
        $TamanoMB = [math]::Round((Get-Item $RutaBackup).Length / 1MB, 2)
        Write-Host "Backup creado: $NombreBackup ($TamanoMB MB)" -ForegroundColor Green
        
        # Limpiar backups antiguos
        $DiasRetener = switch ($TipoBackup.ToLower()) {
            "hourly" { 2 }
            "daily" { 30 }
            "auto" { 7 }
            default { 999 }
        }
        
        if ($DiasRetener -lt 999) {
            $FechaLimite = (Get-Date).AddDays(-$DiasRetener)
            $ArchivosAntiguos = Get-ChildItem $DestinoBackup -Filter "*.zip" | Where-Object { $_.CreationTime -lt $FechaLimite }
            foreach ($archivo in $ArchivosAntiguos) {
                Remove-Item $archivo.FullName -Force -ErrorAction SilentlyContinue
                Write-Host "Eliminado backup antiguo: $($archivo.Name)" -ForegroundColor Yellow
            }
        }
    } else {
        Write-Host "No se encontraron archivos para backup" -ForegroundColor Red
    }
    
} catch {
    Write-Host "Error al crear backup: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "Backup completado" -ForegroundColor Green
'@
    
    $BackupScript | Out-File -FilePath $ScriptBackup -Encoding UTF8
    Write-Host "Script de backup creado: $ScriptBackup" -ForegroundColor Green
}

Write-Host "Script de backup encontrado" -ForegroundColor Green
Write-Host ""

# Configurar tareas programadas
Write-Host "Configurando tareas programadas..." -ForegroundColor Yellow

try {
    # Tarea cada hora (durante horario laboral)
    $TareaHourly = @{
        TaskName = "SistemaGestion_BackupHourly"
        Description = "Backup automatico cada hora del Sistema de Gestion"
        Action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-ExecutionPolicy Bypass -File `"$ScriptBackup`" -TipoBackup hourly"
        Trigger = New-ScheduledTaskTrigger -Daily -At "08:00" -RepetitionInterval (New-TimeSpan -Hours 1) -RepetitionDuration (New-TimeSpan -Hours 12)
        Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
        Principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive
    }
    
    # Verificar si la tarea ya existe
    $TareaExistente = Get-ScheduledTask -TaskName "SistemaGestion_BackupHourly" -ErrorAction SilentlyContinue
    if ($TareaExistente) {
        Unregister-ScheduledTask -TaskName "SistemaGestion_BackupHourly" -Confirm:$false
        Write-Host "Tarea anterior eliminada" -ForegroundColor Yellow
    }
    
    Register-ScheduledTask @TareaHourly -Force | Out-Null
    Write-Host "Backup cada hora configurado (8:00-20:00)" -ForegroundColor Green
    
    # Tarea diaria
    $TareaDaily = @{
        TaskName = "SistemaGestion_BackupDaily"
        Description = "Backup diario del Sistema de Gestion"
        Action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-ExecutionPolicy Bypass -File `"$ScriptBackup`" -TipoBackup daily"
        Trigger = New-ScheduledTaskTrigger -Daily -At "22:00"
        Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
        Principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive
    }
    
    $TareaExistente = Get-ScheduledTask -TaskName "SistemaGestion_BackupDaily" -ErrorAction SilentlyContinue
    if ($TareaExistente) {
        Unregister-ScheduledTask -TaskName "SistemaGestion_BackupDaily" -Confirm:$false
    }
    
    Register-ScheduledTask @TareaDaily -Force | Out-Null
    Write-Host "Backup diario configurado (22:00)" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "TAREAS PROGRAMADAS CONFIGURADAS:" -ForegroundColor Cyan
    Write-Host "Backup cada hora: 8:00 - 20:00 (horario laboral)"
    Write-Host "Backup diario: 22:00"
    Write-Host ""
    
    # Ejecutar un backup inmediato para probar
    Write-Host "Ejecutando backup de prueba..." -ForegroundColor Yellow
    & $ScriptBackup -TipoBackup "manual"
    
    Write-Host ""
    Write-Host "CONFIGURACION COMPLETADA EXITOSAMENTE" -ForegroundColor Green
    Write-Host "======================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Para verificar el funcionamiento:"
    Write-Host "1. Abre Programador de tareas (taskschd.msc)"
    Write-Host "2. Busca 'SistemaGestion_BackupHourly' y 'SistemaGestion_BackupDaily'"
    Write-Host "3. Verifica que esten habilitadas"
    Write-Host ""
    Write-Host "Para backup manual ejecuta:"
    Write-Host ".\.vscode\backup-simple.ps1 -TipoBackup manual"
    
} catch {
    Write-Host "Error al configurar tareas: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "SOLUCION ALTERNATIVA:" -ForegroundColor Yellow
    Write-Host "Ejecuta manualmente cada vez que quieras backup:"
    Write-Host ".\.vscode\backup-simple.ps1 -TipoBackup manual"
}

Write-Host ""
Write-Host "Presiona cualquier tecla para continuar..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

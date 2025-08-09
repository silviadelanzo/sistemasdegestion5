# CONFIGURADOR DE BACKUPS AUTOMATICOS - VERSION CORREGIDA
# Configura tareas programadas de Windows para backups automaticos

Write-Host ""
Write-Host "CONFIGURADOR DE BACKUPS AUTOMATICOS" -ForegroundColor Cyan
Write-Host "===================================" -ForegroundColor Cyan
Write-Host ""

$ProyectoPath = "C:\xampp\htdocs\sistemadgestion5"
$ScriptBackup = "$ProyectoPath\backup_simple_funcional.ps1"

# Verificar que somos administrador
$esAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")

if (-not $esAdmin) {
    Write-Host "AVISO: No tienes permisos de administrador" -ForegroundColor Yellow
    Write-Host "Las tareas programadas pueden no configurarse correctamente" -ForegroundColor Yellow
    Write-Host ""
}

# Verificar que el script de backup existe
if (!(Test-Path $ScriptBackup)) {
    Write-Host "ERROR: No se encuentra el script de backup" -ForegroundColor Red
    Write-Host "Ubicacion esperada: $ScriptBackup" -ForegroundColor Red
    exit 1
}

Write-Host "Script de backup encontrado: backup_simple_funcional.ps1" -ForegroundColor Green
Write-Host ""

try {
    # TAREA 1: BACKUP DIARIO (22:00)
    Write-Host "Configurando backup DIARIO (22:00)..." -ForegroundColor Yellow
    
    $ActionDaily = New-ScheduledTaskAction -Execute "PowerShell.exe" -Argument "-ExecutionPolicy Bypass -File `"$ScriptBackup`" -Tipo daily"
    $TriggerDaily = New-ScheduledTaskTrigger -Daily -At "22:00"
    $SettingsDaily = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
    $PrincipalDaily = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive
    
    # Eliminar tarea existente si existe
    $TareaExistente = Get-ScheduledTask -TaskName "SistemaGestion_BackupDaily" -ErrorAction SilentlyContinue
    if ($TareaExistente) {
        Unregister-ScheduledTask -TaskName "SistemaGestion_BackupDaily" -Confirm:$false
        Write-Host "Tarea diaria anterior eliminada" -ForegroundColor Yellow
    }
    
    Register-ScheduledTask -TaskName "SistemaGestion_BackupDaily" -Action $ActionDaily -Trigger $TriggerDaily -Settings $SettingsDaily -Principal $PrincipalDaily -Description "Backup diario automatico del Sistema de Gestion" | Out-Null
    Write-Host "✅ Backup DIARIO configurado (22:00 cada dia)" -ForegroundColor Green
    
    # TAREA 2: BACKUP SEMANAL (Domingos 23:00)
    Write-Host "Configurando backup SEMANAL (Domingos 23:00)..." -ForegroundColor Yellow
    
    $ActionWeekly = New-ScheduledTaskAction -Execute "PowerShell.exe" -Argument "-ExecutionPolicy Bypass -File `"$ScriptBackup`" -Tipo semanal"
    $TriggerWeekly = New-ScheduledTaskTrigger -Weekly -WeeksInterval 1 -DaysOfWeek Sunday -At "23:00"
    $SettingsWeekly = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
    $PrincipalWeekly = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive
    
    # Eliminar tarea existente si existe
    $TareaExistente = Get-ScheduledTask -TaskName "SistemaGestion_BackupSemanal" -ErrorAction SilentlyContinue
    if ($TareaExistente) {
        Unregister-ScheduledTask -TaskName "SistemaGestion_BackupSemanal" -Confirm:$false
        Write-Host "Tarea semanal anterior eliminada" -ForegroundColor Yellow
    }
    
    Register-ScheduledTask -TaskName "SistemaGestion_BackupSemanal" -Action $ActionWeekly -Trigger $TriggerWeekly -Settings $SettingsWeekly -Principal $PrincipalWeekly -Description "Backup semanal automatico del Sistema de Gestion" | Out-Null
    Write-Host "✅ Backup SEMANAL configurado (Domingos 23:00)" -ForegroundColor Green
    
    # TAREA 3: BACKUP DE EMERGENCIA (al cerrar sesión/apagar)
    Write-Host "Configurando backup de EMERGENCIA (al cerrar sesión)..." -ForegroundColor Yellow
    
    try {
        $ActionEmergency = New-ScheduledTaskAction -Execute "PowerShell.exe" -Argument "-ExecutionPolicy Bypass -File `"$ScriptBackup`" -Tipo emergency"
        $TriggerEmergency = New-ScheduledTaskTrigger -AtLogOff -User $env:USERNAME
        $SettingsEmergency = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Minutes 5)
        $PrincipalEmergency = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive
        
        # Eliminar tarea existente si existe
        $TareaExistente = Get-ScheduledTask -TaskName "SistemaGestion_BackupEmergencia" -ErrorAction SilentlyContinue
        if ($TareaExistente) {
            Unregister-ScheduledTask -TaskName "SistemaGestion_BackupEmergencia" -Confirm:$false
            Write-Host "Tarea de emergencia anterior eliminada" -ForegroundColor Yellow
        }
        
        Register-ScheduledTask -TaskName "SistemaGestion_BackupEmergencia" -Action $ActionEmergency -Trigger $TriggerEmergency -Settings $SettingsEmergency -Principal $PrincipalEmergency -Description "Backup de emergencia al cerrar sesion del Sistema de Gestion" | Out-Null
        Write-Host "✅ Backup EMERGENCIA configurado (al cerrar sesión)" -ForegroundColor Green
    } catch {
        Write-Host "⚠️ No se pudo configurar backup de emergencia: $($_.Exception.Message)" -ForegroundColor Yellow
        Write-Host "   (Esto es normal sin permisos de administrador)" -ForegroundColor Yellow
    }
    
    Write-Host ""
    Write-Host "CONFIGURACION COMPLETADA EXITOSAMENTE" -ForegroundColor Green
    Write-Host "=====================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "TAREAS PROGRAMADAS CONFIGURADAS:" -ForegroundColor Cyan
    Write-Host "• Backup DIARIO: Todos los dias a las 22:00"
    Write-Host "• Backup SEMANAL: Domingos a las 23:00" 
    Write-Host "• Backup EMERGENCIA: Al cerrar sesión/apagar Windows"
    Write-Host ""
    
    # Mostrar las tareas creadas
    Write-Host "VERIFICANDO TAREAS CREADAS:" -ForegroundColor Cyan
    $TareasCreadas = Get-ScheduledTask | Where-Object {$_.TaskName -like "*SistemaGestion*"} | Select-Object TaskName, State
    foreach ($tarea in $TareasCreadas) {
        Write-Host "✅ $($tarea.TaskName) - Estado: $($tarea.State)" -ForegroundColor Green
    }
    
    Write-Host ""
    Write-Host "EJECUTANDO BACKUP DE PRUEBA..." -ForegroundColor Yellow
    & $ScriptBackup -Tipo "configuracion_inicial"
    
} catch {
    Write-Host ""
    Write-Host "ERROR AL CONFIGURAR TAREAS: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "SOLUCION ALTERNATIVA:" -ForegroundColor Yellow
    Write-Host "1. Abre 'Programador de tareas' (taskschd.msc)"
    Write-Host "2. Crea las tareas manualmente"
    Write-Host "3. O ejecuta este script como Administrador"
    Write-Host ""
}

Write-Host ""
Write-Host "Para verificar las tareas programadas:" -ForegroundColor Cyan
Write-Host "taskschd.msc"
Write-Host ""
Write-Host "Para backup manual inmediato:" -ForegroundColor Cyan
Write-Host "PowerShell -ExecutionPolicy Bypass -File backup_simple_funcional.ps1"
Write-Host ""

Write-Host "Presiona cualquier tecla para continuar..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

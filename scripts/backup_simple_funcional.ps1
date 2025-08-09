# BACKUP AUTOMATICO COMPLETO - TODOS LOS TIPOS
param([string]$Tipo = "manual")

$Proyecto = "C:\xampp\htdocs\sistemadgestion5"

# Determinar carpeta segun tipo de backup
switch ($Tipo.ToLower()) {
    "daily" { $Destino = "$Proyecto\.vscode\backups\daily" }
    "diario" { $Destino = "$Proyecto\.vscode\backups\daily" }
    "semanal" { $Destino = "$Proyecto\.vscode\backups\weekly" }
    "weekly" { $Destino = "$Proyecto\.vscode\backups\weekly" }
    "emergencia" { $Destino = "$Proyecto\.vscode\backups\emergency" }
    "emergency" { $Destino = "$Proyecto\.vscode\backups\emergency" }
    "auto" { $Destino = "$Proyecto\.vscode\backups\auto" }
    "configuracion_inicial" { $Destino = "$Proyecto\.vscode\backups\setup" }
    default { $Destino = "$Proyecto\.vscode\backups\manual" }
}

$Fecha = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"

Write-Host ""
Write-Host "BACKUP $($Tipo.ToUpper()) DEL SISTEMA" -ForegroundColor Green
Write-Host "=========================" -ForegroundColor Green
Write-Host "Fecha: $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')"
Write-Host "Tipo: $Tipo"
Write-Host ""

# Crear carpeta si no existe
if (!(Test-Path $Destino)) {
    New-Item -ItemType Directory -Path $Destino -Force | Out-Null
    Write-Host "Carpeta de backup creada" -ForegroundColor Green
}

try {
    # Nombre del archivo backup
    $ArchivoBackup = "$Destino\backup_$($Tipo.ToLower())_$Fecha.zip"
    
    # Obtener archivos importantes
    Write-Host "Recopilando archivos..." -ForegroundColor Yellow
    
    $Archivos = @()
    $Archivos += Get-ChildItem "$Proyecto\modulos" -Recurse -File -ErrorAction SilentlyContinue | Where-Object { $_.Extension -in @('.php', '.sql', '.md', '.txt') }
    $Archivos += Get-ChildItem "$Proyecto\config" -Recurse -File -ErrorAction SilentlyContinue
    $Archivos += Get-ChildItem "$Proyecto\*.php" -File -ErrorAction SilentlyContinue
    $Archivos += Get-ChildItem "$Proyecto\*.md" -File -ErrorAction SilentlyContinue
    $Archivos += Get-ChildItem "$Proyecto\*.sql" -File -ErrorAction SilentlyContinue
    
    Write-Host "Archivos encontrados: $($Archivos.Count)" -ForegroundColor Cyan
    
    if ($Archivos.Count -gt 0) {
        # Crear el backup
        Compress-Archive -Path $Archivos.FullName -DestinationPath $ArchivoBackup -Force
        
        # Verificar que se creo
        if (Test-Path $ArchivoBackup) {
            $Tamano = [math]::Round((Get-Item $ArchivoBackup).Length / 1MB, 2)
            Write-Host ""
            Write-Host "BACKUP CREADO EXITOSAMENTE" -ForegroundColor Green
            Write-Host "Archivo: $ArchivoBackup"
            Write-Host "Tamano: $Tamano MB"
            Write-Host "Archivos incluidos: $($Archivos.Count)"
            
            # Mostrar backups existentes del mismo tipo
            $BackupsExistentes = Get-ChildItem $Destino -Filter "*.zip" | Sort-Object CreationTime -Descending
            Write-Host ""
            Write-Host "HISTORIAL DE BACKUPS $($Tipo.ToUpper()):" -ForegroundColor Cyan
            foreach ($backup in $BackupsExistentes | Select-Object -First 5) {
                $fechaBackup = $backup.CreationTime.ToString("dd/MM/yyyy HH:mm")
                $tamanoBackup = [math]::Round($backup.Length / 1MB, 2)
                Write-Host "   $($backup.Name) - $fechaBackup ($tamanoBackup MB)"
            }
            
            # Limpiar backups antiguos segun tipo
            $DiasRetener = switch ($Tipo.ToLower()) {
                "daily" { 30 }      # Mantener 30 dias
                "diario" { 30 }
                "semanal" { 90 }    # Mantener 90 dias (aprox 12 semanas)
                "weekly" { 90 }
                "emergencia" { 7 }  # Mantener 7 dias
                "emergency" { 7 }
                "auto" { 14 }       # Mantener 14 dias
                default { 999 }     # Manual no se limpia
            }
            
            if ($DiasRetener -lt 999) {
                $FechaLimite = (Get-Date).AddDays(-$DiasRetener)
                $ArchivosAntiguos = Get-ChildItem $Destino -Filter "*.zip" | Where-Object { $_.CreationTime -lt $FechaLimite }
                foreach ($archivo in $ArchivosAntiguos) {
                    Remove-Item $archivo.FullName -Force -ErrorAction SilentlyContinue
                    Write-Host "Eliminado backup antiguo: $($archivo.Name)" -ForegroundColor Yellow
                }
                if ($ArchivosAntiguos.Count -gt 0) {
                    Write-Host "Limpiados $($ArchivosAntiguos.Count) backups antiguos (mas de $DiasRetener dias)" -ForegroundColor Yellow
                }
            }
            
        } else {
            Write-Host "ERROR: No se pudo crear el archivo backup" -ForegroundColor Red
        }
        
    } else {
        Write-Host "ERROR: No se encontraron archivos para backup" -ForegroundColor Red
    }
    
} catch {
    Write-Host "ERROR AL CREAR BACKUP: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "Para hacer backup futuro ejecuta:" -ForegroundColor Yellow
Write-Host "PowerShell -ExecutionPolicy Bypass -File backup_simple_funcional.ps1"
Write-Host ""

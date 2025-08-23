# ===================================================
# BACKUP MANUAL SIMPLE - FUNCIONAL GARANTIZADO
# ===================================================

param([string]$Tipo = "manual")

$Proyecto = "C:\xampp\htdocs\sistemadgestion5"
$Destino = "$Proyecto\.vscode\backups\manual"
$Fecha = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"

Write-Host ""
Write-Host "🔄 BACKUP MANUAL DEL SISTEMA" -ForegroundColor Green
Write-Host "============================" -ForegroundColor Green
Write-Host "Fecha: $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')"
Write-Host ""

# Crear carpeta si no existe
if (!(Test-Path $Destino)) {
    New-Item -ItemType Directory -Path $Destino -Force | Out-Null
    Write-Host "✅ Carpeta de backup creada" -ForegroundColor Green
}

try {
    # Nombre del archivo backup
    $ArchivoBackup = "$Destino\backup_manual_$Fecha.zip"
    
    # Obtener archivos importantes
    Write-Host "📁 Recopilando archivos..." -ForegroundColor Yellow
    
    $Archivos = @()
    $Archivos += Get-ChildItem "$Proyecto\modulos" -Recurse -File -ErrorAction SilentlyContinue | Where-Object { $_.Extension -in @('.php', '.sql', '.md', '.txt') }
    $Archivos += Get-ChildItem "$Proyecto\config" -Recurse -File -ErrorAction SilentlyContinue
    $Archivos += Get-ChildItem "$Proyecto\*.php" -File -ErrorAction SilentlyContinue
    $Archivos += Get-ChildItem "$Proyecto\*.md" -File -ErrorAction SilentlyContinue
    $Archivos += Get-ChildItem "$Proyecto\*.sql" -File -ErrorAction SilentlyContinue
    
    Write-Host "📄 Archivos encontrados: $($Archivos.Count)" -ForegroundColor Cyan
    
    if ($Archivos.Count -gt 0) {
        # Crear el backup
        Compress-Archive -Path $Archivos.FullName -DestinationPath $ArchivoBackup -Force
        
        # Verificar que se creó
        if (Test-Path $ArchivoBackup) {
            $Tamaño = [math]::Round((Get-Item $ArchivoBackup).Length / 1MB, 2)
            Write-Host ""
            Write-Host "✅ BACKUP CREADO EXITOSAMENTE" -ForegroundColor Green
            Write-Host "📁 Archivo: $ArchivoBackup"
            Write-Host "📊 Tamaño: $Tamaño MB"
            Write-Host "📈 Archivos incluidos: $($Archivos.Count)"
            
            # Mostrar backups existentes
            $BackupsExistentes = Get-ChildItem $Destino -Filter "*.zip" | Sort-Object CreationTime -Descending
            Write-Host ""
            Write-Host "📋 HISTORIAL DE BACKUPS:" -ForegroundColor Cyan
            foreach ($backup in $BackupsExistentes | Select-Object -First 5) {
                $fechaBackup = $backup.CreationTime.ToString("dd/MM/yyyy HH:mm")
                $tamañoBackup = [math]::Round($backup.Length / 1MB, 2)
                Write-Host "   $($backup.Name) - $fechaBackup ($tamañoBackup MB)"
            }
            
        } else {
            Write-Host "❌ ERROR: No se pudo crear el archivo backup" -ForegroundColor Red
        }
        
    } else {
        Write-Host "❌ ERROR: No se encontraron archivos para backup" -ForegroundColor Red
    }
    
} catch {
    Write-Host "❌ ERROR AL CREAR BACKUP: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "🔄 Para hacer backup futuro ejecuta:" -ForegroundColor Yellow
Write-Host "PowerShell -ExecutionPolicy Bypass -File backup_manual_simple.ps1"
Write-Host ""

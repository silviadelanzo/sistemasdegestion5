param(
    [Parameter(Mandatory=$true)][string]$Archivo,
    [string]$Base = 'sistemasia_inventpro',
    [string]$Usuario = 'root',
    [string]$DbHost = '127.0.0.1',
    [int]$Puerto = 3306
)

$ErrorActionPreference = 'Stop'

Write-Host "Archivo SQL: $Archivo"
if (!(Test-Path $Archivo)) {
    Write-Host "No se encontro el archivo SQL indicado"
    exit 1
}

$mysqlExe = 'C:\xampp\mysql\bin\mysql.exe'
if (!(Test-Path $mysqlExe)) {
    Write-Host "mysql.exe no encontrado en $mysqlExe"
    exit 1
}

# Probar conexion a MySQL
Write-Host ("Probando conexion a MySQL {0}:{1}..." -f $DbHost,$Puerto)
try {
    & $mysqlExe --protocol=TCP -u $Usuario -h $DbHost -P $Puerto -e "SELECT 1;" | Out-Null
} catch {
    Write-Host "No se pudo conectar a MySQL. Inicie MySQL desde XAMPP y reintente."
    exit 1
}

# Detectar nombre de BD en el dump (USE/CREATE DATABASE) en primeras lineas
$dumpDbName = ''
try {
    $head = Get-Content -Path $Archivo -TotalCount 500 -ErrorAction Stop
    $regexUse = [regex]'(?i)^\s*USE\s+`?([\w\-]+)`?\s*;'
    $m = $head | Where-Object { $regexUse.IsMatch($_) } | Select-Object -First 1
    if ($m) { $dumpDbName = $regexUse.Match($m).Groups[1].Value }
    if (-not $dumpDbName) {
        $regexCreate = [regex]'(?i)^\s*CREATE\s+DATABASE(\s+IF\s+NOT\s+EXISTS)?\s+`?([\w\-]+)`?'
        $m2 = $head | Where-Object { $regexCreate.IsMatch($_) } | Select-Object -First 1
        if ($m2) { $dumpDbName = $regexCreate.Match($m2).Groups[2].Value }
    }
} catch {}

# Elegir estrategia de importacion
if ($dumpDbName) {
    Write-Host "Detectado en dump: base '$dumpDbName'"
    Write-Host "Importando segun dump (sin forzar base)..."
    & $mysqlExe --protocol=TCP -u $Usuario -h $DbHost -P $Puerto -e "CREATE DATABASE IF NOT EXISTS $dumpDbName DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | Out-Null
    $importCmd = '"' + $mysqlExe + '" --protocol=TCP -u ' + $Usuario + ' -h ' + $DbHost + ' -P ' + $Puerto + ' --default-character-set=utf8mb4 < "' + $Archivo + '"'
    & cmd /c $importCmd
    $exit = $LASTEXITCODE
    if ($exit -ne 0) { Write-Host "Fallo la importacion (codigo $exit)"; exit $exit }
    $targetDb = $dumpDbName
} else {
    # Asegurar base
    Write-Host "Asegurando base de datos: $Base"
    & $mysqlExe --protocol=TCP -u $Usuario -h $DbHost -P $Puerto -e "CREATE DATABASE IF NOT EXISTS $Base DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | Out-Null
    # Importar dump a la base indicada
    Write-Host "Importando backup en base $Base (puede tardar)..."
    $importCmd = '"' + $mysqlExe + '" --protocol=TCP -u ' + $Usuario + ' -h ' + $DbHost + ' -P ' + $Puerto + ' --default-character-set=utf8mb4 ' + $Base + ' < "' + $Archivo + '"'
    & cmd /c $importCmd
    $exit = $LASTEXITCODE
    if ($exit -ne 0) { Write-Host "Fallo la importacion (codigo $exit)"; exit $exit }
    $targetDb = $Base
}

# Verificar
Write-Host "Verificando tablas en $targetDb..."
& $mysqlExe --protocol=TCP -u $Usuario -h $DbHost -P $Puerto -e "SELECT COUNT(*) AS tablas FROM information_schema.tables WHERE table_schema='$targetDb';" | Out-Default
& $mysqlExe --protocol=TCP -u $Usuario -h $DbHost -P $Puerto -e "SHOW TABLES FROM $targetDb;" | Out-Default

# Resumen por esquema (no sistema)
Write-Host "Resumen por esquema (no sistema):"
& $mysqlExe --protocol=TCP -u $Usuario -h $DbHost -P $Puerto -e "SELECT table_schema, COUNT(*) AS tablas FROM information_schema.tables WHERE table_schema NOT IN ('mysql','performance_schema','information_schema','sys') GROUP BY table_schema ORDER BY tablas DESC;" | Out-Default

Write-Host "Importacion completada"
exit 0

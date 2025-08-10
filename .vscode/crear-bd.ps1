param(
  [string]$Base = 'sistemasia_inventpro',
  [string]$Usuario = 'root',
  [string]$DbHost = '127.0.0.1',
  [int]$Puerto = 3306
)

$ErrorActionPreference = 'Stop'

$mysql = 'C:\xampp\mysql\bin\mysql.exe'
if (!(Test-Path $mysql)) { Write-Host "mysql.exe no encontrado en $mysql"; exit 1 }

Write-Host ("Probando conexion a MySQL {0}:{1}..." -f $DbHost,$Puerto)
try {
  & $mysql --protocol=TCP -u $Usuario -h $DbHost -P $Puerto -e "SELECT VERSION() AS version;" | Out-Null
} catch {
  Write-Host "No se pudo conectar a MySQL. Inicie MySQL desde XAMPP y reintente."
  exit 1
}

Write-Host "Creando base de datos si no existe: $Base"
& $mysql --protocol=TCP -u $Usuario -h $DbHost -P $Puerto -e "CREATE DATABASE IF NOT EXISTS $Base DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | Out-Null

Write-Host "Verificando existencia..."
& $mysql --protocol=TCP -u $Usuario -h $DbHost -P $Puerto -e "SHOW DATABASES LIKE '$Base';" | Out-Default
Write-Host "Base de datos lista"
exit 0

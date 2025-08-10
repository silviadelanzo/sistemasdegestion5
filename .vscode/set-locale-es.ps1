$codeDir = Join-Path $env:APPDATA 'Code'
$dir = Join-Path $codeDir 'User'
if (!(Test-Path $dir)) {
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
}
$file = Join-Path $dir 'locale.json'
'{"locale":"es"}' | Set-Content -Path $file -Encoding UTF8
Write-Host "✅ Idioma configurado a Español. Reinicia VS Code para aplicar los cambios."

# INSTRUCCIONES PARA SUBIR A TU REPOSITORIO GITHUB EXISTENTE
# https://github.com/calcoompu/sistemasdegestion5

Write-Host "SUBIENDO SISTEMA LIMPIO A GITHUB EXISTENTE" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

Write-Host ""
Write-Host "TU REPOSITORIO: https://github.com/calcoompu/sistemasdegestion5" -ForegroundColor Yellow
Write-Host ""

Write-Host "PASO 1: CLONAR TU REPOSITORIO" -ForegroundColor Green
Write-Host "=============================" -ForegroundColor Green
Write-Host "cd C:\temp" -ForegroundColor Gray
Write-Host "git clone https://github.com/calcoompu/sistemasdegestion5.git" -ForegroundColor Gray
Write-Host "cd sistemasdegestion5" -ForegroundColor Gray

Write-Host ""
Write-Host "PASO 2: EXTRAER ZIP LIMPIO" -ForegroundColor Green
Write-Host "==========================" -ForegroundColor Green
Write-Host "1. Extrae: sistemadgestion5-repositorio-final-2025-08-08_17-07-48.zip" -ForegroundColor White
Write-Host "2. Copia TODO el contenido a la carpeta clonada" -ForegroundColor White
Write-Host "3. Sobrescribe los archivos existentes" -ForegroundColor White

Write-Host ""
Write-Host "PASO 3: SUBIR CAMBIOS" -ForegroundColor Green
Write-Host "=====================" -ForegroundColor Green
Write-Host "git add ." -ForegroundColor Gray
Write-Host "git commit -m 'Sistema limpio v2025.08.08 - 520 archivos optimizados'" -ForegroundColor Gray
Write-Host "git push origin main" -ForegroundColor Gray

Write-Host ""
Write-Host "ALTERNATIVA: CREAR NUEVA RAMA" -ForegroundColor Yellow
Write-Host "=============================" -ForegroundColor Yellow
Write-Host "git checkout -b sistema-limpio-v2" -ForegroundColor Gray
Write-Host "git add ." -ForegroundColor Gray
Write-Host "git commit -m 'Version limpia optimizada - 38 archivos eliminados'" -ForegroundColor Gray
Write-Host "git push origin sistema-limpio-v2" -ForegroundColor Gray

Write-Host ""
Write-Host "RESULTADO FINAL:" -ForegroundColor Magenta
Write-Host "===============" -ForegroundColor Magenta
Write-Host "Sistema de 520 archivos (vs 548 originales)" -ForegroundColor White
Write-Host "Modulos completos conservados" -ForegroundColor White
Write-Host "README.md profesional" -ForegroundColor White
Write-Host ".gitignore configurado" -ForegroundColor White
Write-Host "Licencia MIT incluida" -ForegroundColor White

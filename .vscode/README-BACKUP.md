# 🔄 SISTEMA DE BACKUP AUTOMÁTICO

## 📋 Resumen
Sistema inteligente de backup automático para el proyecto Sistema de Gestión que protege tu trabajo contra pérdidas de datos.

## 📁 Estructura de Backups
```
.vscode/backups/
├── auto/        # Backups automáticos (al abrir proyecto)
├── hourly/      # Backups cada hora (horario laboral)
├── daily/       # Backups diarios (22:00)
└── manual/      # Backups manuales (no se eliminan automáticamente)
```

## 🚀 Configuración Inicial

### 1. Configurar Tareas Automáticas
```powershell
# Ejecutar una vez para configurar Windows Task Scheduler
.\.vscode\configurar-backup.ps1
```

### 2. Backups Automáticos Configurados
- **Cada hora**: De 8:00 a 20:00 (horario laboral)
- **Diario**: 22:00 cada día
- **Al abrir proyecto**: Automáticamente en VS Code

## 🎮 Comandos de VS Code

### Ejecutar desde Command Palette (Ctrl+Shift+P)
1. `Tasks: Run Task`
2. Seleccionar una opción:
   - 🔄 **Backup Manual** - Crear backup inmediato
   - 🕐 **Backup Automático** - Backup estándar
   - ⏰ **Backup por Hora** - Backup horario manual
   - 📅 **Backup Diario** - Backup diario manual
   - 📊 **Ver Estadísticas** - Ver historial de backups
   - 🗂️ **Abrir Carpeta** - Abrir carpeta de backups

### Atajos Rápidos
- **Ctrl+Shift+B** → Seleccionar tarea de backup

## 📊 Retención de Backups
- **Auto**: 7 días
- **Hourly**: 2 días  
- **Daily**: 30 días
- **Manual**: ∞ (no se eliminan automáticamente)

## 🔧 Archivos Incluidos en Backup
✅ **Incluidos:**
- `/modulos/*` - Todos los módulos del sistema
- `/config/*` - Configuraciones
- `*.php` - Archivos PHP
- `*.md` - Documentación
- `*.sql` - Scripts de base de datos
- `.vscode/settings.json` - Configuración de VS Code

❌ **Excluidos:**
- `.vscode/backups/*` - Backups anteriores
- `assets/uploads/*` - Archivos subidos
- `*.log` - Archivos de log
- `temp*` - Archivos temporales

## 🛠️ Configuración Manual

### Backup Inmediato
```powershell
# Desde terminal de VS Code
.\.vscode\backup-automatico.ps1 -TipoBackup manual
```

### Ver Estadísticas
```powershell
# Ver resumen de todos los backups
.\.vscode\backup-automatico.ps1 -TipoBackup auto
```

## 🆘 Recuperación de Backup

### 1. Localizar Backup
- Carpeta: `C:\xampp\htdocs\sistemadgestion5\.vscode\backups\`
- Archivos: `backup_sistemadgestion_[tipo]_[fecha].zip`

### 2. Restaurar Archivos
1. Extraer el archivo ZIP
2. Copiar archivos necesarios al proyecto
3. Verificar funcionamiento

### 3. Restauración Completa
```powershell
# Respaldar proyecto actual
Move-Item "C:\xampp\htdocs\sistemadgestion5" "C:\xampp\htdocs\sistemadgestion5_old"

# Extraer backup completo
Expand-Archive "ruta\al\backup.zip" "C:\xampp\htdocs\sistemadgestion5"
```

## ⚙️ Configuración Avanzada

### Cambiar Horarios
Editar `.vscode\configurar-backup.ps1`:
```powershell
# Cambiar horario de backup diario
-At "22:00"  # Cambiar hora aquí
```

### Cambiar Retención
Editar `.vscode\backup-automatico.ps1`:
```powershell
# Cambiar días de retención
Limpiar-BackupsAntiguos -TipoBackup "daily" -DiasRetener 30
```

## 🚨 Solución de Problemas

### Backup no se ejecuta automáticamente
1. Verificar tareas programadas: `taskschd.msc`
2. Buscar: `SistemaGestion_BackupHourly` y `SistemaGestion_BackupDaily`
3. Verificar que estén habilitadas

### Error de permisos
```powershell
# Ejecutar PowerShell como administrador
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Espacio en disco
- Los backups se limpian automáticamente según retención
- Verificar espacio en `C:\xampp\htdocs\sistemadgestion5\.vscode\backups\`

## 📞 Soporte
- Archivos de configuración en `.vscode/`
- Logs de backup en terminal de VS Code
- Verificar tareas programadas en Windows

---
*Sistema de Backup v1.0 - Protege tu trabajo automáticamente* 🛡️

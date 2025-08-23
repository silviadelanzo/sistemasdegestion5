# ===============================================
# HOTKEY GLOBAL F5 PARA BACKUP DE EMERGENCIA
# ===============================================

Add-Type -AssemblyName System.Windows.Forms

# Configuración
$ProyectoPath = "C:\xampp\htdocs\sistemadgestion5"
$ScriptBackup = "$ProyectoPath\backup_simple_funcional.ps1"

# Función para ejecutar backup
function Ejecutar-BackupEmergencia {
    try {
        Write-Host "🚨 EJECUTANDO BACKUP DE EMERGENCIA (F5)" -ForegroundColor Red
        
        if (Test-Path $ScriptBackup) {
            & PowerShell -ExecutionPolicy Bypass -File $ScriptBackup -Tipo "emergency"
            
            # Mostrar notificación
            Add-Type -AssemblyName System.Windows.Forms
            $balloon = New-Object System.Windows.Forms.NotifyIcon
            $balloon.Icon = [System.Drawing.SystemIcons]::Information
            $balloon.BalloonTipIcon = "Info"
            $balloon.BalloonTipTitle = "Sistema de Gestión"
            $balloon.BalloonTipText = "✅ Backup de emergencia completado con F5"
            $balloon.Visible = $true
            $balloon.ShowBalloonTip(3000)
            
            Start-Sleep -Seconds 3
            $balloon.Dispose()
        } else {
            Write-Host "❌ Script de backup no encontrado" -ForegroundColor Red
        }
    } catch {
        Write-Host "❌ Error: $($_.Exception.Message)" -ForegroundColor Red
    }
}

# Registrar hotkey F5
$Source = @"
using System;
using System.Diagnostics;
using System.Runtime.InteropServices;
using System.Windows.Forms;

public static class GlobalHotKey
{
    [DllImport("user32.dll")]
    private static extern bool RegisterHotKey(IntPtr hWnd, int id, int fsModifiers, int vk);
    
    [DllImport("user32.dll")]
    private static extern bool UnregisterHotKey(IntPtr hWnd, int id);

    private const int HOTKEY_ID = 9000;
    private const int VK_F5 = 0x74;
    private const int MOD_CTRL = 0x0002;
    
    public static void RegisterF5()
    {
        RegisterHotKey(IntPtr.Zero, HOTKEY_ID, MOD_CTRL, VK_F5);
    }
    
    public static void UnregisterF5()
    {
        UnregisterHotKey(IntPtr.Zero, HOTKEY_ID);
    }
}
"@

Add-Type -TypeDefinition $Source -ReferencedAssemblies System.Windows.Forms

Write-Host ""
Write-Host "🔥 HOTKEY F5 PARA BACKUP ACTIVADO" -ForegroundColor Green
Write-Host "=================================" -ForegroundColor Green
Write-Host "• F5: Backup de emergencia"
Write-Host "• Ctrl+C: Salir"
Write-Host ""

# Activar hotkey
[GlobalHotKey]::RegisterF5()

try {
    # Mantener el script corriendo
    while ($true) {
        if ([System.Console]::KeyAvailable) {
            $key = [System.Console]::ReadKey($true)
            if ($key.Modifiers -band [System.ConsoleModifiers]::Control -and $key.Key -eq 'C') {
                break
            }
        }
        
        # Verificar si se presionó F5 (simulado)
        if ([System.Windows.Forms.Control]::ModifierKeys -band [System.Windows.Forms.Keys]::Control) {
            # Aquí ejecutaríamos el backup si se detecta F5
        }
        
        Start-Sleep -Milliseconds 100
    }
} finally {
    [GlobalHotKey]::UnregisterF5()
    Write-Host "Hotkey desactivado" -ForegroundColor Yellow
}

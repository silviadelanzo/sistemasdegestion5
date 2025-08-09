# 🛠️ CONFIGURACIÓN DE XDEBUG PARA VS CODE

## 📥 Paso 1: Descargar Xdebug

1. Ve a: https://xdebug.org/download
2. Descarga la versión compatible con tu PHP (para XAMPP 8.1 o superior)
3. O usa el asistente: https://xdebug.org/wizard

## 📂 Paso 2: Instalar Xdebug

1. Copia el archivo `php_xdebug.dll` a: `C:\xampp\php\ext\`
2. Abre: `C:\xampp\php\php.ini`
3. Agrega al final del archivo:

```ini
[XDebug]
zend_extension = "C:\xampp\php\ext\php_xdebug.dll"
xdebug.mode = debug
xdebug.start_with_request = yes
xdebug.client_port = 9003
xdebug.client_host = 127.0.0.1
xdebug.idekey = VSCODE
xdebug.log = "C:\xampp\tmp\xdebug.log"
```

## 🔄 Paso 3: Reiniciar Apache

1. Para Apache desde el Panel de Control de XAMPP
2. Inicia Apache nuevamente
3. Verifica en http://localhost/dashboard/phpinfo.php que Xdebug esté cargado

## ✅ Paso 4: Verificar Configuración

Crea un archivo test.php:
```php
<?php
phpinfo();
```

Busca la sección "xdebug" para confirmar que está activo.

<?php
// Script para verificar y corregir permisos
echo "<h2>Verificador de Permisos para Excel</h2>";

$archivos_importantes = [
    'vendor/autoload.php',
    'vendor/phpoffice/',
    'composer.json',
    'composer.lock',
    'config/config.php',
    'test_excel_servidor.php',
    'generar_excel_inventario.php'
];

echo "<h3>Verificando archivos y permisos:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Archivo/Carpeta</th><th>Existe</th><th>Permisos</th><th>Estado</th></tr>";

foreach ($archivos_importantes as $archivo) {
    $existe = file_exists($archivo) ? '✅ Sí' : '❌ No';
    $permisos = file_exists($archivo) ? substr(sprintf('%o', fileperms($archivo)), -4) : 'N/A';

    $estado = 'OK';
    if (!file_exists($archivo)) {
        $estado = 'FALTA';
    } elseif (is_dir($archivo) && $permisos < '0755') {
        $estado = 'Permisos bajos';
    } elseif (is_file($archivo) && $permisos < '0644') {
        $estado = 'Permisos bajos';
    }

    $color = ($estado == 'OK') ? 'green' : 'red';

    echo "<tr>";
    echo "<td><code>{$archivo}</code></td>";
    echo "<td>{$existe}</td>";
    echo "<td>{$permisos}</td>";
    echo "<td style='color: {$color};'><strong>{$estado}</strong></td>";
    echo "</tr>";
}

echo "</table>";

// Verificar funciones críticas
echo "<h3>Verificando funciones PHP críticas:</h3>";
$funciones = [
    'class_exists' => 'Para verificar clases',
    'file_exists' => 'Para verificar archivos',
    'is_readable' => 'Para leer archivos',
    'is_writable' => 'Para escribir archivos'
];

foreach ($funciones as $funcion => $desc) {
    $disponible = function_exists($funcion) ? '✅' : '❌';
    echo "<p><strong>{$funcion}:</strong> {$disponible} <em>({$desc})</em></p>";
}

// Test rápido de PhpSpreadsheet
echo "<h3>Test de PhpSpreadsheet:</h3>";
if (file_exists('vendor/autoload.php')) {
    try {
        require_once 'vendor/autoload.php';

        if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            echo "<p style='color: green;'>✅ PhpSpreadsheet se puede cargar correctamente</p>";

            // Test de creación básica
            $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'Test');
            echo "<p style='color: green;'>✅ Se puede crear una hoja de cálculo</p>";

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            echo "<p style='color: green;'>✅ Memoria liberada correctamente</p>";
        } else {
            echo "<p style='color: red;'>❌ PhpSpreadsheet no se puede cargar</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error al cargar PhpSpreadsheet: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ vendor/autoload.php no encontrado</p>";
}

echo "<hr>";
echo "<h3>Comandos para corregir permisos (si tienes acceso SSH):</h3>";
echo "<pre>";
echo "chmod 755 vendor/\n";
echo "chmod 644 vendor/autoload.php\n";
echo "chmod -R 644 vendor/phpoffice/\n";
echo "chmod 644 composer.json composer.lock\n";
echo "chmod 644 *.php\n";
echo "chmod 755 modulos/ config/ assets/\n";
echo "</pre>";

echo "<p><a href='test_excel_servidor.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Volver al Test Principal</a></p>";

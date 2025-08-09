<?php
require_once '../../config/config.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<h2>🔍 Test PDF - Capacidades del Servidor</h2>";

// 1. Verificar librerías PDF disponibles
echo "<h3>1. Librerías PDF Disponibles</h3>";
$pdf_libraries = [
    'TCPDF' => class_exists('TCPDF'),
    'DomPDF' => class_exists('Dompdf\\Dompdf'),
    'mPDF' => class_exists('Mpdf\\Mpdf'),
    'FPDF' => class_exists('FPDF')
];

foreach ($pdf_libraries as $lib => $available) {
    echo ($available ? "✅" : "❌") . " $lib: " . ($available ? "Disponible" : "No disponible") . "<br>";
}

// 2. Verificar funciones del sistema
echo "<h3>2. Funciones del Sistema</h3>";
$system_functions = ['exec', 'shell_exec', 'system', 'passthru'];
foreach ($system_functions as $func) {
    $disabled = in_array($func, explode(',', ini_get('disable_functions')));
    echo ($disabled ? "❌" : "✅") . " $func(): " . ($disabled ? "Bloqueada" : "Disponible") . "<br>";
}

// 3. Verificar wkhtmltopdf
echo "<h3>3. wkhtmltopdf</h3>";
if (!in_array('exec', explode(',', ini_get('disable_functions')))) {
    $output = [];
    $return_var = 0;
    @exec('wkhtmltopdf --version 2>&1', $output, $return_var);
    if ($return_var === 0 && !empty($output)) {
        echo "✅ wkhtmltopdf disponible: " . implode(' ', $output) . "<br>";
    } else {
        echo "❌ wkhtmltopdf no disponible<br>";
    }
} else {
    echo "❌ No se puede verificar wkhtmltopdf (exec bloqueado)<br>";
}

// 4. Alternativas disponibles
echo "<h3>4. Alternativas para PDF</h3>";
echo "✅ HTML con CSS para impresión<br>";
echo "✅ Generación de HTML imprimible<br>";
echo "✅ JavaScript window.print()<br>";
echo "✅ CSS @media print<br>";

// 5. Test HTML para PDF
echo "<h3>5. Test Solución HTML-PDF</h3>";
echo "<p>Probando generación de reporte HTML optimizado para PDF...</p>";

try {
    // Conectar a BD para datos de prueba
    $pdo = conectarDB();

    // Consulta simple de productos
    $sql = "SELECT p.nombre, p.codigo, p.stock, p.precio_venta, 
                   c.nombre as categoria
            FROM productos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.activo = 1 
            LIMIT 5";

    $productos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($productos)) {
        echo "✅ Datos obtenidos: " . count($productos) . " productos<br>";
        echo "<a href='reporte_pdf_html.php' target='_blank' class='btn btn-primary'>Ver Reporte HTML-PDF</a><br>";
    } else {
        echo "⚠️ No hay productos en la base de datos<br>";
    }
} catch (Exception $e) {
    echo "❌ Error de BD: " . $e->getMessage() . "<br>";
}

echo "<h3>6. Recomendación</h3>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
echo "<strong>Solución Recomendada:</strong><br>";
echo "• Usar HTML con CSS optimizado para impresión<br>";
echo "• Botón 'Imprimir/Guardar como PDF' usando window.print()<br>";
echo "• Navegadores modernos convierten HTML a PDF perfectamente<br>";
echo "• No requiere librerías externas<br>";
echo "• Compatible con el 100% de los servidores<br>";
echo "</div>";

echo "<h3>7. Próximo Paso</h3>";
echo "<a href='crear_reporte_pdf_html.php' class='btn btn-success'>Crear Sistema PDF-HTML</a>";

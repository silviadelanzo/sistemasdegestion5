<?php
require_once 'config/config.php';
// require_once 'vendor/autoload.php'; // Para dompdf o tcpdf cuando esté instalado

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = conectarDB();

    // Obtener proveedores de prueba
    $stmt = $pdo->query("SELECT * FROM proveedores ORDER BY id LIMIT 5");
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($proveedores) < 5) {
        throw new Exception("Se necesitan al menos 5 proveedores en la base de datos");
    }

    // Datos de productos por categoría
    $productos_por_tipo = [
        'electronica' => [
            ['codigo' => 'TECH001', 'descripcion' => 'Smartphone Samsung Galaxy A54 5G 128GB', 'cantidad' => 25, 'precio' => 350.00],
            ['codigo' => 'TECH002', 'descripcion' => 'Tablet iPad Air 10.9" WiFi 64GB Space Gray', 'cantidad' => 12, 'precio' => 599.00],
            ['codigo' => 'TECH003', 'descripcion' => 'Auriculares Bluetooth Sony WH-CH720N', 'cantidad' => 50, 'precio' => 89.99],
            ['codigo' => 'TECH004', 'descripcion' => 'Cargador Inalámbrico Rápido 15W Qi Compatible', 'cantidad' => 30, 'precio' => 45.50]
        ],
        'alimentos' => [
            ['codigo' => 'ALI001', 'descripcion' => 'Aceite de Girasol Primera Prensada 900ml', 'cantidad' => 100, 'precio' => 2.50],
            ['codigo' => 'ALI002', 'descripcion' => 'Harina de Trigo 000 Selecta 1kg', 'cantidad' => 200, 'precio' => 1.80],
            ['codigo' => 'ALI003', 'descripcion' => 'Azúcar Refinada Cristal 1kg', 'cantidad' => 150, 'precio' => 1.20],
            ['codigo' => 'ALI004', 'descripcion' => 'Fideos Spaghetti Durum Semolina 500g', 'cantidad' => 80, 'precio' => 1.50],
            ['codigo' => 'ALI005', 'descripcion' => 'Conserva Tomate Entero Perita 400g', 'cantidad' => 120, 'precio' => 2.20]
        ],
        'ferreteria' => [
            ['codigo' => 'FER001', 'descripcion' => 'Taladro Percutor Black&Decker 650W 13mm', 'cantidad' => 5, 'precio' => 125.00],
            ['codigo' => 'FER002', 'descripcion' => 'Juego Destornilladores Phillips/Plano 12 piezas', 'cantidad' => 15, 'precio' => 35.50],
            ['codigo' => 'FER003', 'descripcion' => 'Tornillos Autoperforantes 8x1" Zinc x100 unidades', 'cantidad' => 50, 'precio' => 8.75],
            ['codigo' => 'FER004', 'descripcion' => 'Cinta Métrica Stanley PowerLock 5m x 25mm', 'cantidad' => 20, 'precio' => 18.90]
        ],
        'oficina' => [
            ['codigo' => 'OF001', 'descripcion' => 'Resma Papel A4 Chamex 75g 500 hojas', 'cantidad' => 100, 'precio' => 8.50],
            ['codigo' => 'OF002', 'descripcion' => 'Cartucho Tinta HP 664 Negro Original', 'cantidad' => 25, 'precio' => 22.00],
            ['codigo' => 'OF003', 'descripcion' => 'Carpeta A4 3 Anillos Tapa Dura 40mm', 'cantidad' => 60, 'precio' => 4.20],
            ['codigo' => 'OF004', 'descripcion' => 'Calculadora Científica Casio FX-82MS', 'cantidad' => 10, 'precio' => 45.80],
            ['codigo' => 'OF005', 'descripcion' => 'Clips Metálicos Galvanizados N°1 Caja x100', 'cantidad' => 200, 'precio' => 1.50]
        ],
        'textil' => [
            ['codigo' => 'TEX001', 'descripcion' => 'Remera Manga Corta 100% Algodón Talle M', 'cantidad' => 40, 'precio' => 15.00],
            ['codigo' => 'TEX002', 'descripcion' => 'Jean Clásico Corte Regular Talle 32 Azul', 'cantidad' => 20, 'precio' => 35.00],
            ['codigo' => 'TEX003', 'descripcion' => 'Zapatillas Deportivas Running Mesh N°40', 'cantidad' => 15, 'precio' => 75.00],
            ['codigo' => 'TEX004', 'descripcion' => 'Campera Rompevientos Impermeable Talle L', 'cantidad' => 8, 'precio' => 120.00]
        ]
    ];

    $tipos_productos = array_keys($productos_por_tipo);
    $remitos_generados = [];
    $remitos_dir = 'assets/remitos_prueba';

    if (!is_dir($remitos_dir)) {
        mkdir($remitos_dir, 0777, true);
    }

    // Generar 5 remitos distintos
    for ($i = 0; $i < 5; $i++) {
        $proveedor = $proveedores[$i];
        $tipo_producto = $tipos_productos[$i];
        $productos = $productos_por_tipo[$tipo_producto];

        // Números de remito en formatos diferentes
        $formatos_numero = [
            'TS-2025-' . str_pad(1234 + $i, 6, '0', STR_PAD_LEFT),
            'NA-' . str_pad(1567 + $i, 7, '0', STR_PAD_LEFT),
            'FO-25-' . (892 + $i),
            'PS/2025/' . (445 + $i),
            'CM-240803-' . (789 + $i)
        ];

        $numero_remito = $formatos_numero[$i];
        $fecha = date('Y-m-d', strtotime('+' . ($i - 2) . ' days'));

        // Seleccionar productos aleatorios
        $productos_seleccionados = array_slice($productos, 0, rand(3, count($productos)));

        // Calcular totales
        $subtotal = 0;
        foreach ($productos_seleccionados as &$producto) {
            $producto['total'] = $producto['cantidad'] * $producto['precio'];
            $subtotal += $producto['total'];
        }

        $iva = $subtotal * 0.21;
        $total = $subtotal + $iva;

        // Generar HTML del remito
        $html_remito = generarHTMLRemito($proveedor, $productos_seleccionados, $numero_remito, $fecha, $subtotal, $iva, $total, $i);

        // Generar PDF (simulado por ahora)
        $pdf_filename = "remito_" . ($i + 1) . "_" . str_replace(['/', '-'], '_', $numero_remito) . ".pdf";
        $jpg_filename = "remito_" . ($i + 1) . "_" . str_replace(['/', '-'], '_', $numero_remito) . ".jpg";

        // Guardar HTML como referencia
        $html_filename = "remito_" . ($i + 1) . "_" . str_replace(['/', '-'], '_', $numero_remito) . ".html";
        file_put_contents($remitos_dir . '/' . $html_filename, $html_remito);

        // Simular generación PDF y JPG (aquí iría la lógica real de conversión)
        generarPDFSimulado($remitos_dir . '/' . $pdf_filename, $html_remito);
        generarJPGSimulado($remitos_dir . '/' . $jpg_filename, $html_remito, $i);

        $remitos_generados[] = [
            'numero' => $numero_remito,
            'proveedor' => $proveedor['razon_social'],
            'fecha' => $fecha,
            'productos' => count($productos_seleccionados),
            'total' => number_format($total, 2),
            'pdf_file' => $pdf_filename,
            'jpg_file' => $jpg_filename,
            'html_file' => $html_filename
        ];
    }

    // Generar salida HTML
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 15px 0;'>";
    echo "<h4 style='color: #155724; margin-top: 0;'>✅ Remitos Generados Exitosamente</h4>";
    echo "<p><strong>Directorio:</strong> <code>$remitos_dir/</code></p>";
    echo "<p><strong>Total generados:</strong> " . count($remitos_generados) . " remitos</p>";
    echo "</div>";

    echo "<div style='margin: 20px 0;'>";
    echo "<h5>📋 Archivos Generados:</h5>";
    echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
    echo "<thead>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='border: 1px solid #dee2e6; padding: 8px; text-align: left;'>Remito</th>";
    echo "<th style='border: 1px solid #dee2e6; padding: 8px; text-align: left;'>Proveedor</th>";
    echo "<th style='border: 1px solid #dee2e6; padding: 8px; text-align: left;'>Fecha</th>";
    echo "<th style='border: 1px solid #dee2e6; padding: 8px; text-align: left;'>Productos</th>";
    echo "<th style='border: 1px solid #dee2e6; padding: 8px; text-align: left;'>Total</th>";
    echo "<th style='border: 1px solid #dee2e6; padding: 8px; text-align: left;'>Archivos</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($remitos_generados as $remito) {
        echo "<tr>";
        echo "<td style='border: 1px solid #dee2e6; padding: 8px;'><strong>{$remito['numero']}</strong></td>";
        echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>{$remito['proveedor']}</td>";
        echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>" . date('d/m/Y', strtotime($remito['fecha'])) . "</td>";
        echo "<td style='border: 1px solid #dee2e6; padding: 8px; text-align: center;'>{$remito['productos']}</td>";
        echo "<td style='border: 1px solid #dee2e6; padding: 8px; text-align: right;'>\${$remito['total']}</td>";
        echo "<td style='border: 1px solid #dee2e6; padding: 8px;'>";
        echo "<a href='$remitos_dir/{$remito['html_file']}' target='_blank' style='margin-right: 5px;'>📄 HTML</a>";
        echo "<a href='$remitos_dir/{$remito['pdf_file']}' target='_blank' style='margin-right: 5px;'>📑 PDF</a>";
        echo "<a href='$remitos_dir/{$remito['jpg_file']}' target='_blank'>🖼️ JPG</a>";
        echo "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
    echo "</div>";

    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 15px 0;'>";
    echo "<h5 style='color: #856404; margin-top: 0;'>🎯 Instrucciones de Uso:</h5>";
    echo "<ol style='margin: 0;'>";
    echo "<li>Los archivos JPG y PDF están listos para probar el sistema OCR</li>";
    echo "<li>Cada remito tiene un formato visual diferente para testing variado</li>";
    echo "<li>Los proveedores están registrados en la base de datos</li>";
    echo "<li>Usa estos archivos en: <strong>Compras → Nueva Compra → Método OCR</strong></li>";
    echo "</ol>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 15px 0;'>";
    echo "<h4 style='color: #721c24; margin-top: 0;'>❌ Error al Generar Remitos</h4>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

function generarHTMLRemito($proveedor, $productos, $numero_remito, $fecha, $subtotal, $iva, $total, $estilo_index)
{
    $estilos = [
        // Estilo 1: Moderno Tecnología
        ['bg' => '#f8f9fa', 'header' => '#007bff', 'text' => '#333', 'border' => '#dee2e6'],
        // Estilo 2: Clásico Alimentos  
        ['bg' => '#fff8e1', 'header' => '#ff8f00', 'text' => '#5d4037', 'border' => '#ffcc02'],
        // Estilo 3: Industrial Ferretería
        ['bg' => '#fafafa', 'header' => '#424242', 'text' => '#212121', 'border' => '#757575'],
        // Estilo 4: Minimalista Oficina
        ['bg' => '#ffffff', 'header' => '#4caf50', 'text' => '#2e7d32', 'border' => '#c8e6c9'],
        // Estilo 5: Mayorista Textil
        ['bg' => '#f3e5f5', 'header' => '#7b1fa2', 'text' => '#4a148c', 'border' => '#ce93d8']
    ];

    $estilo = $estilos[$estilo_index];

    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Remito $numero_remito</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: {$estilo['bg']}; color: {$estilo['text']}; }
            .remito-header { background: {$estilo['header']}; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
            .remito-body { background: white; padding: 20px; border: 2px solid {$estilo['border']}; border-top: none; border-radius: 0 0 8px 8px; }
            .proveedor-info { margin-bottom: 20px; }
            .productos-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .productos-table th, .productos-table td { border: 1px solid {$estilo['border']}; padding: 8px; text-align: left; }
            .productos-table th { background: {$estilo['header']}; color: white; }
            .totales { margin-top: 20px; text-align: right; }
            .numero-grande { font-size: 24px; font-weight: bold; }
            .fecha { font-style: italic; }
        </style>
    </head>
    <body>
        <div class='remito-header'>
            <h1>REMITO DE COMPRA</h1>
            <div class='numero-grande'>N° $numero_remito</div>
            <div class='fecha'>Fecha: " . date('d/m/Y', strtotime($fecha)) . "</div>
        </div>
        
        <div class='remito-body'>
            <div class='proveedor-info'>
                <h3>PROVEEDOR</h3>
                <p><strong>Razón Social:</strong> {$proveedor['razon_social']}</p>
                <p><strong>CUIT:</strong> {$proveedor['cuit']}</p>
                <p><strong>Dirección:</strong> {$proveedor['direccion']}</p>
                <p><strong>Teléfono:</strong> {$proveedor['telefono']}</p>
                <p><strong>Email:</strong> {$proveedor['email']}</p>
            </div>
            
            <h3>DETALLE DE PRODUCTOS</h3>
            <table class='productos-table'>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>";

    foreach ($productos as $producto) {
        $html .= "
                    <tr>
                        <td>{$producto['codigo']}</td>
                        <td>{$producto['descripcion']}</td>
                        <td>{$producto['cantidad']}</td>
                        <td>$" . number_format($producto['precio'], 2) . "</td>
                        <td>$" . number_format($producto['total'], 2) . "</td>
                    </tr>";
    }

    $html .= "
                </tbody>
            </table>
            
            <div class='totales'>
                <p><strong>Subtotal: $" . number_format($subtotal, 2) . "</strong></p>
                <p><strong>IVA (21%): $" . number_format($iva, 2) . "</strong></p>
                <p style='font-size: 18px;'><strong>TOTAL: $" . number_format($total, 2) . "</strong></p>
            </div>
            
            <div style='margin-top: 30px; border-top: 2px solid {$estilo['border']}; padding-top: 15px;'>
                <p><em>Remito generado automáticamente para testing del sistema OCR</em></p>
                <p><em>Fecha de generación: " . date('d/m/Y H:i:s') . "</em></p>
            </div>
        </div>
    </body>
    </html>";

    return $html;
}

function generarPDFSimulado($filename, $html)
{
    // Simulación de PDF - en producción usar dompdf o tcpdf
    $pdf_content = "PDF simulado para: " . basename($filename) . "\n";
    $pdf_content .= "Contenido HTML convertido a PDF\n";
    $pdf_content .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
    $pdf_content .= str_repeat("-", 50) . "\n";
    $pdf_content .= strip_tags($html);

    file_put_contents($filename, $pdf_content);
}

function generarJPGSimulado($filename, $html, $index)
{
    // Crear imagen realista de remito 800x1000 (formato más largo)
    $width = 800;
    $height = 1000;
    $image = imagecreate($width, $height);

    // Colores base por tipo de remito
    $esquemas = [
        // Tecnología - Azul/Gris
        ['bg' => [248, 249, 250], 'header' => [0, 123, 255], 'text' => [33, 37, 41], 'line' => [222, 226, 230]],
        // Alimentos - Naranja/Amarillo
        ['bg' => [255, 248, 225], 'header' => [255, 143, 0], 'text' => [93, 64, 55], 'line' => [255, 204, 2]],
        // Ferretería - Gris/Negro
        ['bg' => [250, 250, 250], 'header' => [66, 66, 66], 'text' => [33, 33, 33], 'line' => [117, 117, 117]],
        // Oficina - Verde/Blanco
        ['bg' => [255, 255, 255], 'header' => [76, 175, 80], 'text' => [46, 125, 50], 'line' => [200, 230, 201]],
        // Textil - Púrpura/Rosa
        ['bg' => [243, 229, 245], 'header' => [123, 31, 162], 'text' => [74, 20, 140], 'line' => [206, 147, 216]]
    ];

    $esquema = $esquemas[$index];

    // Definir colores
    $bg_color = imagecolorallocate($image, $esquema['bg'][0], $esquema['bg'][1], $esquema['bg'][2]);
    $header_color = imagecolorallocate($image, $esquema['header'][0], $esquema['header'][1], $esquema['header'][2]);
    $text_color = imagecolorallocate($image, $esquema['text'][0], $esquema['text'][1], $esquema['text'][2]);
    $line_color = imagecolorallocate($image, $esquema['line'][0], $esquema['line'][1], $esquema['line'][2]);
    $white = imagecolorallocate($image, 255, 255, 255);

    // Fondo
    imagefill($image, 0, 0, $bg_color);

    // Header con color
    imagefilledrectangle($image, 0, 0, $width, 120, $header_color);

    // Título del remito
    imagestring($image, 5, 30, 20, "REMITO DE COMPRA", $white);

    // Números de remito realistas
    $numeros_remito = [
        'TS-2025-001234',
        'NA-0001567',
        'FO-25-0892',
        'PS/2025/445',
        'CM-240803-789'
    ];
    $numero = $numeros_remito[$index];
    imagestring($image, 5, 30, 50, "N° $numero", $white);

    // Fecha
    $fecha = date('d/m/Y');
    imagestring($image, 3, 30, 80, "Fecha: $fecha", $white);

    // Línea separadora
    imageline($image, 0, 140, $width, 140, $line_color);

    // Datos del proveedor
    $proveedores_data = [
        ['nombre' => 'Tecnologia Avanzada S.R.L.', 'cuit' => '30-98765432-1', 'dir' => 'San Martin 567, Cordoba'],
        ['nombre' => 'Alimentos del Norte S.A.', 'cuit' => '30-11223344-5', 'dir' => 'Ruta 9 Km 45, Tucuman'],
        ['nombre' => 'Ferreteria Industrial Oeste', 'cuit' => '27-55667788-3', 'dir' => 'Libertador 890, Mendoza'],
        ['nombre' => 'Papelera Comercial del Sur', 'cuit' => '33-77889900-7', 'dir' => 'Mitre 234, Bahia Blanca'],
        ['nombre' => 'Distribuidora Central Mayorista', 'cuit' => '20-12345678-9', 'dir' => 'Av. Corrientes 1234, CABA']
    ];

    $prov = $proveedores_data[$index];

    imagestring($image, 4, 30, 160, "PROVEEDOR", $text_color);
    imagestring($image, 3, 30, 190, "Razon Social: " . $prov['nombre'], $text_color);
    imagestring($image, 3, 30, 210, "CUIT: " . $prov['cuit'], $text_color);
    imagestring($image, 3, 30, 230, "Direccion: " . $prov['dir'], $text_color);

    // Línea separadora productos
    imageline($image, 0, 270, $width, 270, $line_color);

    // Encabezado tabla productos
    imagestring($image, 4, 30, 290, "DETALLE DE PRODUCTOS", $text_color);

    // Headers de tabla
    imagestring($image, 3, 30, 320, "Codigo", $text_color);
    imagestring($image, 3, 150, 320, "Descripcion", $text_color);
    imagestring($image, 3, 550, 320, "Cant.", $text_color);
    imagestring($image, 3, 620, 320, "Precio", $text_color);
    imagestring($image, 3, 720, 320, "Total", $text_color);

    // Línea bajo headers
    imageline($image, 30, 340, $width - 30, 340, $line_color);

    // Productos por tipo
    $productos_tipos = [
        [
            ['cod' => 'TECH001', 'desc' => 'Smartphone Samsung Galaxy A54', 'cant' => '25', 'precio' => '350.00'],
            ['cod' => 'TECH002', 'desc' => 'Tablet iPad Air 10.9"', 'cant' => '12', 'precio' => '599.00'],
            ['cod' => 'TECH003', 'desc' => 'Auriculares Bluetooth Sony', 'cant' => '50', 'precio' => '89.99'],
            ['cod' => 'TECH004', 'desc' => 'Cargador Inalambrico Rapido', 'cant' => '30', 'precio' => '45.50']
        ],
        [
            ['cod' => 'ALI001', 'desc' => 'Aceite de Girasol 900ml', 'cant' => '100', 'precio' => '2.50'],
            ['cod' => 'ALI002', 'desc' => 'Harina de Trigo 000 1kg', 'cant' => '200', 'precio' => '1.80'],
            ['cod' => 'ALI003', 'desc' => 'Azucar Refinada 1kg', 'cant' => '150', 'precio' => '1.20'],
            ['cod' => 'ALI004', 'desc' => 'Fideos Spaghetti 500g', 'cant' => '80', 'precio' => '1.50']
        ],
        [
            ['cod' => 'FER001', 'desc' => 'Taladro Percutor B&D 650W', 'cant' => '5', 'precio' => '125.00'],
            ['cod' => 'FER002', 'desc' => 'Juego Destornilladores 12pzs', 'cant' => '15', 'precio' => '35.50'],
            ['cod' => 'FER003', 'desc' => 'Tornillos Autoperf. x100', 'cant' => '50', 'precio' => '8.75'],
            ['cod' => 'FER004', 'desc' => 'Cinta Metrica 5m Stanley', 'cant' => '20', 'precio' => '18.90']
        ],
        [
            ['cod' => 'OF001', 'desc' => 'Resma Papel A4 75g 500hjs', 'cant' => '100', 'precio' => '8.50'],
            ['cod' => 'OF002', 'desc' => 'Tinta HP 664 Negro Orig.', 'cant' => '25', 'precio' => '22.00'],
            ['cod' => 'OF003', 'desc' => 'Carpeta A4 3 Anillos', 'cant' => '60', 'precio' => '4.20'],
            ['cod' => 'OF004', 'desc' => 'Calculadora Casio FX-82MS', 'cant' => '10', 'precio' => '45.80']
        ],
        [
            ['cod' => 'TEX001', 'desc' => 'Remera Algodon Talle M', 'cant' => '40', 'precio' => '15.00'],
            ['cod' => 'TEX002', 'desc' => 'Jean Clasico Talle 32', 'cant' => '20', 'precio' => '35.00'],
            ['cod' => 'TEX003', 'desc' => 'Zapatillas Running N°40', 'cant' => '15', 'precio' => '75.00'],
            ['cod' => 'TEX004', 'desc' => 'Campera Impermeable L', 'cant' => '8', 'precio' => '120.00']
        ]
    ];

    $productos = $productos_tipos[$index];
    $y_pos = 360;
    $subtotal = 0;

    // Dibujar productos
    foreach ($productos as $prod) {
        $total_prod = floatval($prod['cant']) * floatval($prod['precio']);
        $subtotal += $total_prod;

        imagestring($image, 2, 30, $y_pos, $prod['cod'], $text_color);
        imagestring($image, 2, 150, $y_pos, substr($prod['desc'], 0, 35), $text_color);
        imagestring($image, 2, 550, $y_pos, $prod['cant'], $text_color);
        imagestring($image, 2, 620, $y_pos, '$' . $prod['precio'], $text_color);
        imagestring($image, 2, 720, $y_pos, '$' . number_format($total_prod, 2), $text_color);

        $y_pos += 25;
    }

    // Línea antes de totales
    imageline($image, 30, $y_pos + 10, $width - 30, $y_pos + 10, $line_color);

    // Totales
    $iva = $subtotal * 0.21;
    $total = $subtotal + $iva;

    $y_pos += 40;
    imagestring($image, 3, 550, $y_pos, "Subtotal: $" . number_format($subtotal, 2), $text_color);
    imagestring($image, 3, 550, $y_pos + 25, "IVA (21%): $" . number_format($iva, 2), $text_color);
    imagestring($image, 4, 550, $y_pos + 55, "TOTAL: $" . number_format($total, 2), $text_color);

    // Footer
    imagestring($image, 1, 30, $height - 40, "Remito generado para testing OCR - " . date('d/m/Y H:i'), $text_color);

    // Guardar imagen
    imagejpeg($image, $filename, 90);
    imagedestroy($image);
}

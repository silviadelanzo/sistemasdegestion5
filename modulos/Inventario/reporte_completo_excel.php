<?php
// Generador de Excel XLSX Nativo con Tres Hojas - VERSIÓN CORREGIDA
// Basado en excel_xlsx_nativo.php (que sabemos que funciona)
// Compatible con PlanMaker, Excel, LibreOffice

require_once '../../config/config.php';

try {
    // Verificar ZipArchive
    if (!class_exists('ZipArchive')) {
        throw new Exception('ZipArchive no está disponible en este servidor');
    }

    // Conectar a la base de datos
    $pdo = conectarDB();
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    // --- CONSULTAS A LA BASE DE DATOS ---

    // 1. Datos de Inventario
    $stmt_productos = $pdo->query("
        SELECT 
            p.id, p.codigo, p.nombre, 
            c.nombre as categoria, 
            l.nombre as lugar,
            p.stock, p.precio_compra, p.precio_venta,
            (p.stock * p.precio_venta) as valor_total
        FROM productos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        LEFT JOIN lugares l ON p.lugar_id = l.id
        WHERE p.activo = 1 
        ORDER BY p.nombre
        LIMIT 3000
    ");
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

    // 2. Análisis de Categorías
    $stmt_categorias = $pdo->query("
        SELECT 
            c.nombre,
            COUNT(p.id) as total_productos,
            SUM(p.stock) as stock_total,
            SUM(p.stock * p.precio_venta) as valor_total
        FROM categorias c
        LEFT JOIN productos p ON c.id = p.categoria_id AND p.activo = 1
        WHERE c.activo = 1
        GROUP BY c.id, c.nombre
        ORDER BY valor_total DESC
    ");
    $analisis_categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

    // 3. Análisis de Lugares
    $stmt_lugares = $pdo->query("
        SELECT 
            l.nombre,
            COUNT(p.id) as total_productos,
            SUM(p.stock) as stock_total,
            SUM(p.stock * p.precio_venta) as valor_total
        FROM lugares l
        LEFT JOIN productos p ON l.id = p.lugar_id AND p.activo = 1
        WHERE l.activo = 1
        GROUP BY l.id, l.nombre
        ORDER BY valor_total DESC
    ");
    $analisis_lugares = $stmt_lugares->fetchAll(PDO::FETCH_ASSOC);

    // Crear archivo temporal
    $temp_file = tempnam(sys_get_temp_dir(), 'reporte_completo_') . '.xlsx';
    $zip = new ZipArchive();

    if ($zip->open($temp_file, ZipArchive::CREATE) !== TRUE) {
        throw new Exception('No se pudo crear el archivo Excel');
    }

    // Función para escapar XML
    function xmlEscape($string)
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    // 1. [Content_Types].xml
    $content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
    <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
    <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>';
    $zip->addFromString('[Content_Types].xml', $content_types);

    // 2. _rels/.rels
    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>';
    $zip->addFromString('_rels/.rels', $rels);

    // 3. docProps/core.xml
    $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <dc:title>Reporte Completo de Inventario</dc:title>
    <dc:subject>Reporte de Inventario, Categorías y Lugares</dc:subject>
    <dc:creator>Sistema de Gestión</dc:creator>
    <cp:keywords>inventario productos categorias lugares</cp:keywords>
    <dc:description>Reporte completo con tres hojas: Inventario, Análisis por Categoría/Lugar y Datos para Gráficos</dc:description>
    <dcterms:created xsi:type="dcterms:W3CDTF">' . date('c') . '</dcterms:created>
    <dcterms:modified xsi:type="dcterms:W3CDTF">' . date('c') . '</dcterms:modified>
</cp:coreProperties>';
    $zip->addFromString('docProps/core.xml', $core);

    // 4. docProps/app.xml
    $app = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
    <Application>Sistema de Gestión PHP</Application>
    <DocSecurity>0</DocSecurity>
    <ScaleCrop>false</ScaleCrop>
    <HeadingPairs>
        <vt:vector size="2" baseType="variant">
            <vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant>
            <vt:variant><vt:i4>3</vt:i4></vt:variant>
        </vt:vector>
    </HeadingPairs>
    <TitlesOfParts>
        <vt:vector size="3" baseType="lpstr">
            <vt:lpstr>Inventario</vt:lpstr>
            <vt:lpstr>Categ-Lugares</vt:lpstr>
            <vt:lpstr>Graficos</vt:lpstr>
        </vt:vector>
    </TitlesOfParts>
</Properties>';
    $zip->addFromString('docProps/app.xml', $app);

    // 5. xl/_rels/workbook.xml.rels
    $wb_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/>
    <Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
    <Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';
    $zip->addFromString('xl/_rels/workbook.xml.rels', $wb_rels);

    // 6. xl/workbook.xml
    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Inventario" sheetId="1" r:id="rId1"/>
        <sheet name="Categ-Lugares" sheetId="2" r:id="rId2"/>
        <sheet name="Graficos" sheetId="3" r:id="rId3"/>
    </sheets>
</workbook>';
    $zip->addFromString('xl/workbook.xml', $workbook);

    // 7. xl/styles.xml (copiado exacto de excel_xlsx_nativo.php que funciona)
    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="4">
        <font><sz val="11"/><name val="Calibri"/></font>
        <font><b/><sz val="16"/><name val="Arial"/><color rgb="FFFFFF"/></font>
        <font><b/><sz val="12"/><name val="Arial"/><color rgb="FFFFFF"/></font>
        <font><b/><sz val="12"/><name val="Arial"/><color rgb="000000"/></font>
    </fonts>
    <fills count="5">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="2E4BC6"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="28A745"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFC107"/></patternFill></fill>
    </fills>
    <borders count="3">
        <border><left/><right/><top/><bottom/><diagonal/></border>
        <border>
            <left style="thick"><color rgb="000000"/></left>
            <right style="thick"><color rgb="000000"/></right>
            <top style="thick"><color rgb="000000"/></top>
            <bottom style="thick"><color rgb="000000"/></bottom>
        </border>
        <border>
            <left style="thin"><color rgb="CCCCCC"/></left>
            <right style="thin"><color rgb="CCCCCC"/></right>
            <top style="thin"><color rgb="CCCCCC"/></top>
            <bottom style="thin"><color rgb="CCCCCC"/></bottom>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="7">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
        <xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
        <xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="2" xfId="0" applyBorder="1"/>
        <xf numFmtId="164" fontId="0" fillId="0" borderId="2" xfId="0" applyNumberFormat="1" applyBorder="1"/>
        <xf numFmtId="164" fontId="3" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyNumberFormat="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
    </cellXfs>
    <numFmts count="1">
        <numFmt numFmtId="164" formatCode="$#,##0.00"/>
    </numFmts>
</styleSheet>';
    $zip->addFromString('xl/styles.xml', $styles);

    // 8. Recopilar todas las cadenas únicas para sharedStrings
    $strings = [];
    $string_count = 0;

    function addString($text, &$strings, &$count)
    {
        $text = (string)$text;
        if (!isset($strings[$text])) {
            $strings[$text] = $count++;
        }
        return $strings[$text];
    }

    // Agregar todas las cadenas para las tres hojas
    addString('REPORTE COMPLETO DE INVENTARIO', $strings, $string_count);
    addString('ANÁLISIS POR CATEGORÍAS', $strings, $string_count);
    addString('ANÁLISIS POR LUGARES', $strings, $string_count);
    addString('DATOS PARA GRÁFICOS', $strings, $string_count);
    addString('Fecha: ' . date('d/m/Y H:i:s'), $strings, $string_count);
    addString('Sistema: ' . (defined('SISTEMA_NOMBRE') ? SISTEMA_NOMBRE : 'Sistema de Gestión'), $strings, $string_count);

    $encabezados_inv = ['ID', 'Código', 'Producto', 'Categoría', 'Lugar', 'Stock', 'Precio Compra', 'Precio Venta', 'Valor Total'];
    foreach ($encabezados_inv as $enc) {
        addString($enc, $strings, $string_count);
    }

    $encabezados_cat = ['Categoría', 'Productos', 'Stock Total', 'Valor Total'];
    foreach ($encabezados_cat as $enc) {
        addString($enc, $strings, $string_count);
    }

    $encabezados_lug = ['Lugar', 'Productos', 'Stock Total', 'Valor Total'];
    foreach ($encabezados_lug as $enc) {
        addString($enc, $strings, $string_count);
    }

    addString('TOTALES:', $strings, $string_count);
    addString('Total General', $strings, $string_count);

    // Agregar datos de productos
    foreach ($productos as $producto) {
        addString($producto['codigo'] ?? '', $strings, $string_count);
        addString($producto['nombre'] ?? '', $strings, $string_count);
        addString($producto['categoria'] ?? 'Sin categoría', $strings, $string_count);
        addString($producto['lugar'] ?? 'Sin lugar', $strings, $string_count);
    }

    // Agregar datos de categorías
    foreach ($analisis_categorias as $cat) {
        addString($cat['nombre'] ?? '', $strings, $string_count);
    }

    // Agregar datos de lugares
    foreach ($analisis_lugares as $lug) {
        addString($lug['nombre'] ?? '', $strings, $string_count);
    }

    // 9. xl/sharedStrings.xml
    $shared_strings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $string_count . '" uniqueCount="' . count($strings) . '">';

    foreach (array_keys($strings) as $string) {
        $shared_strings .= '<si><t>' . xmlEscape($string) . '</t></si>';
    }
    $shared_strings .= '</sst>';
    $zip->addFromString('xl/sharedStrings.xml', $shared_strings);

    // Función para obtener coordenada Excel (A1, B1, etc.)
    function getExcelCoord($row, $col)
    {
        $col_letter = chr(65 + $col); // A, B, C, etc.
        return $col_letter . ($row + 1);
    }

    // 10. HOJA 1: INVENTARIO (xl/worksheets/sheet1.xml)
    $worksheet1 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <cols>
        <col min="1" max="1" width="8" customWidth="1"/>
        <col min="2" max="2" width="15" customWidth="1"/>
        <col min="3" max="3" width="35" customWidth="1"/>
        <col min="4" max="4" width="20" customWidth="1"/>
        <col min="5" max="5" width="20" customWidth="1"/>
        <col min="6" max="6" width="10" customWidth="1"/>
        <col min="7" max="7" width="15" customWidth="1"/>
        <col min="8" max="8" width="15" customWidth="1"/>
        <col min="9" max="9" width="18" customWidth="1"/>
    </cols>
    <mergeCells count="3">
        <mergeCell ref="A1:I1"/>
        <mergeCell ref="A2:I2"/>
        <mergeCell ref="A3:I3"/>
    </mergeCells>
    <sheetData>';

    // TÍTULO (con estilo 1 - azul con negrita blanca)
    $worksheet1 .= '<row r="1" ht="25" customHeight="1">';
    $worksheet1 .= '<c r="A1" t="s" s="1"><v>' . $strings['REPORTE COMPLETO DE INVENTARIO'] . '</v></c>';
    $worksheet1 .= '</row>';

    $worksheet1 .= '<row r="2">';
    $worksheet1 .= '<c r="A2" t="s"><v>' . $strings['Fecha: ' . date('d/m/Y H:i:s')] . '</v></c>';
    $worksheet1 .= '</row>';

    $worksheet1 .= '<row r="3">';
    $worksheet1 .= '<c r="A3" t="s"><v>' . $strings['Sistema: ' . (defined('SISTEMA_NOMBRE') ? SISTEMA_NOMBRE : 'Sistema de Gestión')] . '</v></c>';
    $worksheet1 .= '</row>';

    // Fila vacía
    $worksheet1 .= '<row r="4"></row>';

    // ENCABEZADOS (fila 5 con estilo 2 - azul con negrita blanca)
    $worksheet1 .= '<row r="5" ht="20" customHeight="1">';
    foreach ($encabezados_inv as $col => $encabezado) {
        $coord = getExcelCoord(4, $col);
        $worksheet1 .= '<c r="' . $coord . '" t="s" s="2"><v>' . $strings[$encabezado] . '</v></c>';
    }
    $worksheet1 .= '</row>';

    // DATOS (con bordes y formato)
    $fila_num = 6;
    $total_general = 0;
    $total_stock = 0;

    foreach ($productos as $producto) {
        $valor_total = floatval($producto['precio_venta']) * intval($producto['stock']);
        $total_general += $valor_total;
        $total_stock += intval($producto['stock']);

        $worksheet1 .= '<row r="' . $fila_num . '">';

        // ID (numérico con bordes)
        $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 0) . '" s="4"><v>' . intval($producto['id']) . '</v></c>';

        // Código (string con bordes)
        $codigo_idx = $strings[$producto['codigo'] ?? ''];
        $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 1) . '" t="s" s="4"><v>' . $codigo_idx . '</v></c>';

        // Nombre (string con bordes)
        $nombre_idx = $strings[$producto['nombre'] ?? ''];
        $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 2) . '" t="s" s="4"><v>' . $nombre_idx . '</v></c>';

        // Categoría (string con bordes)
        $cat_idx = $strings[$producto['categoria'] ?? 'Sin categoría'];
        $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 3) . '" t="s" s="4"><v>' . $cat_idx . '</v></c>';

        // Lugar (string con bordes)
        $lug_idx = $strings[$producto['lugar'] ?? 'Sin lugar'];
        $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 4) . '" t="s" s="4"><v>' . $lug_idx . '</v></c>';

        // Stock (numérico con bordes)
        $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 5) . '" s="4"><v>' . intval($producto['stock']) . '</v></c>';

        // Precio Compra (numérico con formato moneda)
        $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 6) . '" s="5"><v>' . floatval($producto['precio_compra']) . '</v></c>';

        // Precio Venta (numérico con formato moneda)
        $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 7) . '" s="5"><v>' . floatval($producto['precio_venta']) . '</v></c>';

        // Valor Total (numérico con formato moneda)
        $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 8) . '" s="5"><v>' . $valor_total . '</v></c>';

        $worksheet1 .= '</row>';
        $fila_num++;
    }

    // TOTALES (con estilo 3 - amarillo con negrita negra)
    $total_productos_string = addString(count($productos) . ' productos', $strings, $string_count);
    $worksheet1 .= '<row r="' . $fila_num . '">';
    $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 0) . '" s="3"><v></v></c>';
    $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 1) . '" s="3"><v></v></c>';
    $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 2) . '" t="s" s="3"><v>' . $strings['TOTALES:'] . '</v></c>';
    $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 3) . '" t="s" s="3"><v>' . $total_productos_string . '</v></c>';
    $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 4) . '" s="3"><v></v></c>';
    $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 5) . '" s="3"><v>' . $total_stock . '</v></c>';
    $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 6) . '" s="3"><v></v></c>';
    $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 7) . '" t="s" s="3"><v>' . $strings['Total General'] . '</v></c>';
    $worksheet1 .= '<c r="' . getExcelCoord($fila_num - 1, 8) . '" s="6"><v>' . $total_general . '</v></c>';
    $worksheet1 .= '</row>';

    $worksheet1 .= '</sheetData></worksheet>';
    $zip->addFromString('xl/worksheets/sheet1.xml', $worksheet1);

    // 11. HOJA 2: CATEGORÍAS Y LUGARES (xl/worksheets/sheet2.xml)
    $worksheet2 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <cols>
        <col min="1" max="1" width="25" customWidth="1"/>
        <col min="2" max="2" width="12" customWidth="1"/>
        <col min="3" max="3" width="12" customWidth="1"/>
        <col min="4" max="4" width="18" customWidth="1"/>
    </cols>
    <sheetData>';

    $fila = 1;

    // TÍTULO ANÁLISIS POR CATEGORÍAS
    $worksheet2 .= '<row r="' . $fila . '" ht="25" customHeight="1">';
    $worksheet2 .= '<c r="A' . $fila . '" t="s" s="1"><v>' . $strings['ANÁLISIS POR CATEGORÍAS'] . '</v></c>';
    $worksheet2 .= '</row>';
    $fila++;

    // Fila vacía
    $worksheet2 .= '<row r="' . $fila . '"></row>';
    $fila++;

    // ENCABEZADOS DE CATEGORÍAS (con estilo 2 - azul)
    $worksheet2 .= '<row r="' . $fila . '">';
    foreach ($encabezados_cat as $col => $encabezado) {
        $coord = chr(65 + $col) . $fila;
        $worksheet2 .= '<c r="' . $coord . '" t="s" s="2"><v>' . $strings[$encabezado] . '</v></c>';
    }
    $worksheet2 .= '</row>';
    $fila++;

    // DATOS DE CATEGORÍAS
    $total_cat_productos = 0;
    $total_cat_stock = 0;
    $total_cat_valor = 0;

    foreach ($analisis_categorias as $cat) {
        $total_cat_productos += intval($cat['total_productos']);
        $total_cat_stock += intval($cat['stock_total']);
        $total_cat_valor += floatval($cat['valor_total']);

        $worksheet2 .= '<row r="' . $fila . '">';
        $worksheet2 .= '<c r="A' . $fila . '" t="s" s="4"><v>' . $strings[$cat['nombre'] ?? ''] . '</v></c>';
        $worksheet2 .= '<c r="B' . $fila . '" s="4"><v>' . intval($cat['total_productos']) . '</v></c>';
        $worksheet2 .= '<c r="C' . $fila . '" s="4"><v>' . intval($cat['stock_total']) . '</v></c>';
        $worksheet2 .= '<c r="D' . $fila . '" s="5"><v>' . floatval($cat['valor_total']) . '</v></c>';
        $worksheet2 .= '</row>';
        $fila++;
    }

    // TOTALES DE CATEGORÍAS (destacados)
    $worksheet2 .= '<row r="' . $fila . '">';
    $worksheet2 .= '<c r="A' . $fila . '" t="s" s="3"><v>' . $strings['TOTALES:'] . '</v></c>';
    $worksheet2 .= '<c r="B' . $fila . '" s="3"><v>' . $total_cat_productos . '</v></c>';
    $worksheet2 .= '<c r="C' . $fila . '" s="3"><v>' . $total_cat_stock . '</v></c>';
    $worksheet2 .= '<c r="D' . $fila . '" s="6"><v>' . $total_cat_valor . '</v></c>';
    $worksheet2 .= '</row>';
    $fila++;

    // Filas vacías entre las dos tablas
    $worksheet2 .= '<row r="' . $fila . '"></row>';
    $fila++;
    $worksheet2 .= '<row r="' . $fila . '"></row>';
    $fila++;

    // Guardar la fila del título de lugares para merge
    $fila_titulo_lugares = $fila;

    // TÍTULO ANÁLISIS POR LUGARES
    $worksheet2 .= '<row r="' . $fila . '" ht="25" customHeight="1">';
    $worksheet2 .= '<c r="A' . $fila . '" t="s" s="1"><v>' . $strings['ANÁLISIS POR LUGARES'] . '</v></c>';
    $worksheet2 .= '</row>';
    $fila++;

    // Fila vacía
    $worksheet2 .= '<row r="' . $fila . '"></row>';
    $fila++;

    // ENCABEZADOS DE LUGARES (con estilo 2 - azul)
    $worksheet2 .= '<row r="' . $fila . '">';
    foreach ($encabezados_lug as $col => $encabezado) {
        $coord = chr(65 + $col) . $fila;
        $worksheet2 .= '<c r="' . $coord . '" t="s" s="2"><v>' . $strings[$encabezado] . '</v></c>';
    }
    $worksheet2 .= '</row>';
    $fila++;

    // DATOS DE LUGARES
    $total_lug_productos = 0;
    $total_lug_stock = 0;
    $total_lug_valor = 0;

    foreach ($analisis_lugares as $lug) {
        $total_lug_productos += intval($lug['total_productos']);
        $total_lug_stock += intval($lug['stock_total']);
        $total_lug_valor += floatval($lug['valor_total']);

        $worksheet2 .= '<row r="' . $fila . '">';
        $worksheet2 .= '<c r="A' . $fila . '" t="s" s="4"><v>' . $strings[$lug['nombre'] ?? ''] . '</v></c>';
        $worksheet2 .= '<c r="B' . $fila . '" s="4"><v>' . intval($lug['total_productos']) . '</v></c>';
        $worksheet2 .= '<c r="C' . $fila . '" s="4"><v>' . intval($lug['stock_total']) . '</v></c>';
        $worksheet2 .= '<c r="D' . $fila . '" s="5"><v>' . floatval($lug['valor_total']) . '</v></c>';
        $worksheet2 .= '</row>';
        $fila++;
    }

    // TOTALES DE LUGARES (destacados)
    $worksheet2 .= '<row r="' . $fila . '">';
    $worksheet2 .= '<c r="A' . $fila . '" t="s" s="3"><v>' . $strings['TOTALES:'] . '</v></c>';
    $worksheet2 .= '<c r="B' . $fila . '" s="3"><v>' . $total_lug_productos . '</v></c>';
    $worksheet2 .= '<c r="C' . $fila . '" s="3"><v>' . $total_lug_stock . '</v></c>';
    $worksheet2 .= '<c r="D' . $fila . '" s="6"><v>' . $total_lug_valor . '</v></c>';
    $worksheet2 .= '</row>';

    // Agregar mergeCells al final para los títulos
    $worksheet2 .= '</sheetData>';
    $worksheet2 .= '<mergeCells count="2">';
    $worksheet2 .= '<mergeCell ref="A1:D1"/>';
    $worksheet2 .= '<mergeCell ref="A' . $fila_titulo_lugares . ':D' . $fila_titulo_lugares . '"/>';
    $worksheet2 .= '</mergeCells>';
    $worksheet2 .= '</worksheet>';
    $zip->addFromString('xl/worksheets/sheet2.xml', $worksheet2);

    // 12. HOJA 3: DATOS PARA GRÁFICOS (xl/worksheets/sheet3.xml)
    $worksheet3 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <cols>
        <col min="1" max="1" width="30" customWidth="1"/>
        <col min="2" max="2" width="20" customWidth="1"/>
        <col min="3" max="3" width="5" customWidth="1"/>
        <col min="4" max="4" width="30" customWidth="1"/>
        <col min="5" max="5" width="20" customWidth="1"/>
    </cols>
    <mergeCells count="2">
        <mergeCell ref="A1:B1"/>
        <mergeCell ref="D1:E1"/>
    </mergeCells>
    <sheetData>';

    $fila = 1;

    // TÍTULO PARA DATOS DE CATEGORÍAS
    $datos_categorias_string = addString('Datos para Gráfico de Categorías', $strings, $string_count);
    $datos_lugares_string = addString('Datos para Gráfico de Lugares', $strings, $string_count);

    $worksheet3 .= '<row r="' . $fila . '" ht="25" customHeight="1">';
    $worksheet3 .= '<c r="A' . $fila . '" t="s" s="1"><v>' . $datos_categorias_string . '</v></c>';
    $worksheet3 .= '<c r="D' . $fila . '" t="s" s="1"><v>' . $datos_lugares_string . '</v></c>';
    $worksheet3 .= '</row>';
    $fila++;

    // ENCABEZADOS
    $worksheet3 .= '<row r="' . $fila . '">';
    $worksheet3 .= '<c r="A' . $fila . '" t="s" s="2"><v>' . $strings['Categoría'] . '</v></c>';
    $worksheet3 .= '<c r="B' . $fila . '" t="s" s="2"><v>' . $strings['Valor Total'] . '</v></c>';
    $worksheet3 .= '<c r="D' . $fila . '" t="s" s="2"><v>' . $strings['Lugar'] . '</v></c>';
    $worksheet3 .= '<c r="E' . $fila . '" t="s" s="2"><v>' . $strings['Valor Total'] . '</v></c>';
    $worksheet3 .= '</row>';
    $fila++;

    // DATOS PARA GRÁFICOS (en paralelo)
    $max_filas = max(count($analisis_categorias), count($analisis_lugares));

    for ($i = 0; $i < $max_filas; $i++) {
        $worksheet3 .= '<row r="' . $fila . '">';

        // Datos de Categoría
        if ($i < count($analisis_categorias) && $analisis_categorias[$i]['valor_total'] > 0) {
            $worksheet3 .= '<c r="A' . $fila . '" t="s" s="4"><v>' . $strings[$analisis_categorias[$i]['nombre']] . '</v></c>';
            $worksheet3 .= '<c r="B' . $fila . '" s="5"><v>' . floatval($analisis_categorias[$i]['valor_total']) . '</v></c>';
        } else {
            $worksheet3 .= '<c r="A' . $fila . '" s="4"><v></v></c>';
            $worksheet3 .= '<c r="B' . $fila . '" s="5"><v></v></c>';
        }

        // Datos de Lugar
        if ($i < count($analisis_lugares) && $analisis_lugares[$i]['valor_total'] > 0) {
            $worksheet3 .= '<c r="D' . $fila . '" t="s" s="4"><v>' . $strings[$analisis_lugares[$i]['nombre']] . '</v></c>';
            $worksheet3 .= '<c r="E' . $fila . '" s="5"><v>' . floatval($analisis_lugares[$i]['valor_total']) . '</v></c>';
        } else {
            $worksheet3 .= '<c r="D' . $fila . '" s="4"><v></v></c>';
            $worksheet3 .= '<c r="E' . $fila . '" s="5"><v></v></c>';
        }

        $worksheet3 .= '</row>';
        $fila++;
    }

    // Nota sobre gráficos
    $fila += 2;
    $nota1_string = addString('Nota: Los datos están listos para crear gráficos en Excel o PlanMaker', $strings, $string_count);
    $nota2_string = addString('Seleccione los datos y use Insertar > Gráfico para crear visualizaciones', $strings, $string_count);

    $worksheet3 .= '<row r="' . $fila . '">';
    $worksheet3 .= '<c r="A' . $fila . '" t="s" s="4"><v>' . $nota1_string . '</v></c>';
    $worksheet3 .= '</row>';
    $fila++;
    $worksheet3 .= '<row r="' . $fila . '">';
    $worksheet3 .= '<c r="A' . $fila . '" t="s" s="4"><v>' . $nota2_string . '</v></c>';
    $worksheet3 .= '</row>';

    $worksheet3 .= '</sheetData></worksheet>';
    $zip->addFromString('xl/worksheets/sheet3.xml', $worksheet3);

    // Cerrar ZIP
    $zip->close();

    // Enviar archivo
    $filename = 'Reporte_Completo_Inventario_' . date('Y-m-d_H-i-s') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($temp_file));
    header('Cache-Control: max-age=0');

    readfile($temp_file);
    unlink($temp_file);

    exit;
} catch (Exception $e) {
    die('Error al generar Excel: ' . $e->getMessage());
}

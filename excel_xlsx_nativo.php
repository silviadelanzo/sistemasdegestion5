<?php
// Generador de Excel XLSX sin PhpSpreadsheet usando ZipArchive
// Compatible con PlanMaker, Excel, LibreOffice
// Genera formato XLSX real usando XML nativo

require_once 'config/config.php';

// Verificar si se quiere generar el Excel
if (isset($_GET['generar']) && $_GET['generar'] === 'si') {

    try {
        // Verificar ZipArchive
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive no está disponible en este servidor');
        }

        // Conectar a la base de datos
        $pdo = conectarDB();

        // Obtener datos
        $sql = "SELECT p.id, p.codigo, p.nombre, c.nombre as categoria, 
                       p.stock, p.precio_compra, p.precio_venta,
                       (p.stock * p.precio_venta) as total
                FROM productos p 
                LEFT JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.activo = 1 
                ORDER BY p.nombre
                LIMIT 3000";

        $stmt = $pdo->query($sql);
        $productos = $stmt->fetchAll();

        // Crear archivo temporal
        $temp_file = tempnam(sys_get_temp_dir(), 'excel_') . '.xlsx';
        $zip = new ZipArchive();

        if ($zip->open($temp_file, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('No se pudo crear el archivo Excel');
        }

        // Función para escapar XML
        function xmlEscape($string)
        {
            return htmlspecialchars($string, ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        // 1. [Content_Types].xml
        $content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
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
    <dc:title>Inventario Completo</dc:title>
    <dc:subject>Reporte de Inventario</dc:subject>
    <dc:creator>Sistema de Gestión</dc:creator>
    <cp:keywords>inventario productos</cp:keywords>
    <dc:description>Reporte completo de inventario compatible con PlanMaker</dc:description>
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
            <vt:variant><vt:i4>1</vt:i4></vt:variant>
        </vt:vector>
    </HeadingPairs>
    <TitlesOfParts>
        <vt:vector size="1" baseType="lpstr">
            <vt:lpstr>Inventario</vt:lpstr>
        </vt:vector>
    </TitlesOfParts>
</Properties>';
        $zip->addFromString('docProps/app.xml', $app);

        // 5. xl/_rels/workbook.xml.rels
        $wb_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wb_rels);

        // 6. xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Inventario" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // 7. xl/styles.xml (estilos completos con formato)
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
    <cellXfs count="6">
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

        // Agregar todas las cadenas
        addString('REPORTE DE INVENTARIO COMPLETO', $strings, $string_count);
        addString('Fecha: ' . date('d/m/Y H:i:s') . ' | PHP: ' . PHP_VERSION, $strings, $string_count);
        addString('Sistema: ' . (defined('SISTEMA_NOMBRE') ? SISTEMA_NOMBRE : 'Sistema de Gestión'), $strings, $string_count);

        $encabezados = ['ID', 'Código', 'Producto', 'Categoría', 'Stock', 'Precio Compra', 'Precio Venta', 'Valor Total'];
        foreach ($encabezados as $enc) {
            addString($enc, $strings, $string_count);
        }

        foreach ($productos as $producto) {
            addString($producto['codigo'] ?? '', $strings, $string_count);
            addString($producto['nombre'] ?? '', $strings, $string_count);
            addString($producto['categoria'] ?? 'Sin categoría', $strings, $string_count);
        }

        addString('TOTALES:', $strings, $string_count);
        addString(count($productos) . ' productos', $strings, $string_count);
        addString('TOTAL GENERAL', $strings, $string_count);

        // 9. xl/sharedStrings.xml
        $shared_strings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $string_count . '" uniqueCount="' . count($strings) . '">';

        foreach (array_keys($strings) as $string) {
            $shared_strings .= '<si><t>' . xmlEscape($string) . '</t></si>';
        }
        $shared_strings .= '</sst>';
        $zip->addFromString('xl/sharedStrings.xml', $shared_strings);

        // 10. xl/worksheets/sheet1.xml (hoja de cálculo con formato)
        $worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <cols>
        <col min="1" max="1" width="8" customWidth="1"/>
        <col min="2" max="2" width="15" customWidth="1"/>
        <col min="3" max="3" width="35" customWidth="1"/>
        <col min="4" max="4" width="20" customWidth="1"/>
        <col min="5" max="5" width="10" customWidth="1"/>
        <col min="6" max="6" width="15" customWidth="1"/>
        <col min="7" max="7" width="15" customWidth="1"/>
        <col min="8" max="8" width="18" customWidth="1"/>
    </cols>
    <mergeCells count="3">
        <mergeCell ref="A1:H1"/>
        <mergeCell ref="A2:H2"/>
        <mergeCell ref="A3:H3"/>
    </mergeCells>
    <sheetData>';

        // Función para obtener coordenada Excel (A1, B1, etc.)
        function getExcelCoord($row, $col)
        {
            $col_letter = chr(65 + $col); // A, B, C, etc.
            return $col_letter . ($row + 1);
        }

        // TÍTULO (con estilo 1 - azul con negrita blanca)
        $worksheet .= '<row r="1" ht="25" customHeight="1">';
        $worksheet .= '<c r="A1" t="s" s="1"><v>' . $strings['REPORTE DE INVENTARIO COMPLETO'] . '</v></c>';
        $worksheet .= '</row>';

        $worksheet .= '<row r="2">';
        $worksheet .= '<c r="A2" t="s"><v>' . $strings['Fecha: ' . date('d/m/Y H:i:s') . ' | PHP: ' . PHP_VERSION] . '</v></c>';
        $worksheet .= '</row>';

        $worksheet .= '<row r="3">';
        $worksheet .= '<c r="A3" t="s"><v>' . $strings['Sistema: ' . (defined('SISTEMA_NOMBRE') ? SISTEMA_NOMBRE : 'Sistema de Gestión')] . '</v></c>';
        $worksheet .= '</row>';

        // Fila vacía
        $worksheet .= '<row r="4"></row>';

        // ENCABEZADOS (fila 5 con estilo 2 - verde con negrita blanca)
        $worksheet .= '<row r="5" ht="20" customHeight="1">';
        foreach ($encabezados as $col => $encabezado) {
            $coord = getExcelCoord(4, $col);
            $worksheet .= '<c r="' . $coord . '" t="s" s="2"><v>' . $strings[$encabezado] . '</v></c>';
        }
        $worksheet .= '</row>';        // DATOS (con bordes y formato)
        $fila_num = 6;
        $total_general = 0;
        $total_stock = 0;

        foreach ($productos as $producto) {
            $valor_total = floatval($producto['precio_venta']) * intval($producto['stock']);
            $total_general += $valor_total;
            $total_stock += intval($producto['stock']);

            $worksheet .= '<row r="' . $fila_num . '">';

            // ID (numérico con bordes)
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 0) . '" s="4"><v>' . intval($producto['id']) . '</v></c>';

            // Código (string con bordes)
            $codigo_idx = $strings[$producto['codigo'] ?? ''];
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 1) . '" t="s" s="4"><v>' . $codigo_idx . '</v></c>';

            // Nombre (string con bordes)
            $nombre_idx = $strings[$producto['nombre'] ?? ''];
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 2) . '" t="s" s="4"><v>' . $nombre_idx . '</v></c>';

            // Categoría (string con bordes)
            $cat_idx = $strings[$producto['categoria'] ?? 'Sin categoría'];
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 3) . '" t="s" s="4"><v>' . $cat_idx . '</v></c>';

            // Stock (numérico con bordes)
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 4) . '" s="4"><v>' . intval($producto['stock']) . '</v></c>';

            // Precio Compra (numérico con formato moneda)
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 5) . '" s="5"><v>' . floatval($producto['precio_compra']) . '</v></c>';

            // Precio Venta (numérico con formato moneda)
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 6) . '" s="5"><v>' . floatval($producto['precio_venta']) . '</v></c>';

            // Valor Total (numérico con formato moneda)
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 7) . '" s="5"><v>' . $valor_total . '</v></c>';

            $worksheet .= '</row>';
            $fila_num++;
        }

        // TOTALES (con estilo 3 - amarillo con negrita negra)
        $worksheet .= '<row r="' . $fila_num . '">';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 0) . '" s="3"><v></v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 1) . '" s="3"><v></v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 2) . '" t="s" s="3"><v>' . $strings['TOTALES:'] . '</v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 3) . '" t="s" s="3"><v>' . $strings[count($productos) . ' productos'] . '</v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 4) . '" s="3"><v>' . $total_stock . '</v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 5) . '" s="3"><v></v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 6) . '" t="s" s="3"><v>' . $strings['TOTAL GENERAL'] . '</v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 7) . '" s="5"><v>' . $total_general . '</v></c>';
        $worksheet .= '</row>';

        $worksheet .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $worksheet);

        // Cerrar ZIP
        $zip->close();

        // Enviar archivo
        $filename = 'inventario_xlsx_nativo_' . date('Y-m-d_H-i-s') . '.xlsx';

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
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel XLSX Nativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-file-earmark-excel me-2"></i>
                            Excel XLSX Nativo (Sin PhpSpreadsheet)
                        </h4>
                    </div>
                    <div class="card-body">

                        <div class="alert alert-success">
                            <h5><i class="bi bi-check-circle me-2"></i>¡Solución Perfecta para PlanMaker!</h5>
                            <p>Este generador crea archivos <strong>XLSX reales</strong> sin PhpSpreadsheet:</p>
                            <ul class="mb-0">
                                <li>✅ <strong>Formato XLSX oficial</strong> (OpenXML)</li>
                                <li>✅ <strong>100% compatible con PlanMaker</strong></li>
                                <li>✅ <strong>Sin dependencias de Composer</strong></li>
                                <li>✅ <strong>Funciona con cualquier versión PHP</strong></li>
                            </ul>
                        </div>

                        <?php
                        $compatible = true;
                        $errores = [];

                        // Verificar ZipArchive
                        if (!class_exists('ZipArchive')) {
                            $compatible = false;
                            $errores[] = 'Extensión ZipArchive no disponible';
                        }

                        // Verificar base de datos
                        try {
                            $pdo = conectarDB();
                            $sql = "SELECT COUNT(*) FROM productos WHERE activo = 1";
                            $stmt = $pdo->query($sql);
                            $total_productos = $stmt->fetchColumn();
                        } catch (Exception $e) {
                            $compatible = false;
                            $errores[] = 'Error de base de datos: ' . $e->getMessage();
                            $total_productos = 0;
                        }
                        ?>

                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle me-2"></i>Estado del Sistema:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>PHP:</strong> <?php echo PHP_VERSION; ?></p>
                                    <p><strong>ZipArchive:</strong> <?php echo class_exists('ZipArchive') ? '✅ Disponible' : '❌ No disponible'; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Productos:</strong> <?php echo number_format($total_productos); ?></p>
                                    <p><strong>Estado BD:</strong> <?php echo $total_productos > 0 ? '✅ OK' : '❌ Error'; ?></p>
                                </div>
                            </div>
                        </div>

                        <?php if ($compatible): ?>
                            <div class="alert alert-primary">
                                <h6><i class="bi bi-lightbulb me-2"></i>¿Cómo funciona?</h6>
                                <p>Genera el formato XLSX creando manualmente todos los archivos XML que componen un archivo Excel moderno. Es el mismo formato que usa Excel 2007+ y es totalmente compatible con PlanMaker.</p>
                            </div>

                            <div class="text-center">
                                <a href="?generar=si" class="btn btn-success btn-lg">
                                    <i class="bi bi-download me-2"></i>
                                    Generar Excel XLSX Nativo
                                </a>
                                <p class="text-muted mt-2">
                                    <small>Formato OpenXML compatible con PlanMaker, Excel, LibreOffice</small>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h6><i class="bi bi-exclamation-triangle me-2"></i>Problemas detectados:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($errores as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <h6>Alternativas:</h6>
                                <div class="d-grid gap-2">
                                    <a href="modulos/Inventario/reporte_inventario_csv.php" class="btn btn-info">
                                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                                        Reporte CSV
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Sistema:</h6>
                                <div class="d-grid gap-2">
                                    <a href="test_conexion.php" class="btn btn-outline-primary">
                                        <i class="bi bi-database me-2"></i>
                                        Test Conexión BD
                                    </a>
                                    <a href="modulos/Inventario/productos.php" class="btn btn-outline-success">
                                        <i class="bi bi-box-seam me-2"></i>
                                        Gestionar Productos
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
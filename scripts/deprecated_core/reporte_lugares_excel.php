<?php
// Generador de Excel para Lugares con totales
// Compatible con PlanMaker, Excel, LibreOffice

require_once '../../config/config.php';

if (isset($_GET['generar']) && $_GET['generar'] === 'lugares') {

    try {
        // Verificar ZipArchive
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive no está disponible en este servidor');
        }

        // Conectar a la base de datos
        $pdo = conectarDB();

        // Obtener lugares con totales
        $sql = "SELECT l.id, l.nombre, l.descripcion, l.fecha_creacion,
                       COUNT(p.id) as total_productos,
                       SUM(CASE WHEN p.activo = 1 THEN p.stock ELSE 0 END) as total_stock,
                       SUM(CASE WHEN p.activo = 1 THEN (p.stock * p.precio_venta) ELSE 0 END) as valor_total,
                       SUM(CASE WHEN p.activo = 1 THEN (p.stock * p.precio_compra) ELSE 0 END) as costo_total,
                       COUNT(CASE WHEN p.activo = 1 AND p.stock <= p.stock_minimo THEN 1 END) as productos_bajo_stock,
                       AVG(CASE WHEN p.activo = 1 THEN p.precio_venta ELSE NULL END) as precio_promedio,
                       COUNT(DISTINCT p.categoria_id) as categorias_distintas
                FROM lugares l 
                LEFT JOIN productos p ON l.id = p.lugar_id
                WHERE l.activo = 1
                GROUP BY l.id, l.nombre, l.descripcion, l.fecha_creacion
                ORDER BY valor_total DESC, l.nombre";

        $lugares = $pdo->query($sql)->fetchAll();

        // Resumen general
        $sql_resumen = "SELECT 
                            COUNT(DISTINCT l.id) as total_lugares,
                            COUNT(p.id) as total_productos,
                            SUM(CASE WHEN p.activo = 1 THEN p.stock ELSE 0 END) as total_stock,
                            SUM(CASE WHEN p.activo = 1 THEN (p.stock * p.precio_venta) ELSE 0 END) as valor_total
                        FROM lugares l 
                        LEFT JOIN productos p ON l.id = p.lugar_id
                        WHERE l.activo = 1";
        $resumen = $pdo->query($sql_resumen)->fetch();

        // Crear archivo temporal
        $temp_file = tempnam(sys_get_temp_dir(), 'excel_lugares_') . '.xlsx';
        $zip = new ZipArchive();

        if ($zip->open($temp_file, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('No se pudo crear el archivo Excel');
        }

        // Función para escapar XML
        function xmlEscape($string)
        {
            return htmlspecialchars($string, ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        // Estructura básica del Excel (archivos XML necesarios)
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

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <dc:title>Reporte de Lugares</dc:title>
    <dc:subject>Análisis completo de ubicaciones</dc:subject>
    <dc:creator>Sistema de Gestión</dc:creator>
    <dcterms:created xsi:type="dcterms:W3CDTF">' . date('c') . '</dcterms:created>
    <dcterms:modified xsi:type="dcterms:W3CDTF">' . date('c') . '</dcterms:modified>
</cp:coreProperties>';
        $zip->addFromString('docProps/core.xml', $core);

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
            <vt:lpstr>Lugares</vt:lpstr>
        </vt:vector>
    </TitlesOfParts>
</Properties>';
        $zip->addFromString('docProps/app.xml', $app);

        $wb_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wb_rels);

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Lugares" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // Estilos para lugares (tema naranja/amarillo)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="4">
        <font><sz val="11"/><name val="Calibri"/></font>
        <font><b/><sz val="18"/><name val="Arial"/><color rgb="FFFFFF"/></font>
        <font><b/><sz val="12"/><name val="Arial"/><color rgb="FFFFFF"/></font>
        <font><b/><sz val="12"/><name val="Arial"/><color rgb="000000"/></font>
    </fonts>
    <fills count="5">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF6B35"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF8C42"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFC107"/></fill>
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

        // Recopilar strings
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

        // Strings del reporte
        addString('REPORTE DE LUGARES / UBICACIONES', $strings, $string_count);
        addString('Fecha: ' . date('d/m/Y H:i:s'), $strings, $string_count);
        addString('Total Lugares: ' . number_format($resumen['total_lugares']), $strings, $string_count);
        addString('Valor Total: $' . number_format($resumen['valor_total'], 2), $strings, $string_count);

        $encabezados = ['ID', 'Nombre', 'Descripción', 'Productos', 'Stock Total', 'Valor Total', 'Costo Total', 'Precio Promedio', 'Bajo Stock', 'Categorías', 'Fecha Creación'];
        foreach ($encabezados as $enc) {
            addString($enc, $strings, $string_count);
        }

        foreach ($lugares as $lugar) {
            addString($lugar['nombre'] ?? '', $strings, $string_count);
            addString($lugar['descripcion'] ?? '', $strings, $string_count);
            addString(date('d/m/Y', strtotime($lugar['fecha_creacion'])), $strings, $string_count);
        }

        addString('TOTALES', $strings, $string_count);

        // SharedStrings.xml
        $shared_strings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $string_count . '" uniqueCount="' . count($strings) . '">';

        foreach (array_keys($strings) as $string) {
            $shared_strings .= '<si><t>' . xmlEscape($string) . '</t></si>';
        }
        $shared_strings .= '</sst>';
        $zip->addFromString('xl/sharedStrings.xml', $shared_strings);

        // Hoja de trabajo con datos
        function getExcelCoord($row, $col)
        {
            $col_letter = chr(65 + ($col % 26));
            if ($col >= 26) {
                $col_letter = chr(64 + intval($col / 26)) . $col_letter;
            }
            return $col_letter . ($row + 1);
        }

        $worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <cols>
        <col min="1" max="1" width="8" customWidth="1"/>
        <col min="2" max="2" width="25" customWidth="1"/>
        <col min="3" max="3" width="35" customWidth="1"/>
        <col min="4" max="4" width="12" customWidth="1"/>
        <col min="5" max="5" width="12" customWidth="1"/>
        <col min="6" max="6" width="15" customWidth="1"/>
        <col min="7" max="7" width="15" customWidth="1"/>
        <col min="8" max="8" width="15" customWidth="1"/>
        <col min="9" max="9" width="12" customWidth="1"/>
        <col min="10" max="10" width="12" customWidth="1"/>
        <col min="11" max="11" width="15" customWidth="1"/>
    </cols>
    <mergeCells count="2">
        <mergeCell ref="A1:K1"/>
        <mergeCell ref="A2:K2"/>
    </mergeCells>
    <sheetData>';

        // Título
        $worksheet .= '<row r="1" ht="25" customHeight="1">';
        $worksheet .= '<c r="A1" t="s" s="1"><v>' . $strings['REPORTE DE LUGARES / UBICACIONES'] . '</v></c>';
        $worksheet .= '</row>';

        $worksheet .= '<row r="2">';
        $worksheet .= '<c r="A2" t="s"><v>' . $strings['Fecha: ' . date('d/m/Y H:i:s')] . '</v></c>';
        $worksheet .= '</row>';

        $worksheet .= '<row r="3">';
        $worksheet .= '<c r="A3" t="s"><v>' . $strings['Total Lugares: ' . number_format($resumen['total_lugares'])] . '</v></c>';
        $worksheet .= '<c r="F3" t="s"><v>' . $strings['Valor Total: $' . number_format($resumen['valor_total'], 2)] . '</v></c>';
        $worksheet .= '</row>';

        $worksheet .= '<row r="4"></row>';

        // Encabezados
        $worksheet .= '<row r="5" ht="20" customHeight="1">';
        foreach ($encabezados as $col => $encabezado) {
            $coord = getExcelCoord(4, $col);
            $worksheet .= '<c r="' . $coord . '" t="s" s="2"><v>' . $strings[$encabezado] . '</v></c>';
        }
        $worksheet .= '</row>';

        // Datos
        $fila_num = 6;
        $total_productos = 0;
        $total_stock = 0;
        $total_valor = 0;
        $total_costo = 0;
        $total_bajo_stock = 0;
        $total_categorias = 0;

        foreach ($lugares as $lugar) {
            $total_productos += intval($lugar['total_productos']);
            $total_stock += intval($lugar['total_stock']);
            $total_valor += floatval($lugar['valor_total']);
            $total_costo += floatval($lugar['costo_total']);
            $total_bajo_stock += intval($lugar['productos_bajo_stock']);
            $total_categorias += intval($lugar['categorias_distintas']);

            $worksheet .= '<row r="' . $fila_num . '">';

            // ID
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 0) . '" s="4"><v>' . intval($lugar['id']) . '</v></c>';

            // Nombre
            $nombre_idx = $strings[$lugar['nombre'] ?? ''];
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 1) . '" t="s" s="4"><v>' . $nombre_idx . '</v></c>';

            // Descripción
            $desc_idx = $strings[$lugar['descripcion'] ?? ''];
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 2) . '" t="s" s="4"><v>' . $desc_idx . '</v></c>';

            // Total Productos
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 3) . '" s="4"><v>' . intval($lugar['total_productos']) . '</v></c>';

            // Stock Total
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 4) . '" s="4"><v>' . intval($lugar['total_stock']) . '</v></c>';

            // Valor Total
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 5) . '" s="5"><v>' . floatval($lugar['valor_total']) . '</v></c>';

            // Costo Total
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 6) . '" s="5"><v>' . floatval($lugar['costo_total']) . '</v></c>';

            // Precio Promedio
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 7) . '" s="5"><v>' . floatval($lugar['precio_promedio']) . '</v></c>';

            // Bajo Stock
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 8) . '" s="4"><v>' . intval($lugar['productos_bajo_stock']) . '</v></c>';

            // Categorías distintas
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 9) . '" s="4"><v>' . intval($lugar['categorias_distintas']) . '</v></c>';

            // Fecha
            $fecha_idx = $strings[date('d/m/Y', strtotime($lugar['fecha_creacion']))];
            $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 10) . '" t="s" s="4"><v>' . $fecha_idx . '</v></c>';

            $worksheet .= '</row>';
            $fila_num++;
        }

        // Totales
        $worksheet .= '<row r="' . $fila_num . '">';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 0) . '" s="3"><v></v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 1) . '" t="s" s="3"><v>' . $strings['TOTALES'] . '</v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 2) . '" s="3"><v></v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 3) . '" s="3"><v>' . $total_productos . '</v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 4) . '" s="3"><v>' . $total_stock . '</v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 5) . '" s="5"><v>' . $total_valor . '</v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 6) . '" s="5"><v>' . $total_costo . '</v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 7) . '" s="3"><v></v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 8) . '" s="3"><v>' . $total_bajo_stock . '</v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 9) . '" s="3"><v>' . $total_categorias . '</v></c>';
        $worksheet .= '<c r="' . getExcelCoord($fila_num - 1, 10) . '" s="3"><v></v></c>';
        $worksheet .= '</row>';

        $worksheet .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $worksheet);

        // Cerrar ZIP
        $zip->close();

        // Enviar archivo
        $filename = 'lugares_' . date('Y-m-d_H-i-s') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($temp_file));
        header('Cache-Control: max-age=0');

        readfile($temp_file);
        unlink($temp_file);

        exit;
    } catch (Exception $e) {
        die('Error al generar Excel de lugares: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Lugares Excel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">
                            <i class="bi bi-geo-alt me-2"></i>
                            Reporte de Lugares / Ubicaciones
                        </h4>
                    </div>
                    <div class="card-body">

                        <div class="alert alert-warning">
                            <h5><i class="bi bi-geo-alt-fill me-2"></i>Reporte de Ubicaciones con Análisis Detallado</h5>
                            <p>Este Excel incluye:</p>
                            <ul class="mb-0">
                                <li>📍 <strong>Análisis por ubicación</strong> (productos, stock, valores por lugar)</li>
                                <li>💰 <strong>Análisis financiero</strong> (valor total, costos, precios promedio)</li>
                                <li>📊 <strong>Diversidad de productos</strong> (categorías distintas por lugar)</li>
                                <li>⚠️ <strong>Productos con bajo stock</strong> por ubicación</li>
                                <li>📅 <strong>Fechas de creación</strong> y estadísticas</li>
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
                            $sql = "SELECT COUNT(*) FROM lugares WHERE activo = 1";
                            $stmt = $pdo->query($sql);
                            $total_lugares = $stmt->fetchColumn();

                            $sql_productos = "SELECT COUNT(*) FROM productos p 
                                            JOIN lugares l ON p.lugar_id = l.id 
                                            WHERE p.activo = 1 AND l.activo = 1";
                            $stmt_productos = $pdo->query($sql_productos);
                            $productos_ubicados = $stmt_productos->fetchColumn();
                        } catch (Exception $e) {
                            $compatible = false;
                            $errores[] = 'Error de base de datos: ' . $e->getMessage();
                            $total_lugares = 0;
                            $productos_ubicados = 0;
                        }
                        ?>

                        <div class="alert alert-success">
                            <h6><i class="bi bi-database me-2"></i>Datos Disponibles:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>📍 Lugares activos:</strong> <?php echo number_format($total_lugares); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>📦 Productos ubicados:</strong> <?php echo number_format($productos_ubicados); ?></p>
                                </div>
                            </div>
                        </div>

                        <?php if ($compatible): ?>
                            <div class="text-center">
                                <a href="?generar=lugares" class="btn btn-warning btn-lg">
                                    <i class="bi bi-download me-2"></i>
                                    Generar Reporte de Lugares
                                </a>
                                <p class="text-muted mt-2">
                                    <small>Excel XLSX con análisis completo de ubicaciones</small>
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
                                <h6>Otros Reportes:</h6>
                                <div class="d-grid gap-2">
                                    <a href="reporte_completo_excel.php" class="btn btn-outline-primary">
                                        <i class="bi bi-file-earmark-excel me-2"></i>
                                        Reporte Completo
                                    </a>
                                    <a href="reporte_categorias_excel.php" class="btn btn-outline-info">
                                        <i class="bi bi-tags me-2"></i>
                                        Solo Categorías
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Navegación:</h6>
                                <div class="d-grid gap-2">
                                    <a href="productos.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>
                                        Volver a Productos
                                    </a>
                                    <a href="../admin/lugares_admin.php" class="btn btn-outline-success">
                                        <i class="bi bi-gear me-2"></i>
                                        Gestionar Lugares
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
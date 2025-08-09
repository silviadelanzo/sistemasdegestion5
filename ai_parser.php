<?php
// modulos/compras/ocr_remitos/ai_parser.php

class AIParser
{
    private $patterns;

    public function __construct()
    {
        $this->patterns = $this->loadCommonPatterns();
    }

    public function parseRemito($text, $template)
    {
        try {
            // Detectar estructura del remito
            $structure = $this->detectStructure($text, $template);

            // Extraer información del encabezado
            $header_info = $this->extractHeader($text, $template);

            // Extraer productos/items
            $productos = $this->extractProducts($text, $template, $structure);

            // Extraer totales
            $totales = $this->extractTotals($text, $template);

            return [
                'header' => $header_info,
                'productos' => $productos,
                'totales' => $totales,
                'confidence' => $this->calculateConfidence($productos),
                'raw_text' => $text
            ];
        } catch (Exception $e) {
            throw new Exception("Error parseando remito: " . $e->getMessage());
        }
    }

    private function detectStructure($text, $template)
    {
        $lines = explode("\n", $text);

        // Detectar tipo de estructura
        if ($this->isTableFormat($text)) {
            return $this->parseTableStructure($lines, $template);
        } elseif ($this->isLineFormat($text)) {
            return $this->parseLineStructure($lines, $template);
        } else {
            return $this->parseGenericStructure($lines, $template);
        }
    }

    private function isTableFormat($text)
    {
        // Detectar si es formato tabla (columnas alineadas)
        $patterns = [
            '/CÓDIGO.*DESCRIPCIÓN.*CANT.*PRECIO/i',
            '/COD.*DESC.*QTY.*PRICE/i',
            '/ITEM.*PRODUCT.*AMOUNT.*COST/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    private function isLineFormat($text)
    {
        // Detectar formato línea por línea
        return preg_match_all('/\n\s*[-*]\s*/', $text) > 2;
    }

    private function parseTableStructure($lines, $template)
    {
        $structure = [
            'type' => 'table',
            'header_line' => -1,
            'data_start' => -1,
            'data_end' => -1,
            'columns' => []
        ];

        // Buscar línea de encabezado de tabla
        foreach ($lines as $i => $line) {
            if (preg_match('/CÓDIGO.*DESCRIPCIÓN.*CANT/i', $line)) {
                $structure['header_line'] = $i;
                $structure['data_start'] = $i + 1;

                // Detectar posiciones de columnas
                $structure['columns'] = $this->detectColumns($line);
                break;
            }
        }

        // Buscar fin de datos (línea de total o línea vacía)
        for ($i = $structure['data_start']; $i < count($lines); $i++) {
            if (preg_match('/TOTAL|SUBTOTAL|^\s*$/i', $lines[$i])) {
                $structure['data_end'] = $i - 1;
                break;
            }
        }

        if ($structure['data_end'] == -1) {
            $structure['data_end'] = count($lines) - 1;
        }

        return $structure;
    }

    private function detectColumns($header_line)
    {
        $columns = [];

        // Detectar posiciones de palabras clave
        $keywords = [
            'codigo' => '/CÓDIGO|COD|ITEM/i',
            'descripcion' => '/DESCRIPCIÓN|DESC|PRODUCT/i',
            'cantidad' => '/CANT|QTY|AMOUNT/i',
            'precio' => '/PRECIO|PRICE|COST|IMPORTE/i'
        ];

        foreach ($keywords as $field => $pattern) {
            if (preg_match($pattern, $header_line, $matches, PREG_OFFSET_CAPTURE)) {
                $columns[$field] = [
                    'start' => $matches[0][1],
                    'name' => $matches[0][0]
                ];
            }
        }

        return $columns;
    }

    private function extractHeader($text, $template)
    {
        $header = [];

        // Extraer número de remito
        if (preg_match('/REMITO.*N[ºO°]?\s*:?\s*(\w+)/i', $text, $matches)) {
            $header['numero_remito'] = trim($matches[1]);
        }

        // Extraer fecha
        if (preg_match('/FECHA\s*:?\s*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i', $text, $matches)) {
            $header['fecha'] = $this->normalizeDate($matches[1]);
        }

        // Extraer proveedor
        if (preg_match('/PROVEEDOR\s*:?\s*([^\n\r]+)/i', $text, $matches)) {
            $header['proveedor'] = trim($matches[1]);
        }

        return $header;
    }

    private function extractProducts($text, $template, $structure)
    {
        $productos = [];

        if ($structure['type'] === 'table') {
            $productos = $this->extractTableProducts($text, $structure);
        } else {
            $productos = $this->extractGenericProducts($text, $template);
        }

        // Validar y limpiar productos
        return array_filter($productos, [$this, 'isValidProduct']);
    }

    private function extractTableProducts($text, $structure)
    {
        $lines = explode("\n", $text);
        $productos = [];

        for ($i = $structure['data_start']; $i <= $structure['data_end']; $i++) {
            if (!isset($lines[$i]) || trim($lines[$i]) === '') {
                continue;
            }

            $line = $lines[$i];
            $producto = $this->parseTableLine($line, $structure['columns']);

            if ($producto) {
                $productos[] = $producto;
            }
        }

        return $productos;
    }

    private function parseTableLine($line, $columns)
    {
        $producto = [];

        // Método 1: Por posiciones fijas
        if (!empty($columns)) {
            $sorted_columns = $columns;
            ksort($sorted_columns);

            $pos = 0;
            foreach ($sorted_columns as $field => $col_info) {
                $start = $col_info['start'];
                $next_pos = $this->getNextColumnPosition($sorted_columns, $start);
                $length = $next_pos ? $next_pos - $start : strlen($line) - $start;

                $value = substr($line, $start, $length);
                $producto[$field] = trim($value);
            }
        } else {
            // Método 2: Por expresiones regulares
            $producto = $this->parseLineWithRegex($line);
        }

        return $this->validateAndCleanProduct($producto);
    }

    private function parseLineWithRegex($line)
    {
        $producto = [];

        // Patrón general: CODIGO DESCRIPCION CANTIDAD PRECIO
        $pattern = '/(\w+)\s+(.+?)\s+(\d+)\s+\$?(\d+[.,]\d{2})/';

        if (preg_match($pattern, $line, $matches)) {
            $producto['codigo'] = trim($matches[1]);
            $producto['descripcion'] = trim($matches[2]);
            $producto['cantidad'] = intval($matches[3]);
            $producto['precio'] = floatval(str_replace(',', '.', $matches[4]));
        }

        return $producto;
    }

    private function extractGenericProducts($text, $template)
    {
        $productos = [];
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            // Buscar líneas que parezcan productos
            if ($this->looksLikeProduct($line)) {
                $producto = $this->parseLineWithRegex($line);
                if ($producto) {
                    $productos[] = $producto;
                }
            }
        }

        return $productos;
    }

    private function looksLikeProduct($line)
    {
        // Criterios para identificar línea de producto
        $criteria = [
            preg_match('/\b[A-Z0-9]{3,12}\b/', $line), // Tiene código
            preg_match('/\d+/', $line), // Tiene números
            preg_match('/\$\d+[.,]\d{2}/', $line), // Tiene precio
            strlen(trim($line)) > 10 // Longitud mínima
        ];

        return array_sum($criteria) >= 2;
    }

    private function validateAndCleanProduct($producto)
    {
        if (empty($producto) || !isset($producto['codigo']) || !isset($producto['descripcion'])) {
            return null;
        }

        // Limpiar y validar campos
        $producto['codigo'] = $this->cleanCode($producto['codigo']);
        $producto['descripcion'] = $this->cleanDescription($producto['descripcion']);

        if (isset($producto['cantidad'])) {
            $producto['cantidad'] = max(1, intval($producto['cantidad']));
        }

        if (isset($producto['precio'])) {
            $producto['precio'] = max(0, floatval(str_replace(',', '.', $producto['precio'])));
        }

        return $producto;
    }

    private function cleanCode($code)
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($code)));
    }

    private function cleanDescription($description)
    {
        return preg_replace('/\s+/', ' ', trim($description));
    }

    private function isValidProduct($producto)
    {
        return !empty($producto['codigo']) &&
            !empty($producto['descripcion']) &&
            strlen($producto['codigo']) >= 3 &&
            strlen($producto['descripcion']) >= 5;
    }

    private function extractTotals($text, $template)
    {
        $totales = [];

        // Buscar total general
        if (preg_match('/TOTAL\s*:?\s*\$?(\d+[.,]\d{2})/i', $text, $matches)) {
            $totales['total'] = floatval(str_replace(',', '.', $matches[1]));
        }

        // Buscar subtotal
        if (preg_match('/SUBTOTAL\s*:?\s*\$?(\d+[.,]\d{2})/i', $text, $matches)) {
            $totales['subtotal'] = floatval(str_replace(',', '.', $matches[1]));
        }

        // Buscar IVA
        if (preg_match('/IVA\s*:?\s*\$?(\d+[.,]\d{2})/i', $text, $matches)) {
            $totales['iva'] = floatval(str_replace(',', '.', $matches[1]));
        }

        return $totales;
    }

    private function calculateConfidence($productos)
    {
        if (empty($productos)) {
            return 0;
        }

        $total_score = 0;
        $total_products = count($productos);

        foreach ($productos as $producto) {
            $score = 0;

            // Puntuación por campos completos
            if (!empty($producto['codigo'])) $score += 25;
            if (!empty($producto['descripcion'])) $score += 25;
            if (isset($producto['cantidad']) && $producto['cantidad'] > 0) $score += 25;
            if (isset($producto['precio']) && $producto['precio'] > 0) $score += 25;

            $total_score += $score;
        }

        return $total_score / $total_products;
    }

    private function normalizeDate($date_string)
    {
        // Convertir fecha a formato estándar Y-m-d
        $date = DateTime::createFromFormat('d/m/Y', $date_string);
        if (!$date) {
            $date = DateTime::createFromFormat('d-m-Y', $date_string);
        }
        if (!$date) {
            $date = DateTime::createFromFormat('d/m/y', $date_string);
        }

        return $date ? $date->format('Y-m-d') : null;
    }

    private function getNextColumnPosition($columns, $current_pos)
    {
        $positions = array_column($columns, 'start');
        sort($positions);

        foreach ($positions as $pos) {
            if ($pos > $current_pos) {
                return $pos;
            }
        }

        return null;
    }

    private function loadCommonPatterns()
    {
        return [
            'remito_header' => '/REMITO\s+(?:DE\s+)?(?:ENTREGA\s+)?N[ºO°]?\s*:?\s*(\w+)/i',
            'fecha' => '/FECHA\s*:?\s*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            'proveedor' => '/PROVEEDOR\s*:?\s*([^\n\r]+)/i',
            'codigo_producto' => '/\b[A-Z0-9]{3,12}\b/',
            'precio' => '/\$?(\d+[.,]\d{2})/',
            'cantidad' => '/\b(\d{1,6})\b/',
            'total' => '/TOTAL\s*:?\s*\$?(\d+[.,]\d{2})/i'
        ];
    }
}

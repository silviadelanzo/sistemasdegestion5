<?php
// modulos/compras/ocr_remitos/validation_helper.php

class OCRValidationHelper
{

    // Métodos para análisis de similitud y consenso
    public static function buildSimilarityMatrix($texts)
    {
        $matrix = [];
        $count = count($texts);

        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                if ($i === $j) {
                    $matrix[$i][$j] = 1.0;
                } else {
                    $matrix[$i][$j] = self::calculateTextSimilarity($texts[$i], $texts[$j]);
                }
            }
        }

        return $matrix;
    }

    public static function calculateTextSimilarity($text1, $text2)
    {
        // Algoritmo híbrido de similitud
        $levenshtein = 1 - (levenshtein($text1, $text2) / max(strlen($text1), strlen($text2)));
        $cosine = self::cosineSimilarity($text1, $text2);
        $jaccard = self::jaccardSimilarity($text1, $text2);

        return ($levenshtein * 0.4 + $cosine * 0.4 + $jaccard * 0.2);
    }

    public static function cosineSimilarity($text1, $text2)
    {
        $words1 = array_count_values(str_word_count(strtolower($text1), 1));
        $words2 = array_count_values(str_word_count(strtolower($text2), 1));

        $intersection = array_intersect_key($words1, $words2);
        $dotProduct = 0;

        foreach ($intersection as $word => $count) {
            $dotProduct += $words1[$word] * $words2[$word];
        }

        $magnitude1 = sqrt(array_sum(array_map(function ($x) {
            return $x * $x;
        }, $words1)));
        $magnitude2 = sqrt(array_sum(array_map(function ($x) {
            return $x * $x;
        }, $words2)));

        return ($magnitude1 * $magnitude2) > 0 ? $dotProduct / ($magnitude1 * $magnitude2) : 0;
    }

    public static function jaccardSimilarity($text1, $text2)
    {
        $words1 = array_unique(str_word_count(strtolower($text1), 1));
        $words2 = array_unique(str_word_count(strtolower($text2), 1));

        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));

        return $union > 0 ? $intersection / $union : 0;
    }

    // Métodos para análisis de productos
    public static function parseProducts($text)
    {
        $products = [];
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Detectar líneas con estructura de producto
            if (self::isProductLine($line)) {
                $product = self::extractProductFromLine($line);
                if ($product) {
                    $products[] = $product;
                }
            }
        }

        return $products;
    }

    public static function isProductLine($line)
    {
        // Patrones que indican una línea de producto
        $patterns = [
            '/^[A-Z0-9]{3,}\s+.+\s+\d+[\.\,]?\d*\s+[\$\€\£]?\d+[\.\,]?\d*/',  // CODIGO DESC CANT PRECIO
            '/^\d+\s+[A-Z0-9]{3,}\s+.+\s+\d+/',  // NUM CODIGO DESC CANT
            '/^[A-Z0-9\-]{3,}\s+.+\s+\d+\s*$/',  // CODIGO DESC CANT
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    public static function extractProductFromLine($line)
    {
        // Múltiples patrones de extracción
        $patterns = [
            // Patrón 1: CODIGO DESCRIPCION CANTIDAD PRECIO
            '/^([A-Z0-9\-]{3,})\s+(.+?)\s+(\d+[\.\,]?\d*)\s+[\$\€\£]?(\d+[\.\,]?\d*)/',
            // Patrón 2: NUMERO CODIGO DESCRIPCION CANTIDAD
            '/^\d+\s+([A-Z0-9\-]{3,})\s+(.+?)\s+(\d+[\.\,]?\d*)\s*$/',
            // Patrón 3: CODIGO DESCRIPCION CANTIDAD
            '/^([A-Z0-9\-]{3,})\s+(.+?)\s+(\d+[\.\,]?\d*)\s*$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line, $matches)) {
                return [
                    'codigo' => trim($matches[1]),
                    'descripcion' => trim($matches[2]),
                    'cantidad' => self::parseNumber($matches[3]),
                    'precio' => isset($matches[4]) ? self::parseNumber($matches[4]) : 0,
                    'linea_original' => $line,
                    'confidence' => self::calculateLineConfidence($line, $matches)
                ];
            }
        }

        return null;
    }

    public static function parseNumber($str)
    {
        // Normalizar números con comas/puntos
        $str = str_replace(',', '.', $str);
        return (float) $str;
    }

    public static function calculateLineConfidence($line, $matches)
    {
        $confidence = 0.5; // Base

        // Bonus por código bien formateado
        if (preg_match('/^[A-Z0-9\-]{4,}$/', $matches[1])) {
            $confidence += 0.2;
        }

        // Bonus por descripción coherente
        if (strlen($matches[2]) > 10 && strlen($matches[2]) < 100) {
            $confidence += 0.1;
        }

        // Bonus por cantidad numérica válida
        if (is_numeric($matches[3]) && $matches[3] > 0) {
            $confidence += 0.1;
        }

        // Bonus por precio si existe
        if (isset($matches[4]) && is_numeric($matches[4]) && $matches[4] > 0) {
            $confidence += 0.1;
        }

        return min(1.0, $confidence);
    }

    // Métodos para consenso de productos
    public static function groupSimilarProducts($products)
    {
        $groups = [];
        $processed = [];

        foreach ($products as $i => $product) {
            if (in_array($i, $processed)) continue;

            $group = [$product];
            $processed[] = $i;

            // Buscar productos similares
            foreach ($products as $j => $other_product) {
                if ($i === $j || in_array($j, $processed)) continue;

                if (self::areProductsSimilar($product, $other_product)) {
                    $group[] = $other_product;
                    $processed[] = $j;
                }
            }

            $groups[] = $group;
        }

        return $groups;
    }

    public static function areProductsSimilar($product1, $product2)
    {
        // Similitud por código
        $code_similarity = self::calculateStringSimilarity($product1['codigo'], $product2['codigo']);

        // Similitud por descripción
        $desc_similarity = self::calculateStringSimilarity($product1['descripcion'], $product2['descripcion']);

        // Similitud por cantidad (diferencia relativa)
        $qty_similarity = 1 - abs($product1['cantidad'] - $product2['cantidad']) / max($product1['cantidad'], $product2['cantidad'], 1);

        // Weighted average
        $overall_similarity = ($code_similarity * 0.5 + $desc_similarity * 0.4 + $qty_similarity * 0.1);

        return $overall_similarity > 0.8;
    }

    public static function calculateStringSimilarity($str1, $str2)
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) return 1.0;

        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) return 1.0;

        return 1 - (levenshtein($str1, $str2) / $maxLen);
    }

    // Métodos para construir consenso
    public static function getMostFrequent($array)
    {
        if (empty($array)) return '';

        $frequency = array_count_values($array);
        arsort($frequency);

        return array_key_first($frequency);
    }

    public static function getConsensusNumber($numbers)
    {
        if (empty($numbers)) return 0;

        $numbers = array_filter($numbers, 'is_numeric');
        if (empty($numbers)) return 0;

        // Si todos son iguales, devolver ese valor
        if (count(array_unique($numbers)) === 1) {
            return reset($numbers);
        }

        // Si hay mucha variación, usar la mediana
        sort($numbers);
        $count = count($numbers);
        $middle = floor($count / 2);

        return $count % 2 === 0
            ? ($numbers[$middle - 1] + $numbers[$middle]) / 2
            : $numbers[$middle];
    }

    public static function calculateProductConfidence($product_group)
    {
        if (count($product_group) === 1) {
            return $product_group[0]['confidence'] ?? 0.7;
        }

        // Calcular consistencia entre detecciones
        $codes = array_column($product_group, 'codigo');
        $descriptions = array_column($product_group, 'descripcion');
        $quantities = array_column($product_group, 'cantidad');

        $code_consistency = self::calculateArrayConsistency($codes);
        $desc_consistency = self::calculateDescriptionConsistency($descriptions);
        $qty_consistency = self::calculateNumberConsistency($quantities);

        $base_confidence = array_sum(array_column($product_group, 'confidence')) / count($product_group);
        $consistency_bonus = ($code_consistency + $desc_consistency + $qty_consistency) / 3 * 0.2;
        $multi_detection_bonus = min(count($product_group) * 0.05, 0.2);

        return min(1.0, $base_confidence + $consistency_bonus + $multi_detection_bonus);
    }

    public static function calculateArrayConsistency($array)
    {
        if (count($array) <= 1) return 1.0;

        $unique_count = count(array_unique($array));
        return 1 - (($unique_count - 1) / (count($array) - 1));
    }

    public static function calculateDescriptionConsistency($descriptions)
    {
        if (count($descriptions) <= 1) return 1.0;

        $total_similarity = 0;
        $comparisons = 0;

        for ($i = 0; $i < count($descriptions); $i++) {
            for ($j = $i + 1; $j < count($descriptions); $j++) {
                $total_similarity += self::calculateStringSimilarity($descriptions[$i], $descriptions[$j]);
                $comparisons++;
            }
        }

        return $comparisons > 0 ? $total_similarity / $comparisons : 0;
    }

    public static function calculateNumberConsistency($numbers)
    {
        if (count($numbers) <= 1) return 1.0;

        $numbers = array_filter($numbers, 'is_numeric');
        if (count($numbers) <= 1) return 1.0;

        $mean = array_sum($numbers) / count($numbers);
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $numbers)) / count($numbers);
        $coefficient_variation = $mean > 0 ? sqrt($variance) / $mean : 0;

        return max(0, 1 - $coefficient_variation);
    }

    public static function calculateConsistencyScore($product_group)
    {
        $score = [
            'code_consistency' => self::calculateArrayConsistency(array_column($product_group, 'codigo')),
            'description_consistency' => self::calculateDescriptionConsistency(array_column($product_group, 'descripcion')),
            'quantity_consistency' => self::calculateNumberConsistency(array_column($product_group, 'cantidad')),
            'detection_count' => count($product_group),
            'engines_agreement' => count(array_unique(array_column($product_group, 'detected_by')))
        ];

        $score['overall'] = ($score['code_consistency'] * 0.4 +
            $score['description_consistency'] * 0.3 +
            $score['quantity_consistency'] * 0.2 +
            min($score['detection_count'] / 4, 1) * 0.1);

        return $score;
    }

    // Método para encontrar campos con consenso
    public static function findConsensusFields($engine_results)
    {
        $consensus = [];

        // Extraer datos comunes
        foreach ($engine_results as $engine => $result) {
            if (!$result['success']) continue;

            $parsed = self::parseProducts($result['text']);
            foreach ($parsed as $product) {
                $key = self::generateProductKey($product);
                if (!isset($consensus[$key])) {
                    $consensus[$key] = [];
                }
                $consensus[$key][] = [
                    'engine' => $engine,
                    'product' => $product,
                    'confidence' => $product['confidence']
                ];
            }
        }

        // Filtrar por consenso
        $consensus_fields = [];
        foreach ($consensus as $key => $detections) {
            if (count($detections) >= 2) { // Al menos 2 motores detectaron esto
                $consensus_fields[$key] = [
                    'detections' => $detections,
                    'consensus_product' => self::buildConsensusFromDetections($detections),
                    'confidence' => self::calculateConsensusConfidence($detections)
                ];
            }
        }

        return $consensus_fields;
    }

    private static function generateProductKey($product)
    {
        // Generar clave única para agrupar productos similares
        $code = strtoupper(trim($product['codigo']));
        $desc_words = array_slice(str_word_count(strtolower($product['descripcion']), 1), 0, 3);
        return $code . '_' . implode('_', $desc_words);
    }

    private static function buildConsensusFromDetections($detections)
    {
        $products = array_column($detections, 'product');
        return [
            'codigo' => self::getMostFrequent(array_column($products, 'codigo')),
            'descripcion' => self::getMostFrequent(array_column($products, 'descripcion')),
            'cantidad' => self::getConsensusNumber(array_column($products, 'cantidad')),
            'precio' => self::getConsensusNumber(array_column($products, 'precio'))
        ];
    }

    private static function calculateConsensusConfidence($detections)
    {
        $confidence_sum = array_sum(array_column($detections, 'confidence'));
        $detection_count = count($detections);

        $avg_confidence = $confidence_sum / $detection_count;
        $consensus_bonus = min($detection_count * 0.1, 0.3);

        return min(1.0, $avg_confidence + $consensus_bonus);
    }
}

<?php
// modulos/compras/ocr_remitos/product_matcher.php

class ProductMatcher
{
    private $db;
    private $similarity_threshold;
    private $proveedor_id = null; // contexto de proveedor (opcional)

    public function __construct($db, $proveedor_id = null)
    {
        $this->db = $db;
        $this->similarity_threshold = 0.85; // 85% de similitud mínima
        if (!is_null($proveedor_id)) {
            $this->proveedor_id = (int)$proveedor_id;
        }
    }

    public function matchProducts($productos_remito)
    {
        $results = [
            'exact_matches' => [],      // Coincidencias exactas por código
            'fuzzy_matches' => [],      // Coincidencias por similitud de descripción
            'new_products' => [],       // Productos nuevos no encontrados
            'conflicts' => []           // Múltiples coincidencias posibles
        ];

        foreach ($productos_remito as $producto_remito) {
            $match_result = $this->findBestMatch($producto_remito);

            switch ($match_result['type']) {
                case 'exact':
                    $results['exact_matches'][] = [
                        'remito_product' => $producto_remito,
                        'db_product' => $match_result['product'],
                        'confidence' => 1.0
                    ];
                    break;

                case 'fuzzy':
                    $results['fuzzy_matches'][] = [
                        'remito_product' => $producto_remito,
                        'db_product' => $match_result['product'],
                        'confidence' => $match_result['confidence'],
                        'similarity_score' => $match_result['similarity']
                    ];
                    break;

                case 'conflict':
                    $results['conflicts'][] = [
                        'remito_product' => $producto_remito,
                        'possible_matches' => $match_result['candidates'],
                        'requires_manual_review' => true
                    ];
                    break;

                case 'new':
                    $results['new_products'][] = [
                        'remito_product' => $producto_remito,
                        'suggested_category' => $this->suggestCategory($producto_remito),
                        'suggested_attributes' => $this->extractAttributes($producto_remito)
                    ];
                    break;
            }
        }

        return $results;
    }

    private function findBestMatch($producto_remito)
    {
        // PASO 0: Si tenemos proveedor, intentar por código de proveedor
        if (!empty($this->proveedor_id) && !empty($producto_remito['codigo'])) {
            $byProv = $this->findByProveedorCode($producto_remito['codigo'], $this->proveedor_id);
            if ($byProv) {
                return [
                    'type' => 'exact',
                    'product' => $byProv,
                    'confidence' => 1.0
                ];
            }
        }

        // PASO 1: Buscar coincidencia exacta por código
        $exact_match = $this->findExactMatch($producto_remito);
        if ($exact_match) {
            return [
                'type' => 'exact',
                'product' => $exact_match,
                'confidence' => 1.0
            ];
        }

        // PASO 2: Buscar por código de barras/EAN si existe
        if (!empty($producto_remito['ean'])) {
            $ean_match = $this->findByEAN($producto_remito['ean']);
            if ($ean_match) {
                return [
                    'type' => 'exact',
                    'product' => $ean_match,
                    'confidence' => 1.0
                ];
            }
        }

        // PASO 3: Buscar similitudes por descripción
        $similar_products = $this->findSimilarProducts($producto_remito);

        if (empty($similar_products)) {
            return ['type' => 'new'];
        }

        // Si hay múltiples coincidencias similares, es conflicto
        if (count($similar_products) > 1) {
            $top_matches = array_slice($similar_products, 0, 3); // Top 3
            $max_similarity = max(array_column($top_matches, 'similarity'));

            // Si las mejores coincidencias son muy similares entre sí, es conflicto
            $close_matches = array_filter($top_matches, function ($match) use ($max_similarity) {
                return $match['similarity'] >= ($max_similarity - 0.1);
            });

            if (count($close_matches) > 1) {
                return [
                    'type' => 'conflict',
                    'candidates' => $close_matches
                ];
            }
        }

        // Mejor coincidencia individual
        $best_match = $similar_products[0];

        if ($best_match['similarity'] >= $this->similarity_threshold) {
            return [
                'type' => 'fuzzy',
                'product' => $best_match['product'],
                'confidence' => $best_match['similarity'],
                'similarity' => $best_match['similarity']
            ];
        }

        return ['type' => 'new'];
    }

    private function findByProveedorCode($codigo, $proveedor_id)
    {
        // Normalizar código
        $codigo = strtoupper(trim((string)$codigo));
        if ($codigo === '') return null;

        // Búsqueda directa en productos por código_proveedor y proveedor_principal_id
        // Nota: muchos esquemas guardan el código del proveedor en productos.codigo_proveedor
        //       y el vínculo principal en productos.proveedor_principal_id
        try {
            $sql = "SELECT * FROM productos WHERE proveedor_principal_id = ? AND (codigo_proveedor = ? OR codigo = ?) LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$proveedor_id, $codigo, $codigo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row;
        } catch (Throwable $e) {
            // Ignorar errores y continuar con otros métodos
        }

        return null;
    }

    private function findExactMatch($producto_remito)
    {
        $codigo = $producto_remito['codigo'];

    $sql = "SELECT * FROM productos WHERE codigo = ? OR codigo_alternativo = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$codigo, $codigo]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function findByEAN($ean)
    {
        $sql = "SELECT * FROM productos WHERE ean = ? OR codigo_barras = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ean, $ean]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function findSimilarProducts($producto_remito)
    {
        $descripcion = $producto_remito['descripcion'];

        // Buscar productos con descripción similar
        $sql = "SELECT *, 
                MATCH(descripcion) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score
                FROM productos 
                WHERE MATCH(descripcion) AGAINST(? IN NATURAL LANGUAGE MODE)
                   OR descripcion LIKE ?
                ORDER BY relevance_score DESC, id DESC
                LIMIT 10";

        $like_pattern = '%' . $this->prepareForLike($descripcion) . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$descripcion, $descripcion, $like_pattern]);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular similitud más precisa
        $results = [];
        foreach ($candidates as $candidate) {
            $similarity = $this->calculateSimilarity(
                $descripcion,
                $candidate['descripcion']
            );

            if ($similarity > 0.3) { // Umbral mínimo
                $results[] = [
                    'product' => $candidate,
                    'similarity' => $similarity,
                    'match_type' => $this->getMatchType($similarity)
                ];
            }
        }

        // Ordenar por similitud descendente
        usort($results, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return $results;
    }

    private function calculateSimilarity($str1, $str2)
    {
        // Normalizar cadenas
        $str1 = $this->normalizeString($str1);
        $str2 = $this->normalizeString($str2);

        // Múltiples algoritmos de similitud
        $levenshtein = $this->levenshteinSimilarity($str1, $str2);
        $jaro = $this->jaroWinklerSimilarity($str1, $str2);
        $cosine = $this->cosineSimilarity($str1, $str2);
        $ngram = $this->ngramSimilarity($str1, $str2);

        // Promedio ponderado
        $similarity = (
            $levenshtein * 0.2 +
            $jaro * 0.3 +
            $cosine * 0.3 +
            $ngram * 0.2
        );

        return round($similarity, 3);
    }

    private function normalizeString($str)
    {
        $str = strtolower(trim($str));
        $str = preg_replace('/[^\p{L}\p{N}\s]/u', '', $str); // Solo letras, números y espacios
        $str = preg_replace('/\s+/', ' ', $str); // Normalizar espacios
        return $str;
    }

    private function levenshteinSimilarity($str1, $str2)
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        $max_len = max($len1, $len2);

        if ($max_len == 0) return 1.0;

        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $max_len);
    }

    private function jaroWinklerSimilarity($str1, $str2)
    {
        // Implementación simplificada de Jaro-Winkler
        $len1 = strlen($str1);
        $len2 = strlen($str2);

        if ($len1 == 0 && $len2 == 0) return 1.0;
        if ($len1 == 0 || $len2 == 0) return 0.0;

        $match_window = floor(max($len1, $len2) / 2) - 1;
        $match_window = max(0, $match_window);

        $matches = 0;
        $transpositions = 0;

        $s1_matches = array_fill(0, $len1, false);
        $s2_matches = array_fill(0, $len2, false);

        // Encontrar coincidencias
        for ($i = 0; $i < $len1; $i++) {
            $start = max(0, $i - $match_window);
            $end = min($i + $match_window + 1, $len2);

            for ($j = $start; $j < $end; $j++) {
                if ($s2_matches[$j] || $str1[$i] != $str2[$j]) continue;

                $s1_matches[$i] = true;
                $s2_matches[$j] = true;
                $matches++;
                break;
            }
        }

        if ($matches == 0) return 0.0;

        // Calcular transposiciones
        $k = 0;
        for ($i = 0; $i < $len1; $i++) {
            if (!$s1_matches[$i]) continue;

            while (!$s2_matches[$k]) $k++;

            if ($str1[$i] != $str2[$k]) $transpositions++;
            $k++;
        }

        $jaro = ($matches / $len1 + $matches / $len2 + ($matches - $transpositions / 2) / $matches) / 3;

        return $jaro;
    }

    private function cosineSimilarity($str1, $str2)
    {
        $words1 = explode(' ', $str1);
        $words2 = explode(' ', $str2);

        $all_words = array_unique(array_merge($words1, $words2));

        $vector1 = [];
        $vector2 = [];

        foreach ($all_words as $word) {
            $vector1[] = substr_count($str1, $word);
            $vector2[] = substr_count($str2, $word);
        }

        $dot_product = 0;
        $norm1 = 0;
        $norm2 = 0;

        for ($i = 0; $i < count($vector1); $i++) {
            $dot_product += $vector1[$i] * $vector2[$i];
            $norm1 += $vector1[$i] * $vector1[$i];
            $norm2 += $vector2[$i] * $vector2[$i];
        }

        if ($norm1 == 0 || $norm2 == 0) return 0;

        return $dot_product / (sqrt($norm1) * sqrt($norm2));
    }

    private function ngramSimilarity($str1, $str2, $n = 2)
    {
        $ngrams1 = $this->getNgrams($str1, $n);
        $ngrams2 = $this->getNgrams($str2, $n);

        $intersection = array_intersect($ngrams1, $ngrams2);
        $union = array_unique(array_merge($ngrams1, $ngrams2));

        if (empty($union)) return 1.0;

        return count($intersection) / count($union);
    }

    private function getNgrams($str, $n)
    {
        $ngrams = [];
        $len = strlen($str);

        for ($i = 0; $i <= $len - $n; $i++) {
            $ngrams[] = substr($str, $i, $n);
        }

        return $ngrams;
    }

    private function getMatchType($similarity)
    {
        if ($similarity >= 0.95) return 'very_high';
        if ($similarity >= 0.85) return 'high';
        if ($similarity >= 0.70) return 'medium';
        if ($similarity >= 0.50) return 'low';
        return 'very_low';
    }

    private function suggestCategory($producto)
    {
        $descripcion = strtolower($producto['descripcion']);

        // Palabras clave para categorización automática
        $categories = [
            'Tornillería' => ['tornillo', 'tuerca', 'arandela', 'perno', 'clavo'],
            'Herramientas' => ['destornillador', 'martillo', 'alicate', 'llave', 'taladro'],
            'Electricidad' => ['cable', 'interruptor', 'enchufe', 'lámpara', 'led'],
            'Plomería' => ['caño', 'codo', 'válvula', 'grifo', 'manguera'],
            'Pintura' => ['pintura', 'barniz', 'brocha', 'rodillo', 'thinner'],
            'Construcción' => ['cemento', 'arena', 'ladrillo', 'cal', 'hierro']
        ];

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($descripcion, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return 'Sin categorizar';
    }

    private function extractAttributes($producto)
    {
        $descripcion = $producto['descripcion'];
        $attributes = [];

        // Extraer medidas
        if (preg_match('/(\d+)\s*x\s*(\d+)\s*(?:mm|cm|m)?/i', $descripcion, $matches)) {
            $attributes['medidas'] = $matches[0];
            $attributes['largo'] = $matches[1];
            $attributes['ancho'] = $matches[2];
        }

        // Extraer material
        $materials = ['acero', 'hierro', 'aluminio', 'plástico', 'madera', 'cobre'];
        foreach ($materials as $material) {
            if (stripos($descripcion, $material) !== false) {
                $attributes['material'] = ucfirst($material);
                break;
            }
        }

        // Extraer color
        $colors = ['negro', 'blanco', 'rojo', 'azul', 'verde', 'amarillo', 'gris'];
        foreach ($colors as $color) {
            if (stripos($descripcion, $color) !== false) {
                $attributes['color'] = ucfirst($color);
                break;
            }
        }

        // Extraer marca (primera palabra en mayúsculas)
        if (preg_match('/\b[A-Z][A-Z]+\b/', $descripcion, $matches)) {
            $attributes['marca_posible'] = $matches[0];
        }

        return $attributes;
    }

    private function prepareForLike($str)
    {
        // Preparar string para LIKE query
        $str = str_replace(['%', '_'], ['\%', '\_'], $str);
        return $str;
    }

    public function setSimilarityThreshold($threshold)
    {
        $this->similarity_threshold = max(0.0, min(1.0, $threshold));
    }

    public function setProveedorId($proveedor_id)
    {
        $this->proveedor_id = is_null($proveedor_id) ? null : (int)$proveedor_id;
    }

    public function getMatchingStats($results)
    {
        return [
            'total_products' => array_sum(array_map('count', $results)),
            'exact_matches' => count($results['exact_matches']),
            'fuzzy_matches' => count($results['fuzzy_matches']),
            'new_products' => count($results['new_products']),
            'conflicts' => count($results['conflicts']),
            'success_rate' => $this->calculateSuccessRate($results)
        ];
    }

    private function calculateSuccessRate($results)
    {
        $total = array_sum(array_map('count', $results));
        $successful = count($results['exact_matches']) + count($results['fuzzy_matches']);

        return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
    }
}

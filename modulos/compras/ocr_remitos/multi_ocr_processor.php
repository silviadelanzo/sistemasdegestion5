<?php
// modulos/compras/ocr_remitos/multi_ocr_processor.php

class MultiOCRProcessor
{
    private $ocr_engines;
    private $confidence_threshold = 0.95;

    public function __construct()
    {
        $this->ocr_engines = [
            'tesseract' => new TesseractOCR(),
            'google_vision' => new GoogleVisionOCR(),
            'azure_cognitive' => new AzureCognitiveOCR(),
            'aws_textract' => new AWSTextractOCR()
        ];
    }

    public function processWithMultipleEngines($image_path)
    {
        $results = [];
        $consensus_data = [];

        // PASO 1: Procesar con todos los motores OCR disponibles
        foreach ($this->ocr_engines as $engine_name => $engine) {
            try {
                $start_time = microtime(true);
                $result = $engine->extractText($image_path);
                $processing_time = microtime(true) - $start_time;

                $results[$engine_name] = [
                    'text' => $result['text'],
                    'confidence' => $result['confidence'],
                    'processing_time' => $processing_time,
                    'success' => true,
                    'products' => $this->parseProducts($result['text'])
                ];

                echo "✅ {$engine_name}: {$result['confidence']}% confianza\n";
            } catch (Exception $e) {
                $results[$engine_name] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                echo "❌ {$engine_name}: Error - {$e->getMessage()}\n";
            }
        }

        // PASO 2: Análisis de consenso entre motores
        $consensus_data = $this->analyzeConsensus($results);

        // PASO 3: Validación cruzada de productos
        $validated_products = $this->crossValidateProducts($results);

        // PASO 4: Puntuación final de confianza
        $final_confidence = $this->calculateFinalConfidence($results, $consensus_data);

        return [
            'individual_results' => $results,
            'consensus' => $consensus_data,
            'validated_products' => $validated_products,
            'final_confidence' => $final_confidence,
            'recommendation' => $this->getRecommendation($final_confidence)
        ];
    }

    private function analyzeConsensus($results)
    {
        $successful_engines = array_filter($results, function ($r) {
            return $r['success'];
        });

        if (count($successful_engines) < 2) {
            return ['consensus_possible' => false];
        }

        // Comparar textos extraídos
        $texts = array_column($successful_engines, 'text');
        $similarity_matrix = $this->buildSimilarityMatrix($texts);

        // Encontrar campos con consenso
        $consensus_fields = $this->findConsensusFields($successful_engines);

        return [
            'consensus_possible' => true,
            'engines_count' => count($successful_engines),
            'similarity_matrix' => $similarity_matrix,
            'consensus_fields' => $consensus_fields,
            'avg_confidence' => array_sum(array_column($successful_engines, 'confidence')) / count($successful_engines)
        ];
    }

    private function crossValidateProducts($results)
    {
        $all_products = [];

        // Recopilar productos de todos los motores
        foreach ($results as $engine => $result) {
            if ($result['success'] && isset($result['products'])) {
                foreach ($result['products'] as $product) {
                    $product['detected_by'] = $engine;
                    $all_products[] = $product;
                }
            }
        }

        // Agrupar productos similares
        $product_groups = $this->groupSimilarProducts($all_products);

        // Validar cada grupo
        $validated = [];
        foreach ($product_groups as $group) {
            $consensus_product = $this->buildConsensusProduct($group);
            if ($consensus_product['confidence'] >= $this->confidence_threshold) {
                $validated[] = $consensus_product;
            }
        }

        return $validated;
    }

    private function buildConsensusProduct($product_group)
    {
        $codes = array_column($product_group, 'codigo');
        $descriptions = array_column($product_group, 'descripcion');
        $quantities = array_column($product_group, 'cantidad');
        $prices = array_column($product_group, 'precio');

        // Usar el valor más frecuente o el promedio
        $consensus_code = $this->getMostFrequent($codes);
        $consensus_description = $this->getMostFrequent($descriptions);
        $consensus_quantity = $this->getConsensusNumber($quantities);
        $consensus_price = $this->getConsensusNumber($prices);

        // Calcular confianza basada en consistencia
        $confidence = $this->calculateProductConfidence($product_group);

        return [
            'codigo' => $consensus_code,
            'descripcion' => $consensus_description,
            'cantidad' => $consensus_quantity,
            'precio' => $consensus_price,
            'confidence' => $confidence,
            'detected_by_engines' => array_column($product_group, 'detected_by'),
            'consistency_score' => $this->calculateConsistencyScore($product_group)
        ];
    }

    private function calculateFinalConfidence($results, $consensus_data)
    {
        $successful_count = count(array_filter($results, function ($r) {
            return $r['success'];
        }));

        if ($successful_count === 0) return 0;
        if ($successful_count === 1) return 0.7; // Solo un motor, confianza media

        $avg_confidence = $consensus_data['avg_confidence'] ?? 0;
        $consensus_bonus = $consensus_data['consensus_possible'] ? 0.15 : 0;
        $multi_engine_bonus = min($successful_count * 0.05, 0.25);

        return min(1.0, $avg_confidence + $consensus_bonus + $multi_engine_bonus);
    }

    private function getRecommendation($confidence)
    {
        if ($confidence >= 0.98) return 'auto_process';
        if ($confidence >= 0.90) return 'review_differences';
        if ($confidence >= 0.75) return 'manual_verification';
        return 'human_entry_required';
    }
}

// Implementaciones específicas de motores OCR
class TesseractOCR
{
    public function extractText($image_path)
    {
        // Implementación mejorada de Tesseract
        $processed_image = $this->preprocessForTesseract($image_path);

        // Múltiples configuraciones PSM
        $psm_configs = [6, 7, 8, 11, 12, 13];
        $best_result = null;
        $best_confidence = 0;

        foreach ($psm_configs as $psm) {
            $result = $this->runTesseractWithPSM($processed_image, $psm);
            if ($result['confidence'] > $best_confidence) {
                $best_confidence = $result['confidence'];
                $best_result = $result;
            }
        }

        return $best_result;
    }

    private function preprocessForTesseract($image_path)
    {
        // Preprocesamiento avanzado específico para Tesseract
        $image = imagecreatefromjpeg($image_path);

        // 1. Deskew (corregir inclinación)
        $image = $this->deskewImage($image);

        // 2. Noise removal (eliminar ruido)
        $image = $this->removeNoise($image);

        // 3. Border removal (eliminar bordes)
        $image = $this->removeBorders($image);

        // 4. Character thickness normalization
        $image = $this->normalizeThickness($image);

        $temp_path = sys_get_temp_dir() . '/tesseract_optimized_' . uniqid() . '.png';
        imagepng($image, $temp_path);
        imagedestroy($image);

        return $temp_path;
    }
}

class GoogleVisionOCR
{
    private $api_key;

    public function __construct()
    {
        $this->api_key = getenv('GOOGLE_VISION_API_KEY') ?: 'your-api-key-here';
    }

    public function extractText($image_path)
    {
        if (!$this->api_key || $this->api_key === 'your-api-key-here') {
            throw new Exception('Google Vision API key no configurada');
        }

        $image_data = base64_encode(file_get_contents($image_path));

        $request_data = [
            'requests' => [
                [
                    'image' => ['content' => $image_data],
                    'features' => [
                        ['type' => 'DOCUMENT_TEXT_DETECTION', 'maxResults' => 1]
                    ],
                    'imageContext' => [
                        'languageHints' => ['es', 'en']
                    ]
                ]
            ]
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://vision.googleapis.com/v1/images:annotate?key=" . $this->api_key,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($request_data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code !== 200) {
            throw new Exception("Google Vision API error: HTTP $http_code");
        }

        $result = json_decode($response, true);

        if (isset($result['responses'][0]['fullTextAnnotation'])) {
            $annotation = $result['responses'][0]['fullTextAnnotation'];
            return [
                'text' => $annotation['text'],
                'confidence' => $this->calculateGoogleConfidence($annotation),
                'raw_response' => $result
            ];
        }

        throw new Exception('No se pudo extraer texto con Google Vision');
    }

    private function calculateGoogleConfidence($annotation)
    {
        if (!isset($annotation['pages'])) return 0.5;

        $total_confidence = 0;
        $word_count = 0;

        foreach ($annotation['pages'] as $page) {
            if (!isset($page['blocks'])) continue;

            foreach ($page['blocks'] as $block) {
                if (!isset($block['paragraphs'])) continue;

                foreach ($block['paragraphs'] as $paragraph) {
                    if (!isset($paragraph['words'])) continue;

                    foreach ($paragraph['words'] as $word) {
                        if (isset($word['confidence'])) {
                            $total_confidence += $word['confidence'];
                            $word_count++;
                        }
                    }
                }
            }
        }

        return $word_count > 0 ? ($total_confidence / $word_count) : 0.5;
    }
}

class AzureCognitiveOCR
{
    private $endpoint;
    private $api_key;

    public function __construct()
    {
        $this->endpoint = getenv('AZURE_COGNITIVE_ENDPOINT') ?: 'your-endpoint-here';
        $this->api_key = getenv('AZURE_COGNITIVE_KEY') ?: 'your-key-here';
    }

    public function extractText($image_path)
    {
        if (!$this->api_key || $this->api_key === 'your-key-here') {
            throw new Exception('Azure Cognitive Services no configurado');
        }

        // Simulación para desarrollo
        return [
            'text' => $this->simulateAzureOCR($image_path),
            'confidence' => 0.92,
            'engine' => 'azure_simulation'
        ];
    }

    private function simulateAzureOCR($image_path)
    {
        // Simulación para testing
        return "REMITO DE ENTREGA Nº: 001234\nProveedor: ACME Suministros S.A.\nFecha: 02/08/2025\n\nCÓDIGO      DESCRIPCIÓN                    CANT   PRECIO\nABC123      Tornillo Phillips 3x20mm       100    $2.50\nDEF456      Tuerca hexagonal M6            50     $1.75";
    }
}

class AWSTextractOCR
{
    private $aws_access_key;
    private $aws_secret_key;
    private $aws_region;

    public function __construct()
    {
        $this->aws_access_key = getenv('AWS_ACCESS_KEY_ID') ?: 'your-access-key';
        $this->aws_secret_key = getenv('AWS_SECRET_ACCESS_KEY') ?: 'your-secret-key';
        $this->aws_region = getenv('AWS_REGION') ?: 'us-east-1';
    }

    public function extractText($image_path)
    {
        if (!$this->aws_access_key || $this->aws_access_key === 'your-access-key') {
            throw new Exception('AWS Textract no configurado');
        }

        // Simulación para desarrollo
        return [
            'text' => $this->simulateAWSTextract($image_path),
            'confidence' => 0.94,
            'engine' => 'aws_simulation'
        ];
    }

    private function simulateAWSTextract($image_path)
    {
        // Simulación para testing
        return "REMITO DE ENTREGA Nº: 001234\nProveedor: ACME Suministros S.A.\nFecha: 02/08/2025\n\nCÓDIGO      DESCRIPCIÓN                    CANT   PRECIO\nABC123      Tornillo Phillips 3x20mm       100    $2.50";
    }
}

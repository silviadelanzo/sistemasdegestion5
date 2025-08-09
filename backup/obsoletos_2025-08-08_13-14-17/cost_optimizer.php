<?php
// modulos/compras/ocr_remitos/cost_optimizer.php

class CostOptimizer
{
    private $db;
    private $budget_limit = 50; // Límite mensual en USD
    private $current_month_spent = 0;

    // Costos por API (por 1000 requests)
    private $api_costs = [
        'google_vision' => 1.50,
        'azure_cognitive' => 1.00,
        'aws_textract' => 1.50
    ];

    public function __construct($database)
    {
        $this->db = $database;
        $this->loadMonthlySpending();
    }

    public function shouldUsePremiumAPI($tesseract_confidence, $proveedor_id, $priority = 'normal')
    {
        // DECISION TREE INTELIGENTE PARA MINIMIZAR COSTOS

        // 1. Verificar presupuesto disponible
        if ($this->current_month_spent >= $this->budget_limit) {
            return [
                'use_premium' => false,
                'reason' => 'budget_exceeded',
                'recommended_engine' => 'tesseract_enhanced'
            ];
        }

        // 2. Si Tesseract tiene alta confianza, no gastar
        if ($tesseract_confidence >= 0.95) {
            return [
                'use_premium' => false,
                'reason' => 'tesseract_sufficient',
                'recommended_engine' => 'tesseract_local'
            ];
        }

        // 3. Verificar historial del proveedor
        $provider_stats = $this->getProviderStats($proveedor_id);

        // 4. Calcular ROI esperado
        $expected_roi = $this->calculateExpectedROI($tesseract_confidence, $provider_stats, $priority);

        // 5. Tomar decisión inteligente
        if ($expected_roi > 3.0) { // ROI > 3:1
            return [
                'use_premium' => true,
                'reason' => 'high_roi_expected',
                'recommended_engine' => $this->selectBestAPI($provider_stats),
                'estimated_cost' => $this->api_costs[$this->selectBestAPI($provider_stats)] / 1000
            ];
        }

        return [
            'use_premium' => false,
            'reason' => 'roi_too_low',
            'recommended_engine' => 'tesseract_enhanced',
            'alternative' => 'human_validation_recommended'
        ];
    }

    private function loadMonthlySpending()
    {
        $query = "
            SELECT COALESCE(SUM(api_cost), 0) as total_spent 
            FROM ocr_api_usage 
            WHERE YEAR(usage_date) = YEAR(NOW()) 
            AND MONTH(usage_date) = MONTH(NOW())
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->current_month_spent = $result['total_spent'] ?? 0;
    }

    private function getProviderStats($proveedor_id)
    {
        $query = "
            SELECT 
                AVG(tesseract_confidence) as avg_tesseract_confidence,
                AVG(final_confidence) as avg_final_confidence,
                COUNT(*) as total_processed,
                SUM(CASE WHEN used_premium_api = 1 THEN 1 ELSE 0 END) as premium_used,
                AVG(processing_success_rate) as success_rate,
                AVG(human_validation_rate) as validation_rate
            FROM ocr_provider_performance 
            WHERE proveedor_id = ? 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$proveedor_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: $this->getDefaultStats();
    }

    private function getDefaultStats()
    {
        return [
            'avg_tesseract_confidence' => 0.75,
            'avg_final_confidence' => 0.75,
            'total_processed' => 0,
            'premium_used' => 0,
            'success_rate' => 0.5,
            'validation_rate' => 0.8
        ];
    }

    private function calculateExpectedROI($tesseract_confidence, $provider_stats, $priority)
    {
        // Factores que influyen en el ROI
        $confidence_improvement = $this->estimateConfidenceImprovement($tesseract_confidence, $provider_stats);
        $time_saved = $this->estimateTimeSaved($confidence_improvement);
        $error_cost_avoided = $this->estimateErrorCostAvoided($confidence_improvement);

        // Costo estimado de la API
        $api_cost = 0.0015; // $1.50 por 1000 = $0.0015 por request

        // Beneficios
        $benefits = $time_saved + $error_cost_avoided;

        // Multiplicador por prioridad
        $priority_multiplier = $this->getPriorityMultiplier($priority);

        return ($benefits * $priority_multiplier) / $api_cost;
    }

    private function estimateConfidenceImprovement($tesseract_confidence, $provider_stats)
    {
        // Basado en datos históricos
        if ($tesseract_confidence < 0.6) {
            return 0.35; // Mejora sustancial
        } elseif ($tesseract_confidence < 0.8) {
            return 0.20; // Mejora notable
        } else {
            return 0.10; // Mejora marginal
        }
    }

    private function estimateTimeSaved($confidence_improvement)
    {
        // Tiempo ahorrado en validación humana
        // Asumiendo $15/hora para validación manual
        $hourly_rate = 15;
        $minutes_saved_per_product = $confidence_improvement * 5; // 5 min max por producto

        return ($minutes_saved_per_product / 60) * $hourly_rate;
    }

    private function estimateErrorCostAvoided($confidence_improvement)
    {
        // Costo de errores evitados
        $avg_error_cost = 25; // Costo promedio de un error de inventario
        $error_probability_reduction = $confidence_improvement * 0.5;

        return $avg_error_cost * $error_probability_reduction;
    }

    private function getPriorityMultiplier($priority)
    {
        switch ($priority) {
            case 'critical':
                return 3.0;
            case 'high':
                return 2.0;
            case 'normal':
                return 1.0;
            case 'low':
                return 0.5;
            default:
                return 1.0;
        }
    }

    private function selectBestAPI($provider_stats)
    {
        // Seleccionar API basada en el rendimiento histórico
        $api_performance = $this->getAPIPerformanceForProvider($provider_stats);

        // Ordenar por relación calidad/precio
        uasort($api_performance, function ($a, $b) {
            $ratio_a = $a['avg_confidence'] / $this->api_costs[$a['engine']];
            $ratio_b = $b['avg_confidence'] / $this->api_costs[$b['engine']];
            return $ratio_b <=> $ratio_a;
        });

        return array_key_first($api_performance);
    }

    private function getAPIPerformanceForProvider($provider_stats)
    {
        // Datos históricos o estimados de rendimiento por API
        return [
            'google_vision' => [
                'engine' => 'google_vision',
                'avg_confidence' => 0.96,
                'avg_processing_time' => 2.5,
                'cost_per_request' => $this->api_costs['google_vision'] / 1000
            ],
            'azure_cognitive' => [
                'engine' => 'azure_cognitive',
                'avg_confidence' => 0.94,
                'avg_processing_time' => 3.2,
                'cost_per_request' => $this->api_costs['azure_cognitive'] / 1000
            ],
            'aws_textract' => [
                'engine' => 'aws_textract',
                'avg_confidence' => 0.97,
                'avg_processing_time' => 4.1,
                'cost_per_request' => $this->api_costs['aws_textract'] / 1000
            ]
        ];
    }

    public function logAPIUsage($engine, $cost, $confidence_achieved, $proveedor_id)
    {
        $query = "
            INSERT INTO ocr_api_usage 
            (engine_used, api_cost, confidence_achieved, proveedor_id, usage_date) 
            VALUES (?, ?, ?, ?, NOW())
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$engine, $cost, $confidence_achieved, $proveedor_id]);

        $this->current_month_spent += $cost;
    }

    public function getMonthlyReport()
    {
        $query = "
            SELECT 
                engine_used,
                COUNT(*) as usage_count,
                SUM(api_cost) as total_cost,
                AVG(confidence_achieved) as avg_confidence,
                (SUM(api_cost) / COUNT(*)) as cost_per_request
            FROM ocr_api_usage 
            WHERE YEAR(usage_date) = YEAR(NOW()) 
            AND MONTH(usage_date) = MONTH(NOW())
            GROUP BY engine_used
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $usage_by_engine = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_spent' => $this->current_month_spent,
            'budget_limit' => $this->budget_limit,
            'budget_remaining' => $this->budget_limit - $this->current_month_spent,
            'usage_by_engine' => $usage_by_engine,
            'recommendations' => $this->generateCostRecommendations()
        ];
    }

    private function generateCostRecommendations()
    {
        $recommendations = [];

        if ($this->current_month_spent > ($this->budget_limit * 0.8)) {
            $recommendations[] = "⚠️ Cerca del límite presupuestal. Considera usar solo Tesseract.";
        }

        if ($this->current_month_spent < ($this->budget_limit * 0.3)) {
            $recommendations[] = "💡 Presupuesto disponible. Puedes usar APIs premium para casos complejos.";
        }

        return $recommendations;
    }

    public function setBudgetLimit($new_limit)
    {
        $this->budget_limit = $new_limit;

        // Guardar en configuración
        $query = "
            INSERT INTO sistema_configuracion (clave, valor, updated_at) 
            VALUES ('ocr_budget_limit', ?, NOW())
            ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$new_limit]);
    }
}

// EJEMPLO DE USO INTELIGENTE
class SmartOCRProcessor
{
    private $cost_optimizer;
    private $tesseract;
    private $premium_apis;

    public function __construct($db)
    {
        $this->cost_optimizer = new CostOptimizer($db);
        $this->tesseract = new TesseractOCR();
        $this->premium_apis = [
            'google_vision' => new GoogleVisionOCR(),
            'azure_cognitive' => new AzureCognitiveOCR()
        ];
    }

    public function processDocument($image_path, $proveedor_id, $priority = 'normal')
    {
        echo "🔍 Procesando documento con optimización de costos...\n";

        // PASO 1: Siempre empezar con Tesseract (gratis)
        $tesseract_result = $this->tesseract->extractText($image_path);
        echo "📋 Tesseract confidence: " . ($tesseract_result['confidence'] * 100) . "%\n";

        // PASO 2: Decidir si usar API premium
        $decision = $this->cost_optimizer->shouldUsePremiumAPI(
            $tesseract_result['confidence'],
            $proveedor_id,
            $priority
        );

        if ($decision['use_premium']) {
            echo "💰 Usando API premium: " . $decision['recommended_engine'] . "\n";
            echo "💵 Costo estimado: $" . number_format($decision['estimated_cost'], 4) . "\n";

            // Usar API premium
            $premium_engine = $this->premium_apis[$decision['recommended_engine']];
            $premium_result = $premium_engine->extractText($image_path);

            // Log del uso
            $this->cost_optimizer->logAPIUsage(
                $decision['recommended_engine'],
                $decision['estimated_cost'],
                $premium_result['confidence'],
                $proveedor_id
            );

            return $premium_result;
        } else {
            echo "🆓 Usando solo Tesseract. Razón: " . $decision['reason'] . "\n";
            return $tesseract_result;
        }
    }
}

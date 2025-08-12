<?php
// Cliente simple para Azure Computer Vision OCR (Read API v3.2)
// Uso: $text = AzureOCRClient::extractText($filePath);
class AzureOCRClient {
    private $endpoint;
    private $key;
    private $lang;

    public function __construct($config = null) {
        if ($config === null) {
            $config = require __DIR__ . '/../../../config/azure_ocr.php';
        }
        $this->endpoint = rtrim($config['endpoint'], '/');
        $this->key = $config['key'];
        $this->lang = $config['lang'] ?? 'es';
    }

    public function extractText($filePath) {
        if (!$this->endpoint || !$this->key) {
            throw new Exception('Azure OCR no está configurado.');
        }
        $url = $this->endpoint . '/vision/v3.2/read/analyze?language=' . urlencode($this->lang);
        $data = file_get_contents($filePath);
        $headers = [
            'Ocp-Apim-Subscription-Key: ' . $this->key,
            'Content-Type: application/octet-stream',
        ];
        // 1. Enviar imagen/PDF para análisis
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers_str = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code != 202) {
            throw new Exception('Error enviando a Azure OCR: ' . $body);
        }
        // 2. Obtener URL de operación
        if (!preg_match('/Operation-Location: (.*)/i', $headers_str, $matches)) {
            throw new Exception('No se recibió Operation-Location de Azure.');
        }
        $operationUrl = trim($matches[1]);
        // 3. Poll hasta que esté listo
        $tries = 0;
        do {
            usleep(800000); // 0.8s
            $ch = curl_init($operationUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Ocp-Apim-Subscription-Key: ' . $this->key
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($http_code != 200) {
                throw new Exception('Error consultando resultado Azure OCR: ' . $result);
            }
            $json = json_decode($result, true);
            $status = $json['status'] ?? '';
            $tries++;
        } while ($status === 'running' || $status === 'notStarted' && $tries < 15);
        if ($status !== 'succeeded') {
            throw new Exception('Azure OCR no finalizó correctamente: ' . $status);
        }
        // 4. Extraer texto
        $lines = [];
        foreach (($json['analyzeResult']['readResults'] ?? []) as $page) {
            foreach (($page['lines'] ?? []) as $line) {
                $lines[] = $line['text'];
            }
        }
        return implode("\n", $lines);
    }
}

<?php
// Cliente simple para OCR.space API
// Uso: $text = OCRSpaceClient::extractText($filePath);
class OCRSpaceClient {
    private $apikey;
    private $endpoint;
    private $lang;

    public function __construct($config = null) {
        if ($config === null) {
            $config = require __DIR__ . '/../../../config/ocrspace.php';
        }
        $this->apikey = $config['apikey'];
        $this->endpoint = $config['endpoint'];
        $this->lang = $config['lang'] ?? 'spa';
    }

    public function extractText($filePath) {
        if (!$this->apikey) {
            throw new Exception('OCR.space API Key no configurada.');
        }
        $post = [
            'language' => $this->lang,
            'isOverlayRequired' => 'false',
            'OCREngine' => 2,
        ];
        $cfile = new CURLFile($filePath);
        $post['file'] = $cfile;
        $headers = [
            'apikey: ' . $this->apikey
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) {
            throw new Exception('Error en OCR.space: ' . $err);
        }
        $json = json_decode($result, true);
        if (!isset($json['ParsedResults'][0]['ParsedText'])) {
            throw new Exception('Respuesta inesperada de OCR.space: ' . $result);
        }
        return $json['ParsedResults'][0]['ParsedText'];
    }
}

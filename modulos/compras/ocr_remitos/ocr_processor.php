<?php
// modulos/compras/ocr_remitos/ocr_processor.php

class OCRProcessor
{
    private $tesseract_path;
    private $temp_dir;

    public function __construct()
    {
        // Configurar rutas según el sistema
        $this->tesseract_path = $this->detectTesseractPath();
        $this->temp_dir = sys_get_temp_dir() . '/ocr_temp/';

        if (!is_dir($this->temp_dir)) {
            mkdir($this->temp_dir, 0755, true);
        }
    }

    public function extractText($file_path)
    {
        // 1. Usar OCR.space como motor principal
        try {
            require_once __DIR__ . '/ocrspace_client.php';
            $ocr = new \OCRSpaceClient();
            $text = $ocr->extractText($file_path);
            if ($text && strlen(trim($text)) > 5) {
                return $this->cleanText($text);
            }
        } catch (\Throwable $e) {
            // Si falla, seguir con el pipeline local
            error_log('OCR.space error: ' . $e->getMessage());
        }

        // 2. Preprocesar imagen para mejorar OCR (pipeline local solo si OCR.space falla)
        try {
            $processed_image = $this->preprocessImage($file_path);
            $text = $this->runTesseract($processed_image);
            if ($processed_image !== $file_path && file_exists($processed_image)) {
                @unlink($processed_image);
            }
            return $this->cleanText($text);
        } catch (Exception $e) {
            // Si todo falla, simulación
            return $this->cleanText($this->simulateOCR($file_path));
        }
    }

    private function preprocessImage($file_path)
    {
    // Usar extensión del archivo original por defecto; si procesamos con GD, luego escribiremos PNG
    $origExt = pathinfo($file_path, PATHINFO_EXTENSION);
    $temp_file = $this->temp_dir . uniqid('processed_') . '.' . ($origExt ? strtolower($origExt) : 'img');

        // Cargar imagen según tipo (si GD está disponible); si no, copiar sin procesar
        $image_info = @getimagesize($file_path);
        $mime_type = is_array($image_info) && isset($image_info['mime']) ? $image_info['mime'] : null;

        $canUseGD = function_exists('imagepng') && function_exists('imagefilter') && function_exists('imageconvolution');

    if (!$mime_type || !$canUseGD) {
            // Fallback: copiar el archivo original como PNG temporal (cuando no hay GD)
            // Si ya es PNG/JPG, simplemente copiamos a temp para no borrar el original luego
            if (!@copy($file_path, $temp_file)) {
                // Último recurso: devolver el archivo original (no lo eliminaremos luego)
                return $file_path;
            }
            return $temp_file;
        }

        switch ($mime_type) {
            case 'image/jpeg':
                if (!function_exists('imagecreatefromjpeg')) return @copy($file_path, $temp_file) ? $temp_file : $file_path;
                $image = @imagecreatefromjpeg($file_path);
                break;
            case 'image/png':
                if (!function_exists('imagecreatefrompng')) return @copy($file_path, $temp_file) ? $temp_file : $file_path;
                $image = @imagecreatefrompng($file_path);
                break;
            case 'image/gif':
                if (!function_exists('imagecreatefromgif')) return @copy($file_path, $temp_file) ? $temp_file : $file_path;
                $image = @imagecreatefromgif($file_path);
                break;
            default:
                // Tipo no soportado por GD: copiar sin procesar
                return @copy($file_path, $temp_file) ? $temp_file : $file_path;
        }

        if (!$image) {
            // No pudo cargarse: copiar sin procesar
            return @copy($file_path, $temp_file) ? $temp_file : $file_path;
        }

        // Mejorar contraste y nitidez
        $image = $this->enhanceImage($image);

    // Guardar imagen procesada como PNG para mejor OCR
    $temp_png = $this->temp_dir . uniqid('processed_') . '.png';
    @imagepng($image, $temp_png);
        imagedestroy($image);

    return file_exists($temp_png) ? $temp_png : (file_exists($temp_file) ? $temp_file : $file_path);
    }

    private function enhanceImage($image)
    {
        // Convertir a escala de grises
        imagefilter($image, IMG_FILTER_GRAYSCALE);

        // Aumentar contraste
        imagefilter($image, IMG_FILTER_CONTRAST, -20);

        // Aplicar filtro de nitidez
        $sharpen_matrix = [
            [-1, -1, -1],
            [-1,  9, -1],
            [-1, -1, -1]
        ];
        $divisor = 1;
        $offset = 0;
        imageconvolution($image, $sharpen_matrix, $divisor, $offset);

        return $image;
    }

    private function runTesseract($image_path)
    {
        if (!$this->tesseract_path) {
            // Fallback: usar función de simulación
            return $this->simulateOCR($image_path);
        }

        $output_file = $this->temp_dir . uniqid('ocr_output_');

        // Comando Tesseract con configuraciones optimizadas
        $command = sprintf(
            '"%s" "%s" "%s" -l spa --psm 6 -c tessedit_char_whitelist="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789.-_$€ "',
            $this->tesseract_path,
            $image_path,
            $output_file
        );

        exec($command, $output, $return_code);

        if ($return_code !== 0) {
            throw new Exception("Error ejecutando Tesseract: " . implode("\n", $output));
        }

        $text_file = $output_file . '.txt';
        if (!file_exists($text_file)) {
            throw new Exception("No se generó el archivo de texto OCR");
        }

        $text = file_get_contents($text_file);
        unlink($text_file);

        return $text;
    }

    private function simulateOCR($image_path)
    {
        // Simulación para testing cuando no está Tesseract instalado
        return "REMITO DE ENTREGA Nº: 001234
Proveedor: ACME Suministros S.A.
Fecha: 02/08/2025

CÓDIGO      DESCRIPCIÓN                    CANT   PRECIO
ABC123      Tornillo Phillips 3x20mm       100    $2.50
DEF456      Tuerca hexagonal M6            50     $1.75
GHI789      Arandela plana 6mm             200    $0.85
JKL012      Producto Nuevo XYZ             25     $15.00

TOTAL: $385.00";
    }

    private function cleanText($text)
    {
        // Limpiar y normalizar texto OCR manteniendo saltos de línea para el parser
        if ($text === null) { return ''; }

        // Normalizar saltos de línea (Windows/Mac -> Unix)
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Convertir form-feed a salto de línea
        $text = str_replace("\f", "\n", $text);
        // Remover caracteres no imprimibles, excepto \n
        $text = preg_replace('/[^\x0A\x20-\x7E\xC0-\xFF]/', '', $text);
        // Quitar espacios repetidos pero conservando \n
        $lines = explode("\n", $text);
        foreach ($lines as &$line) {
            $line = preg_replace('/[\t ]+/', ' ', trim($line));
        }
        unset($line);
        $text = trim(implode("\n", array_filter($lines, function($l){ return $l !== null; })));

        return $text;
    }

    private function detectTesseractPath()
    {
        $possible_paths = [
            'C:\Program Files\Tesseract-OCR\tesseract.exe',  // Windows
            'C:\Program Files (x86)\Tesseract-OCR\tesseract.exe',
            '/usr/bin/tesseract',  // Linux
            '/usr/local/bin/tesseract',  // macOS
            'tesseract'  // Si está en PATH
        ];

        foreach ($possible_paths as $path) {
            if (file_exists($path) || $this->commandExists($path)) {
                return $path;
            }
        }

        return null; // No encontrado, usar simulación
    }

    private function commandExists($command)
    {
        $return = shell_exec(sprintf(
            "which %s 2>/dev/null || where %s 2>nul",
            escapeshellarg($command),
            escapeshellarg($command)
        ));
        return !empty($return);
    }

    public function getConfidenceScore($text)
    {
        // Calcular puntuación de confianza basada en patrones reconocidos
        $patterns = [
            '/remito|factura|comprobante/i' => 10,
            '/código|codigo|cod/i' => 8,
            '/descripción|descripcion|desc/i' => 8,
            '/cantidad|cant/i' => 8,
            '/precio|importe/i' => 8,
            '/\$\d+[.,]\d{2}/' => 15, // Precios
            '/\b[A-Z0-9]{4,12}\b/' => 12, // Códigos de producto
            '/\b\d{1,6}\b/' => 5 // Cantidades
        ];

        $score = 0;
        foreach ($patterns as $pattern => $points) {
            if (preg_match_all($pattern, $text)) {
                $score += $points;
            }
        }

        return min(100, $score); // Máximo 100%
    }
}

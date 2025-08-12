<?php
// Configuración de Azure OCR
// Completa estos valores con tu clave y endpoint de Azure Computer Vision
return [
    'endpoint' => getenv('AZURE_OCR_ENDPOINT') ?: '', // Ejemplo: https://<tu-recurso>.cognitiveservices.azure.com/
    'key' => getenv('AZURE_OCR_KEY') ?: '', // Tu clave de Azure Computer Vision
    'lang' => 'es', // Idioma preferido
];

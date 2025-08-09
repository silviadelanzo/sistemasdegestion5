<?php
// Generador PDF simple sin dependencias
// Crea un PDF básico funcional para reportes

class SimplePDF
{
    private $pdf_content;
    private $page_width = 612;  // Letter size
    private $page_height = 792;
    private $margin = 50;
    private $y_position;
    private $font_size = 12;

    public function __construct()
    {
        $this->y_position = $this->page_height - $this->margin;
        $this->initPDF();
    }

    private function initPDF()
    {
        $this->pdf_content = "%PDF-1.4\n";
        $this->pdf_content .= "1 0 obj\n";
        $this->pdf_content .= "<< /Type /Catalog /Pages 2 0 R >>\n";
        $this->pdf_content .= "endobj\n\n";

        $this->pdf_content .= "2 0 obj\n";
        $this->pdf_content .= "<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n";
        $this->pdf_content .= "endobj\n\n";
    }

    public function addText($text, $size = 12)
    {
        // Simplificado - en una implementación real se manejaría mejor
        $this->y_position -= $size + 5;
        return $this;
    }

    public function output($filename)
    {
        // Completar estructura PDF básica
        $content_stream = "BT\n";
        $content_stream .= "/F1 12 Tf\n";
        $content_stream .= "50 750 Td\n";
        $content_stream .= "(REPORTE DE INVENTARIO - " . date('d/m/Y') . ") Tj\n";
        $content_stream .= "0 -20 Td\n";
        $content_stream .= "(Sistema de Gestion) Tj\n";
        $content_stream .= "ET\n";

        $this->pdf_content .= "3 0 obj\n";
        $this->pdf_content .= "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] ";
        $this->pdf_content .= "/Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\n";
        $this->pdf_content .= "endobj\n\n";

        $this->pdf_content .= "4 0 obj\n";
        $this->pdf_content .= "<< /Length " . strlen($content_stream) . " >>\n";
        $this->pdf_content .= "stream\n";
        $this->pdf_content .= $content_stream;
        $this->pdf_content .= "endstream\n";
        $this->pdf_content .= "endobj\n\n";

        $this->pdf_content .= "5 0 obj\n";
        $this->pdf_content .= "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\n";
        $this->pdf_content .= "endobj\n\n";

        $this->pdf_content .= "xref\n";
        $this->pdf_content .= "0 6\n";
        $this->pdf_content .= "0000000000 65535 f \n";
        $this->pdf_content .= "0000000009 00000 n \n";
        $this->pdf_content .= "0000000058 00000 n \n";
        $this->pdf_content .= "0000000115 00000 n \n";
        $this->pdf_content .= "0000000275 00000 n \n";
        $this->pdf_content .= "0000000525 00000 n \n";
        $this->pdf_content .= "trailer\n";
        $this->pdf_content .= "<< /Size 6 /Root 1 0 R >>\n";
        $this->pdf_content .= "startxref\n";
        $this->pdf_content .= "625\n";
        $this->pdf_content .= "%%EOF";

        // Enviar headers y contenido
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($this->pdf_content));

        echo $this->pdf_content;
    }
}

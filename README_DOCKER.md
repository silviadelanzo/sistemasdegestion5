# Sistema de Gestión 5 - Contenedores

Servicios:
- app: PHP 8.1 + Apache con Tesseract (spa) y pdftotext
- db: MariaDB 10.11
- phpMyAdmin: UI de base de datos

Puertos:
- app: http://localhost:8080
- phpMyAdmin: http://localhost:8081

Comandos rápidos:
```powershell
# Levantar todo
docker-compose up -d --build

# Ver logs app
docker-compose logs -f app

# Apagar
docker-compose down
```

Variables de entorno (app):
- DB_HOST=db
- DB_USER=root
- DB_PASS=root
- DB_NAME=sistemasia_inventpro

Notas OCR:
- La imagen ya incluye Tesseract (español) y Poppler (pdftotext).
- Para PDFs escaneados sin texto, el pipeline rasteriza si Imagick está disponible; si no, usa pdftotext cuando haya texto embebido.

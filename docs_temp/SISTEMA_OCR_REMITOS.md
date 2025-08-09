# 🤖 SISTEMA AUTOMATIZADO DE LECTURA DE REMITOS

## 📋 COMPONENTES PRINCIPALES

### 1. **MÓDULO OCR (Reconocimiento de Texto)**
- **Tesseract OCR** - Motor de reconocimiento gratuito
- **Google Vision API** - OCR avanzado (opción premium)
- **Preprocessing de imágenes** - Mejora calidad del escaneo

### 2. **MÓDULO AI PARSER (Interpretación Inteligente)**
- **Plantillas de proveedores** - Configuración por proveedor
- **Machine Learning** - Aprendizaje de nuevos formatos
- **Extracción de campos** - Código, descripción, cantidad, precio

### 3. **MÓDULO COMPARACIÓN DE PRODUCTOS**
- **Matching inteligente** - Comparación por código/descripción
- **Fuzzy matching** - Productos con nombres similares
- **Categorización automática** - Nuevos vs existentes

### 4. **MÓDULO ACTUALIZACIÓN AUTOMÁTICA**
- **Stock existente** - Actualización automática
- **Productos nuevos** - Cola de revisión administrativa
- **Alertas y reportes** - Notificaciones al administrador

## 🔄 FLUJO DE TRABAJO

```
[Remito Escaneado] 
       ↓
[Preprocessing Imagen]
       ↓
[OCR - Extracción Texto]
       ↓
[AI Parser - Interpretación]
       ↓
[Comparación con BD]
       ↓
[Actualización Automática] + [Cola Revisión]
```

## 📁 ESTRUCTURA DE ARCHIVOS

```
modulos/compras/ocr_remitos/
├── scanner.php              # Interfaz de carga
├── ocr_processor.php        # Procesamiento OCR
├── ai_parser.php           # Interpretación inteligente
├── product_matcher.php     # Comparación productos
├── auto_updater.php        # Actualización automática
├── templates/              # Plantillas proveedores
│   ├── proveedor_1.json
│   ├── proveedor_2.json
│   └── generic.json
├── uploads/               # Remitos escaneados
├── processed/            # Archivos procesados
└── logs/                # Logs del sistema
```

## 🎛️ CONFIGURACIÓN POR PROVEEDOR

### Ejemplo: Proveedor "ACME Suministros"
```json
{
  "proveedor_id": 1,
  "nombre": "ACME Suministros",
  "formato_remito": {
    "tipo": "tabla",
    "campos": {
      "codigo": {
        "posicion": "columna_1",
        "regex": "^[A-Z0-9]{4,10}$"
      },
      "descripcion": {
        "posicion": "columna_2",
        "max_length": 100
      },
      "cantidad": {
        "posicion": "columna_3",
        "tipo": "numerico"
      },
      "precio": {
        "posicion": "columna_4",
        "tipo": "decimal"
      }
    },
    "encabezado_detectar": "REMITO DE ENTREGA",
    "tabla_inicio": "CÓDIGO.*DESCRIPCIÓN.*CANT",
    "tabla_fin": "TOTAL"
  }
}
```

## 🤖 ALGORITMO DE MATCHING

### 1. **Matching Exacto**
- Código de producto idéntico
- EAN/Código de barras

### 2. **Matching Inteligente** 
- Similitud de descripción (>85%)
- Coincidencia de marca + modelo
- Análisis semántico

### 3. **Productos Nuevos**
- Sin coincidencias en BD
- Requieren revisión manual
- Auto-categorización sugerida

## 📊 REPORTES AUTOMÁTICOS

- **Productos actualizados** automáticamente
- **Productos nuevos** en cola de revisión
- **Errores de procesamiento** 
- **Estadísticas de precisión** del OCR
- **Alertas de discrepancias** de precios

## 💡 CARACTERÍSTICAS AVANZADAS

- **Aprendizaje automático** de nuevos formatos
- **Detección de errores** de OCR
- **Validación cruzada** con órdenes de compra
- **Integración con códigos de barras**
- **Procesamiento por lotes**

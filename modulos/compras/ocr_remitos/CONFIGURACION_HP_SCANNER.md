# CONFIGURACIÓN HP SCANNER PARA OCR AUTOMÁTICO

## 🖨️ DISPOSITIVO CONFIRMADO
- **Modelo**: HP Ink Tank Wireless 410 Series
- **IP**: 192.168.0.100 (confirmado activo)
- **Interfaz**: HP Smart (accesible via web)
- **Capacidades**: Escaneo, impresión, wireless

## 📋 CONFIGURACIÓN PASO A PASO

### 1. Configurar Carpeta de Destino
```
Carpeta del sistema: C:\xampp\htdocs\sistemadgestion5\assets\scanner_input\
```

### 2. HP Smart Configuration
1. Abrir HP Smart en el ordenador
2. Conectar a HP Ink Tank Wireless 410
3. Ir a "Escanear" > "Configuración"
4. Establecer destino: "Carpeta específica"
5. Seleccionar: `C:\xampp\htdocs\sistemadgestion5\assets\scanner_input\`
6. Formato: PDF o JPG
7. Calidad: Alta (300 DPI mínimo)
8. Guardar configuración

### 3. Configuración de Red
- **Scanner IP**: 192.168.0.100
- **Servidor Web**: 192.168.0.106:80 (XAMPP)
- **Red**: 192.168.0.x/24
- **Protocolo**: HTTP/WebDAV para transferencia

## 🔄 FLUJO AUTOMÁTICO

### Proceso de Escaneo
1. **Colocar documento** en HP scanner
2. **Presionar botón escanear** en dispositivo o HP Smart
3. **Archivo se guarda** automáticamente en `scanner_input/`
4. **Monitor PHP detecta** archivo nuevo cada 30 segundos
5. **OCR procesa** automáticamente el documento
6. **Sistema clasifica** (compra/inventario)
7. **Resultados aparecen** en centro de control

### Tipos de Documentos Soportados
- ✅ **Remitos de compra** → Procesamiento de compras
- ✅ **Facturas** → Procesamiento de compras  
- ✅ **Listas de inventario** → Conteo de stock
- ✅ **Documentos mixtos** → Detección automática

## 🛠️ MÉTODOS DE INTEGRACIÓN

### Método 1: Monitoreo de Carpeta (IMPLEMENTADO)
- **Archivo**: `hp_scanner_monitor.php`
- **Función**: Revisa carpeta cada 30 segundos
- **Ventajas**: Simple, no requiere configuración especial del scanner
- **Estado**: ✅ Funcional

### Método 2: HP Smart API (FUTURO)
- **Requiere**: SDK de HP Smart
- **Función**: Integración directa con aplicación
- **Ventajas**: Tiempo real, metadata completa
- **Estado**: 🔄 En investigación

### Método 3: Email/Cloud (ALTERNATIVO)
- **Función**: Scanner envía por email
- **Monitoreo**: IMAP para recibir archivos
- **Ventajas**: Funciona remotamente
- **Estado**: 📋 Planificado

## 🎯 CONFIGURACIÓN RECOMENDADA

### Calidad de Escaneo
```
Resolución: 300 DPI
Formato: PDF (para OCR) o JPG (para procesamiento rápido)
Color: Color (para mejor detección)
Compresión: Media (balance calidad/tamaño)
```

### Nomenclatura de Archivos
```
Remitos: REMITO_YYYYMMDD_HHMMSS.pdf
Inventario: INVENTARIO_YYYYMMDD_HHMMSS.pdf
Facturas: FACTURA_YYYYMMDD_HHMMSS.pdf
```

### Horarios de Monitoreo
```
Horario comercial: Cada 30 segundos
Fuera de horario: Cada 5 minutos
Modo manual: Bajo demanda
```

## 🚀 INSTRUCCIONES DE USO

### Para el Usuario
1. **Colocar documento** en el scanner HP
2. **Abrir HP Smart** en el ordenador
3. **Seleccionar "Escanear"**
4. **Verificar destino**: `scanner_input/`
5. **Presionar "Escanear"**
6. **Esperar confirmación** en monitor web
7. **Revisar resultados** en centro de control

### Para el Administrador
1. **Acceder** a `hp_scanner_monitor.php`
2. **Verificar estado** del scanner (luz verde)
3. **Monitorear estadísticas** diarias
4. **Revisar archivos** procesados
5. **Activar monitor automático** si es necesario

## 🔧 TROUBLESHOOTING

### Scanner No Detectado
```bash
# Verificar conectividad
ping 192.168.0.100

# Verificar puerto web
http://192.168.0.100
```

### Archivos No Procesan
1. Verificar permisos carpeta `scanner_input/`
2. Comprobar formato de archivo (PDF/JPG)
3. Revisar logs en monitor
4. Verificar espacio en disco

### Errores de OCR
1. Verificar calidad de escaneo (>= 300 DPI)
2. Comprobar iluminación del documento
3. Verificar que el texto sea legible
4. Probar con diferentes formatos

## 📊 MÉTRICAS DE RENDIMIENTO

### Objetivos
- **Precisión OCR**: > 95%
- **Tiempo respuesta**: < 60 segundos
- **Detección automática**: 100%
- **Uptime scanner**: > 99%

### Monitoreo
- Dashboard en tiempo real
- Estadísticas diarias/semanales
- Alertas por errores
- Histórico de procesamiento

## 🎮 DEMO Y SIMULACIÓN

### Archivos de Prueba
```
assets/demo_docs/
├── remito_sample.pdf
├── factura_sample.pdf
├── inventario_sample.pdf
└── mixed_document.pdf
```

### Secuencia de Demo
1. Mostrar monitor en tiempo real
2. Escanear documento de prueba
3. Ver procesamiento automático
4. Revisar resultados en centro de control
5. Verificar base de datos actualizada

---

**🎯 RESULTADO**: Sistema completamente automatizado que convierte el HP scanner en una herramienta OCR empresarial con procesamiento inteligente y centro de control web.

## Tareas Pendientes (Próxima Sesión)

### 1. Conectar Gráficos del Dashboard a la Base de Datos:
- **Prioridad Alta:** Reemplazar los datos simulados de los gráficos en `paneldecontrol.php` con datos reales.
  - **Gráfico de Ventas del Mes:** Crear un script PHP que calcule las ventas por semana o por día para el mes actual y conectarlo al gráfico de barras.
  - **Gráfico de Ventas Anuales:** Modificar el script para que obtenga las ventas totales de cada mes del año actual.
  - **Tarjetas de Estadísticas:** Conectar las tarjetas (Ventas del Mes, Productos Bajos de Stock, Nuevos Clientes) a consultas reales de la base de datos.

### 2. Sistema de Pedidos con OCR:
- **Prioridad Media:** Continuar con el desarrollo del sistema de procesamiento de pedidos.
  - **Crear Mockup del Formulario:** Diseñar y generar el archivo HTML con el borrador visual del formulario de pedidos optimizado para OCR, como se discutió.
  - **Implementar Lógica de OCR:** Una vez aprobado el formulario, empezar a desarrollar la lógica para recibir la imagen, enviarla a un servicio de OCR y procesar la respuesta para generar un pedido.

### 3. Funcionalidades Adicionales y Pruebas:
- **Prioridad Baja:** Tareas de mejora y mantenimiento.
  - **Configurar Cron Job:** Recordar al usuario la necesidad de configurar el cron job para `cron_agenda_alerts.php` en su servidor para el envío de alertas automáticas.
  - **Pruebas Exhaustivas:** Realizar pruebas completas de todas las funcionalidades implementadas, especialmente la agenda y el dashboard.
  - **Notificaciones Avanzadas:** Explorar opciones para notificaciones más allá del correo electrónico (ej. notificaciones internas en el sistema).

### 4. Módulo de Compras:
- **Prioridad Alta:** Corregir errores y mejorar el layout en `modulos/compras/compras.php`.
  - Ajustar el tamaño de las tarjetas de resumen.
  - Resolver el error `Undefined variable $compras_pendientes` y `number_format()` deprecation.
  - Rediseñar la sección de filtros para que ocupe una sola línea y optimizar el espacio.

### 5. Transición a Sistema Monocliente:

- **Prioridad Muy Alta:** Eliminar todas las funcionalidades y referencias relacionadas con el entorno multicliente.
  - Eliminar código y lógica de la aplicación que maneje `cuenta_id` o múltiples clientes.
  - Eliminar la tabla `cuentas` y cualquier columna `cuenta_id` de otras tablas de la base de datos.
  - Asegurar que el sistema opere exclusivamente para un único cliente.

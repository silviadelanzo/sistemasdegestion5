<?php
require_once 'config/config.php';

// Este script está diseñado para ser ejecutado por un cron job
// No debe ser accedido directamente desde el navegador
if (php_sapi_name() !== 'cli') {
    // Opcional: redirigir o mostrar un error si se accede vía web
    // header('Location: /error.php');
    // exit;
}

// Desactivar límite de tiempo para scripts largos si fuera necesario
set_time_limit(0);

// Conectar a la base de datos
try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    error_log("CRON ERROR: No se pudo conectar a la base de datos: " . $e->getMessage());
    exit(1); // Salir con código de error
}

// Definir el rango de tiempo para buscar alertas (ej: próximas 24 horas)
$rango_horas = 24;
$ahora = date('Y-m-d H:i:s');
$futuro = date('Y-m-d H:i:s', strtotime("+{$rango_horas} hours"));

error_log("CRON INFO: Iniciando revisión de alertas de agenda entre {$ahora} y {$futuro}.");

try {
    // Seleccionar alertas y recordatorios pendientes que no han sido notificados
    $sql = "SELECT a.id, a.titulo, a.descripcion, a.fecha_inicio, u.email, u.username 
            FROM agenda a 
            JOIN usuarios u ON a.usuario_id = u.id 
            WHERE a.tipo IN ('alerta', 'recordatorio') 
            AND a.estado = 'pendiente' 
            AND a.notificacion_enviada = FALSE 
            AND a.fecha_inicio BETWEEN ? AND ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ahora, $futuro]);
    $alertas = $stmt->fetchAll();

    if (empty($alertas)) {
        error_log("CRON INFO: No se encontraron alertas pendientes para enviar.");
    }

    foreach ($alertas as $alerta) {
        $asunto = "Alerta de Agenda: " . $alerta['titulo'];
        $mensaje = "Hola " . htmlspecialchars($alerta['username']) . ",\n\n";
        $mensaje .= "Tienes una " . htmlspecialchars($alerta['tipo']) . " pendiente:\n\n";
        $mensaje .= "Título: " . htmlspecialchars($alerta['titulo']) . "\n";
        $mensaje .= "Descripción: " . htmlspecialchars($alerta['descripcion']) . "\n";
        $mensaje .= "Fecha/Hora: " . date('d/m/Y H:i', strtotime($alerta['fecha_inicio'])) . "\n\n";
        $mensaje .= "Por favor, revisa tu agenda en el sistema.\n";
        $mensaje .= "\nSaludos,\nTu Sistema de Gestión";

        $headers = 'From: no-reply@tusistema.com' . "\r\n" . 
                   'Reply-To: no-reply@tusistema.com' . "\r\n" . 
                   'X-Mailer: PHP/" . phpversion();

        // Intentar enviar el correo
        if (mail($alerta['email'], $asunto, $mensaje, $headers)) {
            error_log("CRON SUCCESS: Alerta ID {$alerta['id']} enviada a {$alerta['email']}.");
            // Marcar como notificado
            $update_sql = "UPDATE agenda SET notificacion_enviada = TRUE WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$alerta['id']]);
        } else {
            error_log("CRON ERROR: Falló el envío de alerta ID {$alerta['id']} a {$alerta['email']}.");
        }
    }

} catch (Exception $e) {
    error_log("CRON ERROR: Error durante el procesamiento de alertas: " . $e->getMessage());
    exit(1);
}

error_log("CRON INFO: Revisión de alertas de agenda finalizada.");
exit(0); // Salir con código de éxito
?>

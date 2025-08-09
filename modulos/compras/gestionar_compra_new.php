<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();
$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'crear':
            // Validaciones básicas
            $proveedor_id = $_POST['proveedor_id'] ?? '';
            $fecha_compra = $_POST['fecha_compra'] ?? '';
            $productos = $_POST['productos'] ?? [];

            if (empty($proveedor_id) || empty($fecha_compra) || empty($productos)) {
                throw new Exception('Proveedor, fecha de compra y productos son obligatorios');
            }

            // Iniciar transacción
            $pdo->beginTransaction();

            // Crear la compra
            $stmt = $pdo->prepare("
                INSERT INTO compras (
                    proveedor_id, numero_remito, fecha_compra, fecha_entrega_estimada, 
                    estado, observaciones, usuario_id, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $proveedor_id,
                $_POST['numero_remito'] ?? '',
                $fecha_compra,
                $_POST['fecha_entrega_estimada'] ?: null,
                $_POST['estado'] ?? 'pendiente',
                $_POST['observaciones'] ?? '',
                $_SESSION['user_id']
            ]);

            $compra_id = $pdo->lastInsertId();

            // Insertar productos de la compra
            $total_compra = 0;
            foreach ($productos as $producto) {
                if (empty($producto['producto_id']) || empty($producto['cantidad']) || empty($producto['precio_unitario'])) {
                    continue;
                }

                $cantidad = (float)$producto['cantidad'];
                $precio_unitario = (float)$producto['precio_unitario'];
                $subtotal = $cantidad * $precio_unitario;
                $total_compra += $subtotal;

                $stmt = $pdo->prepare("
                    INSERT INTO compra_detalles (
                        compra_id, producto_id, cantidad, precio_unitario, subtotal
                    ) VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $compra_id,
                    $producto['producto_id'],
                    $cantidad,
                    $precio_unitario,
                    $subtotal
                ]);
            }

            // Actualizar el total de la compra
            $stmt = $pdo->prepare("UPDATE compras SET total = ? WHERE id = ?");
            $stmt->execute([$total_compra, $compra_id]);

            $pdo->commit();

            $_SESSION['mensaje'] = 'Orden de compra creada exitosamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: compras.php');
            exit;

        case 'actualizar':
            $id = $_POST['id'] ?? 0;
            $proveedor_id = $_POST['proveedor_id'] ?? '';
            $fecha_compra = $_POST['fecha_compra'] ?? '';
            $productos = $_POST['productos'] ?? [];

            if (empty($id) || empty($proveedor_id) || empty($fecha_compra)) {
                throw new Exception('ID, proveedor y fecha de compra son obligatorios');
            }

            // Verificar que la compra existe
            $stmt = $pdo->prepare("SELECT id FROM compras WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                throw new Exception('Orden de compra no encontrada');
            }

            // Iniciar transacción
            $pdo->beginTransaction();

            // Actualizar la compra
            $stmt = $pdo->prepare("
                UPDATE compras SET 
                    proveedor_id = ?, numero_remito = ?, fecha_compra = ?, 
                    fecha_entrega_estimada = ?, estado = ?, observaciones = ?,
                    fecha_modificacion = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $proveedor_id,
                $_POST['numero_remito'] ?? '',
                $fecha_compra,
                $_POST['fecha_entrega_estimada'] ?: null,
                $_POST['estado'] ?? 'pendiente',
                $_POST['observaciones'] ?? '',
                $id
            ]);

            // Eliminar detalles existentes
            $stmt = $pdo->prepare("DELETE FROM compra_detalles WHERE compra_id = ?");
            $stmt->execute([$id]);

            // Insertar nuevos productos
            $total_compra = 0;
            foreach ($productos as $producto) {
                if (empty($producto['producto_id']) || empty($producto['cantidad']) || empty($producto['precio_unitario'])) {
                    continue;
                }

                $cantidad = (float)$producto['cantidad'];
                $precio_unitario = (float)$producto['precio_unitario'];
                $subtotal = $cantidad * $precio_unitario;
                $total_compra += $subtotal;

                $stmt = $pdo->prepare("
                    INSERT INTO compra_detalles (
                        compra_id, producto_id, cantidad, precio_unitario, subtotal
                    ) VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $id,
                    $producto['producto_id'],
                    $cantidad,
                    $precio_unitario,
                    $subtotal
                ]);
            }

            // Actualizar el total
            $stmt = $pdo->prepare("UPDATE compras SET total = ? WHERE id = ?");
            $stmt->execute([$total_compra, $id]);

            $pdo->commit();

            $_SESSION['mensaje'] = 'Orden de compra actualizada exitosamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: compras.php');
            exit;

        case 'eliminar':
            $id = $_POST['id'] ?? 0;

            if (empty($id)) {
                throw new Exception('ID de la compra es obligatorio');
            }

            // Verificar que la compra existe
            $stmt = $pdo->prepare("SELECT estado FROM compras WHERE id = ?");
            $stmt->execute([$id]);
            $compra = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$compra) {
                throw new Exception('Orden de compra no encontrada');
            }

            if ($compra['estado'] === 'recibida') {
                throw new Exception('No se puede eliminar una compra ya recibida');
            }

            // Iniciar transacción
            $pdo->beginTransaction();

            // Eliminar detalles
            $stmt = $pdo->prepare("DELETE FROM compra_detalles WHERE compra_id = ?");
            $stmt->execute([$id]);

            // Eliminar compra
            $stmt = $pdo->prepare("DELETE FROM compras WHERE id = ?");
            $stmt->execute([$id]);

            $pdo->commit();

            $_SESSION['mensaje'] = 'Orden de compra eliminada exitosamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: compras.php');
            exit;

        case 'cambiar_estado':
            $id = $_POST['id'] ?? 0;
            $nuevo_estado = $_POST['estado'] ?? '';

            if (empty($id) || empty($nuevo_estado)) {
                throw new Exception('ID y estado son obligatorios');
            }

            $estados_validos = ['pendiente', 'confirmada', 'parcial', 'recibida', 'cancelada'];
            if (!in_array($nuevo_estado, $estados_validos)) {
                throw new Exception('Estado no válido');
            }

            $stmt = $pdo->prepare("UPDATE compras SET estado = ?, fecha_modificacion = NOW() WHERE id = ?");
            $stmt->execute([$nuevo_estado, $id]);

            $_SESSION['mensaje'] = 'Estado de la compra actualizado exitosamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: compras.php');
            exit;

        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'error';

    if (isset($_POST['id']) && $_POST['id']) {
        header('Location: compra_form_new.php?id=' . $_POST['id']);
    } else {
        header('Location: compra_form_new.php');
    }
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['mensaje'] = 'Error de base de datos: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: compras.php');
    exit;
}

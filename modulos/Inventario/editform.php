<?php
require_once '../../config/config.php';

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

$errores = [];
$mensaje_exito = '';

$producto = null;
$producto_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($producto_id) {
    // Obtener datos del producto
    try {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errores[] = 'Error al cargar producto: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['producto_id'])) {
    $producto_id = intval($_POST['producto_id']);
    try {
        // Subida de imagen
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $codigo = $_POST['codigo'];
            $img_dir = __DIR__ . '/../../assets/img/productos/';
            if (!is_dir($img_dir)) @mkdir($img_dir, 0777, true);
            $destino = $img_dir . $codigo . '.jpg';
            $info = getimagesize($_FILES['foto']['tmp_name']);
            if ($info && ($info[2] === IMAGETYPE_JPEG || $info[2] === IMAGETYPE_PNG)) {
                // Convertir a JPG si es PNG
                if ($info[2] === IMAGETYPE_PNG) {
                    $img = imagecreatefrompng($_FILES['foto']['tmp_name']);
                    imagejpeg($img, $destino, 90);
                    imagedestroy($img);
                } else {
                    move_uploaded_file($_FILES['foto']['tmp_name'], $destino);
                }
                $mensaje_exito = 'Imagen actualizada correctamente para el producto ' . htmlspecialchars($codigo);
            } else {
                $errores[] = 'La imagen debe ser JPG o PNG.';
            }
        } else {
            $errores[] = 'No se seleccionó ninguna imagen o hubo un error en la subida.';
        }
    } catch (Exception $e) {
        $errores[] = 'Error al guardar imagen: ' . $e->getMessage();
    }
    // Recargar datos del producto
    try {
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Imagen de Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">Editar Imagen de Producto</h2>
    <?php if ($errores): ?>
        <div class="alert alert-danger">
            <?php foreach ($errores as $e) echo '<div>'.$e.'</div>'; ?>
        </div>
    <?php endif; ?>
    <?php if ($mensaje_exito): ?>
        <div class="alert alert-success"><?php echo $mensaje_exito; ?></div>
    <?php endif; ?>
    <?php if ($producto): ?>
    <form method="post" enctype="multipart/form-data" class="card p-4 bg-white">
        <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
        <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($producto['codigo']); ?>">
        <div class="mb-3">
            <label class="form-label">Código</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($producto['codigo']); ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($producto['nombre']); ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">Imagen actual</label><br>
            <?php 
            $img_path = '../../assets/img/productos/' . $producto['codigo'] . '.jpg';
            if (file_exists($img_path)) {
                echo '<img src="' . $img_path . '?t=' . time() . '" style="max-width:200px;max-height:200px;">';
            } else {
                echo '<span class="text-muted">No hay imagen</span>';
            }
            ?>
        </div>
        <div class="mb-3">
            <label class="form-label">Nueva imagen (JPG o PNG)</label>
            <input type="file" name="foto" accept="image/jpeg,image/png" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Actualizar imagen</button>
    </form>
    <?php else: ?>
        <div class="alert alert-warning">No se encontró el producto. Proporcione un ID válido en la URL (?id=).</div>
    <?php endif; ?>
</div>
</body>
</html>

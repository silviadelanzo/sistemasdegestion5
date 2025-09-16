<?php
require_once '../../config/config.php';
iniciarSesionSegura();
requireLogin('../../login.php');

$pdo = conectarDB();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include "../../config/navbar_code.php"; ?>
    <div class="container-fluid">
        <h1>Panel de Administración</h1>
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5>Usuarios</h5>
                        <a href="usuarios.php" class="btn btn-primary">Gestionar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5>Categorías</h5>
                        <a href="categorias_admin.php" class="btn btn-primary">Gestionar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5>Lugares</h5>
                        <a href="lugares_admin.php" class="btn btn-primary">Gestionar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5>Configuración</h5>
                        <a href="configuracion_sistema.php" class="btn btn-primary">Configurar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5>Auditoría</h5>
                        <a href="analizar_auditoria.php" class="btn btn-primary">Analizar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
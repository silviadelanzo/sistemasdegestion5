<?php
// Redirige a la nueva página de edición de remitos
header('Location: remito_editar.php' . (isset($_GET['id']) ? '?id=' . urlencode($_GET['id']) : ''));
exit;

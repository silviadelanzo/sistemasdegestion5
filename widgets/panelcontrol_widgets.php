<?php
// Recibir mes y año desde Ajax
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date("n");
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date("Y");

// Ajustar si se sale de rango
if ($mes < 1) { $mes = 12; $anio--; }
if ($mes > 12) { $mes = 1; $anio++; }

$diaActual = date("j");
$mesActual = date("n");
$anioActual = date("Y");

// Nombres de días y meses
$diasSemana = ["Do","Lu","Ma","Mi","Ju","Vi","Sa"]; // Changed to Spanish abbreviations
$nombresMes = [
    1=>"Enero",2=>"Febrero",3=>"Marzo",4=>"Abril",5=>"Mayo",6=>"Junio",
    7=>"Julio",8=>"Agosto",9=>"Septiembre",10=>"Octubre",11=>"Noviembre",12=>"Diciembre"
];

// Primer día del mes
$primerDiaMes = mktime(0, 0, 0, $mes, 1, $anio);
$numeroDias = date("t", $primerDiaMes);
$diaSemana = date("w", $primerDiaMes); // 0=domingo

// Mes anterior y siguiente
$mesAnterior = $mes - 1; $anioAnterior = $anio;
if ($mesAnterior < 1) { $mesAnterior = 12; $anioAnterior--; }

$mesSiguiente = $mes + 1; $anioSiguiente = $anio;
if ($mesSiguiente > 12) { $mesSiguiente = 1; $anioSiguiente++; }

// Conexión a la base de datos
require_once '../config/config.php'; // Adjust path as needed
$pdo = conectarDB();

// Buscar eventos del mes seleccionado
        $sql = "SELECT fecha_inicio, titulo, tipo FROM agenda 
        WHERE MONTH(fecha_inicio) = :mes AND YEAR(fecha_inicio) = :anio";
$stmt = $pdo->prepare($sql);
$stmt->execute(['mes' => $mes, 'anio' => $anio]);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convertir a array por día
$eventosPorDia = [];
foreach ($eventos as $e) {
    $dia = date("j", strtotime($e['fecha_inicio']));
    $eventosPorDia[$dia][] = ['titulo' => $e['titulo'], 'tipo' => $e['tipo']];
}

?>

<div class="calendario">
  <div class="nav">
    <a href="#" data-mes="<?php echo $mesAnterior; ?>" data-anio="<?php echo $anioAnterior; ?>" class="nav-link-prev">&lt;&lt;</a>
    <h3><?php echo $nombresMes[$mes]." ".$anio; ?></h3>
    <a href="#" data-mes="<?php echo $mesSiguiente; ?>" data-anio="<?php echo $anioSiguiente; ?>" class="nav-link-next">&gt;&gt;</a>
  </div>
  <table>
    <tr>
      <?php foreach($diasSemana as $dia) echo "<th>$dia</th>"; ?>
    </tr>
    <tr>
    <?php
    // espacios en blanco hasta el primer día
    for ($i = 0; $i < $diaSemana; $i++) {
        echo "<td></td>";
    }

    for ($dia = 1; $dia <= $numeroDias; $dia++) {
        if (($i % 7) == 0) echo "</tr><tr>"; // salto de fila
        $clase = ($dia == $diaActual && $mes == $mesActual && $anio == $anioActual) ? "class='hoy'" : "";
        $fullDate = $anio . '-' . sprintf('%02d', $mes) . '-' . sprintf('%02d', $dia);
        
        echo "<td $clase data-date='$fullDate'>$dia";
        
        // Mostrar eventos del día
        if (isset($eventosPorDia[$dia])) {
            foreach ($eventosPorDia[$dia] as $evento) {
                $tipoChar = '';
                $color = '';
                switch ($evento['tipo']) {
                    case 'reunion':
                        $tipoChar = 'E'; // Evento
                        $color = '#007bff'; // Blue
                        break;
                    case 'tarea':
                        $tipoChar = 'T'; // Tarea
                        $color = '#ffc107'; // Yellow
                        break;
                    case 'alerta':
                        $tipoChar = 'R'; // Recordatorio
                        $color = '#dc3545'; // Red
                        break;
                    default:
                        $tipoChar = 'E'; // Default to Event
                        $color = '#6c757d'; // Gray
                }
                echo "<div style='font-size:10px; color:" . $color . "; font-weight:bold;' title='" . htmlspecialchars($evento['titulo']) . "'>• $tipoChar</div>";
            }
        }
        
        echo "</td>";
        $i++;
    }
    ?>
    </tr>
  </table>
</div>

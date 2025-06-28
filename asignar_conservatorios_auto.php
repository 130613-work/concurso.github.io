<?php
include 'conexion.php';

// Solo ejecutar si concurso está cerrado
$concurso = $conexion->query("SELECT * FROM concurso WHERE id = 1")->fetch_assoc();
if ($concurso['estado'] !== 'cerrado') {
    exit("Las inscripciones aún están abiertas.");
}

$conservatorios = ['A', 'B', 'C'];
$resultado = $conexion->query("SELECT id FROM cuartetos WHERE estado = 'en_concurso' ORDER BY id ASC");
$cuartetos = $resultado->fetch_all(MYSQLI_ASSOC);
$total = count($cuartetos);

$cuarteto_index = 0;
$dia = 1;

// Calcular cantidad de días necesarios (12 cuartetos por día promedio)
$cuartetos_por_dia = 12;
$dias_necesarios = ceil($total / $cuartetos_por_dia);

for ($d = 1; $d <= $dias_necesarios; $d++) {
    $restantes = $total - $cuarteto_index;
    $en_este_dia = min($cuartetos_por_dia, $restantes);

    // Repartir los cuartetos del día entre conservatorios
    $base = floor($en_este_dia / 3); // mínimo por conservatorio
    $extra = $en_este_dia % 3;       // para distribuir de forma pareja

    $distribucion = array_fill_keys($conservatorios, $base);
    for ($i = 0; $i < $extra; $i++) {
        $distribucion[$conservatorios[$i]]++;
    }

    foreach ($conservatorios as $cons) {
        for ($j = 0; $j < $distribucion[$cons]; $j++) {
            if ($cuarteto_index >= $total) break;

            $cuarteto_id = $cuartetos[$cuarteto_index]['id'];
            $stmt = $conexion->prepare("UPDATE cuartetos SET dia = ?, conservatorio = ? WHERE id = ?");
            $stmt->bind_param("isi", $dia, $cons, $cuarteto_id);
            $stmt->execute();

            $cuarteto_index++;
        }
    }

    $dia++;
}

echo "Cuartetos asignados automáticamente en $dias_necesarios días de forma equilibrada.";
?>

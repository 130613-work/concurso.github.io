<?php
include 'verificar_rol.php';
verificarRol('administrador');
include 'conexion.php';

$concurso = $conexion->query("SELECT * FROM concurso WHERE id = 1")->fetch_assoc();
$mostrar_conservatorios = true;

if ($concurso['estado'] === 'cerrado') {
    $conservatorios = ['A', 'B', 'C'];
    $resultado = $conexion->query("SELECT id FROM cuartetos WHERE estado = 'en_concurso' ORDER BY id ASC");
    $cuartetos = $resultado->fetch_all(MYSQLI_ASSOC);
    $total = count($cuartetos);

    $cuarteto_index = 0;
    $dia = 1;

    while ($cuarteto_index < $total) {
        $por_dia = min($total - $cuarteto_index, 12);
        $por_cons = array_fill_keys($conservatorios, 0);
        $meta = floor($por_dia / 3);
        $resto = $por_dia % 3;

        foreach ($conservatorios as $i => $cons) {
            $por_cons[$cons] = $meta + ($resto > $i ? 1 : 0);
        }

        foreach ($por_cons as $cons => $cantidad) {
            for ($i = 0; $i < $cantidad; $i++) {
                if ($cuarteto_index >= $total) break;
                $id = $cuartetos[$cuarteto_index]['id'];
                $stmt = $conexion->prepare("UPDATE cuartetos SET dia = ?, conservatorio = ? WHERE id = ?");
                $stmt->bind_param("isi", $dia, $cons, $id);
                $stmt->execute();
                $cuarteto_index++;
            }
        }

        $dia++;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrador - Concurso</title>
    <link rel="stylesheet" href="style_admin.css">
</head>
<body>

<?php if (isset($_SESSION['mensaje'])): ?>
    <div class="mensaje"><?= $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></div>
<?php endif; ?>

<div class="logout-container">
    <a href="logout.php" class="boton-rojo">Cerrar sesión</a>
</div>

<h1>Vista Administrador</h1>
<p><strong>Estado:</strong> <?= strtoupper($concurso['estado']) ?> |
   <strong>Fase:</strong> <?= ($concurso['fase'] === 'eliminatoria1') ? 'Primera Eliminatoria' : (($concurso['fase'] === 'eliminatoria2') ? 'Segunda Eliminatoria' : (($concurso['fase'] === 'final') ? 'Final' : 'Desconocida')) ?>
</p>

<form method="post" action="cambiar_estado.php">
    <input type="hidden" name="csrf" value="<?= md5(session_id()) ?>">
    <button class="boton" name="accion" value="abrir">Abrir Inscripciones</button>
    <button class="boton" name="accion" value="cerrar">Cerrar Inscripciones</button>
    <button class="boton" name="accion" value="limpiar" onclick="return confirm('¿Seguro que deseas limpiar todo?')">Limpiar Datos</button>
</form>

<form method="post" action="actualizar_clasificados.php">
    <button class="boton" type="submit" onclick="return confirm('¿Actualizar clasificados de la fase actual?')">Actualizar Clasificados</button>
</form>

<form method="post" action="cambiar_fase.php">
    <input type="hidden" name="csrf" value="<?= md5(session_id()) ?>">
    <label for="fase">Cambiar fase:</label>
    <select name="fase" id="fase">
        <option value="eliminatoria1" <?= $concurso['fase'] === 'eliminatoria1' ? 'selected' : '' ?>>Primera Eliminatoria</option>
        <option value="eliminatoria2" <?= $concurso['fase'] === 'eliminatoria2' ? 'selected' : '' ?>>Segunda Eliminatoria</option>
        <option value="final" <?= $concurso['fase'] === 'final' ? 'selected' : '' ?>>Final</option>
    </select>
    <button class="boton" type="submit">Cambiar Fase</button>
</form>

<h2>Cuartetos por Conservatorio y Día</h2>
<?php
$asignados = $conexion->query("SELECT * FROM cuartetos WHERE dia IS NOT NULL AND conservatorio IS NOT NULL ORDER BY dia, conservatorio, nombre");
$por_dia = [];
while ($fila = $asignados->fetch_assoc()) {
    $por_dia[$fila['dia']][$fila['conservatorio']][] = $fila;
}

foreach ($por_dia as $dia => $cons_data):
    echo "<h2>Día $dia</h2>";
    echo "<div class='centrado'>
            <form method='get' action='asignar_jurados_conservatorio.php'>
                <input type='hidden' name='dia' value='$dia'>
                <button class='boton'>Asignar Jurados Día $dia</button>
            </form>
          </div>";
    echo "<div class='grid-conservatorios'>";
    foreach ($cons_data as $cons => $cuartetos):
        echo "<div class='tabla-conservatorio'><h3>Conservatorio $cons</h3>
              <table><thead>
              <tr><th>Nombre</th><th>Obra Obligatoria</th><th>Obra Libre</th><th>Profesor</th><th>Estado</th><th>Jurados</th></tr>
              </thead><tbody>";
        foreach ($cuartetos as $c) {
            $prof = $conexion->query("SELECT nombre FROM usuarios WHERE id = {$c['profesor_id']}")->fetch_assoc()['nombre'] ?? 'Desconocido';

            $jur_query = $conexion->query("SELECT u.nombre FROM jurado_conservatorio jc 
                                           JOIN usuarios u ON jc.jurado_id = u.id 
                                           WHERE jc.dia = {$c['dia']} AND jc.conservatorio = '{$c['conservatorio']}'");
            $jurados = [];
            while ($j = $jur_query->fetch_assoc()) {
                $jurados[] = htmlspecialchars($j['nombre']);
            }
            $jurados_txt = count($jurados) ? implode(', ', $jurados) : 'Sin asignar';

            echo "<tr><td>" . htmlspecialchars($c['nombre']) . "</td>
                      <td>" . htmlspecialchars($c['obra_obligatoria']) . "</td>
                      <td>" . htmlspecialchars($c['obra_libre']) . "</td>
                      <td>" . htmlspecialchars($prof) . "</td>
                      <td>" . ucfirst($c['estado']) . "</td>
                      <td>$jurados_txt</td></tr>";
        }
        echo "</tbody></table></div>";
    endforeach;
    echo "</div><hr>";
endforeach;
?>
</body>
</html>

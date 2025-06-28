<?php
include 'verificar_rol.php';
verificarRol('administrador');
include 'conexion.php';

$dia = isset($_GET['dia']) ? intval($_GET['dia']) : 0;
if ($dia <= 0) {
    die("Día inválido");
}

$conservatorios = ['A', 'B', 'C'];

// Obtener todos los jurados disponibles
$jurados = $conexion->query("SELECT id, nombre FROM usuarios WHERE rol = 'jurado'")->fetch_all(MYSQLI_ASSOC);

// Si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($conservatorios as $cons) {
        // Borrar asignaciones anteriores para ese día y conservatorio
        $conexion->query("DELETE FROM jurado_conservatorio WHERE dia = $dia AND conservatorio = '$cons'");

        // Insertar los seleccionados
        if (!empty($_POST["jurados_$cons"])) {
            $seleccionados = $_POST["jurados_$cons"];
            if (count($seleccionados) != 3) {
                echo "<p style='color:red'>Debes seleccionar exactamente 3 jurados para el conservatorio $cons.</p>";
                exit;
            }
            foreach ($seleccionados as $jid) {
                $stmt = $conexion->prepare("INSERT INTO jurado_conservatorio (jurado_id, dia, conservatorio) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $jid, $dia, $cons);
                $stmt->execute();
            }
        }
    }
    $_SESSION['mensaje'] = "Jurados asignados correctamente para el Día $dia.";
    header("Location: admin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Jurados</title>
    <link rel="stylesheet" href="style_admin.css">
</head>
<body>
<h1>Asignar Jurados - Día <?= $dia ?></h1>
<form method="post">
    <?php foreach ($conservatorios as $cons): ?>
        <h2>Conservatorio <?= $cons ?></h2>
        <?php foreach ($jurados as $j): ?>
            <label>
                <input type="checkbox" name="jurados_<?= $cons ?>[]" value="<?= $j['id'] ?>">
                <?= htmlspecialchars($j['nombre']) ?>
            </label><br>
        <?php endforeach; ?>
        <p><em>Seleccione exactamente 3 jurados</em></p>
        <hr>
    <?php endforeach; ?>
    <button type="submit" class="boton">Guardar Jurados</button>
    <a href="admin.php" class="boton-rojo">Cancelar</a>
</form>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
    header("Location: index.php");
    exit;
}
include 'conexion.php';

$idAlumno = $_SESSION['id'];

// Obtener el cuarteto y sus datos desde la tabla alumnos
$query = "SELECT c.nombre AS cuarteto, c.obra_libre, c.obra_obligatoria, u.nombre AS profesor, a.cuarteto_id
          FROM alumnos a
          JOIN cuartetos c ON a.cuarteto_id = c.id
          JOIN usuarios u ON c.profesor_id = u.id
          WHERE a.id = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $idAlumno);
$stmt->execute();
$resultado = $stmt->get_result();
$datosCuarteto = $resultado->fetch_assoc();

if (!$datosCuarteto) {
    echo "<p>No perteneces a ningún cuarteto registrado.</p>";
    exit;
}

// Obtener los integrantes del mismo cuarteto
$cuartetoId = $datosCuarteto['cuarteto_id'];
$stmtInt = $conexion->prepare("SELECT nombre, instrumento FROM alumnos WHERE cuarteto_id = ?");
$stmtInt->bind_param("i", $cuartetoId);
$stmtInt->execute();
$integrantes = $stmtInt->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Vista Alumno</title>
    <link rel="stylesheet" href="style_alumno.css">
</head>

<body>
    <h1 class="bienvenida">🎻 Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></h1>

    <div class="contenedor">
        <div class="tarjeta-alumno">
            <div class="avatar">
                <?= strtoupper(substr($_SESSION['nombre'], 0, 1)) ?>
            </div>

            <div class="info-alumno">
                <p><strong>Alumno:</strong> <?= htmlspecialchars($_SESSION['nombre']) ?></p>
                <p><strong>Instrumento:</strong>
                    <?php
                    $stmtInstr = $conexion->prepare("SELECT instrumento FROM alumnos WHERE id = ?");
                    $stmtInstr->bind_param("i", $idAlumno);
                    $stmtInstr->execute();
                    $instrumento = $stmtInstr->get_result()->fetch_assoc()['instrumento'] ?? 'Desconocido';
                    echo htmlspecialchars($instrumento);
                    ?>
                </p>
            </div>
        </div>


        <h2>Integrantes</h2>
        <ul>
            <?php while ($row = $integrantes->fetch_assoc()): ?>
                <li><?= htmlspecialchars($row['nombre']) ?> - <?= htmlspecialchars($row['instrumento']) ?></li>
            <?php endwhile; ?>
        </ul>

        <div class="logout">
            <a href="logout.php" class="boton">Cerrar sesión</a>
        </div>
    </div>
</body>

</html>
<?php
include 'verificar_rol.php';
verificarRol('administrador'); // También podrías permitir a jurado o profesor si lo deseas
include 'conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$cuarteto = $conexion->query("
    SELECT c.*, u.nombre AS profesor_nombre 
    FROM cuartetos c 
    JOIN usuarios u ON c.profesor_id = u.id 
    WHERE c.id = $id
")->fetch_assoc();

if (!$cuarteto) {
    echo "Cuarteto no encontrado.";
    exit;
}

$alumnos = $conexion->query("SELECT * FROM alumnos WHERE cuarteto_id = $id");

$evaluaciones = $conexion->query("
    SELECT e.*, u.nombre AS jurado_nombre 
    FROM evaluaciones e 
    JOIN usuarios u ON e.jurado_id = u.id 
    WHERE e.cuarteto_id = $id
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Cuarteto</title>
    <link rel="stylesheet" href="style_admin.css">
    <style>
        .contenedor { padding: 30px; background-color: #f9f9f9; border-radius: 10px; }
        h1 { color: #6a1b9a; }
        ul { list-style-type: disc; margin-left: 20px; }
        .seccion { margin-bottom: 30px; }
        .evaluacion { background: #fff; padding: 10px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 5px; }
    </style>
</head>
<body>

    <div class="logout-container">
        <a href="logout.php" class="boton-rojo">Cerrar sesión</a>
    </div>

    <div class="contenedor">
        <h1>Detalles del Cuarteto: <?= htmlspecialchars($cuarteto['nombre']) ?></h1>

        <div class="seccion">
            <strong>Obra Obligatoria:</strong> <?= htmlspecialchars($cuarteto['obra_obligatoria']) ?><br>
            <strong>Obra Libre:</strong> <?= htmlspecialchars($cuarteto['obra_libre']) ?><br>
            <strong>Profesor Responsable:</strong> <?= htmlspecialchars($cuarteto['profesor_nombre']) ?><br>
            <strong>Estado:</strong> <?= ucfirst($cuarteto['estado']) ?>
        </div>

        <div class="seccion">
            <h2>Integrantes</h2>
            <ul>
                <?php while($a = $alumnos->fetch_assoc()): ?>
                    <li><?= htmlspecialchars($a['nombre']) ?>, <?= $a['edad'] ?> años, DNI: <?= $a['dni'] ?> - <?= $a['instrumento'] ?></li>
                <?php endwhile; ?>
            </ul>
        </div>

        <div class="seccion">
            <h2>Evaluaciones del Jurado</h2>
            <?php if ($evaluaciones->num_rows > 0): ?>
                <?php while ($e = $evaluaciones->fetch_assoc()): ?>
                    <div class="evaluacion">
                        <strong>Jurado:</strong> <?= htmlspecialchars($e['jurado_nombre']) ?><br>
                        <strong>Puntuación:</strong> <?= $e['puntuacion'] ?><br>
                        <strong>Comentario:</strong><br>
                        <em><?= nl2br(htmlspecialchars($e['comentario'])) ?></em>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Aún no hay evaluaciones registradas para este cuarteto.</p>
            <?php endif; ?>
        </div>

        <a href="admin_concurso.php" class="boton">← Volver</a>
    </div>

</body>
</html>

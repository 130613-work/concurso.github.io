<?php
include 'verificar_rol.php';
verificarRol('jurado');
include 'conexion.php';

// Obtener la fase actual del concurso
$fase_actual = '';
$result = $conexion->query("SELECT fase FROM concurso WHERE id = 1");
if ($row = $result->fetch_assoc()) {
    $fase_actual = $row['fase'];
}

// Obtener lista de cuartetos según fase
if ($fase_actual === 'eliminatoria2') {
    // Solo los 5 mejores de la eliminatoria1
    $cuartetos = $conexion->query("
        SELECT c.id, c.nombre
        FROM cuartetos c
        JOIN (
            SELECT cuarteto_id
            FROM evaluaciones
            WHERE fase = 'eliminatoria1'
            GROUP BY cuarteto_id
            ORDER BY AVG(puntuacion) DESC
            LIMIT 5
        ) clasificados ON c.id = clasificados.cuarteto_id
        WHERE c.estado = 'en_concurso'
        ORDER BY c.nombre ASC
    ");
} elseif ($fase_actual === 'final') {
    // Solo los 3 mejores de la eliminatoria2
    $cuartetos = $conexion->query("
        SELECT c.id, c.nombre
        FROM cuartetos c
        JOIN (
            SELECT cuarteto_id
            FROM evaluaciones
            WHERE fase = 'eliminatoria2'
            GROUP BY cuarteto_id
            ORDER BY AVG(puntuacion) DESC
            LIMIT 3
        ) clasificados ON c.id = clasificados.cuarteto_id
        WHERE c.estado = 'en_concurso'
        ORDER BY c.nombre ASC
    ");
} else {
    // Fase eliminatoria1: todos los cuartetos en concurso
    $cuartetos = $conexion->query("
        SELECT id, nombre 
        FROM cuartetos 
        WHERE estado = 'en_concurso' 
        ORDER BY nombre ASC
    ");
}

// Mapear la fase ENUM a texto legible
$nombres_fase = [
    'eliminatoria1' => 'Primera Eliminatoria',
    'eliminatoria2' => 'Segunda Eliminatoria',
    'final' => 'Final del Concurso'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Evaluar Cuarteto</title>
    <link rel="stylesheet" href="style_jurado.css">
</head>
<body>
    <a class="cerrar-sesion" href="logout.php">Cerrar sesión</a>

    <h1>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></h1>
    <h2>Evaluar Cuarteto</h2>
    <p><strong>Fase actual:</strong> <?= $nombres_fase[$fase_actual] ?? ucfirst($fase_actual) ?></p>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="mensaje <?= strpos($_SESSION['mensaje'], '✅') !== false ? 'mensaje-ok' : 'mensaje-error' ?>">
            <?= $_SESSION['mensaje'] ?>
        </div>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <form action="guardar_evaluacion.php" method="post">
        <label for="cuarteto_id">Selecciona cuarteto:</label>
        <select name="cuarteto_id" id="cuarteto_id" required>
            <option value="">-- Elige --</option>
            <?php while ($fila = $cuartetos->fetch_assoc()): ?>
                <option value="<?= $fila['id'] ?>"><?= htmlspecialchars($fila['nombre']) ?></option>
            <?php endwhile; ?>
        </select>

        <!-- Campo oculto con la fase actual -->
        <input type="hidden" name="fase" value="<?= htmlspecialchars($fase_actual) ?>">

        <div class="criterio">
            <label>Afinación (0–10):</label>
            <input type="number" name="afinacion" min="0" max="10" required>
        </div>
        <div class="criterio">
            <label>Técnica (0–10):</label>
            <input type="number" name="tecnica" min="0" max="10" required>
        </div>
        <div class="criterio">
            <label>Coordinación (0–10):</label>
            <input type="number" name="coordinacion" min="0" max="10" required>
        </div>
        <div class="criterio">
            <label>Expresión (0–10):</label>
            <input type="number" name="expresion" min="0" max="10" required>
        </div>
        <div class="criterio">
            <label>Interpretación (0–10):</label>
            <input type="number" name="interpretacion" min="0" max="10" required>
        </div>

        <label for="comentario">Comentario del jurado:</label>
        <textarea name="comentario" id="comentario" rows="4" cols="50"></textarea>

        <br><br>
        <button type="submit">Guardar evaluación</button>
    </form>

    <script>
        // Ocultar mensaje automáticamente
        setTimeout(() => {
            const mensaje = document.querySelector('.mensaje');
            if (mensaje) {
                mensaje.style.transition = 'opacity 0.5s';
                mensaje.style.opacity = '0';
                setTimeout(() => mensaje.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>

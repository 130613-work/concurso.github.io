<?php
include 'verificar_rol.php';
verificarRol('administrador');
include 'conexion.php';

$concurso = $conexion->query("SELECT * FROM concurso WHERE id = 1")->fetch_assoc();
$fase = $concurso['fase'];

// Consulta solo evaluaciones de la fase actual
$sql = "
SELECT 
    c.id, 
    c.nombre, 
    AVG(e.puntuacion) AS promedio,
    COUNT(e.id) AS total_evaluaciones
FROM cuartetos c
LEFT JOIN evaluaciones e ON c.id = e.cuarteto_id AND e.fase = '$fase'
GROUP BY c.id, c.nombre
ORDER BY promedio DESC
";


$resultado = $conexion->query($sql);
$cuartetos = [];
while ($row = $resultado->fetch_assoc()) {
    $cuartetos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados del Concurso</title>
    <link rel="stylesheet" href="style_admin.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: white; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background-color: #f3e5f5; }
        .alto { color: green; font-weight: bold; }
        .medio { color: #ff9800; font-weight: bold; }
        .bajo { color: red; font-weight: bold; }
    </style>
</head>
<body>

<div class="logout-container">
    <a href="admin.php" class="boton-rojo">Ir a Vista Administrador</a>
</div>

<h1>Resultados del Concurso</h1>
<p><strong>Fase actual:</strong> <?= ucfirst($fase) ?></p>

<table>
    <thead>
        <tr>
            <th>Nombre del Cuarteto</th>
            <th>Promedio</th>
            <th>Total Evaluaciones</th>
            <th>Resultado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($cuartetos as $index => $cuarteto): ?>
            <tr>
                <td><?= htmlspecialchars($cuarteto['nombre']) ?></td>
                <td><?= is_null($cuarteto['promedio']) ? "Sin evaluar" : number_format($cuarteto['promedio'], 2) ?></td>
                <td><?= $cuarteto['total_evaluaciones'] ?></td>
                <td>
                    <?php if (is_null($cuarteto['promedio'])): ?>
                        <span class="bajo">Pendiente</span>
                    <?php else: ?>
                        <?php if ($fase === 'eliminatoria1' && $index < 5): ?>
                            <span class="alto">Clasificado a la segunda fase</span>
                        <?php elseif ($fase === 'eliminatoria2' && $index < 3): ?>
                            <span class="alto">Clasificado a la final</span>
                        <?php elseif ($fase === 'final' && $index < 3): ?>
                            <?php if ($index === 0): ?>
                                <span class="alto">🥇 Primer Lugar</span>
                            <?php elseif ($index === 1): ?>
                                <span class="medio">🥈 Segundo Lugar</span>
                            <?php elseif ($index === 2): ?>
                                <span class="bajo">🥉 Tercer Lugar</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="bajo">Eliminado</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>

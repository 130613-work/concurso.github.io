<?php 
session_start();
include 'verificar_rol.php';
verificarRol('jurado');
include 'conexion.php';

// Obtener datos del formulario
$cuarteto_id = isset($_POST['cuarteto_id']) ? intval($_POST['cuarteto_id']) : null;
$fase = $_POST['fase'] ?? null;
$jurado_id = $_SESSION['id'] ?? null;

$afinacion = isset($_POST['afinacion']) ? floatval($_POST['afinacion']) : null;
$tecnica = isset($_POST['tecnica']) ? floatval($_POST['tecnica']) : null;
$coordinacion = isset($_POST['coordinacion']) ? floatval($_POST['coordinacion']) : null;
$expresion = isset($_POST['expresion']) ? floatval($_POST['expresion']) : null;
$interpretacion = isset($_POST['interpretacion']) ? floatval($_POST['interpretacion']) : null;
$comentario = trim($_POST['comentario'] ?? '');

// Validar presencia de datos
if (!$cuarteto_id || !$fase || !$jurado_id || 
    $afinacion === null || $tecnica === null || $coordinacion === null || 
    $expresion === null || $interpretacion === null) {
    $_SESSION['mensaje'] = '❌ Datos incompletos.';
    header('Location: jurado.php');
    exit;
}

// Validar fase válida
$fases_validas = ['eliminatoria1', 'eliminatoria2', 'final'];
if (!in_array($fase, $fases_validas)) {
    $_SESSION['mensaje'] = '❌ Fase inválida.';
    header('Location: jurado.php');
    exit;
}

// Validar rangos de puntajes
foreach ([$afinacion, $tecnica, $coordinacion, $expresion, $interpretacion] as $nota) {
    if ($nota < 0 || $nota > 10) {
        $_SESSION['mensaje'] = '❌ Las puntuaciones deben estar entre 0 y 10.';
        header('Location: jurado.php');
        exit;
    }
}

// Calcular promedio
$promedio = ($afinacion + $tecnica + $coordinacion + $expresion + $interpretacion) / 5;

// Comprobar si ya existe evaluación del mismo jurado en esta fase
$sql_verificar = "SELECT id FROM evaluaciones 
                  WHERE cuarteto_id = ? AND jurado_id = ? AND fase = ?";
$stmt = $conexion->prepare($sql_verificar);
$stmt->bind_param("iis", $cuarteto_id, $jurado_id, $fase);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $_SESSION['mensaje'] = '❌ Ya has evaluado a este cuarteto en esta fase.';
    $stmt->close();
    header('Location: jurado.php');
    exit;
}
$stmt->close();

// Insertar evaluación
$sql_insertar = "INSERT INTO evaluaciones 
(cuarteto_id, jurado_id, fase, afinacion, tecnica, coordinacion, expresion, interpretacion, puntuacion, comentario)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conexion->prepare($sql_insertar);
$stmt->bind_param(
    "iissssssds",
    $cuarteto_id,
    $jurado_id,
    $fase,
    $afinacion,
    $tecnica,
    $coordinacion,
    $expresion,
    $interpretacion,
    $promedio,
    $comentario
);

if ($stmt->execute()) {
    $_SESSION['mensaje'] = '✅ Evaluación guardada correctamente.';
} else {
    $_SESSION['mensaje'] = '❌ Error al guardar la evaluación.';
}
$stmt->close();

header('Location: jurado.php');
exit;

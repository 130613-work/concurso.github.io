<?php
session_start();
include 'verificar_rol.php';
verificarRol('administrador');
include 'conexion.php';

// Validar token CSRF simple (opcional pero recomendable)
if (!isset($_POST['csrf']) || $_POST['csrf'] !== md5(session_id())) {
    die("Token CSRF inválido.");
}

// Validar entrada
$fase = $_POST['fase'] ?? null;
$fases_permitidas = ['eliminatoria1', 'eliminatoria2', 'final'];

if (!in_array($fase, $fases_permitidas)) {
    die("Fase inválida.");
}

// Ejecutar actualización
$stmt = $conexion->prepare("UPDATE concurso SET fase = ? WHERE id = 1");
$stmt->bind_param("s", $fase);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $_SESSION['mensaje'] = "Fase actualizada correctamente a '$fase'.";
} else {
    $_SESSION['mensaje'] = "No se realizó ningún cambio.";
}

$stmt->close();
header("Location: admin.php");
exit;

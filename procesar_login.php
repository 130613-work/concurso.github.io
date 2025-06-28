<?php
session_start();
include 'conexion.php';

$usuario = $_POST['usuario'] ?? '';
$clave = $_POST['clave'] ?? '';

if (empty($usuario) || empty($clave)) {
    echo "error";
    exit;
}

// PRIMERA OPCIÓN: Usuario del sistema (admin, profesor, jurado)
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $usuarioData = $resultado->fetch_assoc();

    if (password_verify($clave, $usuarioData['clave'])) {
        $_SESSION['usuario'] = $usuarioData['usuario'];
        $_SESSION['nombre'] = $usuarioData['nombre'];
        $_SESSION['rol'] = $usuarioData['rol'];
        $_SESSION['id'] = $usuarioData['id'];

        echo strtolower($usuarioData['rol']);
        exit;
    }
}

// Si no está en usuarios, buscamos en alumnos
$stmt2 = $conexion->prepare("SELECT * FROM alumnos WHERE nombre = ? AND dni = ?");
$stmt2->bind_param("ss", $usuario, $clave);
$stmt2->execute();
$resultado2 = $stmt2->get_result();

if ($alumno = $resultado2->fetch_assoc()) {
    $_SESSION['usuario'] = $alumno['nombre'];
    $_SESSION['rol'] = 'alumno';
    $_SESSION['nombre'] = $alumno['nombre'];
    $_SESSION['dni'] = $alumno['dni'];
    $_SESSION['id'] = $alumno['id'];
    echo 'alumno';
    exit;
}

echo 'error';
exit;
?>
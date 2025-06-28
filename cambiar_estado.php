<?php 
session_start();
include 'conexion.php';

if ($_POST['csrf'] !== md5(session_id())) {
    die("CSRF token inválido.");
}

$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'abrir':
        $conexion->query("UPDATE concurso SET estado = 'abierto' WHERE id = 1");
        break;

    case 'cerrar':
        $conexion->query("UPDATE concurso SET estado = 'cerrado' WHERE id = 1");
        break;

    case 'limpiar':
        // 1. Eliminar evaluaciones (depende de cuartetos)
        $conexion->query("DELETE FROM evaluaciones");
        $conexion->query("ALTER TABLE evaluaciones AUTO_INCREMENT = 1");

        // 2. Eliminar alumnos (depende de cuartetos)
        $conexion->query("DELETE FROM alumnos");
        $conexion->query("ALTER TABLE alumnos AUTO_INCREMENT = 1");

        // 3. Eliminar cuartetos
        $conexion->query("DELETE FROM cuartetos");
        $conexion->query("ALTER TABLE cuartetos AUTO_INCREMENT = 1");

        // 4. Restaurar el concurso a estado inicial
        $conexion->query("UPDATE concurso SET estado = 'abierto', fase = 'eliminatoria1' WHERE id = 1");

        // Nota: La obra obligatoria se mantiene tal cual, no se toca.
        break;
}

header("Location: admin.php");
exit;

<?php
include 'conexion.php';

if (isset($_GET['dni'])) {
    $dni = trim($_GET['dni']);

    if (!preg_match('/^\d{8}$/', $dni)) {
        echo 'invalido';
        exit;
    }

    $dni_escapado = $conexion->real_escape_string($dni);
    $res = $conexion->query("SELECT id FROM alumnos WHERE dni = '$dni_escapado' LIMIT 1");

    echo $res->num_rows > 0 ? 'repetido' : 'ok';
}
?>

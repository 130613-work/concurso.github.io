<?php
include 'conexion.php';

if (isset($_GET['nombre'])) {
    $nombre = $conexion->real_escape_string(trim($_GET['nombre']));
    $existe = $conexion->query("SELECT id FROM cuartetos WHERE nombre = '$nombre' LIMIT 1");

    echo $existe->num_rows > 0 ? 'existe' : 'disponible';
}
?>

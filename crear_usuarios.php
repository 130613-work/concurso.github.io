<?php
include 'conexion.php';
$usuarios = [

    ['admin', 'Administrador', 'admin123', 'administrador'],
    ['prof1', 'Prof. Ana', 'prof123', 'profesor'],
    ['prof2', 'Prof. Jorge', 'prof123', 'profesor'],
    ['prof3', 'Prof. Aurora', 'prof123', 'profesor'],
    ['prof4', 'Prof. Romario', 'prof123', 'profesor'],
    ['prof5', 'Prof. Josue', 'prof123', 'profesor'],
    ['jurado1', 'Jurado Marget', 'jurado123', 'jurado'],
    ['jurado2', 'Jurado Carlos', 'jurado123', 'jurado'],
    ['jurado3', 'Jurado Sheyla', 'jurado123', 'jurado'],
    ['jurado4', 'Jurado Mario', 'jurado123', 'jurado'],
    ['jurado5', 'Jurado Lucía', 'jurado123', 'jurado'],
    ['jurado6', 'Jurado Bruno', 'jurado123', 'jurado'],
    ['jurado7', 'Jurado Kate', 'jurado123', 'jurado'],
    ['jurado8', 'Jurado Owen', 'jurado123', 'jurado'],
    ['jurado9', 'Jurado Giacomo', 'jurado123', 'jurado'],


];

foreach ($usuarios as $u) {
    $usuario = $u[0];
    $nombre = $u[1];
    $clave_hash = password_hash($u[2], PASSWORD_DEFAULT);
    $rol = $u[3];
    $stmt = $conexion->prepare("INSERT INTO usuarios (usuario, nombre, clave, rol) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $usuario, $nombre, $clave_hash, $rol);
    $stmt->execute();
}

echo "Usuarios insertados correctamente.";
?>